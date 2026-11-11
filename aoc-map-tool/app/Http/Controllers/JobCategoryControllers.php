<?php

namespace App\Http\Controllers;

use App\Enums\JobCategoryAction;
use App\Events\JobCategoryChanged;
use App\Helpers\H;
use App\Http\Requests\JobCategories\StoreJobCategoryRequest;
use App\Models\JobCategories;
use Illuminate\Http\Request;

class JobCategoryControllers extends Controller
{
    public function index()
    {
        return view('jobcategory.grid');
    }

    public function getJobCategoryList(Request $request)
    {
        $columns = [
            0 => 'State',
            1 => 'Type',
            2 => 'category',
            3 => 'created.name',
            4 => 'updated.name',
        ];

        $jobCategoryLists = JobCategories::select('job_categories.*', 'created.name as created_name', 'updated.name as updated_name')->leftjoin('users as created', 'created.id', '=', 'job_categories.created_by')->leftjoin('users as updated', 'updated.id', '=', 'job_categories.updated_by');

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $searchValue = '%' . $request->search['value'] . '%';
            $jobCategoryLists = $jobCategoryLists
            ->where(function ($query) use ($searchValue) {
                $query->where('job_categories.State', 'like', $searchValue)
                    ->orWhere('job_categories.Type', 'like', $searchValue)
                    ->orWhere('job_categories.category', 'like', $searchValue)
                    ->orWhere('created.name', 'like', $searchValue)
                    ->orWhere('updated.name', 'like', $searchValue);
            });
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $jobCategoryLists = $jobCategoryLists->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        $total_records = $jobCategoryLists->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $jobCategoryLists = $jobCategoryLists->offset($request['start'])->limit($request['length']);
        }

        $jobCategoryLists = $jobCategoryLists->get();

        $cnt = 0;
        foreach ($jobCategoryLists as $jobCategory) {
            $jobCategoryLists[$cnt]['State'] = $jobCategory['State'];
            $jobCategoryLists[$cnt]['Type'] = $jobCategory['Type'];
            $jobCategoryLists[$cnt]['category'] = $jobCategory['category'];
            $jobCategoryLists[$cnt]['created_name'] = $jobCategory['created_name'];
            $jobCategoryLists[$cnt]['updated_name'] = $jobCategory['updated_name'];

            if (H::canPermissions('jobcategory.edit')) {
                $jobCategoryLists[$cnt]['action'] = view(
                    'jobcategory.action',
                    ['jobCategory' => $jobCategory]
                )->render();
            }

            $cnt++;
        }

        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $jobCategoryLists,
        ];

        return $data;
    }

    public function add()
    {
        return view('jobcategory.form');
    }

    public function edit($id)
    {
        $jobCategoryDetail = JobCategories::with('logs')->find($id);

        return view('jobcategory.form', [
            'jobCategoryDetail' => $jobCategoryDetail,
            'logs' => $jobCategoryDetail?->logs->sortByDesc('updated_at'),
        ]);
    }

    public function saveJobCategory(StoreJobCategoryRequest $request)
    {
        $data = $request->validated();
        $user = H::getLoggedInUser();

        if (!empty($request->id)) {
            $jobCategory = JobCategories::findOrFail($request->id);
            $oldValues = $jobCategory->only(['State', 'Type', 'category']);

            $jobCategory->update([
                'State' => $data['State'],
                'Type' => $data['Type'],
                'category' => $data['category'],
                'updated_by' => $user['id'],
            ]);

            $newValues = $jobCategory->fresh()->only(['State', 'Type', 'category']);

            event(new JobCategoryChanged(
                category: $jobCategory,
                action: JobCategoryAction::UPDATED,
                oldValues: $oldValues,
                newValues: $newValues,
                userId: $user['id'],
            ));

            return redirect()->route('jobcategory')->with('success', 'Job category updated!');
        }

        $data = array_merge($data, ['created_by' => $user['id']]);
        $jobCategory = JobCategories::create($data);

        $newValues = $jobCategory->only(['State', 'Type', 'category']);

        event(new JobCategoryChanged(
            category: $jobCategory,
            action: JobCategoryAction::CREATED,
            oldValues: null,
            newValues: $newValues,
            userId: $user['id'],
        ));

        return redirect()->route('jobcategory')->with('success', 'Job category added!');
    }

    public function destroy($id)
    {
        $jobcategories = JobCategories::find($id);
        $jobcategories->delete();

        return redirect()->route('jobcategory')->with('success', 'job category deleted!');
    }

    public function toggle($id)
    {
        $category = JobCategories::query()->findOrFail($id);
        $oldValue = $category->is_enabled;
        $category->is_enabled = !$category->is_enabled;
        $category->updated_by = H::getLoggedInUser()['id'] ?? null;
        $category->save();

        event(new JobCategoryChanged(
            category: $category,
            action: $category->is_enabled ? JobCategoryAction::ENABLED : JobCategoryAction::DISABLED,
            oldValues: [$oldValue ? JobCategoryAction::ENABLED : JobCategoryAction::DISABLED],
            newValues: [$category->is_enabled ? JobCategoryAction::ENABLED : JobCategoryAction::DISABLED],
            userId: $category->updated_by
        ));

        return redirect()->route('jobcategory')->with(
            'success',
            'Job category ' . ($category->is_enabled ? 'enabled' : 'disabled') . '!'
        );
    }
}
