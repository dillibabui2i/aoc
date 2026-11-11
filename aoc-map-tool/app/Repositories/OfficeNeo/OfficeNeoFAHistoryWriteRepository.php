<?php

namespace App\Repositories\OfficeNeo;

use App\Models\OfficeFAHistory;

class OfficeNeoFAHistoryWriteRepository
{
    public function __construct(private OfficeFAHistory $model)
    {
    }

    public function createOrUpdateRecord(string $email): OfficeFAHistory
    {
        $data = ['email' => $email];

        return $this->model->firstOrCreate($data, $data);
    }
}
