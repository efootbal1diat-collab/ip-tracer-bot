@echo off
title IP Tracer - Sinkronisasi Data ke GitHub
cls
echo ========================================================
echo   Memperbarui Data IP (Excel + Scan) & Sync ke GitHub...
echo ========================================================
echo.
php artisan ip:sync-github
echo.
echo ========================================================
echo   Selesai! Data terbaru sudah tersimpan.
echo ========================================================
pause
