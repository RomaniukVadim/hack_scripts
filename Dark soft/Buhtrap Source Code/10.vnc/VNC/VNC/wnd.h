//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: wnd.h
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	functions for windows manipulation

#ifndef __WND_H_
#define __WND_H_

#define WCF_MOUSE_AUTOCAPTURE       0x00000001
#define WCF_MOUSE_CLIENT_TO_SCREEN  0x00000002
#define WCF_PAINTMETHOD_NOP         0x00000004
#define WCF_PAINT_ON_TOP            0x00000008
#define WCF_NO_CAPTURE              0x00000010
#define WCF_MENU                    0x00000020
#define WCF_JAVA                    0x00000040
#define WCF_PAINT_ALWAYS_BOTTOM     0x00000080
#define WCF_NEVERCLOSE              0x00000100
#define WCF_IGNOREWINDOW            0x00000200
#define WCF_STOLENWINDOW            0x00000400
#define WCF_STARTMENU               0x00000800
#define WCF_MOZILLA                 0x00001000
#define WCF_OPERA                   0x00002000
#define WCF_JOPERA                  0x00004000 // opera on xp
#define WCF_DRAW_CHILD              0x00008000
#define WCF_XP_MOUSE                0x00010000

#define WCF_W7_AND_BELOW        0x20000000
#define WCF_XP_AND_BELOW        0x40000000
#define WCF_ABOVE_XP             0x80000000

#define HASH_MENU                   0x0C63FEB9 //#32768
#define HASH_SYSSHADOW              0x00510FA8 //SysShadow
#define HASH_BASEBAR                0x8E29E9A7 //BaseBar
#define HASH_MENUSITE               0XE5CD1344 //MenuSite
#define HASH_TOOLTIP32              0x7255C974 //tooltips_class32
#define HASH_BUTTON                 0x3DAAA90B //button
#define HASH_STARMENU               0x9BE8214B //D2VControlHost
#define HASH_DESK                   0xD323FBEF //Progman
#define HASH_MOZILLAMENU            0x800367F8 //MozillaDropShadowWindowClass
#define HASH_MOZILLAWND             0x3853C1FB //MozillaWindowClass 
#define HASH_MOZILLADLG             0x00cdb5ce //MozillaDialogClass
#define HASH_AWTPLUGIN              0x285B6568 //SunAwtPlugin
#define HASH_AWTFRAME               0xE04A99F6 //SunAwtFrame
#define HASH_AWTCANVAS              0x14C13799 //SunAwtCanvas
#define HASH_AWTDIALOG              0xF43F83E3 //SunAwtDialog
#define HASH_QPOPUP                 0x7BFAC6B0 //QPopup
#define HASH_DEFVIEW                0x8BB9FD5B //SHELLDLL_DefView
#define HASH_WORKERW                0x4B93A08D //WorkerW
#define HASH_IME                    0xC36BEB0A //IME
#define HASH_MSCTFIMEUI             0xE1AF2364 //MSCTFIME UI
#define HASH_GDIPHOOKWND            0xF7DC2308 //GDI+ Hook Window Class
#define HASH_AUTOSUGGEST            0x7D2BA740 //Auto-Suggest Dropdown
#define HASH_IHWINDOWCLASS          0x3E05CF56 //IHWindowClass
#define HASH_BBARWINDOWCLASS        0x336B357E //BBarWindowClass
#define HASH_TSCSHELLCONTCLASS      0xB2A1979A //TscShellContainerClass
#define HASH_IESERVER               0xDBC2E831 //Internet Explorer_Server
#define HASH_IEFRAME                0xa17190df //IEFrame
#define HASH_DirectUIHWND           0xe09a2cea //DirectUIHWND
#define HASH_LINKS_EXPLORER         0x6523cc2e //Links Explorer
#define HASH_MSOUNISTAT             0x15991329 //MSOUNISTAT
#define HASH_OPERA                  0x1654db4c //OperaWindowClass
#define HASH_OUIWINDOW              0xe1a98a88 //OUIWINDOW, opera
#define HASH_TPANEL                 0x18f161f3 //TMyPanel, total commander
#define HASH_CHROME_WIDGETWIN_0     0xf1c4b754 // chrome menu Chrome_WidgetWin_0
#define HASH_CHROME_WIDGETWIN_2     0x1fcad678 // chrome menu Chrome_WidgetWin_2
#define HASH_CHROME_WIDGETWIN_1     0x86c387c2 // Chrome_WidgetWin_1
#define HASH_Chrome_RenderWidgetHostHWND 0xff28cef3 //Chrome_RenderWidgetHostHWND

#define WND_MSG_TIMEOUT 1000

// windows proportion
#define NOTDEFINED 0
#define HORIZONTAL 1
#define VERTICAL 2

typedef struct _WND_CLASS_INFO
{
	int dwClassHash;
	DWORD wMethod;
	DWORD dwProcess; // process name hash
	WORD  MinMajor; // min major
	WORD  MaxMajor;
}WND_CLASS_INFO,*PWND_CLASS_INFO;

typedef struct _CURSOR_INFO
{
	POINT pt;
	DWORD dwArea;
	HWND hGlobalWnd;
}CURSOR_INFO,*PCURSOR_INFO;


typedef int (__stdcall *_MessageBoxTimeout)(IN HWND hWnd, IN char *lpText,IN char *lpCaption, IN UINT uType,IN WORD wLanguageId, IN DWORD dwMilliseconds);
extern _MessageBoxTimeout MessageBoxTimeout;

HWND FindWnd(HWND hParent,TCHAR *lpClass,TCHAR *lpText);
BOOL IsWindowVisibleEx( HWND hWnd );
BOOL IsWindowEx(PVNC_DESKTOP pDesktop,HWND hWnd);
BOOL IsAltTabItem(WINDOWINFO *lpWndInfo);
BOOL IsWindowInFullScreenMode(PVNC_DESKTOP pDesktop, HWND hWnd );
BOOL IsMenuEx(PVNC_DESKTOP pDesktop,HWND hWnd);
BOOL IsMDI(HWND hWnd);
BOOL IsConsole(HWND hWnd);
BOOL IsStyleHaveSizeBorders(LONG_PTR dwStyle);

#define IsFullScreen(pSession,hWnd) ((IsWindowInFullScreenMode(&pSession->Desktop,hWnd)) && (hWnd == SS_GET_FOREGROUND_WND(pSession)))

HWND GetBestChild(HWND hWnd,POINT pt);
HWND _GetBestChild(HWND hWnd,POINT pt);

DWORD GetWindowClassFlags(PVNC_DESKTOP pDesktop,HWND hWnd);
DWORD GetWindowClassFlagsEx(PVNC_DESKTOP pDesktop,char * ClassName);

HWND GetTopPopupWindow(PVNC_DESKTOP Desktop,HWND hWnd);
VOID SetForegroundWnd(PVNC_SESSION pSession,HWND hWnd);
HWND GetWindowFocus(HWND hWnd);

int GetClassHash(HWND hWnd);
BOOL CheckWindowStyle(HWND hWnd);

HWND _WindowFromPoint(PVNC_SESSION pSession,POINT pt,WORD *dwHitTest,HWND *lphParent,WORD wDeep,BOOL bClick);
HWND GetBestChildEx(HWND hWnd,POINT pt);

VOID AttachToInput( PVNC_SESSION pVncSession, HWND hWnd );
VOID DettachToInput( PVNC_SESSION pVncSession, HWND hWnd );
VOID DestroyMenus( PVNC_DESKTOP Desktop, HWND hWnd );

int GetWindowProportion( HWND hWnd );
BOOL IsDialogWindow( HWND hWnd );

#endif //__WND_H_