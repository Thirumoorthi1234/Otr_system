@echo off
setlocal enabledelayedexpansion
echo ============================================
echo   XAMPP MySQL EMERGENCY REPAIR SCRIPT
echo ============================================
echo 1. IMPORTANT: Click "STOP" on MySQL in XAMPP first.
echo 2. Close your XAMPP Control Panel if possible for best results.
echo.
pause

set "MYSQL_ROOT=C:\xampp2\mysql"
set "DATA_DIR=%MYSQL_ROOT%\data"
set "BACKUP_DIR=%MYSQL_ROOT%\backup"

:: Create timestamp
set "TS=%date:~-4%%date:~4,2%%date:~7,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
set "TS=%TS: =0%"

if not exist "%DATA_DIR%" (
    echo [ERROR] Could not find MySQL data folder at %DATA_DIR%
    echo Please verify your XAMPP installation path.
    pause
    exit /b
)

echo [1/4] Backing up corrupted data to "data_broken_%TS%"...
rename "%DATA_DIR%" "data_broken_%TS%"

echo [2/4] Restoring fresh configuration from backup folder...
mkdir "%DATA_DIR%"
xcopy "%BACKUP_DIR%\*" "%DATA_DIR%\" /E /I /Q /Y

echo [3/4] Restoring your "otr_system" database folder...
if exist "%MYSQL_ROOT%\data_broken_%TS%\otr_system" (
    mkdir "%DATA_DIR%\otr_system"
    xcopy "%MYSQL_ROOT%\data_broken_%TS%\otr_system\*" "%DATA_DIR%\otr_system\" /E /I /Q /Y
) else (
    echo [WARNING] "otr_system" database not found in broken data.
)

echo [4/4] Restoring main InnoDB data file (ibdata1)...
if exist "%MYSQL_ROOT%\data_broken_%TS%\ibdata1" (
    copy "%MYSQL_ROOT%\data_broken_%TS%\ibdata1" "%DATA_DIR%\ibdata1" /Y
) else (
    echo [ERROR] "ibdata1" not found. This is needed for your data.
)

echo.
echo ============================================
echo   REPAIR COMPLETE!
echo   Go to XAMPP Control Panel and click "Start" on MySQL.
echo ============================================
echo.
echo If it works, your website "otr" will load instantly again.
pause
