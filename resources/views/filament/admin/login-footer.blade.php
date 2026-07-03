@php
    $githubUrl = config('community.github_url');
    $docsUrl = config('community.docs_url');
@endphp

<div class="weblex-admin-login-footer">
    <nav aria-label="WeblexAI resources">
        <a href="{{ $githubUrl }}" target="_blank" rel="noopener noreferrer" aria-label="GitHub repository">
            <img src="{{ asset('images/brand/github.svg') }}" alt="" aria-hidden="true">
        </a>

        @if ($docsUrl && $docsUrl !== '#')
            <a href="{{ $docsUrl }}" target="_blank" rel="noopener noreferrer">Docs</a>
        @endif
    </nav>
</div>
