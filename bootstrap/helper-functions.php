<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

if (! function_exists('formatted_date_str')) {
    function formatted_date_str($time = false): string
    {
        if ($time) {
            return 'D, jS M Y h:i A';
        }

        return 'D, jS M Y';
    }
}

if (! function_exists('uploadToGallery')) {
    function uploadToGallery(Model|Authenticatable $model, $image, $collection): Media
    {
        if (! in_array(InteractsWithMedia::class, class_uses_recursive($model))) {
            throw new Exception('this model type is not a mediable class');
        }

        return $model->addMedia($image)
            ->toMediaCollection($collection);
    }
}

if (! function_exists('cedi')) {
    function cedi(): string
    {
        return '₵';
    }
}

if (! function_exists('generateUniqueModelNumber')) {
    function generateUniqueModelNumber($model, $column = 'code', $limit = 16): string|int
    {
        do {
            $code = Str::random($limit);
        } while ($model::where($column, '=', $code)->first());

        return $code;
    }
}

if (! function_exists('getProjectParams')) {
    function getProjectParams(array $merge = []): array
    {
        $project = request()->get('project');
        $pName = $project->name ?? '';
        $pUuid = request()->get('uuid') ?? '';

        return ['project' => $pName, 'uuid' => $pUuid, ...$merge];
    }
}

if (! function_exists('successRes')) {
    function successRes(string $message = '', array $data = []): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }
}

if (! function_exists('errorRes')) {
    function errorRes(string $message = 'Something went wrong'): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }
}

if (! function_exists('getViewsAndTranslRequestsQueryDate')) {
    function getViewsAndTranslRequestsQueryDate(string $message = 'Something went wrong'): array
    {
        $dateQuery = request()->get('date');
        $dateTo = now()->endOfDay();
        $dateFrom = match ($dateQuery) {
            'today' => now()->startOfDay(),
            'l7' => now()->subDays(7)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };
        if ($dateQuery === 'yesterday') {
            $dateTo = now()->subDay()->endOfDay();
            $dateFrom = now()->subDay()->startOfDay();
        }

        return [$dateFrom, $dateTo];
    }
}

if (! function_exists('versioned_asset')) {
    function versioned_asset($path): string
    {
        $file = public_path($path);

        if (file_exists($file)) {
            return asset($path).'?v='.filemtime($file);
        }

        return asset($path);
    }
}
