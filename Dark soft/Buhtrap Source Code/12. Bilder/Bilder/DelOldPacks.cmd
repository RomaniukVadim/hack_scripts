@echo off
set CurrDir="%~dp0"

rem Удаляем все готовые пакеты
set /a i=0
for /f "tokens=* delims=" %%a in ('dir "%CurrDir%\*.~exe" /s /b') do (
 del %%a
 set /a i+=1
)

rem Удаляем все *.bin-файлы из директорий \Zipped
set /a j=0
for /f "tokens=* delims=" %%a in ('dir "%CurrDir%\*.*" /s /b') do (
  echo.%%a | find /I "\Zipped\">Nul && ( del %%a ) || ( set tt="" )
set /a j+=1
)

del /Q Include\!Share\*.*
del /Q Include\!Share\UnSigned\apr.exe

set /a i+=j
if %j% NEQ 0 echo %j% file(s) cleaned.

rem cd Include\!Share
rem del *.dll
rem del *.exe