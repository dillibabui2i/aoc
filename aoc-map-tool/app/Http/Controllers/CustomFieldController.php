<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\CustomFields;
use Carbon\Carbon;

class CustomFieldController extends Controller
{
    public function clinician(string $clinicianId)
    {
        $map       = config('constant.customFieldEntity');
        $entityCfg = Arr::get($map, 'clinician', []); // still points to clinician config for now

        $tableName  = $entityCfg[1] ?? null;
        $primaryKey = $entityCfg[2] ?? 'id';

        if (!$tableName) {
            return response()->json([
                'status'  => false,
                'message' => 'Provider entity table is not configured.',
            ], 500);
        }

        $providerRow = DB::table($tableName)->where('ClinicianID', $clinicianId)->first();

        $fields = CustomFields::where('is_deleted', 0)
            ->where('entity', 'clinician')
            ->orderBy('category')
            ->orderBy('sort')
            ->get();

        $payload = $fields->map(function ($f) use ($providerRow) {
            // dropdown-like options
            $options = null;
            if (in_array($f->input_type, ['dropdown', 'mdropdown'])) {
                $options = array_values(array_filter(
                    preg_split('/\r\n|[\r\n]/', (string) $f->options),
                    fn($opt) => trim($opt) !== ''
                ));
            } elseif ($f->input_type === 'boolean') {
                $options = ['Yes', 'No'];
            }

            // map value directly from providerRow
            $value = null;
            if ($providerRow && !empty($f->db_field_name)) {
                $raw = $providerRow->{$f->db_field_name} ?? null;

                if ($f->input_type === 'mdropdown') {
                    $value = is_string($raw) && $raw !== ''
                        ? array_map('trim', explode(',', $raw))
                        : (is_array($raw) ? $raw : []);
                } elseif ($f->input_type === 'boolean') {
                    $normalized = is_string($raw) ? strtolower($raw) : $raw;
                    if (in_array($normalized, [1, '1', true, 'true', 'yes'], true)) {
                        $value = true;
                    } elseif (in_array($normalized, [0, '0', false, 'false', 'no'], true)) {
                        $value = false;
                    } else {
                        $value = null;
                    }
                } elseif ($f->input_type === 'date') {
                    if ($raw instanceof \DateTimeInterface) {
                        $value = $raw->format('Y-m-d');
                    } elseif (is_string($raw) && strtotime($raw)) {
                        $value = Carbon::parse($raw)->format('Y-m-d');
                    } else {
                        $value = $raw;
                    }
                } else {
                    $value = $raw;
                }
            }

            return [
                'id'             => $f->id,
                'entity'         => $f->entity,
                'category'       => $f->category ?: 'Default',
                'sort'           => $f->sort,
                'input_type'     => $f->input_type,
                'db_field_name'  => $f->db_field_name,
                'frontend_label' => $f->frontend_label,
                'call_script'    => $f->call_script,
                'is_required'    => (bool) $f->is_required,
                'is_dependent'   => (bool) $f->is_dependent,
                'dependent_on'   => $this->decodeJsonSafe($f->dependent_on),
                'preference_tag' => $f->preference_tag,
                'options'        => $options,
                'value'          => $value, // ✅ mapped from provider row
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data'   => $payload,
            'meta'   => [
                'clinicianId' => $clinicianId,
                'table'       => $tableName,
                'primary_key' => $primaryKey,
                'count'       => $payload->count(),
            ],
        ]);
    }

    private function decodeJsonSafe($str)
    {
        if (!is_string($str) || trim($str) === '') return null;
        try {
            return json_decode($str, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
