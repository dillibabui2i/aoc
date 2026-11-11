<?php

namespace App\Services\FirstAdvantage;

use App\Candidates;
use App\MssqlProviders;
use App\RecruitingSchedule;
use App\FirstAdvantageCheck;
use App\Repositories\OfficeNeo\OfficeNeoFAHistoryReadRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class RetryService
{
    private int $userId;
    private ?string $email;
    private FirstAdvantageService $firstAdvantageService;
    private Candidates $candidatesModel;
    private MssqlProviders $mssqlProvidersModel;
    private RecruitingSchedule $recruitingScheduleModel;
    private FirstAdvantageCheck $firstAdvantageCheckModel;
    private FirstAdvantageValidation $firstAdvantageValidation;
    private OfficeNeoFAHistoryReadRepository $officeNeoFAHistoryReadRepository;

    public function __construct(
        FirstAdvantageService $firstAdvantageService,
        Candidates $candidatesModel,
        MssqlProviders $mssqlProvidersModel,
        RecruitingSchedule $recruitingScheduleModel,
        FirstAdvantageCheck $firstAdvantageCheckModel,
        FirstAdvantageValidation $firstAdvantageValidation,
        OfficeNeoFAHistoryReadRepository $officeNeoFAHistoryReadRepository
    ) {
        $this->userId = Auth::check() ? Auth::id() : 0;
        $this->firstAdvantageService = $firstAdvantageService;
        $this->candidatesModel = $candidatesModel;
        $this->mssqlProvidersModel = $mssqlProvidersModel;
        $this->recruitingScheduleModel = $recruitingScheduleModel;
        $this->firstAdvantageCheckModel = $firstAdvantageCheckModel;
        $this->firstAdvantageValidation = $firstAdvantageValidation;
        $this->officeNeoFAHistoryReadRepository = $officeNeoFAHistoryReadRepository;
    }

    public function retry(int $id, string $type): void
    {
        $log = [];
        $log['type'] = $type;

        $hasInitiated = $this->firstAdvantageCheckModel->hasInitiated($id, $type);
        $data = $this->extractCandidate($id, $type);
        $response = $this->firstAdvantageService->createCandidate(
            $this->getCreateOrderData($data)
        );

        $log['data'] = $data;
        $log['response'] = $response;

        if (isset($response['error'])) {
            $response['userId'] = $this->userId;
            $response['aoc_candidate_id'] = $id;
            $this->firstAdvantageValidation->registerErrors($response);
        }

        if (isset($response['candidate_id']) && !$hasInitiated) {
            $this->firstAdvantageCheckModel->insert([
                'aoc_candidate_id' => $id,
                'type' => $type,
                'fa_candidate_id' => $response['candidate_id']
            ]);
        }

        if (!isset($response['error'])) {
            $this->firstAdvantageValidation->deleteAll($id, $type);
        }

        if (!$hasInitiated && !isset($response['error'])) {
            $inviteResponse = $this->firstAdvantageService->invite($this->getInviteData($response, $id, $type));

            $log['invite-data'] = $this->getInviteData($response, $id, $type);
            $log['inviteResponse'] = $inviteResponse;
            if (isset($inviteResponse['error'])) {
                $inviteResponse['userId'] = $this->userId;
                $inviteResponse['aoc_candidate_id'] = $id;
                $this->firstAdvantageValidation->registerErrors($inviteResponse);
            } else {
                $this->firstAdvantageValidation->deleteAll($id, $type);
            }
        }

        if ($hasInitiated) {
            $update = ['updated_at' => now()];
            if (isset($response['candidate_id'])) {
                $update['fa_candidate_id'] = $response['candidate_id'];
            }

            $this->firstAdvantageCheckModel->updateFieldsByRecruiting($id, $update);
        }

        Log::channel('first_advantage')->error('First Advantage -> retry -> [LOG]', [
            'dump' => $log
        ]);
    }

    /**
     * @param array $candidate
     * @return array
     */
    private function getCreateOrderData(array $candidate): array
    {
        $this->email = $candidate['email'];

        return [
            'email' => $candidate['email'],
            'override_candidate_data' => true,
            'persons' => [
                [
                    'given_name' => $candidate['firstName'],
                    'family_name' => $candidate['lastName'],
                    'type' => 'subject'
                ]
            ],
            'employers' => [
                [
                    'employer_name' => 'Angels of Care'
                ]
            ],
            'education' => [
                [
                    'school_name' => 'Other',
                    'contact_info' => [
                        'contact_method' => [
                            'internet_email_address' => $candidate['email'],
                        ]
                    ],
                    'person_name' => [
                        'given_name' => $candidate['firstName'],
                        'family_name' => $candidate['lastName'],
                        'type' => 'subject'
                    ]
                ]
            ],
        ];
    }

    public function getInviteData(array $response, int $id, string $type): array
    {
         if ($type === 'neo') {
            $email = $this->officeNeoFAHistoryReadRepository->getByEmail($id)->email;
            $model = $this->recruitingScheduleModel->where('description', 'like', "%$email%")->first();
        } elseif ($type === 'provider') {
             $model = $this->recruitingScheduleModel->where('aoc_provider_id', $id)->latest()->first();
        } else {
             $model = $this->recruitingScheduleModel->where('aoc_candidate_id', $id)->latest()->first();
         }

        $city = $model->branchLocation->city ?? $model->City;

        $packageId = $city === 'Dallas' ? 2377 : 2168;
        $accountName = $city === 'Dallas' ? "texas" : "mckinney";

        return [
            'candidate_id' => $response['candidate_id'],
            'package_id' => $packageId,
            'candidate_type' => 'PROFILE',
            'quote_backs' => [
                [
                    'name' => 'candidate_id',
                    'value' => $response['candidate_id'],
                ],
                [
                    'name' => 'aoc_ipea_id',
                    'value' => $model->aoc_candidate_id,
                ],
                [
                    'name' => 'aoc_clinician_id',
                    'value' => $model->ClinicianID,
                ]
            ],
            'additional_attributes' => [
                'order_as' => [
                    'account_id' => $this->firstAdvantageService->getAccountID($accountName),
                    'user_id' => "XCHANGE", /*Auth::user()->id,*/
                    'email' => $this->email
                ]
            ],
            'position_locations' => [
                [
                    'country' => 'US',
                    'city' => $city ?? '',
                    'state' => 'TX',
                ]
            ],
            'locale' => [
                'language_code' => 'en',
                'country_code' => 'us'
            ],
        ];
    }

    private function extractCandidate(int $id, string $type): array
    {
        if ($type === 'candidate') {
            $data = $this->candidatesModel->find($id);

            return [
                'firstName' => $data->firstName,
                'lastName' => $data->lastName,
                'email' => $data->maila
            ];
        } elseif ($type === 'neo') {
            $email = $this->officeNeoFAHistoryReadRepository->getByEmail($id)->email;
            $model = $this->recruitingScheduleModel->where('description', 'like', "%$email%")->first();
            $data = json_decode($model->description, true);

            return [
                'firstName' => $data['First Name'] ?? '',
                'lastName' => $data['Last Name'] ?? '',
                'email' => $email,
            ];
        } else {
            $data = $this->mssqlProvidersModel->find($id);

            return [
                'firstName' => $data->FirstName,
                'lastName' => $data->LastName,
                'email' => !empty($data->AOC_Email) ? $data->AOC_Email : $data->External_Email,
            ];
        }
    }
}
