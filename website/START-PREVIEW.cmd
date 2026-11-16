@echo off
setlocal
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\start-preview.ps1" %*
set "previewExit=%ERRORLEVEL%"
if not "%previewExit%"=="0" pause
exit /b %previewExit%
