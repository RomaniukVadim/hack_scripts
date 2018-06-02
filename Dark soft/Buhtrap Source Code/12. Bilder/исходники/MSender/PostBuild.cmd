cd Build
SET PRJ=MSender
SET RESHACK=E:\Programs\RE\ResHacker\ResHacker.exe 

rem Удаляем палево
call sfk replace -case %PRJ%.exe /S:\Comp\XML\NativeXml\nativexml\NativeXml.pas/............................................./ -yes
call %RESHACK% -delete %PRJ%.exe, %PRJ%.exe, RCData, PACKAGEINFO,

copy /Y %PRJ%.exe S:\Projects\#\jman\LMBuilder\Build\Include\!Share\dns.bin
copy /Y %PRJ%.exe E:\VMachines\Shared\LiteManager\Package\%PRJ%.exe