<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceTagController extends Controller
{
    public function showModal(Request $request)
    {
        $ids = explode(',', $request->input('ids', ''));
        return view('billing.add_tags', [
            'selectedIds' => implode(',', $ids)
        ]);
    }

    public function saveTags(Request $request)
    {
        $invoiceIds = explode(',', $request->input('selected_ids'));
        $tagIds = $request->input('tags', []);

        if (empty($invoiceIds) || empty($tagIds)) {
            return response()->json(['error' => 'Invalid input.'], 422);
        }

        foreach ($invoiceIds as $invoiceId) {
            DB::table('claim_tag')->where('claim_id', $invoiceId)->delete();
            foreach ($tagIds as $tagId) {
                DB::table('claim_tag')->insert([
                    'claim_id' => $invoiceId,
                    'tag_id' => $tagId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json(['status' => true]);
    }
}
