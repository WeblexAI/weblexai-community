<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TranslationRequestService
{
    public static function getDonutData(Project $project, $dateFrom, $dateTo): Collection|\Illuminate\Database\Eloquent\Collection
    {
        return $project->translationRequests()
            ->select('target_lang_id', DB::raw('COUNT(*) as total'))
            ->with('targetLanguage')
            ->groupBy('target_lang_id')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->targetLanguage->name,
                    'total' => $row->total,
                    'color' => $row->targetLanguage->color,
                    'date' => 'Aug 11, 2024',
                ];
            });
    }

    public static function getLineData(Project $project, $dateFrom, $dateTo): array
    {
        $lineGroupByQuery = request()->get('linegby') ?? 'day';
        switch ($lineGroupByQuery) {
            case 'week':
                $dateSelect = DB::raw("to_char(created_at, 'IYYY-\"W\"IW') as period");
                $dateGroupBy = DB::raw("to_char(created_at, 'IYYY-\"W\"IW')");
                $displayFormat = 'M d, Y';
                $dateKeyFn = function (string $period): Carbon {
                    if (! str_contains($period, '-W')) {
                        return Carbon::createFromFormat('Y-m-d', $period)->startOfDay();
                    }
                    [$year, $week] = explode('-W', $period);

                    return Carbon::now()->setISODate((int) $year, (int) $week)->startOfWeek();
                };
                break;

            case 'month':
                $dateSelect = DB::raw("to_char(created_at, 'YYYY-MM') as period");
                $dateGroupBy = DB::raw("to_char(created_at, 'YYYY-MM')");
                $displayFormat = 'M Y';
                $dateKeyFn = function (string $period): Carbon {
                    return Carbon::createFromFormat('Y-m', $period)->startOfMonth();
                };
                break;

            case 'day':
            default:
                $dateSelect = DB::raw('created_at::date as period');
                $dateGroupBy = DB::raw('created_at::date');
                $displayFormat = 'M d, Y';
                $dateKeyFn = function (string $period): Carbon {
                    return Carbon::createFromFormat('Y-m-d', $period)->startOfDay();
                };
                break;
        }

        $lineRequests = $project->translationRequests()
            ->with('targetLanguage:id,name,color')
            ->select(
                'target_lang_id',
                $dateSelect,
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy($dateGroupBy, 'target_lang_id')
            ->orderBy($dateGroupBy)
            ->get();

        if ($lineRequests->isEmpty()) {
            $lineData = [];
            $lineColors = [];
        } else {
            $uniquePeriods = $lineRequests->pluck('period')->unique();
            $periodMap = $uniquePeriods->mapWithKeys(function ($period) use ($dateKeyFn) {
                return [$period => $dateKeyFn($period)];
            });

            $sortedPeriods = $periodMap
                ->sortBy(fn (Carbon $d) => $d->getTimestamp())
                ->keys()
                ->values();

            $languages = $lineRequests
                ->map(fn ($r) => optional($r->targetLanguage)->name)
                ->filter()
                ->unique()
                ->values();

            $data = $sortedPeriods->map(function ($period) use ($lineRequests, $languages, $dateKeyFn, $displayFormat) {
                $carbonDate = $dateKeyFn($period);
                $row = [
                    'date' => $carbonDate->format($displayFormat),
                ];

                foreach ($languages as $lang) {
                    $sum = $lineRequests
                        ->filter(function ($r) use ($period, $lang) {
                            return ($r->period === $period) && (optional($r->targetLanguage)->name === $lang);
                        })
                        ->sum('total');

                    $row[$lang] = (int) $sum;
                }

                return $row;
            });

            $lineData = $data->values()->toArray();
            $lineColors = $lineRequests
                ->pluck('targetLanguage')
                ->filter()
                ->unique('id')
                ->mapWithKeys(fn ($lang) => [$lang->name => $lang->color])
                ->toArray();
        }

        return [$lineData, $lineColors];
    }

    public static function getPagesData(Project $project, $dateFrom, $dateTo): \Illuminate\Database\Eloquent\Collection|Collection
    {
        $pages = $project->pages()
            ->with(['translationRequests' => function ($q) use ($dateFrom, $dateTo) {
                $q->select('page_id', 'target_lang_id', DB::raw('COUNT(*) as total'))
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->groupBy('page_id', 'target_lang_id');
            }, 'translationRequests.targetLanguage'])
            ->get();

        $pagesData = $pages->map(function (Page $page) {
            $totalRequests = $page->translationRequests->sum('total');

            return [
                'path' => $page->path,
                'requests_count' => $totalRequests,
                'translationRequests' => $page->translationRequests->map(function ($req) use ($totalRequests) {
                    $percentage = $totalRequests > 0
                        ? round(($req->total / $totalRequests) * 100, 2)
                        : 0;

                    return [
                        'language_name' => $req->targetLanguage->name,
                        'count' => $req->total,
                        'color' => $req->targetLanguage->color,
                        'percentage' => $percentage,
                    ];
                })->sortBy('language_name')->values(),
            ];
        });

        return $pagesData;
    }
}
