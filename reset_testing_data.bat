@echo off
title Reset Data Siswa untuk Testing
cd /d "%~dp0"

echo ===================================================================
echo   RESET DATA PENGUJIAN SISWA (MOODLE / AICODE / ACMLS)
echo ===================================================================
echo.
echo Pilih opsi reset:
echo   1. Reset Course 22 (JavaScript Fundamental) - Direkomendasikan
echo   2. Reset SEMUA Course
echo   3. Batal
echo.

set /p choice="Masukkan pilihan (1/2/3): "

if "%choice%"=="1" (
    echo.
    echo Menjalankan reset untuk Course 22...
    php cli_reset_student_data.php --courseid=22 --confirm
) else if "%choice%"=="2" (
    echo.
    echo Menjalankan reset untuk SEMUA Course...
    php cli_reset_student_data.php --all --confirm
) else (
    echo.
    echo Dibatalkan.
)

echo.
pause
