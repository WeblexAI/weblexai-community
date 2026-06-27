param(
    [string] $Project = $env:WEBLEX_E2E_PROJECT,
    [string] $Port = $env:WEBLEX_E2E_PORT
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($Project)) {
    $Project = 'weblex-e2e'
}

if ([string]::IsNullOrWhiteSpace($Port)) {
    $Port = '18080'
}

$Root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Set-Location $Root

$EnvFile = 'tests\e2e\docker.env.runtime'
$CookieFile = 'tests\e2e\cookies.runtime.txt'
$InstallHtml = 'tests\e2e\install.runtime.html'
$InstallResponse = 'tests\e2e\install-response.runtime.txt'
$ConfigResponse = 'tests\e2e\config-response.runtime.json'
$TranslationResponse = 'tests\e2e\translation-response.runtime.ndjson'
$TranslationPayload = 'tests\e2e\translation-payload.runtime.json'
$DeniedResponse = 'tests\e2e\denied-response.runtime.json'
$BackupDir = 'tests\e2e\backups.runtime'

Copy-Item 'tests\e2e\docker.env.example' $EnvFile -Force
$envContent = Get-Content $EnvFile -Raw
$envContent = $envContent -replace 'APP_PORT=.*', "APP_PORT=$Port"
$envContent = $envContent -replace 'APP_URL=.*', "APP_URL=http://localhost:$Port"
Set-Content -Path $EnvFile -Value $envContent -NoNewline

function Invoke-Compose {
    docker compose --env-file $EnvFile -p $Project -f docker-compose.yml -f tests\e2e\docker-compose.e2e.yml @args

    if ($LASTEXITCODE -ne 0) {
        throw "docker compose $args failed with exit code $LASTEXITCODE"
    }
}

function Wait-ForUrl {
    param(
        [string] $Url,
        [int] $Attempts = 120
    )

    for ($i = 0; $i -lt $Attempts; $i++) {
        try {
            curl.exe -fsS $Url | Out-Null
            return
        } catch {
            Start-Sleep -Seconds 2
        }
    }

    throw "Timed out waiting for $Url"
}

function Assert-Contains {
    param(
        [string] $Path,
        [string] $Pattern
    )

    if (-not (Select-String -Path $Path -SimpleMatch $Pattern -Quiet)) {
        Get-Content $Path | Write-Error
        throw "Expected $Path to contain: $Pattern"
    }
}

try {
    $succeeded = $false

    Invoke-Compose down -v --remove-orphans
    Invoke-Compose up -d --build

    Wait-ForUrl "http://localhost:$Port/install" 180
    curl.exe -fsS -c $CookieFile "http://localhost:$Port/install" -o $InstallHtml

    $html = Get-Content $InstallHtml -Raw
    $csrfMatch = [regex]::Match($html, 'name="_token" value="([^"]*)"')
    if (-not $csrfMatch.Success) {
        throw 'Could not extract installer CSRF token.'
    }

    $csrf = $csrfMatch.Groups[1].Value

    curl.exe -sS -i -b $CookieFile -c $CookieFile `
        -X POST "http://localhost:$Port/install" `
        -F "_token=$csrf" `
        -F "app_name=WeblexAI Community Edition" `
        -F "app_url=http://localhost:$Port" `
        -F "app_locale=en" `
        -F "app_timezone=UTC" `
        -F "filesystem_disk=public" `
        -F "admin_name=E2E Admin" `
        -F "admin_email=admin@example.test" `
        -F "admin_password=E2e-Password-123!" `
        -F "admin_password_confirmation=E2e-Password-123!" `
        -o $InstallResponse

    Assert-Contains $InstallResponse 'HTTP/1.1 302'
    Wait-ForUrl "http://localhost:$Port/login" 60

    Invoke-Compose exec -T app php artisan tinker --execute="require base_path('tests/e2e/prepare.php');"
    $apiKey = (& docker compose --env-file $EnvFile -p $Project -f docker-compose.yml -f tests\e2e\docker-compose.e2e.yml exec -T app cat /tmp/weblex-e2e-api-key).Trim()

    curl.exe -fsS `
        -H "Authorization: Bearer $apiKey" `
        -H "Accept: application/json" `
        -H "Origin: http://fixture.test" `
        -H "X-Page-Url: http://fixture.test/products" `
        -H "X-Page-Title: Products" `
        "http://localhost:$Port/api/project/config" `
        -o $ConfigResponse
    Assert-Contains $ConfigResponse '"is_active":true'
    Assert-Contains $ConfigResponse '"iso_2":"fr"'

    Set-Content -Path $TranslationPayload -Value '{"source":"en","target":"fr","translatables":[{"id":"headline","text":"Hello world"}]}' -NoNewline
    $translationStatus = & curl.exe -sS -o $TranslationResponse -w '%{http_code}' `
        -H "Authorization: Bearer $apiKey" `
        -H "Accept: application/json" `
        -H "Origin: http://fixture.test" `
        -H "X-Page-Url: http://fixture.test/products" `
        -H "X-Page-Title: Products" `
        -H "Content-Type: application/json" `
        --data-binary "@$TranslationPayload" `
        "http://localhost:$Port/api/project/translations"

    if ($translationStatus -ne '200') {
        Get-Content $TranslationResponse | Write-Host
        throw "Expected translation endpoint to return 200, got $translationStatus."
    }

    Assert-Contains $TranslationResponse '"type":"batch"'
    Assert-Contains $TranslationResponse 'Mock FR: Hello world'
    Assert-Contains $TranslationResponse '"type":"complete"'

    $deniedStatus = & curl.exe -sS -o $DeniedResponse -w '%{http_code}' `
        -H "Authorization: Bearer $apiKey" `
        -H "Accept: application/json" `
        -H "Origin: http://evil.test" `
        -H "X-Page-Url: http://evil.test/products" `
        "http://localhost:$Port/api/project/config"

    if ($deniedStatus -ne '401') {
        Get-Content $DeniedResponse | Write-Host
        throw "Expected rejected origin to return 401, got $deniedStatus."
    }

    $providerStats = & docker compose --env-file $EnvFile -p $Project -f docker-compose.yml -f tests\e2e\docker-compose.e2e.yml exec -T mock-provider wget -qO- http://127.0.0.1:8081/stats
    if ($providerStats -notmatch '"requests": [1-9][0-9]*') {
        throw "Mock provider was not called: $providerStats"
    }

    New-Item -ItemType Directory -Force $BackupDir | Out-Null
    $env:COMPOSE_PROJECT_NAME = $Project
    $env:COMPOSE_FILE = 'docker-compose.yml;tests/e2e/docker-compose.e2e.yml'
    $env:WEBLEX_ENV_FILE = './tests/e2e/docker.env.runtime'
    $env:MSYS_NO_PATHCONV = '1'
    try {
        $backupOutput = & 'C:\Program Files\Git\bin\bash.exe' scripts/backup-docker.sh $BackupDir
        if ($LASTEXITCODE -ne 0) {
            throw "backup-docker.sh failed with exit code $LASTEXITCODE"
        }

        $backupArchive = $backupOutput.Trim()
        Invoke-Compose exec -T app rm -f /app/storage/app/private/e2e-sentinel.txt
        & 'C:\Program Files\Git\bin\bash.exe' scripts/restore-docker.sh $backupArchive
        if ($LASTEXITCODE -ne 0) {
            throw "restore-docker.sh failed with exit code $LASTEXITCODE"
        }
    } finally {
        Remove-Item Env:\MSYS_NO_PATHCONV -ErrorAction SilentlyContinue
    }

    Wait-ForUrl "http://localhost:$Port/login" 120
    Invoke-Compose exec -T app test -f /app/storage/app/private/e2e-sentinel.txt
    Invoke-Compose exec -T worker php artisan horizon:status
    Invoke-Compose exec -T scheduler php artisan schedule:list

    $succeeded = $true
    Write-Host 'Docker install, origin auth, translation, backup, restore, worker, and scheduler E2E passed.'
} finally {
    if ($env:WEBLEX_E2E_KEEP_ON_FAILURE -eq '1' -and -not $succeeded) {
        Write-Host "Keeping E2E stack '$Project' for inspection."
    } else {
        Invoke-Compose down -v --remove-orphans
    }
}
