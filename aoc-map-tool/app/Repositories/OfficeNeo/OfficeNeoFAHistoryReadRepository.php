<?php

namespace App\Repositories\OfficeNeo;

use App\Models\OfficeFAHistory;

class OfficeNeoFAHistoryReadRepository
{
    public function __construct(private OfficeFAHistory $model)
    {
    }

    public function getByEmail(string $email): ?OfficeFAHistory
    {
        return $this->model->where('email', $email);
    }

    public function getByFind(int $id): ?OfficeFAHistory
    {
        return $this->model->find($id);
    }
}
