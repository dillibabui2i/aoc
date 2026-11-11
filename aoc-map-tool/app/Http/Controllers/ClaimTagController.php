<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimTagController extends Controller
{
    public function index($claimId)
    {
        $tags = DB::table('tags')->select('id', 'name')->get();

        $selectedTagIds = DB::table('claim_tag')
            ->where('claim_id', $claimId)
            ->pluck('tag_id')
            ->toArray();

        return response()->json([
            'all_tags' => $tags,
            'selected_tags' => $selectedTagIds
        ]);
    }

    public function store(Request $request, $claimId)
    {
        $request->validate([
            'tag_ids' => 'required|array'
        ]);

        DB::table('claim_tag')->where('claim_id', $claimId)->delete();

        $insert = [];
        foreach ($request->tag_ids as $tagId) {
            $insert[] = [
                'claim_id' => $claimId,
                'tag_id' => $tagId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('claim_tag')->insert($insert);

        return response()->json(['success' => true]);
    }
}

