<?php

namespace App\Services\OTP;

use App\DTO\CreateOtpLogDTO;
use App\DTO\SendLoginOtpDTO;
use App\DTO\VerifyOtpDTO;
use App\Models\ExternalUserPatients;
use App\Models\ExternalUsers;
use App\Models\MobileOtpLog;
use App\Repositories\OTP\OtpRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OtpService
{
    /**
     * @param OtpRepository $otpRepository
     */
    public function __construct(protected OtpRepository $otpRepository)
    {
    }

    /**
     * @param SendLoginOtpDTO $otpDTO
     * @return JsonResponse
     * @throws \Throwable
     */
    public function requestOtp(SendLoginOtpDTO $otpDTO): JsonResponse
    {
        return DB::transaction(function () use ($otpDTO) {

            $now = Carbon::now();
            $mobileNumber = $otpDTO->mobileNumber;
            $email = $otpDTO->email;
            $data = (object)[]; //todo fix me

            $latestOtp = MobileOtpLog::query()
                ->when($mobileNumber, fn ($q) => $q->where('mobile_number', $mobileNumber))
                ->when($email, fn ($q) => $q->where('email', $email))
                ->orderByDesc('send_at')
                ->lockForUpdate()
                ->first();

            // --- AC8
            if ($latestOtp && $latestOtp->cooldown_until && $now->lt($latestOtp->cooldown_until)) {
                return response()->json([
                    'reason' => 'COOLDOWN',
                    'message' => __('messages.otp_cooldown'),
                    'resend_available_at' => $latestOtp->cooldown_until,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            $activeOtp = MobileOtpLog::query()
                ->whereNull('verified_at')
                ->whereNull('invalidated_at')
                ->when($mobileNumber, fn ($q) => $q->where('mobile_number', $mobileNumber))
                ->when($email, fn ($q) => $q->where('email', $email))
                ->orderByDesc('send_at')
                ->first();

            // --- AC1/AC2: 30s delay ---
            if ($activeOtp && $now->lt(Carbon::parse($activeOtp->send_at)->addSeconds(config('otp.resendDelaySeconds')))) {
                return response()->json([
                    'reason' => 'RESEND_TOO_EARLY',
                    'message' => __('messages.otp_resend_too_early'),
                    'resend_available_at' => Carbon::parse($activeOtp->send_at)->addSeconds(config('otp.resendDelaySeconds')),
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            // --- AC4: max 3 resend ---
            if ($activeOtp && $activeOtp->resend_count >= config('otp.resendLimit')) {
                $activeOtp->cooldown_until = $now->addMinutes(config('otp.cooldownMinutes'));
                $activeOtp->invalidated_at = $now;
                $activeOtp->invalidated_reason = 'resend_limit';
                $activeOtp->save();

                return response()->json([
                    'reason' => 'COOLDOWN',
                    'message' => __('messages.otp_resend_limit'),
                    'resend_available_at' => $activeOtp->cooldown_until,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            $newOtp = $email ? MobileOtpLog::generateEmailOtp($email) : MobileOtpLog::generateMobileOtp($mobileNumber);

            // --- AC3
            if ($activeOtp) {
                $activeOtp->invalidated_at = $now;
                $activeOtp->invalidated_reason = 'resend';
                $activeOtp->save();
            }

            $dto = new CreateOtpLogDTO(
                mobileNumber: $mobileNumber,
                email: $email,
                otp: $newOtp,
                sendAt: $now,
                expiresAt: $now->copy()->addSeconds(config('otp.ttl')),
                resendCount: $activeOtp ? $activeOtp->resend_count + 1 : 1,
                attemptCount: 0,
                limitEventsCount: $activeOtp ? $activeOtp->limit_events_count : 0
            );

            $newOtpModel = $this->otpRepository->saveOtpLog($dto);

            $this->sendCode($mobileNumber, $email, $newOtp);

            return response()->json([
                'code' => 200, ///don't ask me why, todo fix me
                'data' => $data,
                'message' => __('messages.otp_sent_email_mobile'),
                'expires_at' => $newOtpModel->expires_at,
                'resend_available_at' => $now->copy()->addSeconds(config('otp.resendDelaySeconds')),
            ]);
        });
    }

    /**
     * @param VerifyOtpDTO $otpDTO
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpDTO $otpDTO): JsonResponse
    {
        return DB::transaction(function () use ($otpDTO) {

            $now = Carbon::now();
            $email = $otpDTO->email ?? null;
            $mobileNumber = $otpDTO->mobileNumber ?? null;
            $otpInput = $otpDTO->otp;

            $otp = MobileOtpLog::query()
                ->when($email, fn ($q) => $q->where('email', $email))
                ->when($mobileNumber, fn ($q) => $q->where('mobile_number', $mobileNumber))
                ->orderByDesc('send_at')
                ->lockForUpdate()
                ->first();

            if (!$otp) {
                return response()->json(['message' => __('messages.otp_invalid')], Response::HTTP_BAD_REQUEST);
            }

            // --- AC8
            if ($otp->cooldown_until && $now->lt($otp->cooldown_until)) {
                return response()->json([
                    'message' => 'Too many attempts. Please wait 15 minutes.',
                    'resend_available_at' => $otp->cooldown_until->toDateTimeString(),
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            if ($otp->invalidated_at) {
                return response()->json(['message' => __('messages.otp_invalid'), 'reason' => 'invalidated'], Response::HTTP_BAD_REQUEST);
            }

            if ($now->gt($otp->expires_at)) {
                $otp->invalidated_at = $now;
                $otp->invalidated_reason = 'expired';
                $otp->save();

                return response()->json(['message' => __('messages.otp_expired')], Response::HTTP_BAD_REQUEST);
            }

            if ($otpInput !== $otp->otp) {
                $otp->attempt_count++;

                if ($otp->attempt_count >= config('otp.retryLimit')) {
                    $otp->limit_events_count++;

                    if ($otp->limit_events_count >= config('otp.cooldownEventsThreshold')) {
                        $otp->cooldown_until = $now->addMinutes(config('otp.cooldownMinutes'));
                        $otp->limit_events_count = 0;
                    }
                }

                $otp->save();

                return response()->json([
                    'message' => __('messages.otp_invalid'),
                    'attempts_left' => max(0, config('otp.retryLimit') - $otp->attempt_count),
                    'resend_available_at' => $otp->cooldown_until?->toDateTimeString(),
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($otp->verified_at) {
                return response()->json(['message' => __('messages.otp_already_verified')], Response::HTTP_OK);
            }

            $otp->verified_at = $now;
            $otp->save();

            if ($mobileNumber) {
                $data = $this->buildUserPayload($mobileNumber);
                return response()->json([
                    'message' => __('messages.otp_verified'),
                    'data' => $data,
                ], Response::HTTP_OK);
            }

            return response()->json(['message' => __('messages.otp_verified')], Response::HTTP_OK);
        });
    }

    /**
     * @param string|null $mobileNumber
     * @param string|null $email
     * @param string $otp
     * @return void
     */
    private function sendCode(?string $mobileNumber, ?string $email, string $otp): void
    {
        if ($mobileNumber) {
            MobileOtpLog::sendSMS([
                'msg' => __('otp.otp_sms_msg', ['otp' => $otp]),
                'to'  => $mobileNumber
            ]);
        }

        if ($email) {
            $user = ExternalUsers::where('email', $email)->first();
            $firstName = $user ? explode(' ', $user->name)[0] : 'User';

            MobileOtpLog::sendEmailOtp([
                'otp' => $otp,
                'email' => $email,
                'first_name' => $firstName
            ]);
        }
    }

    /**
     * @param string|null $mobileNumber
     * @return array
     */
    private function buildUserPayload(?string $mobileNumber): array
    {
        $user = ExternalUsers::where('mobile_number', $mobileNumber)->first();

        if (!$user) {
            return [];
        }

        $u = ExternalUsers::doPostLogOperation(['id' => $user->id]);

        $payload = [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'user_id' => $u->user_id,
            'aoc_provider_id' => $u->aoc_provider_id,
            'status' => $u->status,
            'token' => $u->token,
        ];

        if ($u->aoc_provider_id == 0) {
            $payload['patients'] = ExternalUserPatients::getPatientList($u->id);
        } else {
            $payload['patients'] = [];
        }

        return $payload;
    }
}
