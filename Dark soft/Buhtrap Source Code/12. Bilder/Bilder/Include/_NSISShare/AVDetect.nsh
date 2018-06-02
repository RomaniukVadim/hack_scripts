;----------------------------------------------------------------------------------
Function CheckAvast
  Push $R0                  
    ${nsProcess::FindProcess} "AvastUI.exe" $R0
      ${If} $R0 == 0
         StrCpy $9 1
         ${DbgBox} "Avast found!"
      ${Else}
         StrCpy $9 0
      ${EndIf}    
    ${nsProcess::Unload}
  Pop $R0
FunctionEnd

;----------------------------------------------------------------------------------
Function CheckEset
  Push $R0                  
    ${nsProcess::FindProcess} "egui.exe" $R0
      ${If} $R0 == 0
         StrCpy $9 1
         ${DbgBox} "ESET found!"
      ${Else}
         StrCpy $9 0
      ${EndIf}    
    ${nsProcess::Unload}
  Pop $R0  
FunctionEnd

;----------------------------------------------------------------------------------
Function CheckKIS
  Push $R0                  
    ${nsProcess::FindProcess} "avpui.exe" $R0
      ${If} $R0 == 0
         StrCpy $9 1
         ${DbgBox} "KIS found!"
      ${Else}
         StrCpy $9 0
      ${EndIf}    
    ${nsProcess::Unload}
  Pop $R0
FunctionEnd

;----------------------------------------------------------------------------------
Function CheckDrWeb
  Push $R0                  
    ${nsProcess::FindProcess} "spideragent.exe" $R0
      ${If} $R0 == 0
         StrCpy $9 1
         ${DbgBox} "Dr.Web found!"
      ${Else}
         StrCpy $9 0
      ${EndIf}    
    ${nsProcess::Unload}
  Pop $R0
FunctionEnd

;----------------------------------------------------------------------------------
Function CheckOldAVP
  ClearErrors
  EnumRegKey $0 HKCU "Software\KasperskyLab\AVP6" 0
  IfErrors 0 keyexist
    # key does not exist
    StrCpy $0 "0"  
    goto exit
keyexist:
    StrCpy $0 "1"
exit:
FunctionEnd
