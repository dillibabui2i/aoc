<?php

namespace App\Services\FirstAdvantage;

use App\FirstAdvantageErrors;
use Illuminate\Support\Facades\Auth;

/**
 * Class PackageManager
 * @package App\Services\FirstAdvantage\Validation
 */
class FirstAdvantageValidation
{
    private int $userId;
    private array $errors;
    private FirstAdvantageErrors $firstAdvantageErrors;

    public function __construct(FirstAdvantageErrors $firstAdvantageErrors)
    {
        $this->errors = [];
        $this->userId = Auth::check() ? Auth::id() : 0;
        $this->firstAdvantageErrors = $firstAdvantageErrors;
    }

    public function checkForErrors(array $data)
    {
        if (empty($data['firstName'])) {
            $this->errors[] = [
                'code' => 442,
                'message' => 'First Name cannot be empty',
                'userId' => $this->userId,
                'aoc_candidate_id' => $data['aoc_candidate_id'],
                'type' => $data['type']
            ];
        }

        if (empty($data['lastName'])) {
            $this->errors[] = [
                'code' => 442,
                'message' => 'First Name cannot be empty',
                'userId' => $this->userId,
                'aoc_candidate_id' => $data['aoc_candidate_id']
            ];
        }

        if (empty($data['state'])) {
            $this->errors[] = [
                'code' => 442,
                'message' => 'State cannot be empty',
                'userId' => $this->userId,
                'aoc_candidate_id' => $data['aoc_candidate_id']
            ];
        }

        if (empty($data['city'])) {
            $this->errors[] = [
                'code' => 442,
                'message' => 'City cannot be empty',
                'userId' => $this->userId,
                'aoc_candidate_id' => $data['aoc_candidate_id']
            ];
        }

        if (empty($data['email'])) {
            $this->errors[] = [
                'code' => 442,
                'message' => 'E-mail cannot be empty',
                'userId' => $this->userId,
                'aoc_candidate_id' => $data['aoc_candidate_id']
            ];
        }

        foreach ($this->errors as $error) {
            $this->registerErrors($error);
        }
    }

    public function registerErrors(array $data)
    {
        $normalizedMessage = trim(preg_replace('/\s+/', ' ', $data['message']));
        $this->firstAdvantageErrors->firstOrCreate(
            [
                'aoc_candidate_id' => $data['aoc_candidate_id'],
                'message' => $normalizedMessage
            ],
            $data
        );
    }

    public function deleteAll(int $id, string $type)
    {
        $this->firstAdvantageErrors->deleteAll($id, $type);
    }
}
