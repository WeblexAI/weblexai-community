# Laravel Blade Example

Add the SDK to the main layout that wraps the pages you want translated.

```blade
<link rel="stylesheet" href="{{ config('services.weblexai.url') }}/wlai/weblexai.css">
<script defer src="{{ config('services.weblexai.url') }}/wlai/weblexai.min.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', function () {
        WeblexAI.init(@json(config('services.weblexai.project_key')));
    });
</script>
```

Example configuration:

```php
// config/services.php
'weblexai' => [
    'url' => env('WEBLEXAI_URL', 'http://localhost:8787'),
    'project_key' => env('WEBLEXAI_PROJECT_KEY'),
],
```

Add the app URL, for example `http://localhost:8000`, as an accepted origin on the WeblexAI project.
