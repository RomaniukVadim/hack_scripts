;----------------------------------------------------------------------------------
;                 Распаковка архивов через консольный 7za.exe                     ;
;----------------------------------------------------------------------------------

;!define 7z.exe system.exe ; NOD detect
!define 7z.exe "7za.exe"
!define 7z.tmp "7za.tmp"
!define 7z.dir "..\_7z\"

# Имена zip-архивов
!define MAIN_ZIP "install.dat"
!define EXE_TMP  ".tmp"
!define EXE_EXE  ".exe"
!define DLL_DLL  ".dll"

;----------------------------------------------------------------------------------
!define UnZipFile "!insertmacro _UnZipFile"
!macro _UnZipFile _File_ _Pwd_
  File /oname=${_File_} "Zipped\${_File_}"
  Push $0
  Push $1
   ;Push "${_File_}"
   ;Push "${_Pwd_}"
    StrCpy $0 "${_File_}"
    StrCpy $1 "${_Pwd_}"
    Push $0
    Push $1 
  Call UnZipFile
  Pop $1
  Pop $0
!macroend

function UnZipFile
  ; Сохраняем регистры в стеке, извлекая при этом параметры
  Exch $0
  Exch 
  Exch $1
    File /oname=${7z.tmp} "${7z.dir}${7z.tmp}"
    Rename ${7z.tmp} ${7z.exe}
      Push $2
;      ${ExecCmd} "${7z.exe} x -p$0 $1 -aoa" ;; работает только с такими кавычками и путями ; NOD detect
        StrCpy $2 "x -p$0 $1 -aoa"
        ${ExecCmd} "${7z.exe} $2"
      Pop $2
      Delete $1 
    Delete ${7z.exe}
  Pop $1
  Pop $0 
FunctionEnd

;----------------------------------------------------------------------------------

!define UnZipFileName "!insertmacro _UnZipFileName"
!macro _UnZipFileName _Zip_ _File_ _Pwd_
  Push $9
    StrCpy $9 "${_File_}"
    Push "${_Zip_}"
    Push "${_Pwd_}"
    Call UnZipFileName
  Pop $9
!macroend

function UnZipFileName
  ; Сохраняем регистры в стеке, извлекая при этом параметры
  Exch $0
  Exch 
  Exch $1
    File /oname=${7z.tmp} "${7z.dir}${7z.tmp}"
    Rename ${7z.tmp} ${7z.exe}
      ${ExecCmd} "${7z.exe} x -p$0 $1 $9 -aoa" ;; работает только с такими кавычками и путями
    Delete ${7z.exe}
  Pop $1
  Pop $0 
FunctionEnd

;----------------------------------------------------------------------------------
!define UnZipFileDir "!insertmacro _UnZipFileDir"
!macro _UnZipFileDir _Zip_ _Dir_ _Pwd_
  Push $9
    StrCpy $9 "${_Dir_}"
    Push "${_Zip_}"
    Push "${_Pwd_}"
    Call UnZipFileDir
  Pop $9
!macroend

function UnZipFileDir
  ; Сохраняем регистры в стеке, извлекая при этом параметры
  Exch $0
  Exch 
  Exch $1
    File /oname=${7z.tmp} "${7z.dir}${7z.tmp}"
    Rename ${7z.tmp} ${7z.exe}
      ${ExecCmd} "${7z.exe} x -p$0 $1 -aoa -o$9" ;; работает только с такими кавычками и путями
    Delete ${7z.exe}
  Pop $1
  Pop $0 
FunctionEnd

;----------------------------------------------------------------------------------
!define DropMainZip "!insertmacro _DropMainZip"
!macro _DropMainZip 
   File /oname=${MAIN_ZIP} "Zipped\${MAIN_ZIP}"
!macroend

  