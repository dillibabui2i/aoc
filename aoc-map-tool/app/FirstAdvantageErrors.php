<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FirstAdvantageErrors extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'first_advantage_errors';
    protected $fillable = [
        'code',
        'message',
        'userId',
        'aoc_candidate_id',
        'type',
    ];

    /**
     * @param int $id
     * @param string $type
     * @return FirstAdvantageErrors|null
     */
    public function getAllErrors(int $id, string $type): ?FirstAdvantageErrors
    {
        return $this->where('aoc_candidate_id', $id)->where('type', $type)->latest()->first();
    }

    /**
     * @param int $id
     * @param string $type
     * @return FirstAdvantageErrors|null
     */
    public function getLatestError(int $id, string $type): ?FirstAdvantageErrors
    {
        return $this->where('aoc_candidate_id', $id)->where('type', $type)->latest()->first();
    }

    /**
     * @param int $id
     * @param string $type
     * @return void
     */
    public function deleteAll(int $id, string $type): void
    {
        $this->where('aoc_candidate_id', $id)->where('type', $type)->delete();
    }
}
