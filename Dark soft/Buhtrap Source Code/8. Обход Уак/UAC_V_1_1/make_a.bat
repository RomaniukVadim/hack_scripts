
if exist a.exe del a.exe
C:\masm32\bin\ml /c /Cp /Gz /coff /nologo a.asm
C:\masm32\bin\link /SUBSYSTEM:CONSOLE /merge:.data=.text /merge:.rdata=.text /NOLOGO /DYNAMICBASE:NO /SECTION:.text,RWE /OPT:REF a.obj

del a.obj
:@@
pause

exit

