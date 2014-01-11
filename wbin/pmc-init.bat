@echo off
cd /d %~dp0
cd ..
cls

echo    ___  __  ________       _                  _     __        __           __
echo   / _ \/  ^|/  / ___/ _  __(_)__    ___ ___ __(_)___/ /__ ___ / /____ _____/ /_
echo  / ___/ /^|_/ / /__  ^| ^|/ / / _ \  / _ `/ // / / __/  '_/(_-^</ __/ _ `/ __/ __/
echo /_/  /_/  /_/\___/  ^|___/_/ .__/  \_, /\_,_/_/\__/_/\_\/___/\__/\_,_/_/  \__/ 
echo                          /_/       /_/                                        
echo.

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
powershell -command "Set-ExecutionPolicy RemoteSigned"
powershell -command ".\wbin\pmc-init.ps1"
powershell -command ".\wbin\vip-init.ps1"
