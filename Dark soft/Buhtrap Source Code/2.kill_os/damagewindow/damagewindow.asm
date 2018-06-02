
format PE GUI 4.0
entry start

include 'win32a.inc'
include 'encoding\win1251.inc'

section '.idata' data readable writeable

  data import

  dd 0,0,0,rva kernel_name,rva kernel_table
  dd 0,0,0,rva user_name,rva user_table
  dd 0,0,0,0,0

  end data

  kernel_table:
    ExitProcess dd rva _ExitProcess
    GetModuleHandle dd rva _GetModuleHandleA
    dd 0

  user_table:
    DialogBoxParam dd rva _DialogBoxParamA
    dd 0

  kernel_name db 'KERNEL32.DLL',0
  user_name db 'USER32.DLL',0

  _ExitProcess dw 0
    db 'ExitProcess',0
  _GetModuleHandleA dw 0
    db 'GetModuleHandleA',0

  _DialogBoxParamA dw 0
    db 'DialogBoxParamA',0

section '.text' code readable executable

proc DialogProc hwnddlg,msg,wparam,lparam
	push	ebx esi edi
	cmp	[msg],WM_INITDIALOG
	je	.wminitdialog
	xor	eax,eax
	jmp	.finish
  .wminitdialog:
	mov	eax,1
  .finish:
	pop	edi esi ebx
	ret
endp

  start:
	invoke	GetModuleHandle,0
	invoke	DialogBoxParam,eax,37,HWND_DESKTOP,DialogProc,0
	invoke	ExitProcess,0

section '.rsrc' resource data readable

  directory RT_DIALOG,dialogs

  resource dialogs,\
	   37,LANG_RUSSIAN+SUBLANG_DEFAULT,demonstration

  dialog demonstration,'Ошибка чтения жесткого диска',0,0,184,139,DS_MODALFRAME+DS_CENTER+WS_POPUP+WS_VISIBLE+WS_CAPTION+WS_SYSMENU,WS_EX_TOPMOST,0,'MS Shell Dlg',8
    dialogitem 'STATIC','Система выявила серьезную ошибку системного диска Windows. Сохраните данные и выйдите из системы. Все несохраненные изменения будут потеряны. Отключение системы вызвано NT AUTHORITY\SYSTEM.',-1,33,11,147,52,SS_LEFT+WS_CHILD+WS_VISIBLE+WS_GROUP
    dialogitem 'BUTTON','Сообщение',-1,30,73,146,58,BS_GROUPBOX+WS_CHILD+WS_VISIBLE
    dialogitem 'STATIC','Необходимо экстренно завершить работу Windows поскольку произошла непредвиденная ошибка чтения и записи на жесткий диск. Возможна неисправность оборудования.',-1,36,83,137,45,SS_LEFT+WS_CHILD+WS_VISIBLE+WS_GROUP
    dialogitem 'STATIC',32513,-1,7,12,18,20,SS_ICON+WS_CHILD+WS_VISIBLE
  enddialog
