;----------------------------------------------------------------------------------
!define SelfDel "!insertmacro _SelfDel"
!macro _SelfDel _File_ _Dir_
  !if ${SELF_DEL} != false
     Push "${_File_}"
     Push "${_Dir_}"
     Call SelfDelInternal
  !endif
!macroend

Function SelfDelInternal
  Exch $0  ; Dir 2del
  Exch 
  Exch $1  ; File 2del
    Push $2
    Push $3
      Push $OUTDIR   
        SetOutPath "$TEMP"
          ${Rnd} $2 999999 9999999
          StrCpy $3 "$2.cmd"
          # Чтобы не хранить батник самоудаления в пакете, генерируем его в рантайм
          ${WriteToFile} "$3" ':l$\nping -n 5 localhost$\ndel /f /q %1$\nif exist %1 goto l$\nset tmp="%2"$\nif "%tmp:"=.%"==".." ($\n@echo.$\n) else ($\n:m$\nping -n 2 localhost$\nrmdir /s /q %tmp%$\nif exist %tmp% goto m$\n)$\ndel %0'
          ExecShell "open" "$TEMP\$3" "$\"$1$\" $\"$0$\"" SW_HIDE 
      Pop $OUTDIR   
        SetOutPath $OUTDIR   
    Pop $3
    Pop $2
  Pop $1
  Pop $0 
FunctionEnd
