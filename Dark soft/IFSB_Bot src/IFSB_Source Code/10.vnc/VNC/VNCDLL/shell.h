#ifndef __SHELL_H_
#define __SHELL_H_

//OLE
typedef HRESULT (__stdcall *ptr_CoCreateInstance)(
	REFCLSID rclsid,
	LPUNKNOWN pUnkOuter,
	DWORD dwClsContext,
	REFIID riid,
	LPVOID * ppv
	);

typedef HRESULT (__stdcall *ptr_CoCreateInstanceEx)(
	REFCLSID rclsid,
	IUnknown * punkOuter,
	DWORD dwClsCtx,
	COSERVERINFO * pServerInfo,
	ULONG cmq,
	MULTI_QI * pResults
	);

typedef HRESULT (__stdcall *ptr_CoGetClassObject)(
	REFCLSID rclsid,
	DWORD dwClsContext,
	COSERVERINFO * pServerInfo,
	REFIID riid,
	LPVOID * ppv
	);

typedef HRESULT (__stdcall *ptr_CoRegisterClassObject)(
	REFCLSID rclsid,
	IUnknown * pUnk,
	DWORD dwClsContext,
	DWORD flags,
	LPDWORD  lpdwRegister
	);

typedef BOOL (__stdcall *ptr_SetImmersiveBackgroundWindow)( HWND hWnd );

typedef DWORD (__stdcall *ptr_MsgWaitForMultipleObjectsEx)(
	DWORD nCount,
	HANDLE *pHandles,
	DWORD dwMilliseconds,
	DWORD dwWakeMask,
	DWORD dwFlags
	);

typedef HANDLE (__stdcall *ptr_CreateEventW)(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	BOOL bManualReset,
	BOOL bInitialState,
	LPCWSTR lpName
	);

typedef HANDLE (__stdcall *ptr_CreateEventA)(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	BOOL bManualReset,
	BOOL bInitialState,
	LPCSTR lpName
	);

typedef HANDLE (__stdcall *ptr_CreateEventExW)(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	LPCWSTR lpName,
	DWORD dwFlags,
	DWORD dwDesiredAccess
	);

typedef HANDLE (__stdcall *ptr_CreateEventExA)(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	LPCSTR lpName,
	DWORD dwFlags,
	DWORD dwDesiredAccess
	);

typedef HANDLE (__stdcall *ptr_OpenEventW)(
	DWORD dwDesiredAccess,
	BOOL bInheritHandle,
	LPCWSTR lpName
	);

typedef HANDLE (__stdcall *ptr_OpenEventA)(
	DWORD dwDesiredAccess,
	BOOL bInheritHandle,
	LPCSTR lpName
	);

//OLE
HRESULT __stdcall my_CoCreateInstance(
	REFCLSID rclsid,
	LPUNKNOWN pUnkOuter,
	DWORD dwClsContext,
	REFIID riid,
	LPVOID * ppv
	);

HRESULT __stdcall my_CoCreateInstanceEx(
	REFCLSID rclsid,
	IUnknown * punkOuter,
	DWORD dwClsCtx,
	COSERVERINFO * pServerInfo,
	ULONG cmq,
	MULTI_QI * pResults
	);

HRESULT __stdcall my_CoGetClassObject(
	REFCLSID rclsid,
	DWORD dwClsContext,
	COSERVERINFO * pServerInfo,
	REFIID riid,
	LPVOID * ppv
	);

HRESULT __stdcall my_CoRegisterClassObject(
	REFCLSID rclsid,
	IUnknown * pUnk,
	DWORD dwClsContext,
	DWORD flags,
	LPDWORD  lpdwRegister
	);

BOOL __stdcall my_SetImmersiveBackgroundWindow( HWND hWnd );
BOOL __stdcall my_AcquireIAMKey( PVOID Arg );
BOOL __stdcall my_EnableIAMAccess( PVOID Arg, DWORD Enable );
BOOL __stdcall my_EnableIAMAccessWin81( PVOID Arg1, PVOID Arg2, DWORD Enable );
DWORD __stdcall my_PsmRegisterKeyNotification(PVOID Arg1,PVOID Arg2);

DWORD __stdcall my_MsgWaitForMultipleObjectsEx(
	DWORD nCount,
	HANDLE *pHandles,
	DWORD dwMilliseconds,
	DWORD dwWakeMask,
	DWORD dwFlags
	);

HANDLE __stdcall my_CreateEventW(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	BOOL bManualReset,
	BOOL bInitialState,
	LPCWSTR lpName
	);

HANDLE __stdcall my_CreateEventA(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	BOOL bManualReset,
	BOOL bInitialState,
	LPCSTR lpName
	);

HANDLE __stdcall my_CreateEventExW(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	LPCWSTR lpName,
	DWORD dwFlags,
	DWORD dwDesiredAccess
	);

HANDLE __stdcall my_CreateEventExA(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	LPCSTR lpName,
	DWORD dwFlags,
	DWORD dwDesiredAccess
	);

HANDLE __stdcall my_OpenEventW(
	DWORD dwDesiredAccess,
	BOOL bInheritHandle,
	LPCWSTR lpName
	);

HANDLE __stdcall my_OpenEventA(
	DWORD dwDesiredAccess,
	BOOL bInheritHandle,
	LPCSTR lpName
	);

WINERROR ShellHookActivate(VOID);
VOID ShellHookDectivate(VOID);

#endif //__SHELL_H_