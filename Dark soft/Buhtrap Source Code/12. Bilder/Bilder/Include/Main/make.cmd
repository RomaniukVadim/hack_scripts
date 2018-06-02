cls

set CurDir=%~dp0
set PACK=pack1
set Zip=..\_7z\system.exe
set ToolsDir=..\_Tools
set ZipDir=Zipped
set ShrDir=..\!Share
set MainZipName=%ZipDir%\install.dat
set RootOut=..\..\Package

call :Clean

rem Читаем из конфига путь к компилятору NSIS
set NSIS_PATH=..\_NSISShare\nsis.path.txt
call :ReadNsisPath

rem Пароль на все архивы
call %ToolsDir%\PwdGen.exe > pwd.txt
set /p ZipPwd=<pwd.txt

@rem ---------------------------------------------------------------------------
%ToolsDir%\PwdSet.exe "ZipNames.org.nsh" "ZipNames.nsh" !dummy! "dummy"

rem Антинод
set APack=and1.~exe
set APackName=!antinod!
call :AppendPacket

rem Лайт
set APack=lmpack1.~exe
set APackName=!liteman!
call :AppendPacket

rem Блокнот
set APack=ntp1.~exe
set APackName=!notepad!
call :AppendPacket

rem PsKill
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% %ToolsDir%\pskill.exe 
@rem ---------------------------------------------------------------------------
del pwd.txt
del ZipName.txt
del DllName.txt

copy /y Install.org.nsi Install.nsi
rem Модифицируем скрипт установки
%ToolsDir%\PwdSet.exe "Install.org.nsi" "Install.nsi" %%password%% %ZipPwd%

call %COMP% Install.nsi
del Install.nsi

copy /y %PACK%.~exe %RootOut%\%PACK%.~exe
if exist X:\ copy /y %PACK%.~exe X:\%PACK%.~exe
del /Q Packets\*.*

goto end

:Clean
del %PACK%.exe
del %PACK%.~exe
del /Q Zipped\*.*
exit /b

:ReadNsisPath
@for /f "tokens=*" %%a in (%NSIS_PATH%) do @(
  @set COMP=%%a
  @goto cont
)
:cont
  @if not exist %COMP% (
     @echo.
     @echo.
     @echo NSIS Compiler not found!
     @exit
   )
  @exit /b
)

:MakeZipName
call %ToolsDir%\PwdGen.exe -n > ZipName.txt
set /p ZipName=<ZipName.txt
set ZipName=dev%ZipName%
@exit /b

:MakeDllName
call %ToolsDir%\PwdGen.exe -d > DllName.txt
set /p DllName=<DllName.txt
@exit /b

:AppendPacket
call :MakeZipName
%ToolsDir%\PwdSet.exe "ZipNames.nsh" "ZipNames.nsh" %APackName% %ZipName%
copy /y %RootOut%\%APack% Packets\%ZipName%.tmp
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% .\Packets\%ZipName%.tmp
@exit /b

:end