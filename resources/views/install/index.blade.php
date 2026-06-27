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
    $requirementsReady = collect($checks)->every('passed');
    $errorFields = array_keys($errors->toArray());
    $steps = $isDocker
        ? [
            ['id' => 'public', 'title' => 'Public access', 'description' => 'Set the browser-facing URL', 'image' => 'cloud-hosting-amico.svg'],
            ['id' => 'admin', 'title' => 'Administrator', 'description' => 'Create the owner account', 'image' => 'control-panel-pana.svg'],
        ]
        : [
            ['id' => 'readiness', 'title' => 'Readiness', 'description' => 'Verify server requirements', 'image' => 'app-installation-bro.svg'],
            ['id' => 'public', 'title' => 'Public access', 'description' => 'Set the browser-facing URL', 'image' => 'cloud-hosting-amico.svg'],
            ['id' => 'services', 'title' => 'Services', 'description' => 'Connect PostgreSQL and Redis', 'image' => 'cloud-hosting-amico.svg'],
            ['id' => 'admin', 'title' => 'Administrator', 'description' => 'Create the owner account', 'image' => 'control-panel-pana.svg'],
        ];
    $stepIds = array_column($steps, 'id');
    $stepIndexes = array_flip($stepIds);
    $firstStep = $stepIds[0];
    $lastStep = $stepIds[array_key_last($stepIds)];
    $docsUrl = config('community.docs_url');
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
                        <p class="text-sm text-slate-500">Community Edition installer</p>
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
                        <div class="flex gap-3">
                            <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 8v4M12 16h.01"/>
                            </svg>
                            <div>
                                <p class="font-semibold">Review the highlighted information.</p>
                                <ul class="mt-1 list-disc space-y-1 pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="post" action="{{ route('install.store') }}" id="installer-form">
                    @csrf
                    <input type="hidden" name="app_locale" value="en">

                    @unless ($isDocker)
                        <div data-step="readiness">
                            <div class="install-panel">
                                <section class="install-copy">
                                    <p class="eyebrow">Server checks</p>
                                    <h1>Confirm this server is ready.</h1>
                                    <p>Check PHP, extensions, and writable paths before setup continues.</p>

                                    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 bg-slate-50 px-5 py-4">
                                            <div>
                                                <p class="font-semibold text-slate-950">{{ $requirementsReady ? 'Requirements passed' : 'Action required' }}</p>
                                                <p class="text-sm text-slate-500">{{ count($checks) }} checks completed.</p>
                                            </div>
                                            <span class="status-pill {{ $requirementsReady ? 'status-ready' : 'status-error' }}">{{ $requirementsReady ? 'Ready' : 'Fix required' }}</span>
                                        </div>
                                        <div class="divide-y divide-slate-100 px-5">
                                            @foreach ($checks as $check)
                                                <div class="flex items-start justify-between gap-4 py-3">
                                                    <span class="text-sm font-medium text-slate-700">{{ $check['name'] }}</span>
                                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $check['passed'] ? 'text-brand-700' : 'text-red-700' }}">
                                                        <span class="size-1.5 rounded-full {{ $check['passed'] ? 'bg-brand-500' : 'bg-red-500' }}"></span>
                                                        {{ $check['passed'] ? 'Ready' : $check['detail'] }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </section>

                                <aside class="install-visual">
                                    <img src="{{ asset('images/install/app-installation-bro.svg') }}" alt="Application installation illustration">
                                    <div class="visual-note">
                                        <p class="font-semibold text-slate-950">Traditional install</p>
                                        <p>Resolve failed checks on the host before continuing.</p>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    @endunless

                    <div data-step="public" @if ($firstStep !== 'public') hidden @endif>
                        <div class="install-panel">
                            <section class="install-copy">
                                <p class="eyebrow">Public access</p>
                                <h1>Set the address browsers will use.</h1>
                                <p>Use the final HTTPS URL exposed by your proxy, tunnel, or load balancer.</p>

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
                                        <small>Use <code>localhost</code> only for local testing. Public websites need a browser-reachable URL.</small>
                                    </label>

                                    <label>Locale
                                        <input value="English" disabled>
                                        <small>Only English is supported for the installer interface right now.</small>
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
                                <img src="{{ asset('images/install/cloud-hosting-amico.svg') }}" alt="Cloud hosting illustration">
                                <div class="visual-note">
                                    <p class="font-semibold text-slate-950">Production URL</p>
                                    <p>Point DNS and HTTPS at WeblexAI before using the project snippet on a public website.</p>
                                </div>
                            </aside>
                        </div>
                    </div>

                    @unless ($isDocker)
                        <div data-step="services" hidden>
                            <div class="install-panel">
                                <section class="install-copy">
                                    <p class="eyebrow">Infrastructure</p>
                                    <h1>Connect PostgreSQL and Redis.</h1>
                                    <p>Use private services reachable by this application server.</p>

                                    <div class="form-card">
                                        <h2>PostgreSQL</h2>
                                        <label>Host <input name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required></label>
                                        <label>Port <input type="number" name="db_port" value="{{ old('db_port', 5432) }}" min="1" max="65535" required></label>
                                        <label>Database <input name="db_database" value="{{ old('db_database', 'weblex') }}" required></label>
                                        <label>Username <input name="db_username" value="{{ old('db_username', 'weblex') }}" required></label>
                                        <label>Password
                                            <span class="password-field">
                                                <input type="password" name="db_password" autocomplete="new-password">
                                                <button type="button" data-password-toggle aria-label="Show password" aria-pressed="false"></button>
                                            </span>
                                        </label>

                                        <h2 class="pt-3">Redis</h2>
                                        <label>Host <input name="redis_host" value="{{ old('redis_host', '127.0.0.1') }}" required></label>
                                        <label>Port <input type="number" name="redis_port" value="{{ old('redis_port', 6379) }}" min="1" max="65535" required></label>
                                        <label>Password
                                            <span class="password-field">
                                                <input type="password" name="redis_password" autocomplete="new-password">
                                                <button type="button" data-password-toggle aria-label="Show password" aria-pressed="false"></button>
                                            </span>
                                        </label>
                                        <label>Database <input type="number" name="redis_db" value="{{ old('redis_db', 0) }}" min="0" max="15" required></label>
                                    </div>
                                </section>

                                <aside class="install-visual">
                                    <img src="{{ asset('images/install/cloud-hosting-amico.svg') }}" alt="Cloud services illustration">
                                    <div class="visual-note">
                                        <p class="font-semibold text-slate-950">Private services</p>
                                        <p>Keep database and cache ports closed to the public internet.</p>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    @endunless

                    <div data-step="admin" hidden>
                        <div class="install-panel">
                            <section class="install-copy">
                                <p class="eyebrow">Administrator</p>
                                <h1>Create the owner account.</h1>
                                <p>This account signs in after setup and manages every user.</p>

                                <div class="form-card">
                                    <label>Name <input name="admin_name" value="{{ old('admin_name') }}" autocomplete="name" required maxlength="100"></label>
                                    <label>Email <input type="email" name="admin_email" value="{{ old('admin_email') }}" autocomplete="email" required></label>
                                    <label>Password
                                        <span class="password-field">
                                            <input type="password" name="admin_password" autocomplete="new-password" required>
                                            <button type="button" data-password-toggle aria-label="Show password" aria-pressed="false"></button>
                                        </span>
                                        <small>At least 12 characters with uppercase, lowercase, number, and symbol.</small>
                                    </label>
                                    <label>Confirm password
                                        <span class="password-field">
                                            <input type="password" name="admin_password_confirmation" autocomplete="new-password" required>
                                            <button type="button" data-password-toggle aria-label="Show password confirmation" aria-pressed="false"></button>
                                        </span>
                                    </label>
                                </div>
                            </section>

                            <aside class="install-visual">
                                <img src="{{ asset('images/install/control-panel-pana.svg') }}" alt="Control panel illustration">
                                <div class="visual-note">
                                    <p class="font-semibold text-slate-950">Ready to finish</p>
                                    <p>WeblexAI will write the environment file, run migrations, seed defaults, and sign you in.</p>
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div class="mt-7 flex items-center justify-between gap-4">
                        <button type="button" id="previous-step" class="secondary-button" hidden>
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            Back
                        </button>
                        <span></span>
                        <button type="button" id="next-step" class="primary-button" @disabled(! $isDocker && ! $requirementsReady && $firstStep === 'readiness')>
                            Continue
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                        <button type="submit" id="install-button" class="primary-button" hidden>
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                            <span id="install-button-label">Install WeblexAI</span>
                        </button>
                    </div>
                </form>

                <footer class="install-footer">
                    <a class="github-link" href="{{ config('community.github_url') }}" target="_blank" rel="noopener noreferrer" aria-label="GitHub repository">
                        <img src="{{ asset('images/brand/github.svg') }}" alt="" aria-hidden="true">
                    </a>
                    <span aria-hidden="true">/</span>
                    @if ($docsUrl !== '#')
                        <a href="{{ $docsUrl }}" target="_blank" rel="noopener noreferrer">Docs</a>
                    @else
                        <span class="is-disabled">Docs</span>
                    @endif
                    <span aria-hidden="true">/</span>
                    <span>Illustrations by <a href="https://storyset.com" target="_blank" rel="noopener noreferrer">Storyset</a></span>
                </footer>
            </div>
        </section>
    </main>
</div>

<script>
    const form = document.querySelector('#installer-form');
    const steps = Array.from(document.querySelectorAll('[data-step]'));
    const triggers = Array.from(document.querySelectorAll('[data-step-trigger]'));
    const previousButton = document.querySelector('#previous-step');
    const nextButton = document.querySelector('#next-step');
    const installButton = document.querySelector('#install-button');
    const installButtonLabel = document.querySelector('#install-button-label');
    const password = form.elements.namedItem('admin_password');
    const passwordConfirmation = form.elements.namedItem('admin_password_confirmation');
    const errorFields = @json($errorFields);
    const requirementsReady = @json($requirementsReady);
    const isDocker = @json($isDocker);
    const currentRequestUrl = @json($currentRequestUrl);
    const stepIds = @json($stepIds);
    const fieldStepIds = {
        app_name: 'public',
        app_url: 'public',
        app_locale: 'public',
        app_timezone: 'public',
        db_host: 'services',
        db_port: 'services',
        db_database: 'services',
        db_username: 'services',
        db_password: 'services',
        redis_host: 'services',
        redis_port: 'services',
        redis_password: 'services',
        redis_db: 'services',
        admin_name: 'admin',
        admin_email: 'admin',
        admin_password: 'admin',
        admin_password_confirmation: 'admin',
        infrastructure: isDocker ? 'public' : 'services',
        installation: 'admin',
    };
    let currentStep = errorFields.reduce((index, field) => {
        const stepId = fieldStepIds[field] ?? stepIds[0];
        return Math.max(index, stepIds.indexOf(stepId));
    }, 0);

    const validateStep = () => {
        const controls = Array.from(steps[currentStep].querySelectorAll('input:not([disabled]), select:not([disabled])'));
        const invalid = controls.find((control) => !control.checkValidity());

        if (invalid) {
            invalid.reportValidity();
            invalid.focus();
            return false;
        }

        return true;
    };

    const showStep = (step) => {
        currentStep = Math.max(0, Math.min(step, steps.length - 1));
        steps.forEach((panel, index) => panel.hidden = index !== currentStep);
        triggers.forEach((trigger, index) => {
            const active = index === currentStep;
            const complete = index < currentStep;
            trigger.classList.toggle('is-active', active);
            trigger.classList.toggle('is-complete', complete);
            trigger.setAttribute('aria-current', active ? 'step' : 'false');
        });
        previousButton.hidden = currentStep === 0;
        nextButton.hidden = currentStep === steps.length - 1;
        installButton.hidden = currentStep !== steps.length - 1;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const hiddenIcon = `
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M2.1 12a10.8 10.8 0 0 1 19.8 0 10.8 10.8 0 0 1-19.8 0Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    `;
    const visibleIcon = `
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 4.2A10.8 10.8 0 0 1 21.9 12a11 11 0 0 1-3.2 4.2M6.6 6.6A11.1 11.1 0 0 0 2.1 12a10.8 10.8 0 0 0 7.8 6.8"/>
        </svg>
    `;

    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const input = toggle.previousElementSibling;
        toggle.innerHTML = hiddenIcon;
        toggle.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            toggle.innerHTML = isVisible ? hiddenIcon : visibleIcon;
            toggle.setAttribute('aria-label', isVisible ? 'Show value' : 'Hide value');
            toggle.setAttribute('aria-pressed', String(!isVisible));
            input.focus({ preventScroll: true });
        });
    });

    document.querySelectorAll('[data-use-current-url]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = form.elements.namedItem('app_url');
            input.value = currentRequestUrl;
            input.focus();
        });
    });

    const validatePasswordConfirmation = () => {
        passwordConfirmation.setCustomValidity(
            passwordConfirmation.value && passwordConfirmation.value !== password.value
                ? 'The password confirmation does not match.'
                : '',
        );
    };

    triggers.forEach((trigger) => trigger.addEventListener('click', () => {
        const target = stepIds.indexOf(trigger.dataset.stepTrigger);
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
    form.addEventListener('submit', (event) => {
        if (currentStep < steps.length - 1) {
            event.preventDefault();
            if (validateStep()) {
                showStep(currentStep + 1);
            }
            return;
        }
        installButton.disabled = true;
        installButtonLabel.textContent = 'Installing...';
    });
    password.addEventListener('input', validatePasswordConfirmation);
    passwordConfirmation.addEventListener('input', validatePasswordConfirmation);
    showStep(currentStep);
</script>
</body>
</html>
