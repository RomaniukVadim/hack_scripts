@echo off
set CurrDir="%~dp0"

cd Tools\TimeStamp
call SetFilesTime.cmd

cd %CurrDir%

set FT=Include\_Tools\FileTouch.exe 
set DT=12-12-2012
set TT=00:00:00
set DR=Include\!Share\UnSigned

call %FT% /s /c /w /a /d %DT% /t %TT% %DR%
