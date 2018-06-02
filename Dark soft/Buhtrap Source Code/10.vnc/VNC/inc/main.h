//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: main.h
// $Revision: 207 $
// $Date: 2014-07-16 12:51:46 +0400 (Ср, 16 июл 2014) $
// description:
//	VNC main includes, constants and definitions.

#pragma once

#ifndef WINVER					// Allow use of features specific to Windows XP or later.
#define WINVER 0x0501			// Change this to the appropriate value to target other versions of Windows.
#endif

#ifndef _WIN32_WINNT			// Allow use of features specific to Windows XP or later.                   
#define _WIN32_WINNT 0x0501		// Change this to the appropriate value to target other versions of Windows.
#endif						

#define WIN32_LEAN_AND_MEAN		// Exclude rarely-used stuff from Windows headers


// Global predefinitions
#define	_NO_CRT					TRUE
//#define	_KIP_SUPPORT			TRUE
#define	_BC_CLIENT				TRUE
#define	_START_ON_DLL_LOAD		TRUE

// enables win8 support
#define _ENABLE_WIN8_SUPPORT	TRUE


// Specify TRUE to inject the VNCDLL.DLL without a file as a PE image.
// Specify FALSE to inject it normally as a DLL.
#define		_INJECT_AS_IMAGE		TRUE

// Registers VNC executable loader within Windows autorun
#if _INJECT_AS_IMAGE
 #define	_REGISTER_EXE	TRUE
#endif

// uncomment this to disable cpulimits for VNC
// #define _DISABLE_CPU_LIMIT 1

#if (defined(_KERNEL_MODE_INJECT) && !defined(_START_ON_DLL_LOAD))
 #define		_START_ON_DLL_LOAD		TRUE
#endif


#include <windows.h>
#include <Sddl.h>

#include <tchar.h>
#include <psapi.h>
#include <wininet.h>
#include <shlwapi.h>
#include <shellapi.h>


#ifdef _NO_CRT
	#undef strlen
	#undef _stricmp
	#undef _mbsicmp
	#undef strscmp
	#undef wcscmp
	#undef wsclen
	#undef malloc
	#undef free
	#undef strupr
	#undef wcsupr
	#undef _mbsupr
	#undef strtoul
	#undef strcat
	#undef wcscat

	#undef _tcsrchr
	#undef strchr
	#undef wcschr
	#undef strrchr
	#undef wcsrchr
	#undef _tcsicmp

	#define strlen		lstrlenA
	#define wcslen		lstrlenW
	#define strcmp		lstrcmp
	#define wcscmp		lstrcmpW
	#define _stricmp	lstrcmpiA
	#define _tcsicmp	lstrcmpiA
	#define	_mbsicmp	lstrcmpiA
	#define strcat		lstrcatA
	#define wcscat		lstrcatW

	#define malloc(x)		LocalAlloc(LPTR, x)
	#define free(x)			LocalFree(x)
	#define	realloc(x, y)	LocalReAlloc(x, y, LMEM_MOVEABLE)

	#define strupr	_strupr	// ndll
	#define wcsupr	_wcsupr	//
	#define	_mbsupr	_strupr //


	#define strtoul(a,b,c)	StrToIntA(a)
	#define wcstoul(a,b,c)	StrToIntW(a)
	#define strchr			StrChrA
	#define wcschr			StrChrW
	#define strrchr(a,b)	StrRChrA(a, NULL, b)
	#define wcsrchr(a,b)	StrRChrW(a, NULL, b)
	#define _tcsrchr(a,b)	StrRChr(a, NULL, b)
	
#endif



#ifndef NTSTATUS
#define NTSTATUS LONG
#endif

#pragma warning(push)
#pragma warning(disable:4005) // macro redefinition
#include "ntdll.h"
#include <ntstatus.h>
#pragma warning(pop)




#pragma warning (disable:4996)	// 'wcscpy': This function or variable may be unsafe. Consider using wcscpy_s instead.

// Interlocked intrinsics
#ifdef __cplusplus
 extern "C" {
#endif
	extern LONG  __cdecl _InterlockedIncrement(LONG volatile *Addend);
	extern LONG  __cdecl _InterlockedDecrement(LONG volatile *Addend);
	extern LONG  __cdecl _InterlockedAnd(LONG volatile *Destination, LONG Value);
	extern LONG  __cdecl _InterlockedOr(LONG volatile *Destination, LONG Value);
#ifdef __cplusplus
 }
#endif


#pragma intrinsic(_InterlockedIncrement)
#pragma intrinsic(_InterlockedDecrement)
#pragma intrinsic(_InterlockedAnd)
#pragma intrinsic(_InterlockedOr)


// DbgPrint() and checked heap allocations
#include "dbg.h"

// Lists support 
#include "listsup.h"

// Windows sockets
#include "winsock2.h"

// Usefull types
typedef INT	WINERROR;					// One of the Windows error codes defined within winerror.h
#define ERROR_UNSUCCESSFULL	0xffffffff	// Common unsuccessful code
#define	INVALID_INDEX		(-1)

// Macros
#define MAX_PATH_BYTES (MAX_PATH*sizeof(_TCHAR))

#define cstrlenW(str)	(sizeof(str)/sizeof(WCHAR))-1
#define cstrlenA(str)	(sizeof(str)-1)

// constant string length
#if _UNICODE
C_ASSERT(FALSE);
	#define cstrlen(str)	cstrlenW(str)
#else
	#define cstrlen(str)	cstrlenA(str)
#endif


// minimum buffer size
#define BUFFER_INCREMENT	 0x1000

// timer period macros
#define _RELATIVE(x)		-(x)
#define _SECONDS(x)			(LONGLONG)x*10000000
#define _MILLISECONDS(x)	(LONGLONG)x*10000
#define _MINUTES(x)			(LONGLONG)x*600000000


#define szSpace				_T(" ")


#define	htonS(x)			((LOBYTE(x) << 8) + HIBYTE(x))
#define	htonL(x)			((LOBYTE(LOWORD(x)) << 24) + (HIBYTE(LOWORD(x)) << 16) + (LOBYTE(HIWORD(x)) << 8) + HIBYTE(HIWORD(x)))


PVOID __stdcall	AppAlloc(ULONG Size);
VOID __stdcall	AppFree(PVOID pMem);
