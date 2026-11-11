<?php

namespace App\VBC\Services\Hospitalization;

use App\VBC\DTO\Hospitalization\HospitalizationFilterDTO;
use App\VBC\Http\Resources\Hospitalization\AllHospitalizationCollectionItemResource;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HospitalizationExportService
{
    /** Same columns as the "All Hospitalizations" table, minus "Actions". */
    private const HEADERS = [
        'Patient Name', 'Patient ID', 'Tracker ID', 'Level', 'Admission Type',
        'Admission Date', 'Facility Name', 'Assignee', 'Status', 'Discharge Date',
        'Terminal Payer', 'Branch', 'Last Follow-up',
    ];

    public function __construct(private HospitalizationReadRepository $repository)
    {
    }

    public function export(HospitalizationFilterDTO $dto): StreamedResponse
    {
        $trackers = $this->repository->getAllForExport($dto);
        $filename = 'all_hospitalizations_' . now()->format('m-d-Y_H-i-s') . '.csv';

        return response()->stream(function () use ($trackers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, self::HEADERS);

            foreach ($trackers as $tracker) {
                $row = AllHospitalizationCollectionItemResource::make($tracker)->resolve();
                fputcsv($file, [
                    $row['patient_name'],
                    $row['patient_account'],
                    $row['tracker_id'],
                    $row['level'],
                    $row['type'],
                    $row['admission_date'],
                    $row['facility_name'],
                    $row['assignee_name'],
                    $row['status'],
                    $row['discharge_date'],
                    $row['terminal_payer'],
                    $row['branch'],
                    $row['last_follow_up'],
                ]);
            }

            fclose($file);
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
