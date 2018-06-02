cd Build
SET PRJ=MpLoader
SET RESHACK=E:\Programs\RE\ResHacker\ResHacker.exe 

rem Удаляем палево

call %RESHACK% -delete %PRJ%.exe, %PRJ%.exe, RCData, PACKAGEINFO,
call %RESHACK% -delete %PRJ%.exe, %PRJ%.exe, RCData, DVCLAL,

                                  
copy /Y %PRJ%.exe E:\VMachines\Shared\Monitor\Ldr\%PRJ%.exe
