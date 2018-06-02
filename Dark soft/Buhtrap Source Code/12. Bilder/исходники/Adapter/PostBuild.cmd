cd Build
SET PRJ=Adapter
SET RESHACK=E:\Programs\RE\ResHacker\ResHacker.exe 

rem Удаляем палево
call sfk replace -case %PRJ%.exe /S:\Comp\XML\NativeXml\nativexml\NativeXml.pas/............................................./ -yes
call %RESHACK% -delete %PRJ%.exe, %PRJ%.exe, RCData, PACKAGEINFO,
rem call %RESHACK% -delete %PRJ%.exe, %PRJ%.exe, RCData, DVCLAL,

rem copy /Y %PRJ%.exe S:\Projects\#\jman\LMBuilder\Build\Include\!Share\Unsigned\apr.bin
copy /Y %PRJ%.exe S:\Projects\#\jman\LMBuilder2\Build\Include\!Share\UnSigned\apr.bin
copy /Y %PRJ%.exe X:\apr.bin
rem copy /Y %PRJ%.exe E:\VMachines\Shared\LiteManager\Package\%PRJ%.exe