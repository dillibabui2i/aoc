<?php

namespace App\Http\Controllers;

use App\ActivityLog;
use App\Http\Controllers\Controller;
use App\Tag;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $columns = [
            ['name' => 'name', 'label' => 'Tag Name'],
            ['name' => 'description', 'label' => 'Description'],
            ['name' => 'created_by', 'label' => 'Created By'],
            ['name' => 'created_at', 'label' => 'Date Created'],
            ['name' => 'status', 'label' => 'Status'],
        ];

        $filters = [
            [
                'label' => 'Status',
                'name' => 'status',
                'type' => 'select',
                'source' => [
                    ['value' => 'enabled', 'text' => 'Enabled'],
                    ['value' => 'disabled', 'text' => 'Disabled'],
                ]
            ],
        ];

        if ($request->ajax()) {
            return $this->data($request);
        }

        return view('tags.grid', compact('columns', 'filters'));
    }

    public function tagForm(Request $request)
    {
        $tag = null;
        $activityLogs = [];

        if ($request->filled('id')) {
            $tag = Tag::findOrFail($request->input('id'));
            $activityLogs = ActivityLog::query()
                ->where('page_action', 'Tags: ' . $tag->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('tags.form-modal', compact('tag', 'activityLogs'));
    }

    public function data(Request $request)
    {
        $columns = [
            ['name' => 'name'],
            ['name' => 'description'],
            ['name' => 'created_by'],
            ['name' => 'created_at'],
            ['name' => 'status'],
        ];

        $query = Tag::with('creator');

        if ($request->filled('filters')) {
            foreach ($request->filters as $filter) {
                if ($filter['name'] === 'status' && $filter['value'] !== null) {
                    $query->where('status', $filter['value']);
                }
            }
        }

        return DataTables::of($query)
            ->addColumn('created_by', fn($tag) => $tag->creator->name ?? '—')
            ->addColumn('action', function ($tag) {
                return view('tags.actions', compact('tag'))->render(); // or inline string
            })
            ->editColumn('created_at', fn($tag) => $tag->created_at->format('Y-m-d'))
            ->escapeColumns([]) // allow raw HTML
            ->rawColumns(['action', 'created_by'])
            ->make(true);
    }

    public function create()
    {
        return view('tags.form');
    }

    public function store(StoreTagRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['status'] = 'enabled';

        $tag = Tag::create($data);

        $activityLog = [
            'user_name'=> Auth::getName(),
            'ip_address'=>$request->ip(),
            'page_action'=>'Tags: ' . $tag->id,
            'log_info'=>'Tag is created by ' . Auth::getUser()->name
        ];
        ActivityLog::addActivityLog($activityLog);

        return redirect()->route('managetags')->with('success', 'Tag created successfully.');
    }

    public function edit(Tag $tag)
    {
        return view('tags.form', compact('tag'));
    }

    public function update(UpdateTagRequest $request, string $tag)
    {
        $data = $request->validated();
        $tag = Tag::findOrFail($tag);
        $tag->update($data);

        $activityLog = [
            'user_name'=> Auth::getName(),
            'ip_address'=>$request->ip(),
            'page_action'=>'Tags: ' . $tag->id,
            'log_info'=>'Tag is updated by ' . Auth::getUser()->name
        ];
        ActivityLog::addActivityLog($activityLog);

        return response()->json(['message' => 'Tag updated successfully.']);
    }

    public function toggleStatus(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        $tag->status = $request->status;
        $tag->save();

        $activityLog = [
            'user_name'=> Auth::getName(),
            'ip_address'=>$request->ip(),
            'page_action'=>'Tags: ' . $tag->id,
            'log_info'=>'Tag status is changed by ' . Auth::getUser()->name
        ];
        ActivityLog::addActivityLog($activityLog);

        return response()->json(['success' => true]);
    }
}