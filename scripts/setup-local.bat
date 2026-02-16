@echo off
setlocal

set MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe
set DB_HOST=127.0.0.1
set DB_PORT=3306
set DB_NAME=profplanner
set DB_USER=root
set DB_PASS=

if not exist "%MYSQL_EXE%" (
  echo mysql.exe not found at %MYSQL_EXE%
  exit /b 1
)

echo [1/3] Creating database...
"%MYSQL_EXE%" --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 exit /b 1

echo [2/3] Importing schema...
"%MYSQL_EXE%" --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% %DB_NAME% < "%~dp0..\database\schema.sql"
if errorlevel 1 exit /b 1

echo [3/3] Importing demo data...
"%MYSQL_EXE%" --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% %DB_NAME% < "%~dp0..\database\seed_demo.sql"
if errorlevel 1 exit /b 1

if not exist "%~dp0..\uploads" mkdir "%~dp0..\uploads"

echo.
echo Setup complete.
echo Open: http://localhost:8080/profplanner_CLIENT_JOBS/
echo Login:
echo   superadmin@profplanner.app / Pp!Sup3rAdm1n#2026
echo   werkgever@profplanner.app / Pp!Werkg3ver#2026
echo   werknemer@profplanner.app / Pp!Werkn3mer#2026
echo   salesmanager@profplanner.app / Pp!SalesMng#2026
echo   salesagent@profplanner.app / Pp!SalesAg3nt#2026
endlocal
