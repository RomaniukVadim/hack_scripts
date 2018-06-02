cd Build
SET PRJ=SetupLib
SET RESHACK=E:\Programs\RE\ResHacker\ResHacker.exe 

rem Удаляем палево

call %RESHACK% -delete %PRJ%.dll, %PRJ%.dll, RCData, PACKAGEINFO,
call %RESHACK% -delete %PRJ%.dll, %PRJ%.dll, RCData, DVCLAL,
call sfk replace -case %PRJ%.dll /SetupLib.dll/............/ -yes

                                  
copy /Y %PRJ%.dll ..\..\Loader\Build\1.dll
copy /Y %PRJ%.dll E:\VMachines\Shared\Monitor\Ldr\1.dll
