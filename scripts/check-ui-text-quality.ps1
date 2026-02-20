param(
    [string]$Root = ".",
    [string[]]$Paths = @("resources/views", "app/Http/Controllers", "app/View"),
    [string[]]$Extensions = @("*.blade.php", "*.php", "*.js", "*.ts", "*.vue", "*.md"),
    [switch]$Strict
)

$ErrorActionPreference = "Stop"

function Get-TargetFiles {
    param(
        [string]$RootPath,
        [string[]]$ScanPaths,
        [string[]]$FilePatterns
    )

    $result = @()
    foreach ($scanPath in $ScanPaths) {
        $fullPath = Join-Path $RootPath $scanPath
        if (-not (Test-Path $fullPath)) {
            continue
        }

        foreach ($pattern in $FilePatterns) {
            $result += Get-ChildItem -Path $fullPath -Recurse -File -Filter $pattern -ErrorAction SilentlyContinue
        }
    }

    return $result | Group-Object -Property FullName | ForEach-Object { $_.Group[0] }
}

function Add-Issue {
    param(
        [System.Collections.Generic.List[object]]$Issues,
        [string]$Type,
        [string]$Path,
        [int]$Line,
        [string]$Snippet
    )

    $Issues.Add([PSCustomObject]@{
        Type = $Type
        Path = $Path
        Line = $Line
        Snippet = $Snippet
    })
}

function Scan-File {
    param(
        [string]$Path,
        [System.Collections.Generic.List[object]]$Issues
    )

    $lines = Get-Content -Path $Path -Encoding UTF8
    for ($i = 0; $i -lt $lines.Length; $i++) {
        $lineNo = $i + 1
        $line = $lines[$i]
        $lineForWordCheck = [regex]::Replace($line, 'https?://\S+', '')
        $lineForWordCheck = [regex]::Replace($lineForWordCheck, '[?&][A-Za-z0-9_\-]+=', '')

        if ($line -match 'Ã|Ä|Å|â|[\uFFFD]') {
            Add-Issue -Issues $Issues -Type "mojibake" -Path $Path -Line $lineNo -Snippet $line
        }

        if ($lineForWordCheck -match '[\p{L}]\?[\p{L}]') {
            Add-Issue -Issues $Issues -Type "question-mark-in-word" -Path $Path -Line $lineNo -Snippet $line
        }
    }
}

Set-Location $Root

$issues = New-Object "System.Collections.Generic.List[object]"
$files = Get-TargetFiles -RootPath (Get-Location).Path -ScanPaths $Paths -FilePatterns $Extensions

foreach ($file in $files) {
    Scan-File -Path $file.FullName -Issues $issues
}

Write-Output ("Checked files: {0}" -f $files.Count)

if ($issues.Count -eq 0) {
    Write-Output "No text quality issue detected."
    exit 0
}

$issues | Select-Object -First 200 | ForEach-Object {
    Write-Output ("[{0}] {1}:{2} -> {3}" -f $_.Type, $_.Path, $_.Line, $_.Snippet.Trim())
}

if ($issues.Count -gt 200) {
    Write-Output ("... and {0} more issues." -f ($issues.Count - 200))
}

$hasCritical = ($issues | Where-Object { $_.Type -in @("mojibake", "question-mark-in-word") }).Count -gt 0

if ($Strict -or $hasCritical) {
    Write-Error ("Text quality check failed. Issue count: {0}" -f $issues.Count)
    exit 1
}

Write-Warning ("Text quality check found non-critical issues. Issue count: {0}" -f $issues.Count)
exit 0
