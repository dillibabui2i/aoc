<?php

namespace App\Http\Controllers;

use App\DTO\TrachVentClass\EnrollmentResultDTO;
use App\DTO\TrachVentClass\TrachVentClassInfoDTO;
use App\Enums\TrachVentClassCancellationScope;
use App\Http\Requests\TrachVentClass\CalendarSearchRequest;
use App\Http\Requests\TrachVentClass\ClinicianEnrollmentRequest;
use App\Exceptions\TrachVentClassRegistrationException;
use App\Http\Requests\TrachVentClass\OccurrenceInfoRequest;
use App\Http\Requests\TrachVentClass\RescheduleRegistrationRequest;
use App\Http\Requests\TrachVentClass\ScheduleClassOptionsRequest;
use App\Http\Requests\TrachVentClass\SearchClientsRequest;
use App\Http\Requests\TrachVentClass\WeekEventsRequest;
use App\Repositories\ClassCategory\ClassCategoryReadRepository;
use App\Repositories\TrachVentClass\TrachVentClassOccurrenceReadRepository;
use App\Repositories\User\UserReadRepository;
use App\Services\TrachVentClass\ClinicianEnrollmentService;
use App\Services\TrachVentClass\TrachVentClassCancellationService;
use App\Services\TrachVentClass\TrachVentClassRegistrationService;
use App\Services\TrachVentClass\WeekEventsService;
use App\Transformer\TrachVentClass\TrachVentClassOccurrenceTransformer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\ConstantHelper;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TrachVentClassOccurrenceController extends Controller
{
    public function __construct(
        private UserReadRepository $userReadRepository,
        private ClassCategoryReadRepository $classCategoryReadRepository,
        private ClinicianEnrollmentService $clinicianEnrollmentService,
        private TrachVentClassOccurrenceReadRepository $trachVentClassOccurrenceReadRepository,
        private ConstantHelper $constantHelper,
        private TrachVentClassOccurrenceTransformer $trachVentClassOccurrenceTransformer,
        private WeekEventsService $weekEventsService,
        private TrachVentClassCancellationService $trachVentClassCancellationService,
        private TrachVentClassRegistrationService $trachVentClassRegistrationService,
    ) {
    }

    public function getTrachVentScheduleClass(ScheduleClassOptionsRequest $request): array
    {
        $dto = $request->toDto();
        $sortByDate = LD::checkFeatureFlag('P1-3035-change-date-sort-trach-vent');
        $classTypeLabel = $sortByDate
            ? $dto->classTypeLabel
            : null;

        return $this->trachVentClassOccurrenceReadRepository
            ->availableForSchedulingForm($dto->locationId, $dto->trachVentClassType, $classTypeLabel, $sortByDate)
            ->toArray();
    }

    public function getTrachVentClassWeekEvents(WeekEventsRequest $request): View
    {
        $dto = $request->toDto();
        $reqData = $request->validated();

        if ($dto->searchLabel !== null) {
            $reqData['searchData'] = $reqData['search'];
            $reqData['search'] = $dto->searchLabel;
        }

        Session::put(['lastTrachVentClassCalendarSearch' => $reqData]);

        $viewData = $this->weekEventsService->build($dto);

        return view('trachventclass.weekEvents', [
            'scheduleData' => $viewData->scheduleData,
            'branches' => $viewData->branches,
            'branchData' => $viewData->branchData,
            'week' => $viewData->week,
            'state' => $viewData->state,
        ]);
    }

    public function getTrachVentClassCalender(): View
    {
        $branches = $this->constantHelper->getBranches()->toArray();
        $states = $this->constantHelper->getBranchStates()->toArray();
        $branches = H::arrGroupBy($branches, 'state');
        $classCategories = $this->classCategoryReadRepository->orderedList();

        return view('trachventclass.calendar', [
            'branches' => $branches,
            'states' => $states,
            'classCategories' => $classCategories,
        ]);
    }

    public function getTrachVentClassInfo(OccurrenceInfoRequest $request): View
    {
        $occurrence = $this->trachVentClassOccurrenceReadRepository->findWithDetailsOrFirstForClass($request->occurrenceId());

        if ($occurrence === null) {
            return view('trachventclass.getTrachVentClassInfoNotFound');
        }

        $instructor = $this->userReadRepository->getInstructorsByIds([$occurrence->instructor_id])->first();
        $canScheduleClinician = LD::checkFeatureFlag('schedule_trach_vent_tpn_class')
            && H::canPermissions('showscheduleclassform');
        $canCancelOccurrence = LD::checkFeatureFlag('schedule_trach_vent_tpn_class')
            && H::canPermissions('trachventclass.destroyOccurrence');

        $classDetails = TrachVentClassInfoDTO::fromOccurrence(
            $occurrence,
            $instructor->name ?? '',
            $canScheduleClinician,
            $canCancelOccurrence,
        );

        $classRequestDetails = $this->trachVentClassOccurrenceReadRepository->findEnrollmentRequests($occurrence->id);

        $classWhen = Carbon::parse($classDetails->classDate)->format('D') . ' '
            . getFormatedDate($classDetails->classDate) . ', '
            . getFormatedTimeRange($classDetails->classTime, $classDetails->endTime);

        return view('trachventclass.getTrachVentClassInfo', [
            'classDetails' => $classDetails,
            'classRequestDetails' => $classRequestDetails,
            'classWhen' => $classWhen,
        ]);
    }

    public function searchClients(SearchClientsRequest $request): JsonResponse
    {
        return response()->json(
            $this->clinicianEnrollmentService->searchClients($request->term())
        );
    }

    public function enroll(ClinicianEnrollmentRequest $request, int $occurrence): JsonResponse
    {
        $result = $this->clinicianEnrollmentService->handleEnroll($request->toDTO(), $occurrence);

        return match ($result->status) {
            EnrollmentResultDTO::STATUS_CLASS_NOT_FOUND => response()->json(['message' => 'Class not found.'], Response::HTTP_NOT_FOUND),
            EnrollmentResultDTO::STATUS_CLASS_FULL => response()->json(['message' => 'This class is full.'], Response::HTTP_CONFLICT),
            EnrollmentResultDTO::STATUS_ALREADY_ENROLLED => response()->json(['message' => 'This clinician is already enrolled in this class.'], Response::HTTP_CONFLICT),
            EnrollmentResultDTO::STATUS_ENROLLED => response()->json([
                'message' => 'Clinician scheduled successfully.',
                'enrolled_count' => $result->enrolledCount,
                'capacity' => $result->capacity,
                'is_full' => $result->enrolledCount >= $result->capacity,
            ]),
            default => response()->json(['message' => 'Unexpected error.'], Response::HTTP_INTERNAL_SERVER_ERROR),
        };
    }

    public function getTrachVentCalendarSearchString(CalendarSearchRequest $request): JsonResponse
    {
        $term = (string) ($request->validated('term') ?? '');

        if ($term === '') {
            return response()->json([]);
        }

        $rows = $this->trachVentClassOccurrenceReadRepository->searchCalendarRequests($term);

        return response()->json($this->trachVentClassOccurrenceTransformer->transformCalendarSearchRows($rows));
    }

    public function destroyOccurrence(int $occurrence): JsonResponse
    {
        return $this->cancel($occurrence, TrachVentClassCancellationScope::OCCURRENCE, 'Class cancelled successfully.');
    }

    public function destroyFutureOccurrences(int $occurrence): JsonResponse
    {
        return $this->cancel($occurrence, TrachVentClassCancellationScope::FUTURE, 'This and all future classes cancelled successfully.');
    }

    public function destroyClassOccurrences(int $occurrence): JsonResponse
    {
        return $this->cancel($occurrence, TrachVentClassCancellationScope::SERIES, 'All classes in the series cancelled successfully.');
    }

    private function cancel(int $occurrenceId, TrachVentClassCancellationScope $scope, string $message): JsonResponse
    {
        $occurrence = $this->trachVentClassOccurrenceReadRepository->findWithDetails($occurrenceId);

        if ($occurrence === null) {
            return response()->json(['message' => 'Class not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->trachVentClassCancellationService->cancel($occurrence, $scope);

        return response()->json(['message' => $message]);
    }

    public function cancelRegistration(int $occurrence, int $registration): JsonResponse
    {
        try {
            $this->trachVentClassRegistrationService->cancel($occurrence, $registration);
        } catch (TrachVentClassRegistrationException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getCode());
        }

        return response()->json(['message' => 'Clinician cancelled from the class and the instructor was notified.']);
    }

    public function rescheduleOptions(int $occurrence, int $registration): JsonResponse
    {
        try {
            $classes = $this->trachVentClassRegistrationService->rescheduleOptions($occurrence, $registration);
        } catch (TrachVentClassRegistrationException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getCode());
        }

        return response()->json(['classes' => $classes]);
    }

    public function rescheduleRegistration(RescheduleRegistrationRequest $request, int $occurrence, int $registration): JsonResponse
    {
        try {
            $this->trachVentClassRegistrationService->reschedule(
                $occurrence,
                $registration,
                $request->input('target_occurrence_id')
            );
        } catch (TrachVentClassRegistrationException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getCode());
        }

        return response()->json(['message' => 'Clinician rescheduled and the instructor was notified.']);
    }
}
