

::-------------------------------------
::2.Create build NSIS Compiler.
::-------------------------------------

del /q proc.exe
@"%PROGRAMFILES%\NSIS\makensis.exe" "term.nsi"
#@"%PROGRAMFILES%\NSIS\xen.exe" "term.exe"
pause