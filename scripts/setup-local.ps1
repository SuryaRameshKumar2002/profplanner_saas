param(
  [string]$DbHost = '127.0.0.1',
  [int]$DbPort = 3306,
  [string]$DbName = 'profplanner',
  [string]$DbUser = 'root',
  [string]$DbPass = '',
  [string]$MysqlExe = 'C:\xampp\mysql\bin\mysql.exe'
)

$ErrorActionPreference = 'Stop'

if (!(Test-Path $MysqlExe)) {
  throw "mysql.exe not found at '$MysqlExe'. Install XAMPP or pass -MysqlExe with the correct path."
}

$root = Split-Path -Parent $PSScriptRoot
$schemaFile = Join-Path $root 'database\schema.sql'
$seedFile = Join-Path $root 'database\seed_demo.sql'

if (!(Test-Path $schemaFile)) { throw "Missing file: $schemaFile" }
if (!(Test-Path $seedFile)) { throw "Missing file: $seedFile" }

$mysqlArgsBase = @("--host=$DbHost", "--port=$DbPort", "--user=$DbUser")
if ($DbPass -ne '') { $mysqlArgsBase += "--password=$DbPass" }

Write-Host "[1/4] Creating database '$DbName' if needed..."
& $MysqlExe @mysqlArgsBase -e "CREATE DATABASE IF NOT EXISTS $DbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -ne 0) { throw 'Failed to create database.' }

Write-Host "[2/4] Importing schema.sql..."
Get-Content $schemaFile | & $MysqlExe @mysqlArgsBase $DbName
if ($LASTEXITCODE -ne 0) { throw 'Failed to import schema.sql.' }

Write-Host "[3/4] Importing seed_demo.sql..."
Get-Content $seedFile | & $MysqlExe @mysqlArgsBase $DbName
if ($LASTEXITCODE -ne 0) { throw 'Failed to import seed_demo.sql.' }

Write-Host "[4/4] Ensuring uploads folder exists..."
$uploadsDir = Join-Path $root 'uploads'
New-Item -ItemType Directory -Path $uploadsDir -Force | Out-Null

Write-Host ''
Write-Host 'Setup complete.'
Write-Host "Open: http://localhost:8080/$(Split-Path $root -Leaf)/"
Write-Host 'Test users:'
Write-Host '  admin@profplanner.local / password123'
Write-Host '  werkgever@test.nl / password123'
Write-Host '  werknemer@test.nl / password123'
Write-Host '  salesmanager@test.nl / password123'
Write-Host '  sales@test.nl / password123'
