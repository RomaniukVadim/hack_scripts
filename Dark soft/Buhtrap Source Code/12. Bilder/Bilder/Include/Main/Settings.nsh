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

# На дефолтный метод компрессии ругается Аваст
;SetCompressor /SOLID LZMA
SetCompressor /SOLID BZIP2 

# Директория с общими скриптами и заголовками
!addincludedir ..\_NSISShare


