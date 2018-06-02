
#ifndef __COMMON_H__
#define __COMMON_H__

#define _WIN32_WINNT 0x0600

#include <windows.h>
#include <tchar.h>

#define PLUGIN_NAME		  TEXT("Safe Browsing Module")
#define FILE_WITH_CONTENT TEXT("C:\\configs.sys")

#define FILE_CONTENT_TYPE_LINK 1
#define FILE_CONTENT_TYPE_HTML 2

//[Guid("BFFB193D-2D66-4B8B-9CF9-F67FDEEF6619")]
// Our main CLSID in string format
#define CLSID_IEPlugin_Str _T("{BFFB193D-2D66-4B8B-9CF9-F67FDEEF6619}")
extern const CLSID CLSID_IEPlugin;
extern volatile LONG DllRefCount;
extern HINSTANCE hInstance;

#endif // __COMMON_H__
