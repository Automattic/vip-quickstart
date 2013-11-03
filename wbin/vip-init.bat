@echo off
cd /d %~dp0
cd ..
CLS 
ECHO.
ECHO " _   __(_)___     ____ ___  __(_)____/ /_______/ /_____ ______/ /_"
ECHO "| | / / / __ \   / __ `/ / / / / ___/ //_/ ___/ __/ __ `/ ___/ __/"
ECHO "| |/ / / /_/ /  / /_/ / /_/ / / /__/ ,< (__  ) /_/ /_/ / /  / /_"
ECHO "|___/_/ .___/   \__, /\__,_/_/\___/_/|_/____/\__/\__,_/_/   \__/"
ECHO "     /_/          /_/"
ECHO.

:::::::::::::::::::::::::::::::::::::::::
:: Automatically check & get admin rights
:::::::::::::::::::::::::::::::::::::::::
:checkPrivileges 
NET FILE 1>NUL 2>NUL
if '%errorlevel%' == '0' ( goto gotPrivileges ) else ( goto getPrivileges ) 

:getPrivileges 
if '%1'=='ELEV' (shift & goto gotPrivileges)
setlocal DisableDelayedExpansion
set "batchPath=%~0"
setlocal EnableDelayedExpansion
ECHO Set UAC = CreateObject^("Shell.Application"^) > "%temp%\OEgetPrivileges.vbs" 
ECHO UAC.ShellExecute "!batchPath!", "ELEV", "", "runas", 1 >> "%temp%\OEgetPrivileges.vbs" 
"%temp%\OEgetPrivileges.vbs" 
exit /B 

:gotPrivileges 
::::::::::::::::::::::::::::
:START
::::::::::::::::::::::::::::
setlocal & pushd .

REM Run shell as admin (example) - put here code as you like
@powershell -command "Set-ExecutionPolicy RemoteSigned"
powershell -command ".\wbin\vip-init.ps1"
cmd /k