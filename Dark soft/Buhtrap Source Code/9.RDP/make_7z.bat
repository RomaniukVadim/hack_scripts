::--------------------------------------
::1.Create encrypt container 7z archive.
::--------------------------------------

del /q relase\rut_rdp.exe
@"%PROGRAMFILES%\7-Zip\7z.exe" a "tmpr\tmpr.7z" -p333cb962ac59075b907152d234b70111 -ssw -aoa -mx=10 -ms=on -mhe "tmpr3" "tmpr4" "tmpr5" "tmpr6" "tmpr7"
del /q tmpr\tmpr1
ren "tmpr\tmpr.7z" "tmpr1"
pause

