//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: kbd.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	keyboard events handling

#ifndef __KBD_H_
#define __KBD_H_

VOID KeyBoardInit();
VOID KbdOnKeyEvent(PVNC_SESSION pVncSession, ULONG keysym, BOOL down);

DWORD KbdGetLocalesNum();
HKL* KbdGetLocales();

WORD KbdUpdateInputState(PVNC_SESSION pVncSession,ULONG bVK,BOOL bDown);

#endif //__KBD_H_