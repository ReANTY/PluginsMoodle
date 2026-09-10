@echo off
title AICode Executor Service (Port 3001)
cd /d "%~dp0services\executor"

if not exist node_modules (
    echo [INFO] Menginstal dependensi node_modules...
    call npm install
)

echo ========================================================
echo   AICode Executor Service
echo   Berjalan di: http://127.0.0.1:3001
echo   Mode: DEV (vm2 sandbox tanpa perlu Docker)
echo ========================================================
echo.

set EXECUTOR_MODE=dev
node server.js
pause
