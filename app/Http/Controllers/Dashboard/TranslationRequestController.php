<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Models\Project;
use App\Services\TranslationRequestService;
use Inertia\Inertia;
use Inertia\Response;

class TranslationRequestController extends Controller
{
    public function index(Project $project): Response
    {
        [$dateFrom, $dateTo] = getViewsAndTranslRequestsQueryDate();

        $requestsCount = $project->translationRequests()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        $donutData = TranslationRequestService::getDonutData($project, $dateFrom, $dateTo);
        [$lineData, $lineColors] = TranslationRequestService::getLineData($project, $dateFrom, $dateTo);
        $pagesData = TranslationRequestService::getPagesData($project, $dateFrom, $dateTo);

        return Inertia::render('Project/TranslationRequests', [
            'requestsCount' => $requestsCount,
            'donutData' => $donutData,
            'lineData' => $lineData,
            'lineColors' => collect($lineColors)->values()->toArray(),
            'pagesData' => $pagesData,
        ]);
    }
}

