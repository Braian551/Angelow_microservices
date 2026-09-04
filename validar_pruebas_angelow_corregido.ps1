param(
    [switch]$PauseAtEnd
)

$ErrorActionPreference = "Continue"

# Ejecutar siempre desde la carpeta donde está este .ps1
$ProjectRoot = $PSScriptRoot

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $ProjectRoot = (Get-Location).Path
}

Set-Location $ProjectRoot

$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$LogDir = Join-Path $ProjectRoot "evidencias"
$LogFile = Join-Path $LogDir "validacion_tecnica_$Timestamp.txt"

New-Item -ItemType Directory -Path $LogDir -Force | Out-Null

$results = @()

function Write-Section {
    param([string]$Title)

    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Cyan
    Write-Host " $Title" -ForegroundColor Cyan
    Write-Host "============================================================" -ForegroundColor Cyan
}

function Add-Result {
    param(
        [string]$Name,
        [int]$ExitCode
    )

    $status = if ($ExitCode -eq 0) { "PASS" } else { "FAIL" }

    $script:results += [PSCustomObject]@{
        Prueba    = $Name
        Resultado = $status
        Codigo    = $ExitCode
    }
}

function Invoke-LoggedCommand {
    param(
        [string]$Title,
        [scriptblock]$Command
    )

    Write-Host ""
    Write-Host ">>> $Title" -ForegroundColor Yellow

    # IMPORTANTE:
    # Out-Host evita que la salida del comando se mezcle con el código de salida
    # que devuelve esta función. Antes, Tee-Object devolvía toda la salida por el
    # pipeline y $code terminaba siendo un System.Object[].
    & $Command 2>&1 |
        Tee-Object -FilePath $LogFile -Append |
        Out-Host

    $exitCode = $LASTEXITCODE

    if ($null -eq $exitCode) {
        $exitCode = 0
    }

    return [int]$exitCode
}

# ------------------------------------------------------------
# Verificaciones previas
# ------------------------------------------------------------

Write-Section "VERIFICACIONES PREVIAS"

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: Docker no está disponible en PATH." -ForegroundColor Red
    exit 1
}

$ComposeFiles = @(
    "docker-compose.yml",
    "docker-compose.yaml",
    "compose.yml",
    "compose.yaml"
)

$ComposeFound = $false

foreach ($composeFile in $ComposeFiles) {
    if (Test-Path (Join-Path $ProjectRoot $composeFile)) {
        $ComposeFound = $true
        break
    }
}

if (-not $ComposeFound) {
    Write-Host "ERROR: No se encontró un archivo Docker Compose en la raíz del proyecto." -ForegroundColor Red
    exit 1
}

Write-Host "Raíz del proyecto: $ProjectRoot"
Write-Host "Log de evidencia:  $LogFile"

Write-Host ""
Write-Host "Estado actual de Docker Compose:" -ForegroundColor Yellow

docker compose ps 2>&1 |
    Tee-Object -FilePath $LogFile -Append |
    Out-Host

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "ERROR: docker compose ps falló. Revisa Docker antes de continuar." -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------
# Backend - PHPUnit
# ------------------------------------------------------------

$services = @(
    "auth-service",
    "catalog-service",
    "cart-service",
    "order-service",
    "payment-service",
    "discount-service",
    "shipping-service",
    "notification-service",
    "audit-service"
)

Write-Section "PRUEBAS BACKEND - PHPUNIT"

foreach ($service in $services) {

    $code = Invoke-LoggedCommand `
        -Title "PHPUnit: $service" `
        -Command {
            docker compose exec -T $service php artisan test
        }

    Add-Result -Name "PHPUnit $service" -ExitCode $code
}

# ------------------------------------------------------------
# Frontend - Build
# ------------------------------------------------------------

Write-Section "BUILD FRONTEND"

$code = Invoke-LoggedCommand `
    -Title "Frontend: npm run build" `
    -Command {
        docker compose exec -T frontend sh -c "npm run build"
    }

Add-Result -Name "Frontend npm run build" -ExitCode $code

# ------------------------------------------------------------
# Flutter
# ------------------------------------------------------------

Write-Section "VALIDACIÓN FLUTTER"

$MobilePath = Join-Path $ProjectRoot "mobile\repartidor"

if (-not (Test-Path $MobilePath)) {

    Write-Host "ERROR: No existe la carpeta $MobilePath" -ForegroundColor Red

    Add-Result -Name "Flutter analyze" -ExitCode 1
    Add-Result -Name "Flutter test" -ExitCode 1
}
elseif (-not (Get-Command flutter -ErrorAction SilentlyContinue)) {

    Write-Host "ERROR: Flutter no está disponible en PATH." -ForegroundColor Red

    Add-Result -Name "Flutter analyze" -ExitCode 1
    Add-Result -Name "Flutter test" -ExitCode 1
}
else {

    Push-Location $MobilePath

    try {

        $code = Invoke-LoggedCommand `
            -Title "Flutter analyze" `
            -Command {
                flutter analyze
            }

        Add-Result -Name "Flutter analyze" -ExitCode $code

        $code = Invoke-LoggedCommand `
            -Title "Flutter test" `
            -Command {
                flutter test
            }

        Add-Result -Name "Flutter test" -ExitCode $code
    }
    finally {
        Pop-Location
    }
}

# ------------------------------------------------------------
# Resumen final
# ------------------------------------------------------------

Write-Section "RESUMEN FINAL DE VALIDACIÓN"

$results | Format-Table -AutoSize

"" | Out-File -FilePath $LogFile -Append -Encoding utf8
"============================================================" | Out-File -FilePath $LogFile -Append -Encoding utf8
"RESUMEN FINAL DE VALIDACIÓN" | Out-File -FilePath $LogFile -Append -Encoding utf8
"============================================================" | Out-File -FilePath $LogFile -Append -Encoding utf8

$summaryText = $results | Format-Table -AutoSize | Out-String
$summaryText | Out-File -FilePath $LogFile -Append -Encoding utf8

$failed = @($results | Where-Object { $_.Resultado -eq "FAIL" })

if ($failed.Count -eq 0) {

    Write-Host ""
    Write-Host "VALIDACIÓN FINAL: TODO APROBADO" -ForegroundColor Green

    "VALIDACIÓN FINAL: TODO APROBADO" |
        Out-File -FilePath $LogFile -Append -Encoding utf8

    Write-Host ""
    Write-Host "Evidencia guardada en:" -ForegroundColor Green
    Write-Host $LogFile

    if ($PauseAtEnd) {
        Read-Host "Presiona ENTER para cerrar"
    }

    exit 0
}
else {

    Write-Host ""
    Write-Host "VALIDACIÓN FINAL: HAY PRUEBAS FALLIDAS" -ForegroundColor Red

    "VALIDACIÓN FINAL: HAY PRUEBAS FALLIDAS" |
        Out-File -FilePath $LogFile -Append -Encoding utf8

    Write-Host ""
    Write-Host "Fallaron:" -ForegroundColor Red
    $failed | Format-Table -AutoSize

    Write-Host ""
    Write-Host "Revisa el log completo:" -ForegroundColor Yellow
    Write-Host $LogFile

    if ($PauseAtEnd) {
        Read-Host "Presiona ENTER para cerrar"
    }

    exit 1
}
