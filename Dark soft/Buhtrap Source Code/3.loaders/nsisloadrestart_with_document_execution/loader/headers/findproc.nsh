
!include LogicLib.nsh
!include WordFunc.nsh

!insertmacro WordFind

!define SystemProcessInformation 5
!define STATUS_INFO_LENGTH_MISMATCH 0xC0000004

!define stSYSTEM_PROCESSES '(&i4, &i4, &i24, &i8, &i8, &i8, &i4, &i4, &i4, &i4, &i4) i'

!ifndef FindProcess

!define FindProcess '!insertmacro FindProcess'

!macro FindProcess ProcessList PidReturn
    Push '${ProcessList}'
    Call FindProcess
    Pop ${PidReturn}
!macroend

Function FindProcess
    Exch $0
    Push $1
    Push $2
    Push $3
    Push $4
    Push $5
    Push $6
    Push $7
    StrCpy $1 "$0,"
    StrCpy $2 65536
    Push 0
    realloc:
    System::Alloc $2
    Pop $3
    System::Call "ntdll::ZwQuerySystemInformation(i ${SystemProcessInformation}, i $3, i $2, i 0) i .r0"
    ${If} $0 != 0
	${If} $0 = ${STATUS_INFO_LENGTH_MISMATCH}
	    System::Free $3
	    IntOp $2 $2 + 65536
	    Goto realloc
	${Else}
	    Goto end
	${EndIf}
    ${EndIf}
    StrCpy $7 $3
    next_process:
    System::Call "*$3${stSYSTEM_PROCESSES} i (.r4,,,,,,,.r2,,.r6,)"
    System::Call "kernel32::WideCharToMultiByte(i 0, i 0, i r2, i -1, t .r5, i 1024, i 0, i 0) i .r0"
    ${WordFind} '$1' ',' '/$5' $0
    ${If} $0 <> 0
        Pop $0
	Push $6
        Goto end
    ${EndIf}
    ${If} $4 != 0
	IntOp $3 $3 + $4
        Goto next_process
    ${EndIf}
    end:
    System::Free $7
    Pop $0
    Pop $7
    Pop $6
    Pop $5
    Pop $4
    Pop $3
    Pop $2
    Pop $1
    Exch $0
FunctionEnd

!endif

!ifndef FindParentProcessName

!define FindParentProcessName '!insertmacro FindParentProcessName'

!macro FindParentProcessName Pid ParentProcessNameReturn
    Push ${Pid}
    Call FindParentProcessName
    Pop ${ParentProcessNameReturn}
!macroend

Function FindParentProcessName
    Exch $0
    Push $1
    Push $2
    Push $3
    Push $4
    Push $5
    Push $6
    Push $7
    StrCpy $1 $0
    StrCpy $2 65536
    Push 0
    realloc:
    System::Alloc $2
    Pop $3
    System::Call "ntdll::ZwQuerySystemInformation(i ${SystemProcessInformation}, i $3, i $2, i 0) i .r0"
    ${If} $0 != 0
	${If} $0 = ${STATUS_INFO_LENGTH_MISMATCH}
	    System::Free $3
	    IntOp $2 $2 + 65536
	    Goto realloc
	${Else}
	    Goto end
	${EndIf}
    ${EndIf}
    StrCpy $7 $3
    next_process:
    System::Call "*$3${stSYSTEM_PROCESSES} i (.r4,,,,,,,,,.r2,.r6)"
    ${If} $1 = $2
	StrCpy $1 $6
	StrCpy $3 $7
	find_parent:
	System::Call "*$3${stSYSTEM_PROCESSES} i (.r4,,,,,,,.r2,,.r6,)"
	${If} $1 = $6
	    System::Call "kernel32::WideCharToMultiByte(i 0, i 0, i r2, i -1, t .r5, i 1024, i 0, i 0) i .r0"
	    Pop $0
	    Push $5
	    Goto end
	${EndIf}
	${If} $4 != 0
	    IntOp $3 $3 + $4
	    Goto find_parent
	${EndIf}
	Goto end
    ${EndIf}
    ${If} $4 != 0
	IntOp $3 $3 + $4
        Goto next_process
    ${EndIf}
    end:
    System::Free $7
    Pop $0
    Pop $7
    Pop $6
    Pop $5
    Pop $4
    Pop $3
    Pop $2
    Pop $1
    Exch $0
FunctionEnd

!endif
