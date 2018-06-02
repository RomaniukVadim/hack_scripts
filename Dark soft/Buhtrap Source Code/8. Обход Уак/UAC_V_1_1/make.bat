
if exist e.exe del e.exe

C:\masm32\bin\ml /c /Cp /Gz /coff /nologo e.asm
C:\masm32\bin\link /SUBSYSTEM:WINDOWS /merge:.data=.text /merge:.rdata=.text /NOLOGO /FIXED /SECTION:.text,RWE /OPT:REF e.obj

rem C:\masm32\bin\ml /c /Cp /Gz /coff /nologo e.asm
rem C:\masm32\bin\link /SUBSYSTEM:WINDOWS /NOLOGO /RELEASE /SECTION:.text,RWE /OPT:REF e.obj

del e.obj
:@@
pause

exit

