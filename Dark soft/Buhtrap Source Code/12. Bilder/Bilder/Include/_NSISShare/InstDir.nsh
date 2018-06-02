# Создаем собственную директорию для распаковки файлов со случайным именем и удаляем ее после окончания установки
Function .onInit
  ${GenUnZipDir} $UnZipDir
  CreateDirectory $UnZipDir
  SetOutPath $UnZipDir

  ;${DbgBox} "UnZipDir: $UnZipDir"
FunctionEnd

Function .onInstSuccess
  RMDir /r $UnZipDir
  ${SelfDel} "$EXEPATH" "$UnZipDir"
FunctionEnd

Function .onInstFailed
  RMDir /r $UnZipDir
  ${SelfDel} "$EXEPATH" "$UnZipDir"
FunctionEnd

Function .onUserAbort
  RMDir /r $UnZipDir
  ${SelfDel} "$EXEPATH" "$UnZipDir"
FunctionEnd           