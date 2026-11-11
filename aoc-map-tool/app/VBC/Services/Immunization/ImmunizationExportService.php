<?php

namespace App\VBC\Services\Immunization;

use App\VBC\DTO\Immunization\ImmunizationFilterDTO;
use App\VBC\Http\Resources\Immunization\AllImmunizationCollectionItemResource;
use App\VBC\Repositories\Immunization\ImmunizationReadRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImmunizationExportService
{
    /** Same columns as the "All Immunizations" table, minus "Actions". */
    private const HEADERS = [
        'Patient Name', 'Patient ID', 'Branch', 'Payer', 'Vaccine Name',
        'Dose', 'Earliest Date', 'Due Date', 'Status',
    ];

    public function __construct(private ImmunizationReadRepository $repository)
    {
    }

    public function export(ImmunizationFilterDTO $dto): StreamedResponse
    {
        $doses = $this->repository->getAllForExport($dto);
        $filename = 'all_immunizations_' . now()->format('m-d-Y_H-i-s') . '.csv';

        return response()->stream(function () use ($doses) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, self::HEADERS);

            foreach ($doses as $dose) {
                $row = AllImmunizationCollectionItemResource::make($dose)->resolve();
                fputcsv($file, [
                    $row['patient_name'],
                    $row['patient_account'],
                    $row['branch'],
                    $row['payer'],
                    $row['vaccine_name'],
                    $row['dose'],
                    $row['earliest_date'],
                    $row['due_date'],
                    $row['status'],
                ]);
            }

            fclose($file);
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
