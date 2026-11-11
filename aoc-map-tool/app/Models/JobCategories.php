<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \Illuminate\Database\Eloquent\Collection<int, JobCategoryLogs> $logs
 * @property string  $category
 */
class JobCategories extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'job_categories';
    protected $guarded = [];


    public  function logs(): HasMany
    {
        return $this->hasMany(JobCategoryLogs::class, 'job_category_id', 'id');
    }
}
