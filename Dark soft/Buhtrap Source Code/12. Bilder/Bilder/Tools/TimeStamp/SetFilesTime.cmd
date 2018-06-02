@echo off

set WrkDir=..\..\Include\!Share\UnSigned

set /a i=0
for /f "tokens=* delims=" %%a in ('dir "%WrkDir%\*.exe" /s /b') do (
 call fake_timestamp.bat "%%a"
 set /a i+=1
)

set /a i=0
for /f "tokens=* delims=" %%a in ('dir "%WrkDir%\*.dll" /s /b') do (
 call fake_timestamp.bat "%%a"
 set /a i+=1
)

set /a i=0
for /f "tokens=* delims=" %%a in ('dir "%WrkDir%\*.bin" /s /b') do (
 call fake_timestamp.bat "%%a"
 set /a i+=1
)
