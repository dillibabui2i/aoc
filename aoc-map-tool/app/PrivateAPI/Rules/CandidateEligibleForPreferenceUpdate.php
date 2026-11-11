<?php

namespace App\PrivateAPI\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CandidateEligibleForPreferenceUpdate implements Rule
{
    private const XXX__WORKFLOW_PREFIX = 'XXX';
    private const BLOCKED_WORKFLOW_PHRASES = [
        'do not contact',
        'do not hire',
        'do not rehire',
    ];
    private string $message = 'The candidate is not eligible for this update.';

    public function passes($attribute, $value): bool
    {
        $candidate = DB::table('candidates')
            ->where('id', $value)
            ->first(['is_deleted', 'appWorkflowState']);

        if (!$candidate) {
            $this->message = 'The selected candidate is invalid.';
            return false;
        }

        if ((int)$candidate->is_deleted !== 0) {
            $this->message = 'The candidate is deleted.';
            return false;
        }

        $state = (string)($candidate->appWorkflowState ?? '');
        $normalizedState = strtolower(trim(preg_replace('/\s+/', ' ', $state)));

        foreach (self::BLOCKED_WORKFLOW_PHRASES as $phrase) {
            if (str_contains($normalizedState, $phrase)) {
                $this->message = 'The candidate workflow state does not allow this update.';
                return false;
            }
        }

        if (str_starts_with($normalizedState, strtolower(self::XXX__WORKFLOW_PREFIX))) {
            $this->message = 'The candidate workflow state does not allow this update.';
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }
}
