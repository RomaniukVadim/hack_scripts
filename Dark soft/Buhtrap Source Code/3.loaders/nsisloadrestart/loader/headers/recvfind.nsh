#################################################################################################
# RecFind.nsh - Recursive FindFirst, FindNext, FindClose.
#  by Afrow UK
#
# Last modified 23rd December 2008

; Usage:
; ------------------------------------------------------------
; ${RecFindOpen} `Dir\Path` $CurrentDirVar $CurrentFileVar
;  ... Do stuff with $CurrentDirVar ...
; ${RecFindFirst}
;  ... Do stuff with $CurrentFileVar ...
; ${RecFindNext}
; ${RecFindClose}

; Notes:
; ------------------------------------------------------------

; Looping is handled by the macro's internally.
; Do not use the stack (Push, Pop, Exch)

; ${RecFindOpen} opens a search in a new directory in the tree.
;  The macro's will loop back to this instruction when a new
;  directory is opened for searching.

; ${RecFindFirst} gets file names out of the current directory.
;  The macro's will loop back to this instruction when a new file
;  is found.

; ${RecFindNext} gets the next file in the current directory, and loops to
;  ${RecFindFirst} again.

; ${RecFindClose} closes the search and clears the stack.

; Example 1:
; ------------------------------------------------------------
; ${RecFindOpen} `C:\Dir` $R0 $R1
;  DetailPrint `Dir: C:\Dir$R0`
; ${RecFindFirst}
;  DetailPrint `File: C:\Dir$R0\$R1`
; ${RecFindNext}
; ${RecFindClose}

; Example 2:
; ------------------------------------------------------------
; ${RecFindOpen} `C:\Dir` $R0 $R1
;  DetailPrint `Dir: C:\Dir$R0`
; ${RecFindFirst}
;  DetailPrint `File: C:\Dir$R0\$R1`
;  StrCmp $R1 `a_file.txt` Found
; ${RecFindNext}
;  Found:
; ${RecFindClose}

#################################################################################################

!ifndef _RecFind_Included
!define _RecFind_Included

Var _RecFindVar1
Var _RecFindVar2

!macro _RecFindOpen Dir CurrentDirVar CurrentFileVar

 !define _Local          `${__LINE__}`
 !define _Dir            `${Dir}`
 !define _CurrentDirVar  `${CurrentDirVar}`
 !define _CurrentFileVar `${CurrentFileVar}`

  !define _RecFindOpenSet

 StrCpy $_RecFindVar2 1
 Push ``

 `nextDir${_Local}:`
 Pop `${_CurrentDirVar}`
 IntOp $_RecFindVar2 $_RecFindVar2 - 1

!macroend
!define RecFindOpen `!insertmacro _RecFindOpen`

!macro _RecFindFirst

 !ifndef _RecFindOpenSet
  !error `Incorrect use of RecFind commands!`
 !else
  !define _RecFindFirstSet
 !endif

 ClearErrors
 FindFirst $_RecFindVar1 `${_CurrentFileVar}` `${_Dir}${_CurrentDirVar}\*.*`
 IfErrors `Done${_Local}`

  `checkFile${_Local}:`
  StrCmp ${_CurrentFileVar} .  `nextFile${_Local}`
  StrCmp ${_CurrentFileVar} .. `nextFile${_Local}`

  IfFileExists `${_Dir}${_CurrentDirVar}\${_CurrentFileVar}\*.*` 0 +4
   Push `${_CurrentDirVar}\${_CurrentFileVar}`
   IntOp $_RecFindVar2 $_RecFindVar2 + 1
    Goto `nextFile${_Local}`

!macroend
!define RecFindFirst `!insertmacro _RecFindFirst`

!macro _RecFindNext

 !ifndef _RecFindOpenSet | _RecFindFirstSet
  !error `Incorrect use of RecFind commands!`
 !else
  !define _RecFindNextSet
 !endif

 `nextFile${_Local}:`

 ClearErrors
 FindNext $_RecFindVar1 `${_CurrentFileVar}`
 IfErrors 0 `checkFile${_Local}`

 StrCmp $_RecFindVar2 0 +3
 FindClose $_RecFindVar1
 Goto `nextDir${_Local}`

!macroend
!define RecFindNext `!insertmacro _RecFindNext`

!macro _RecFindClose

 !ifndef _RecFindOpenSet | _RecFindFirstSet | _RecFindNextSet
  !error `Incorrect use of RecFind commands!`
 !else
  !undef _RecFindOpenSet
  !undef _RecFindFirstSet
  !undef _RecFindNextSet
 !endif

 `Done${_Local}:`
 FindClose $_RecFindVar1

 StrCmp $_RecFindVar2 0 +4
  Pop $_RecFindVar1
  IntOp $_RecFindVar2 $_RecFindVar2 - 1
  Goto -3

 !undef _CurrentFileVar
 !undef _CurrentDirVar
 !undef _Dir
 !undef _Local

!macroend
!define RecFindClose `!insertmacro _RecFindClose`

!endif