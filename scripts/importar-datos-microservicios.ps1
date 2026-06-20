param(
    [string]$SqlPath = ".\basededatos.sql"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if (-not (Test-Path $SqlPath)) {
    throw "No se encontro el archivo SQL en la ruta: $SqlPath"
}

$sqlFullPath = (Resolve-Path $SqlPath).Path

function Read-SqlFileLines {
    param(
        [string]$Path
    )

    # Lee el dump como bytes para evitar que Windows PowerShell convierta UTF-8 a ANSI y rompa tildes/ñ.
    $bytes = [System.IO.File]::ReadAllBytes($Path)
    $encodingName = "UTF-8"

    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $text = [System.Text.UTF8Encoding]::new($false, $true).GetString($bytes, 3, $bytes.Length - 3)
    } else {
        try {
            $text = [System.Text.UTF8Encoding]::new($false, $true).GetString($bytes)
        } catch [System.Text.DecoderFallbackException] {
            # Fallback para dumps exportados desde herramientas antiguas en Windows-1252/Latin-1.
            $encodingName = "Windows-1252"
            $text = [System.Text.Encoding]::GetEncoding(1252).GetString($bytes)
        }
    }

    Write-Host "Dump leído como ${encodingName}: $Path"
    return $text -split "`r?`n", 0, "RegexMatch"
}

function Write-Utf8NoBomFile {
    param(
        [string]$Path,
        [string[]]$Lines
    )

    # psql recibe un archivo UTF-8 real sin BOM para conservar caracteres como Bogotá, Itaú y Bancamía.
    $utf8NoBom = [System.Text.UTF8Encoding]::new($false)
    [System.IO.File]::WriteAllText($Path, ($Lines -join [Environment]::NewLine), $utf8NoBom)
}

$sqlLines = Read-SqlFileLines -Path $sqlFullPath

$copyBlocks = @{}
$setvalLines = @{}

function Get-CopyBlockForImport {
    param(
        [string]$TableName,
        [string[]]$BlockLines
    )

    $dedupeColumnIndex = @{
        popular_searches = 1
        google_auth = 2
    }

    if (-not $dedupeColumnIndex.ContainsKey($TableName)) {
        return $BlockLines
    }

    $header = $BlockLines[0]
    $footer = '\.'
    $rows = $BlockLines[1..($BlockLines.Count - 2)]
    $seen = @{}
    $filteredRows = @()

    $columnIndex = [int]$dedupeColumnIndex[$TableName]

    foreach ($row in $rows) {
        if ([string]::IsNullOrWhiteSpace($row) -or $row -eq '\N') {
            continue
        }

        $parts = $row -split "`t"
        if ($parts.Count -le $columnIndex) {
            continue
        }

        $key = $parts[$columnIndex].ToLower()
        if (-not $seen.ContainsKey($key)) {
            $seen[$key] = $true
            $filteredRows += $row
        }
    }

    return @($header) + $filteredRows + @($footer)
}

$lineIndex = 0
while ($lineIndex -lt $sqlLines.Count) {
    $line = $sqlLines[$lineIndex]

    if ($line -match '^COPY public\.([a-zA-Z0-9_]+)\s+\(.*\)\s+FROM stdin;$') {
        $tableName = $Matches[1].ToLower()
        $blockLines = @($line)
        $lineIndex++

        while ($lineIndex -lt $sqlLines.Count) {
            $blockLines += $sqlLines[$lineIndex]
            if ($sqlLines[$lineIndex] -eq '\.') {
                break
            }
            $lineIndex++
        }

        $copyBlocks[$tableName] = $blockLines
    }

    if ($line -match "^SELECT pg_catalog\.setval\('public\.([a-zA-Z0-9_]+)',\s*([0-9]+),\s*(true|false)\);$") {
        $sequenceName = $Matches[1].ToLower()
        $setvalLines[$sequenceName] = $line
    }

    $lineIndex++
}

$services = @(
    @{
        Name = "auth-service"
        DbService = "auth-db"
        DbName = "angelow_auth"
        Tables = @("users","access_tokens","google_auth","login_attempts","password_resets","personal_access_tokens","sessions")
    },
    @{
        Name = "catalog-service"
        DbService = "catalog-db"
        DbName = "angelow_catalog"
        Tables = @("categories","collections","colors","sizes","products","product_collections","product_color_variants","product_size_variants","product_images","variant_images","wishlist","product_reviews","review_votes","product_questions","question_answers","popular_searches","search_history","site_settings","sliders","announcements","stock_history")
    },
    @{
        Name = "cart-service"
        DbService = "cart-db"
        DbName = "angelow_cart"
        Tables = @("carts","cart_items")
    },
    @{
        Name = "order-service"
        DbService = "order-db"
        DbName = "angelow_orders"
        Tables = @("orders","order_items","order_status_history","order_views")
    },
    @{
        Name = "payment-service"
        DbService = "payment-db"
        DbName = "angelow_payments"
        Tables = @("colombian_banks","bank_account_config","payment_transactions")
    },
    @{
        Name = "discount-service"
        DbService = "discount-db"
        DbName = "angelow_discounts"
        Tables = @("discount_types","discount_codes","discount_code_products","discount_code_usage","percentage_discounts","fixed_amount_discounts","free_shipping_discounts","bulk_discount_rules","user_applied_discounts")
    },
    @{
        Name = "shipping-service"
        DbService = "shipping-db"
        DbName = "angelow_shipping"
        Tables = @("shipping_methods","shipping_price_rules","user_addresses")
    },
    @{
        Name = "notification-service"
        DbService = "notification-db"
        DbName = "angelow_notifications"
        Tables = @("notification_types","notifications","notification_preferences","notification_queue","admin_notification_dismissals")
    },
    @{
        Name = "audit-service"
        DbService = "audit-db"
        DbName = "angelow_audit"
        Tables = @("audit_categories","audit_orders","audit_users","productos_auditoria","eliminaciones_auditoria")
    }
)

foreach ($service in $services) {
    Write-Host ""
    Write-Host "==> Importando datos para $($service.Name) ($($service.DbName))"

    $importLines = @(
        "BEGIN;",
        "SET client_encoding = 'UTF8';"
    )

    foreach ($table in $service.Tables) {
        $importLines += "TRUNCATE TABLE public.$table RESTART IDENTITY CASCADE;"
    }

    foreach ($table in $service.Tables) {
        if ($copyBlocks.ContainsKey($table)) {
            $importLines += Get-CopyBlockForImport -TableName $table -BlockLines $copyBlocks[$table]
        }
    }

    foreach ($table in $service.Tables) {
        $sequenceKey = "${table}_id_seq"
        if ($setvalLines.ContainsKey($sequenceKey)) {
            $importLines += $setvalLines[$sequenceKey]
        }
    }

    $importLines += "COMMIT;"

    $tempPath = Join-Path $env:TEMP ("angelow_import_{0}.sql" -f $service.Name)
    Write-Utf8NoBomFile -Path $tempPath -Lines $importLines

    $result = Get-Content -Path $tempPath -Encoding UTF8 | docker compose exec -T $service.DbService psql -U postgres -d $service.DbName 2>&1
    $result | Out-Host

    $hasError = $false
    foreach ($line in $result) {
        if ($line -match '^ERROR:') {
            $hasError = $true
            break
        }
    }

    if ($hasError) {
        throw "La importacion fallo para $($service.Name). Revisa los errores anteriores."
    }

    Remove-Item -Path $tempPath -Force
}

Write-Host ""
Write-Host "Importacion completada en todas las bases de microservicios."
