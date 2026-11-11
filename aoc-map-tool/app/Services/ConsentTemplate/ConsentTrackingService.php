<?php

namespace App\Services\ConsentTemplate;

use App\ConsentTemplate;
use App\DTO\ConsentTrack\ConsentTableParams;
use App\DTO\ConsentTrack\ConsentTrackDTO;
use App\Enums\ConsentTemplates\StatusEnum;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ConsentTrackingService
{
    /**
     * @param ConsentTrackDTO $data
     * @return void
     */
    public function create(ConsentTrackDTO $data): void
    {
        $version = $data->getStatus() === StatusEnum::DRAFT->value ? null : $this->generateNextVersion($data);

        $payload = [
            'type' => $data->getType(),
            'language' => $data->getLanguage(),
            'content' => $data->getContent(),
            'version' => $version,
            'status' => $data->getStatus(),
            'created_by' => Auth::id(),
            'activated_at' => $data->getStatus() === StatusEnum::PUBLISHED->value ? now() : null,
        ];

        ConsentTemplate::create($payload);
    }

    /**
     * @param ConsentTableParams $tableParams
     * @return JsonResponse
     */
    public function getData(ConsentTableParams $tableParams): JsonResponse
    {
        $query = ConsentTemplate::query()->with('creator');
        $this->applyFilters($query, $tableParams);

        return Datatables::eloquent($query)
            ->filter(function (Builder $query) use ($tableParams) {
                $this->applySearch($query, $tableParams);
            })
            ->addColumn('actions', fn ($row) => view('consentTemplateTracking.tableActions', compact('row'))->render())
            ->addColumn('created_by', fn ($row) => $row->creator->name)
            ->editColumn('version', fn ($row) => $row->version ?? '---')
            ->editColumn('created_at', fn ($row) => Carbon::parse($row->created_at)->format('M jS Y'))
            ->editColumn('updated_at', fn ($row) => Carbon::parse($row->updated_at)->format('M jS Y'))
            ->editColumn('language', fn ($row) => config('consentTrackingTemplate.language.' . $row->language))
            ->editColumn('status', fn ($row) => view('consentTemplateTracking.statusCell', compact('row'))->render())
            ->rawColumns(['status', 'actions'])
            ->toJson();
    }

    /**
     * @param Builder $query
     * @param ConsentTableParams $filters
     * @return void
     */
    private function applyFilters(Builder $query, ConsentTableParams $filters): void
    {
        if (!empty($filters->consent_version)) {

            $query->whereIn('version', $filters->consent_version);
        }

        if (!empty($filters->consent_type)) {
            $query->whereIn('type', $filters->consent_type);
        }

        if (!empty($filters->consent_status)) {
            $query->whereIn('status', $filters->consent_status);
        }
    }

    /**
     * @param Builder $query
     * @param ConsentTableParams $filters
     * @return void
     */
    private function applySearch(Builder $query, ConsentTableParams $filters): void
    {
        if (empty($filters->search) || empty($filters->search['value'])) {
            return;
        }

        $rawTerm = $filters->search['value'];
        $term    = '%' . $rawTerm . '%';

        $languageCodes = $this->searchLanguageCodes($rawTerm);

        $query->where(function (Builder $q) use ($term, $languageCodes) {
            $q->orWhereHas('creator', function (Builder $q2) use ($term) {
                $q2->where('name', 'like', $term);
            });

            if (!empty($languageCodes)) {
                $q->orWhereIn('language', $languageCodes);
            } else {
                $q->orWhere('language', 'like', $term);
            }
        });
    }

    /**
     * @param string $search
     * @return array
     */
    private function searchLanguageCodes(string $search): array
    {
        $search = mb_strtolower($search);
        $languages = config('consentTrackingTemplate.language', []);

        $matchedCodes = [];

        foreach ($languages as $code => $label) {
            if (mb_strpos(mb_strtolower($label), $search) !== false) {
                $matchedCodes[] = $code;
            }
        }

        return $matchedCodes;
    }

    /**
     * @param ConsentTrackDTO $params
     * @return void
     * @throws Exception
     */
    public function update(ConsentTrackDTO $params): void
    {
        $consent = $this->getConsentTemplateById($params->getId());
        $payload = [];

        if ($consent->status !== StatusEnum::DRAFT->value) {
            throw new Exception('Only draft consents can be updated.');
        }

        if ($params->getStatus() === StatusEnum::DRAFT->value) {
            $payload = [
               'content' => $params->getContent(),
               'status' => $params->getStatus(),
               'language' => $params->getLanguage(),
           ];
        }

        if ($params->getStatus() === StatusEnum::PUBLISHED->value) {
            $payload = [
                'content' => $params->getContent(),
                'status' => $params->getStatus(),
                'language' => $params->getLanguage(),
                'version' => $this->generateNextVersion($params),
                'activated_at' => now(),
            ];
        }

        $consent->update($payload);
    }

    /**
     * Delete a draft consent
     */
    public function delete(ConsentTemplate $consent): bool
    {
        if ($consent->status !== StatusEnum::DRAFT->value) {
            throw new \Exception('Only draft consents can be deleted.');
        }

        return $consent->delete();
    }

    /**
     * @param int $id
     * @return ConsentTemplate|null
     */
    public function getConsentTemplateById(int $id): ?ConsentTemplate
    {
        return ConsentTemplate::find($id);
    }

    /**
     * @return array
     */
    public function getListOfVersions(): array
    {
        try {
            return ConsentTemplate::query()
                ->whereNotNull('version')
                ->distinct()
                ->orderBy('version', 'asc')
                ->pluck('version')
                ->map(function ($version) {
                    $v = number_format((float) $version, 1, '.', '');
                    $label = 'V' . $v;

                    return [
                        'text'  => $label,
                        'value' => $v,
                    ];
                })
                ->values()
                ->all();
        } catch (\Exception $e) {
            Log::error('Failed to get list of versions', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param ConsentTrackDTO $data
     * @return float
     */
    protected function generateNextVersion(ConsentTrackDTO $data): float
    {
        $lastVersion = ConsentTemplate::query()
            ->where('type', $data->getType())
            ->where('language', $data->getLanguage())
            ->max('version');

        if ($lastVersion === null) {
            return 1.0;
        }

        $current = (float)$lastVersion;

        if ($current <= 0) {
            return 1.0;
        }

        return $current + 1.0;
    }
}
