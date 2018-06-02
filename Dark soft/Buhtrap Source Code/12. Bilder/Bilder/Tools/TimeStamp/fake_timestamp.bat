@echo off

echo Post-parsing target %1
pushd
cd /d %~dp0
php -n -f fake_timestamps.php %1
popd
if NOT ERRORLEVEL 0 (
	echo ERR: fake_timestamps.php parse failure
	pause
	exit
)