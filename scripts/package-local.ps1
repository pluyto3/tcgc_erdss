param(
    [Parameter(Mandatory = $true)]
    [string]$OutputPath
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$outputFullPath = [IO.Path]::GetFullPath($OutputPath)
New-Item -ItemType Directory -Path (Split-Path -Parent $outputFullPath) -Force | Out-Null

$files = [Collections.Generic.List[IO.FileInfo]]::new()
foreach ($directory in @('app', 'config', 'database', 'docs', 'public', 'resources', 'routes', 'scripts', 'tests', 'vendor')) {
    foreach ($file in Get-ChildItem -LiteralPath (Join-Path $projectRoot $directory) -Recurse -Force -File) {
        $relative = [IO.Path]::GetRelativePath($projectRoot, $file.FullName).Replace('\', '/')
        if ($relative -match '\.sqlite(?:-.*)?$' -or $file.Name -eq '.env') { continue }
        if ($relative.StartsWith('public/uploads/') -and $file.Name -ne '.htaccess') { continue }
        $files.Add($file)
    }
}
foreach ($relative in @('artisan', 'composer.json', 'composer.lock', 'phpunit.xml', 'README.md', 'START-HERE.md', '.gitignore', '.htaccess', 'bootstrap/app.php', 'bootstrap/providers.php', 'bootstrap/cache/.gitignore', 'storage/.gitignore', 'storage/backups/original-project.zip')) {
    $files.Add((Get-Item -LiteralPath (Join-Path $projectRoot $relative)))
}

$archive = [IO.Compression.ZipFile]::Open($outputFullPath, [IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($file in $files) {
        $entryName = 'tcgc_erdss/' + [IO.Path]::GetRelativePath($projectRoot, $file.FullName).Replace('\', '/')
        [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $file.FullName, $entryName, [IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally {
    $archive.Dispose()
}

$check = [IO.Compression.ZipFile]::OpenRead($outputFullPath)
try {
    $entryNames = @($check.Entries.FullName)
    if ($entryNames -contains 'tcgc_erdss/.env' -or @($entryNames | Where-Object { $_ -match '\.sqlite(?:-.*)?$|^tcgc_erdss/storage/(framework|logs|composer-cache)/' }).Count -gt 0) {
        throw 'The package unexpectedly contains local environment or runtime state.'
    }
    foreach ($required in @('artisan', 'vendor/autoload.php', 'scripts/setup-local.php', 'START-HERE.md', 'docs/demonstration.env.example', 'docs/institutional.env.example', 'docs/CLIENT-HANDOVER-GUIDE.md')) {
        if ($entryNames -notcontains ('tcgc_erdss/' + $required)) { throw "Missing required package file: $required" }
    }
    Write-Output "Verified $($check.Entries.Count) archive entries."
} finally {
    $check.Dispose()
}
Get-Item -LiteralPath $outputFullPath | Select-Object FullName, Length
