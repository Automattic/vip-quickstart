@echo off
pushd %~dp0\..
vagrant up %1
copy /Y %SystemRoot%\System32\drivers\etc\hosts pmc\hosts.backup > nul
copy /Y %SystemRoot%\System32\drivers\etc\hosts pmc > nul
vagrant ssh -c /srv/pmc/setup-sites.sh -- -t
copy pmc\hosts /Y %SystemRoot%\System32\drivers\etc  > nul
popd
