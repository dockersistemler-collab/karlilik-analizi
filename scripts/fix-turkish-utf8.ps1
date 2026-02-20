param(
    [string]$Root = ".",
    [string[]]$Extensions = @("*.blade.php", "*.php", "*.js", "*.ts", "*.vue", "*.md"),
    [string[]]$ExcludeDirs = @("vendor", "node_modules", ".git", "storage", "bootstrap/cache", "public/vendor", "tmp", "YEDEK"),
    [switch]$VerboseLog,
    [int]$MaxPasses = 8
)

$enc1252 = [System.Text.Encoding]::GetEncoding(1252)
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$mojibakePattern = "\u00C3|\u00C4|\u00C5|\u00E2|\uFFFD"

function Get-MojibakeScore([string]$text) {
    if ([string]::IsNullOrEmpty($text)) { return 0 }
    return ([regex]::Matches($text, $mojibakePattern)).Count
}

function Try-FixMojibake([string]$text) {
    if ([string]::IsNullOrEmpty($text)) { return $text }
    try {
        return [System.Text.Encoding]::UTF8.GetString($enc1252.GetBytes($text))
    } catch {
        return $text
    }
}

function Normalize-Path([string]$path) {
    return [System.IO.Path]::GetFullPath($path).TrimEnd([char[]]@('\', '/')).ToLowerInvariant()
}

$rootResolved = Resolve-Path -LiteralPath $Root -ErrorAction SilentlyContinue
if (-not $rootResolved) {
    Write-Output ("Root bulunamadi: {0}" -f $Root)
    exit 1
}
$rootFull = Normalize-Path $rootResolved.Path

$excludeRoots = @()
foreach ($excludeDir in $ExcludeDirs) {
    $excludeRoots += Normalize-Path (Join-Path $rootFull $excludeDir)
}

function Is-ExcludedPath([string]$fullPath, [string[]]$excludedRoots) {
    $normalized = Normalize-Path $fullPath
    foreach ($excludeRoot in $excludedRoots) {
        if ($normalized -eq $excludeRoot) { return $true }
        if ($normalized.StartsWith($excludeRoot + "\\")) { return $true }
    }
    return $false
}

$allFiles = @()
foreach ($ext in $Extensions) {
    $allFiles += Get-ChildItem -Path $rootFull -Recurse -File -Filter $ext -ErrorAction SilentlyContinue |
        Where-Object { -not (Is-ExcludedPath $_.FullName $excludeRoots) }
}
$files = $allFiles | Group-Object -Property FullName | ForEach-Object { $_.Group[0] }

$updated = 0
$checked = 0
foreach ($file in $files) {
    $checked++
    $path = $file.FullName
    try {
        $raw = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)
    } catch {
        continue
    }

    $beforeScore = Get-MojibakeScore $raw
    if ($beforeScore -eq 0) { continue }

    $candidate = $raw
    $bestScore = $beforeScore

    for ($i = 0; $i -lt $MaxPasses; $i++) {
        $next = Try-FixMojibake $candidate
        $nextScore = Get-MojibakeScore $next
        if ($nextScore -ge $bestScore) { break }
        $candidate = $next
        $bestScore = $nextScore
        if ($bestScore -eq 0) { break }
    }

    if ($bestScore -lt $beforeScore) {
        [System.IO.File]::WriteAllText($path, $candidate, $utf8NoBom)
        $updated++
        if ($VerboseLog) {
            Write-Output ("FIXED: {0} ({1} -> {2})" -f $path, $beforeScore, $bestScore)
        }
    }
}

Write-Output ("Checked: {0}" -f $checked)
Write-Output ("Updated: {0}" -f $updated)
