@echo off
setlocal

set "POS_URL=http://panel.com.local/ventas/pos"
set "CHROME_X64=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
set "CHROME_X86=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
set "EDGE_X64=%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe"
set "EDGE_X86=%ProgramFiles%\Microsoft\Edge\Application\msedge.exe"

if exist "%CHROME_X64%" (
  start "" "%CHROME_X64%" --kiosk-printing --app="%POS_URL%"
  exit /b 0
)

if exist "%CHROME_X86%" (
  start "" "%CHROME_X86%" --kiosk-printing --app="%POS_URL%"
  exit /b 0
)

if exist "%EDGE_X64%" (
  start "" "%EDGE_X64%" --kiosk-printing --app="%POS_URL%"
  exit /b 0
)

if exist "%EDGE_X86%" (
  start "" "%EDGE_X86%" --kiosk-printing --app="%POS_URL%"
  exit /b 0
)

echo No se encontro Chrome ni Edge instalado en rutas comunes.
echo Instala Google Chrome o Microsoft Edge y vuelve a ejecutar este archivo.
pause
exit /b 1
