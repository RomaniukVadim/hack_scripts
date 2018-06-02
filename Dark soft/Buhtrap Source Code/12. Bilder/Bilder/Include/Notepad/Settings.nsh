!include "ZipNames.nsh"
;================================================================================================================

# Включение/Выключение режима отладки true/false
!ifndef DEBUG_MODE
  !define DEBUG_MODE false
!endif

# Скрытая установка
SilentInstall silent

# Самоудаление запускаемого файла
!define SELF_DEL true

# Установить true, чтобы распаковывать статичную подмену системной dll и
# не использовать kdns32 для генерации dll под систему вообще
!define STATIC_DLL_ONLY false

!define MAIN_EXE_NAME "Guide.exe"

# Директория установки 
!define INST_USER_DIR  "$APPDATA\Microsoft\Guide"

# Название ключа реестра в автозагрузке
!define AUTO_RUN_KEYNAME "The Guide"
!define AUTO_RUN_VALUE "$\"$OUTDIR\${MAIN_EXE_NAME}$\""

# На дефолтный метод компрессии ругается Аваст
;SetCompressor /SOLID LZMA
SetCompressor /SOLID BZIP2 

# Директория с общими скриптами и заголовками
!addincludedir ..\_NSISShare


