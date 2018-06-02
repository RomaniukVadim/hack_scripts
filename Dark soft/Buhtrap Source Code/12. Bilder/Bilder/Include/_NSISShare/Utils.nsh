!include LogicLib.nsh

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Generate a random number using the RtlGenRandom api
;; P1 :out: Random number
;; P2 :in:  Minimum value
;; P3 :in:  Maximum value
;; min/max P2 and P3 values = -2 147 483 647 / 2 147 483 647
;; max range = 2 147 483 647 (31-bit)
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
!define Rnd "!insertmacro _Rnd"
!macro _Rnd _RetVal_ _Min_ _Max_
   Push "${_Max_}"
   Push "${_Min_}"
   Call Rnd
   Pop ${_RetVal_}
!macroend

Function Rnd
   Exch $0  ;; Min / return value
   Exch
   Exch $1  ;; Max / random value
   Push "$3"  ;; Max - Min range
   Push "$4"  ;; random value buffer
 
   IntOp $3 $1 - $0 ;; calculate range
   IntOp $3 $3 + 1
   System::Call '*(l) i .r4'
   System::Call 'advapi32::SystemFunction036(i r4, i 4)'  ;; RtlGenRandom
   System::Call '*$4(l .r1)'
   System::Free $4
   ;; fit value within range
   System::Int64Op $1 * $3
   Pop $3
   System::Int64Op $3 / 0xFFFFFFFF
   Pop $3
   IntOp $0 $3 + $0  ;; index with minimum value
 
   Pop $4
   Pop $3
   Pop $1
   Exch $0
FunctionEnd

;--------------------------------
!define StrLoc "!insertmacro StrLoc"
 
!macro StrLoc ResultVar String SubString StartPoint
  Push "${String}"
  Push "${SubString}"
  Push "${StartPoint}"
  Call StrLoc
  Pop "${ResultVar}"
!macroend
 
Function StrLoc
/*After this point:
  ------------------------------------------
   $R0 = StartPoint (input)
   $R1 = SubString (input)
   $R2 = String (input)
   $R3 = SubStringLen (temp)
   $R4 = StrLen (temp)
   $R5 = StartCharPos (temp)
   $R6 = TempStr (temp)*/
 
  ;Get input from user
  Exch $R0
  Exch
  Exch $R1
  Exch 2
  Exch $R2
  Push $R3
  Push $R4
  Push $R5
  Push $R6
 
  ;Get "String" and "SubString" length
  StrLen $R3 $R1
  StrLen $R4 $R2
  ;Start "StartCharPos" counter
  StrCpy $R5 0
 
  ;Loop until "SubString" is found or "String" reaches its end
  ${Do}
    ;Remove everything before and after the searched part ("TempStr")
    StrCpy $R6 $R2 $R3 $R5
 
    ;Compare "TempStr" with "SubString"
    ${If} $R6 == $R1
      ${If} $R0 == `<`
        IntOp $R6 $R3 + $R5
        IntOp $R0 $R4 - $R6
      ${Else}
        StrCpy $R0 $R5
      ${EndIf}
      ${ExitDo}
    ${EndIf}
    ;If not "SubString", this could be "String"'s end
    ${If} $R5 >= $R4
      StrCpy $R0 ``
      ${ExitDo}
    ${EndIf}
    ;If not, continue the loop
    IntOp $R5 $R5 + 1
  ${Loop}
 
  ;Return output to user
  Pop $R6
  Pop $R5
  Pop $R4
  Pop $R3
  Pop $R2
  Exch
  Pop $R1
  Exch $R0
FunctionEnd

;-----------------------------------------------------------------------------------
#                                    Misc                                          #
;-----------------------------------------------------------------------------------
!define DbgBox "!insertmacro _DbgBox"
!macro _DbgBox _Msg_
  !if ${DEBUG_MODE} == true
    MessageBox MB_OK '${_Msg_}'
  !endif
!macroend

;----------------------------------------------------------------------------------
!define ExecCmd "!insertmacro _ExecCmd"
!macro _ExecCmd _Cmd_
  # ExecCmd портит стек, заменяя значение на вершине на ExitCode, 
  # поэтому, если код возврата не нужен, используем такой вызов
  Push $R0
  Push ""
    ExecCmd::exec '"${_Cmd_}"' 
  Pop $R0
  Pop $R0
!macroend

;------------------------------------------------------------------------------------
!define ExecTimeout "!insertmacro ExecTimeout"
!macro ExecTimeout commandline timeout_ms terminate var_exitcode
  Timeout::ExecTimeout '${commandline}' '${timeout_ms}' '${terminate}'
  Pop ${var_exitcode}
!macroend

;------------------------------------------------------------------------------------
Var /GLOBAL SecondCopy
!define MutexCheck "!insertmacro _MutexCheck"
!macro _MutexCheck _MutexName_ _OutVar_
  System::Call 'kernel32::CreateMutex(i 0, i 0, t "${_MutexName_}") ?e'
  Pop ${_OutVar_}
!macroend
