<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install WeblexAI Community Edition</title>
    @vite('resources/css/install.css')
</head>
<body>
@php
    $steps = [
        ['id' => 'public', 'title' => 'Public access', 'description' => 'Set the browser-facing URL'],
        ['id' => 'admin', 'title' => 'Administrator', 'description' => 'Create the owner account'],
    ];
    $docsUrl = config('community.docs_url');
    $githubUrl = config('community.github_url');
@endphp

<div class="min-h-screen bg-slate-50">
    <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-5 py-6 lg:px-8">
        <header class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <div class="grid size-11 place-items-center rounded-2xl bg-brand-600 text-white">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m5 8 7-4 7 4-7 4-7-4Z"/>
                            <path d="m5 12 7 4 7-4M5 16l7 4 7-4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-semibold tracking-tight text-slate-950">WeblexAI</p>
                        <p class="text-sm text-slate-500">Docker setup</p>
                    </div>
                </div>

                <nav class="overflow-x-auto" aria-label="Installation progress">
                    <ol class="flex min-w-max items-center gap-2">
                        @foreach ($steps as $index => $step)
                            <li class="flex items-center gap-2">
                                <button type="button" data-step-trigger="{{ $step['id'] }}" class="step-trigger">
                                    <span data-step-number class="step-number">{{ $index + 1 }}</span>
                                    <span class="text-left">
                                        <span data-step-title class="step-title">{{ $step['title'] }}</span>
                                        <span class="step-description">{{ $step['description'] }}</span>
                                    </span>
                                </button>
                                @if (! $loop->last)
                                    <span class="h-px w-8 bg-slate-200" aria-hidden="true"></span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
        </header>

        <section class="flex flex-1 items-center py-8">
            <div class="w-full">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                        <p class="font-semibold">Review the highlighted information.</p>
                        <ul class="mt-1 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('install.store') }}" id="installer-form">
                    @csrf
                    <input type="hidden" name="app_locale" value="en">

                    <div data-step="public">
                        <div class="install-panel">
                            <section class="install-copy">
                                <p class="eyebrow">Public access</p>
                                <h1>Set the address browsers will use.</h1>
                                <p>Use the final URL exposed by your reverse proxy, tunnel, or local Docker port.</p>

                                <div class="form-card">
                                    <label>Application name
                                        <input name="app_name" value="{{ old('app_name', 'WeblexAI Community Edition') }}" required maxlength="100">
                                    </label>

                                    <label>Application URL
                                        <span class="url-field">
                                            <input type="url" name="app_url" value="{{ old('app_url', $defaultAppUrl) }}" placeholder="https://translate.example.com" required>
                                            @if ($currentRequestUrl)
                                                <button type="button" data-use-current-url>Use current</button>
                                            @endif
                                        </span>
                                        <small>For a custom domain, configure DNS and HTTPS in your external proxy, then use that final HTTPS URL here.</small>
                                    </label>

                                    <label>Timezone
                                        <select name="app_timezone" required>
                                            @foreach ($timezones as $timezone)
                                                <option value="{{ $timezone }}" @selected(old('app_timezone', 'UTC') === $timezone)>{{ $timezone }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            </section>

                            <aside class="install-visual">
                                <div class="install-visual-graphic is-public" aria-hidden="true">
                                    <span class="visual-browser"></span>
                                    <span class="visual-server"></span>
                                    <span class="visual-link"></span>
                                    <span class="visual-dot one"></span>
                                    <span class="visual-dot two"></span>
                                </div>
                                <div class="visual-note">
                                    <p class="font-semibold text-slate-950">Docker services are ready</p>
                                    <p>PostgreSQL, Redis, the application, workers, and scheduler run in the Compose stack.</p>
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div data-step="admin" hidden>
                        <div class="install-panel">
                            <section class="install-copy">
                                <p class="eyebrow">Administrator</p>
                                <h1>Create the first administrator.</h1>
                                <p>This account manages projects, provider credentials, users, backups, and application settings.</p>

                                <div class="form-card">
                                    <label>Name
                                        <input name="admin_name" value="{{ old('admin_name') }}" required maxlength="100" autocomplete="name">
                                    </label>

                                    <label>Email
                                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required maxlength="255" autocomplete="email">
                                    </label>

                                    <label>Password
                                        <input type="password" name="admin_password" required autocomplete="new-password">
                                        <small>Use at least 12 characters with letters, mixed case, numbers, and symbols.</small>
                                    </label>

                                    <label>Confirm password
                                        <input type="password" name="admin_password_confirmation" required autocomplete="new-password">
                                    </label>
                                </div>
                            </section>

                            <aside class="install-visual">
                                <div class="install-visual-graphic" aria-hidden="true">
                                    <span class="visual-user"></span>
                                    <span class="visual-shield"></span>
                                    <span class="visual-panel"></span>
                                </div>
                                <div class="visual-note">
                                    <p class="font-semibold text-slate-950">Keep your application key safe</p>
                                    <p>It protects encrypted provider credentials and is retained in the persistent Docker configuration volume.</p>
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-between gap-3">
                        <button type="button" id="previous-step" class="secondary-button">Back</button>
                        <div class="ml-auto flex gap-3">
                            <button type="button" id="next-step" class="primary-button">Continue</button>
                            <button type="submit" id="install-button" class="primary-button" hidden>
                                <span id="install-button-label">Install WeblexAI</span>
                            </button>
                        </div>
                    </div>
                </form>

                <footer class="install-footer">
                    <span>Docker-only Community Edition</span>
                    @if ($docsUrl && $docsUrl !== '#')
                        <a href="{{ $docsUrl }}" target="_blank" rel="noopener noreferrer">Documentation</a>
                    @endif
                    @if ($githubUrl && $githubUrl !== '#')
                        <a href="{{ $githubUrl }}" target="_blank" rel="noopener noreferrer">GitHub</a>
                    @endif
                </footer>
            </div>
        </section>
    </main>
</div>

<script>
    const form = document.querySelector('#installer-form');
    const steps = [...document.querySelectorAll('[data-step]')];
    const triggers = [...document.querySelectorAll('[data-step-trigger]')];
    const previousButton = document.querySelector('#previous-step');
    const nextButton = document.querySelector('#next-step');
    const installButton = document.querySelector('#install-button');
    const installButtonLabel = document.querySelector('#install-button-label');
    let currentStep = 0;

    const validateStep = () => {
        const fields = [...steps[currentStep].querySelectorAll('input, select')];
        return fields.every((field) => field.reportValidity());
    };

    const showStep = (index) => {
        currentStep = index;
        steps.forEach((step, stepIndex) => {
            step.hidden = stepIndex !== currentStep;
        });
        triggers.forEach((trigger, triggerIndex) => {
            const active = triggerIndex === currentStep;
            trigger.classList.toggle('is-active', active);
            trigger.classList.toggle('is-complete', triggerIndex < currentStep);
            trigger.setAttribute('aria-current', active ? 'step' : 'false');
        });
        previousButton.hidden = currentStep === 0;
        nextButton.hidden = currentStep === steps.length - 1;
        installButton.hidden = currentStep !== steps.length - 1;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    triggers.forEach((trigger) => trigger.addEventListener('click', () => {
        const target = triggers.indexOf(trigger);
        if (target <= currentStep || (target === currentStep + 1 && validateStep())) {
            showStep(target);
        }
    }));

    previousButton.addEventListener('click', () => showStep(currentStep - 1));
    nextButton.addEventListener('click', () => {
        if (validateStep()) {
            showStep(currentStep + 1);
        }
    });

    form.addEventListener('submit', () => {
        installButton.disabled = true;
        installButtonLabel.textContent = 'Installing...';
    });

    document.querySelector('[data-use-current-url]')?.addEventListener('click', () => {
        const input = form.elements.namedItem('app_url');
        input.value = @json($currentRequestUrl);
        input.focus();
    });

    showStep(0);
</script>
</body>
</html>
