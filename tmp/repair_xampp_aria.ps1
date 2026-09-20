$ErrorActionPreference = 'Stop'
if (Get-Process mysqld -ErrorAction SilentlyContinue) { throw 'MySQL must be stopped before recovery.' }
$dataPath = (Resolve-Path -LiteralPath 'C:/xampp/mysql/data').Path
if ($dataPath -ne 'C:\xampp\mysql\data') { throw 'Unexpected database directory.' }
$backupPath = 'C:\xampp\mysql\recovery-backups\aria-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
New-Item -ItemType Directory -Path $backupPath | Out-Null
Copy-Item -LiteralPath $dataPath -Destination (Join-Path $backupPath 'data') -Recurse
$originalFiles = Get-ChildItem -LiteralPath $dataPath -Recurse -File
foreach ($file in $originalFiles) {
    $relative = $file.FullName.Substring($dataPath.Length + 1)
    $copy = Join-Path (Join-Path $backupPath 'data') $relative
    if ((Get-FileHash -LiteralPath $file.FullName).Hash -ne (Get-FileHash -LiteralPath $copy).Hash) { throw "Backup verification failed: $relative" }
}
Write-Output "Verified full backup: $backupPath"
if (Get-Process mysqld -ErrorAction SilentlyContinue) { throw 'MySQL started during backup. Recovery cancelled.' }
$logArchive = Join-Path $backupPath 'quarantined-logs'
New-Item -ItemType Directory -Path $logArchive | Out-Null
Get-ChildItem -LiteralPath $dataPath -File | Where-Object Name -Match '^aria_log\.\d+$' | ForEach-Object {
    if ($_.DirectoryName -ne $dataPath) { throw 'Unexpected log path.' }
    Move-Item -LiteralPath $_.FullName -Destination (Join-Path $logArchive $_.Name)
}
$tables = Get-ChildItem -LiteralPath $dataPath -Recurse -Filter '*.MAI' -File
foreach ($table in $tables) {
    & C:/xampp/mysql/bin/aria_chk.exe --no-defaults --ignore-control-file --safe-recover $table.FullName
    if ($LASTEXITCODE -ne 0) { throw "Repair failed: $($table.Name). Backup preserved at $backupPath" }
}
Write-Output "Repaired $($tables.Count) Aria tables. Backup: $backupPath"

$mysqlExe = 'C:/xampp/mysql/bin/mysqld.exe'
$mysqlArgs = @(
    '--defaults-file=C:/xampp/mysql/bin/my.ini',
    '--standalone',
    '--console'
)
Start-Process -FilePath $mysqlExe -ArgumentList $mysqlArgs -WindowStyle Hidden

$deadline = (Get-Date).AddSeconds(30)
do {
    Start-Sleep -Seconds 1
    & C:/xampp/mysql/bin/mysqladmin.exe --protocol=tcp --host=127.0.0.1 --port=3306 --user=root ping 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Output 'MySQL started successfully.'
        & C:/xampp/mysql/bin/mysqlcheck.exe --protocol=tcp --host=127.0.0.1 --port=3306 --user=root --repair mysql
        if ($LASTEXITCODE -ne 0) { throw "mysqlcheck repair failed. Backup preserved at $backupPath" }
        & C:/xampp/mysql/bin/mysqlcheck.exe --protocol=tcp --host=127.0.0.1 --port=3306 --user=root --all-databases --check
        if ($LASTEXITCODE -ne 0) { throw "mysqlcheck verification failed. Backup preserved at $backupPath" }
        Write-Output "XAMPP MySQL repair complete. Backup: $backupPath"
        exit 0
    }
} while ((Get-Date) -lt $deadline)

throw "MySQL did not start after repair. Backup preserved at $backupPath"
