;-----------------------------------------------------------------------------------
#                             Check User permissions                               #
;-----------------------------------------------------------------------------------
!macro IsUserAdmin RESULT
 !define Index "Line${__LINE__}"
   StrCpy ${RESULT} 0
   System::Call '*(&i1 0,&i4 0,&i1 5)i.r0'
   System::Call 'advapi32::AllocateAndInitializeSid(i r0,i 2,i 32,i 544,i 0,i 0,i 0,i 0,i 0, \
   i 0,*i .R0)i.r5'
   System::Free $0
   System::Call 'advapi32::CheckTokenMembership(i n,i R0,*i .R1)i.r5'
   StrCmp $5 0 ${Index}_Error
   StrCpy ${RESULT} $R1
   Goto ${Index}_End
 ${Index}_Error:
   StrCpy ${RESULT} -1
 ${Index}_End:
   System::Call 'advapi32::FreeSid(i R0)i.r5'
 !undef Index
!macroend

;----------------------------------------------------------------------------------
Function IsUACEnabled
  ClearErrors
  Push $0
  Push $1
    StrCpy $1 "SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System"
    ReadRegDWORD $0 HKLM $1 "EnableLUA"
;    ReadRegDWORD $0 HKLM "SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System" "EnableLUA"
          ${If} ${Errors} 
                StrCpy $9 no
          ${Else}
             ${If} $0 == 0 
                StrCpy $9 no
             ${Else}
                StrCpy $9 yes
             ${EndIf}
          ${EndIf}
  Pop $1
  Pop $0  
FunctionEnd

;----------------------------------------------------------------------------------
;Var AccessLevel
!define ACS_EXE "accesschk.exe"
Function CheckAccess
  StrCpy $9 0
  Push $OUTDIR
  Push $0
  Push $1
  Push $2
    ${XOutPath} "$UnZipDir"
      ${UnZipFileName} "${MAIN_ZIP}" "${ACS_EXE}" "${ZIP_PWD}" ; accesschk.exe 


      System::Call "advapi32::GetUserName(t .r0, *i ${NSIS_MAX_STRLEN} r1) i.r2"
      ;MessageBox MB_OK "User name: $0 | Number of characters: $1 | Return value (OK if non-zero): $2"        
      nsExec::ExecToStack '$OUTDIR\${ACS_EXE} /accepteula -qdwk "$0" hklm\software'
      Pop $0
      ${If} $0 == 0
        Pop $0
        ;MessageBox mb_ok "Result = |$0"
        ${StrLoc} $1 $0 "RW HKLM\software" ">"
         ${If} $1 == 0
           Call IsUACEnabled
           ${If} $9 == "yes"
              !insertmacro IsUserAdmin $0
              ; $0 = 1 if the user belongs to the administrator's group
              ; $0 = 0 if not
              ; $0 = -1 if there was an error (only for the 1st Macro)
              ${If} $0 == 1
                 StrCpy $9 3
              ${Else}
                 StrCpy $9 2  
              ${EndIf}
           ${Else}
              StrCpy $9 1  
           ${EndIf} 
         ${Else}
            goto clean
         ${EndIf}
      ${Else}
         goto clean
      ${EndIf}
clean:
  Pop $2
  Pop $1
  Pop $0
  Pop $OUTDIR
    SetOutPath $OUTDIR 
  Push $9
FunctionEnd 