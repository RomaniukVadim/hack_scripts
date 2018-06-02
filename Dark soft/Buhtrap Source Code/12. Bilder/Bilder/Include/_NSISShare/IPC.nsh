!define IPC_TXT $TEMP\11223344.txt
;!define L2_EXE "l2.exe"
;!define L1_EXE "l1.exe"
;!define M32_EXE "m32.exe"
;!define M64_EXE "m64.exe"
;!define S2_EXE "s2.exe"
;!define N32_EXE "s3x86.exe"
;!define N64_EXE "s3x64.exe"
;!define A32_EXE "s3a86.exe"
;!define A64_EXE "s3a64.exe"
;!define H1_DLL "h1.dll"
;!define H2_DLL "h2.dll"

;!define N32_EXE "s4x86.exe"
;!define N64_EXE "s4x64.exe"


Function DelIPCFiles
    Delete "${IPC_TXT}"
    Delete "${IPC_TXT}1"
FunctionEnd
;--------------------------------

Function WaitIPC
  ; ∆дем, пока запуститс€ и начнет работать процесс антиUAC
  IfFileExists "${IPC_TXT}" 0 +3
    Sleep 2000
  goto -2
FunctionEnd
;--------------------------------

/*
!define IPC_TIME_OUT 60
Function WaitIPC_TimeOut
  Push $0
loop:
    IfFileExists "${IPC_TXT}" 0 exit
      Sleep 1000
      IntOp $0 $0 + 1
      IntCmpU $0 ${IPC_TIME_OUT} exit 0 exit
    Goto loop
exit:
  Pop $0
FunctionEnd
;--------------------------------

Function Wait_L2
  Push $R0
   # ∆дем, когда отработает лаунчер
    Sleep 2000
    ${nsProcess::FindProcess} "${L2_EXE}" $R0
      ${If} $R0 == 0 
        goto -3
      ${EndIf}
  Pop $R0
FunctionEnd
;--------------------------------
*/
