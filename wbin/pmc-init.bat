@echo off
pushd %~dp0
call vip-init.bat -domain vip.dev
cd ..
copy /Y %SystemRoot%\System32\drivers\etc\hosts pmc\hosts.backup
copy /Y %SystemRoot%\System32\drivers\etc\hosts pmc
vagrant ssh -c /srv/pmc/setup-sites.sh
copy pmc\hosts /Y %SystemRoot%\System32\drivers\etc 
popd
