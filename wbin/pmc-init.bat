@echo off
pushd %~dp0
vip-init.bat -domain vip.dev
cd ..
vagrant ssh -c /srv/pmc/setup-sites.sh
popd
