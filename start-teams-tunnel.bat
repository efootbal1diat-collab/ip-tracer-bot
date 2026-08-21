@echo off
title Cloudflare Tunnel for Microsoft Teams & Remote Web
cls
echo ========================================================
echo   Starting Cloudflare Tunnel (HTTPS Public Bridge)...
echo ========================================================
echo.
echo Salin link HTTPS (trycloudflare.com) yang muncul di bawah:
echo.
.\cloudflared.exe tunnel --protocol http2 --url http://localhost
pause
