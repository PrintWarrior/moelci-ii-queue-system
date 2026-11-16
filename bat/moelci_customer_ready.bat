
@echo off
title MOELCI-II Queue System Launcher

:: Launch Brave in Private Window
start "" "brave.exe" --incognito --new-window "http://localhost/moelci-ii-queue-system/"

:: Wait for the page to load
timeout /t 8 /nobreak >nul

:: Automatically enter login credentials
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
"$ws = New-Object -ComObject WScript.Shell; ^
$ws.AppActivate('Brave'); ^
Start-Sleep -Seconds 2; ^
$ws.SendKeys('Customer'); ^
Start-Sleep -Milliseconds 500; ^
$ws.SendKeys('{TAB}'); ^
$ws.SendKeys('customer123'); ^
Start-Sleep -Milliseconds 500; ^
$ws.SendKeys('{TAB}'); ^
$ws.SendKeys('{TAB}'); ^
$ws.SendKeys('{ENTER}')"

exit
