<?php

namespace App\Enums;

enum FeatureFlagsEnum: string
{
    //JOB VITE
    case JOBVITE_ADD_CANDIDATE_NOTE = 'jobvite-add-candidate-note';
    case ADD_PET_FILTER_TO_JOBVITE_PET_BUCKET = 'add-pet-filter-to-jobvite-pet-bucket';
    case ENABLE_NURSING_FILTER = 'enable-nursing-filter';
    case ENABLE_THERAPIST_BUCKET = 'enable-therapist-bucket';
}
