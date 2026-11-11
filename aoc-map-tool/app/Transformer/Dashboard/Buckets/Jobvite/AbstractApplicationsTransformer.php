<?php

namespace App\Transformer\Dashboard\Buckets\Jobvite;

use App\DTO\Dashboard\Buckets\Jobvite\CandidateApplicationsDTO;
use App\Helpers\LD;
use App\Traits\DateFormatter;
use Carbon\Carbon;
use DateMalformedStringException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use stdClass;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

abstract class AbstractApplicationsTransformer
{
    use DateFormatter;

    private const ASSIGN_RECRUITER_TO_JOB = [
        'NEW',
        'recruiter_phone_screen',
        'jpet',
        'pending_graduation_followup',
    ];

    abstract protected function getButtonsView(): string;

    abstract protected function getCheckboxView(): string;

    /**
     * @param Collection $data
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @param array $pastDue
     * @return JsonResponse
     * @throws Throwable
     */
    public function transform(
        Collection $data,
        CandidateApplicationsDTO $candidateApplicationsDTO,
        array $pastDue
    ): JsonResponse {
        $datatable = DataTables::of($data)
            ->with('pastduecount', $pastDue['past_due_count'])
            ->setRowAttr([
                'class' => fn(stdClass $row) => $this->getRowClass($row, $candidateApplicationsDTO),
            ])
            ->addColumn('application_date', fn(stdClass $row) => $this->formatApplicationDate($row))
            ->addColumn('Action', fn(stdClass $row) => $this->getActionButtons($row, $candidateApplicationsDTO));

        if ($this->canShowCheckbox($candidateApplicationsDTO)) {
            $datatable->addColumn('select', fn(stdClass $row) => $this->getCheckbox($row, $candidateApplicationsDTO))
                ->escapeColumns('select');
        } else {
            $datatable->escapeColumns('Action');
        }

        return $datatable->addIndexColumn()->make(true);
    }

    /**
     * @param stdClass $row
     * @param CandidateApplicationsDTO $dto
     * @return string
     */
    private function getRowClass(stdClass $row, CandidateApplicationsDTO $dto): string
    {
        $status = $dto->getBaseStatus();
        $now = now();

        if ($status === 'NEW' && !empty($row->application_date)) {
            $appliedAt = Carbon::parse($row->application_date);

            if ($appliedAt->isBefore($now->copy()->subHour())) {
                return 'dashboard-older-record-cl-ra';
            }
            return '';
        }

        if ($status === 'recruiter_phone_screen' && !empty($row->FollowUpDateTime)) {
            $followUpAt = Carbon::parse($row->FollowUpDateTime);

            if ($followUpAt->isBefore($now->copy()->subDay())) {
                return 'dashboard-older-record-cl-ra';
            }
            return '';
        }

        if (!empty($row->workFlowStateChangeDate)) {
            $changedAt = Carbon::parse($row->workFlowStateChangeDate);

            if ($changedAt->isBefore($now->copy()->subDay())) {
                return 'dashboard-older-record-cl-ra';
            }
        }

        return '';
    }

    /**
     * @param stdClass $row
     * @return string
     * @throws DateMalformedStringException
     */
    private function formatApplicationDate(stdClass $row): string
    {
        return $this->getFormatedDateTime($row->application_date) ?: '-';
    }

    /**
     * @param CandidateApplicationsDTO $dto
     * @return bool
     * @throws Throwable
     */
    private function canShowCheckbox(CandidateApplicationsDTO $dto): bool
    {
        return LD::checkFeatureFlag('jobvite-assign-recruiter-to-job')
            && in_array($dto->getBaseStatus(), self::ASSIGN_RECRUITER_TO_JOB, true);
    }

    /**
     * @param stdClass $row
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return string
     * @throws Throwable
     */
    private function getCheckbox(stdClass $row, CandidateApplicationsDTO $candidateApplicationsDTO): string
    {
        return view($this->getCheckboxView(), [
            'id' => $row->id,
            'type' => $candidateApplicationsDTO->getStatus(),
        ])->render();
    }

    /**
     * @param stdClass $row
     * @param CandidateApplicationsDTO $candidateApplicationsDTO
     * @return string
     * @throws Throwable
     */
    private function getActionButtons(stdClass $row, CandidateApplicationsDTO $candidateApplicationsDTO): string
    {
        return view($this->getButtonsView(), [
            'id' => $row->id,
            'type' => $candidateApplicationsDTO->getStatus(),
        ])->render();
    }
}
