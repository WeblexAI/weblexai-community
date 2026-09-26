[CmdletBinding()]
param(
    [ValidateSet('Install', 'Update')]
    [string] $Mode = 'Install',
    [string] $Version = 'latest',
    [string] $Directory = (Join-Path (Get-Location) 'weblexai')
)

$ErrorActionPreference = 'Stop'

$repositoryUrl = 'https://raw.githubusercontent.com/WeblexAI/weblexai-community'

function Stop-Installer {
    param([string] $Message)

    throw $Message
}

function Invoke-RequiredCommand {
    param([string] $Command)

    if (-not (Get-Command $Command -ErrorAction SilentlyContinue)) {
        Stop-Installer "Required command not found: $Command"
    }
}

function Invoke-Download {
    param(
        [string] $Url,
        [string] $Destination
    )

    Invoke-WebRequest -UseBasicParsing -Uri $Url -OutFile $Destination
}

function Set-EnvValue {
    param(
        [string] $Path,
        [string] $Key,
        [string] $Value
    )

    $content = [System.IO.File]::ReadAllText($Path)
    $pattern = "(?m)^$([regex]::Escape($Key))=.*$"
    $replacement = "$Key=$Value"

    if ([regex]::IsMatch($content, $pattern)) {
        $content = [regex]::Replace(
            $content,
            $pattern,
            [System.Text.RegularExpressions.MatchEvaluator] { param($match) $replacement },
            1
        )
    } else {
        $content = $content.TrimEnd() + [Environment]::NewLine + $replacement + [Environment]::NewLine
    }

    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $content, $encoding)
}

function New-DatabasePassword {
    $bytes = New-Object byte[] 32
    $random = [System.Security.Cryptography.RandomNumberGenerator]::Create()

    try {
        $random.GetBytes($bytes)
    } finally {
        $random.Dispose()
    }

    return (($bytes | ForEach-Object { $_.ToString('x2') }) -join '')
}

if ($Version -eq 'latest') {
    $sourceRef = 'main'
    $dockerVersion = 'latest'
} elseif ($Version -match '^v?(\d+\.\d+\.\d+)$') {
    $dockerVersion = $Matches[1]
    $sourceRef = "v$dockerVersion"
} else {
    Stop-Installer 'Version must be latest or a semver value such as 1.0.0.'
}

Invoke-RequiredCommand 'docker'

& docker compose version *> $null
if ($LASTEXITCODE -ne 0) {
    Stop-Installer 'Docker Compose v2 is required.'
}

& docker info *> $null
if ($LASTEXITCODE -ne 0) {
    Stop-Installer 'The Docker daemon is not running or is not accessible.'
}

$Directory = [System.IO.Path]::GetFullPath($Directory)
$composeFile = Join-Path $Directory 'docker-compose.yml'
$envFile = Join-Path $Directory '.env'
$temporaryDirectory = Join-Path $Directory ".weblexai-install-$([guid]::NewGuid().ToString('N'))"
$sourceUrl = "$repositoryUrl/$sourceRef"

New-Item -ItemType Directory -Path $Directory -Force | Out-Null

function Invoke-Compose {
    param([string[]] $Arguments)

    & docker compose --project-directory $Directory --env-file $envFile --file $composeFile @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Docker Compose failed with exit code $LASTEXITCODE."
    }
}

function Test-Compose {
    Invoke-Compose @('config', '--quiet')
}

try {
    if ($Mode -eq 'Install') {
        if (Test-Path -LiteralPath $envFile) {
            Stop-Installer "An existing .env was found in $Directory. Choose another directory or use update mode."
        }

        if (Test-Path -LiteralPath $composeFile) {
            Stop-Installer "An existing docker-compose.yml was found in $Directory. Choose another directory or use update mode."
        }

        New-Item -ItemType Directory -Path $temporaryDirectory -Force | Out-Null
        Write-Output "Downloading WeblexAI $dockerVersion deployment files..."
        Invoke-Download "$sourceUrl/docker-compose.yml" (Join-Path $temporaryDirectory 'docker-compose.yml')
        Invoke-Download "$sourceUrl/.env.example" (Join-Path $temporaryDirectory '.env.example')
        Copy-Item (Join-Path $temporaryDirectory '.env.example') (Join-Path $temporaryDirectory '.env')

        $databasePassword = New-DatabasePassword
        if ($databasePassword.Length -ne 64) {
            Stop-Installer 'Unable to generate a database password.'
        }

        $temporaryEnv = Join-Path $temporaryDirectory '.env'
        Set-EnvValue $temporaryEnv 'APP_VERSION' $dockerVersion
        Set-EnvValue $temporaryEnv 'DB_PASSWORD' $databasePassword

        $composeFile = Join-Path $temporaryDirectory 'docker-compose.yml'
        $envFile = $temporaryEnv
        Test-Compose

        Move-Item -LiteralPath $composeFile -Destination (Join-Path $Directory 'docker-compose.yml') -Force
        Move-Item -LiteralPath $envFile -Destination (Join-Path $Directory '.env') -Force
        $composeFile = Join-Path $Directory 'docker-compose.yml'
        $envFile = Join-Path $Directory '.env'

        Write-Output 'Pulling Docker images...'
        Invoke-Compose @('pull')
        Write-Output 'Starting WeblexAI...'
        Invoke-Compose @('up', '-d')
        Write-Output 'WeblexAI is starting at http://localhost:8787/install'
        exit 0
    }

    if (-not (Test-Path -LiteralPath $envFile) -or -not (Test-Path -LiteralPath $composeFile)) {
        Stop-Installer "No WeblexAI deployment was found in $Directory. Run install mode first."
    }

    New-Item -ItemType Directory -Path $temporaryDirectory -Force | Out-Null
    Write-Output "Downloading WeblexAI $dockerVersion deployment files..."
    Invoke-Download "$sourceUrl/docker-compose.yml" (Join-Path $temporaryDirectory 'docker-compose.yml')
    $temporaryEnv = Join-Path $temporaryDirectory '.env'
    Copy-Item -LiteralPath $envFile -Destination $temporaryEnv
    Set-EnvValue $temporaryEnv 'APP_VERSION' $dockerVersion

    $composeFile = Join-Path $temporaryDirectory 'docker-compose.yml'
    $envFile = $temporaryEnv
    Test-Compose

    Move-Item -LiteralPath $composeFile -Destination (Join-Path $Directory 'docker-compose.yml') -Force
    Move-Item -LiteralPath $envFile -Destination (Join-Path $Directory '.env') -Force
    $composeFile = Join-Path $Directory 'docker-compose.yml'
    $envFile = Join-Path $Directory '.env'

    Write-Output 'Pulling Docker images...'
    Invoke-Compose @('pull')
    Write-Output 'Running database migrations...'
    Invoke-Compose @('--profile', 'tools', 'run', '--rm', 'migrate')
    Write-Output 'Starting WeblexAI...'
    Invoke-Compose @('up', '-d')
    Write-Output "WeblexAI was updated in $Directory"
} finally {
    if (Test-Path -LiteralPath $temporaryDirectory) {
        Remove-Item -LiteralPath $temporaryDirectory -Recurse -Force
    }
}
