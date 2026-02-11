param(
  [int]$Port = 8090
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

Write-Host "Starting PHP dev server from: $root"
Write-Host "Open: http://127.0.0.1:$Port/"
Write-Host "Press Ctrl+C to stop."

php -S 127.0.0.1:$Port -t $root

