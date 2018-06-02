#ifndef __REG_H_
#define __REG_H_

void RegMoveFromKeyToKey(TCHAR *lpFrom,TCHAR *lpTo);
LPWSTR RegQueryKeyNameW(IN HKEY hKey );

//
//	Allocates a memory and loads the specified registry value into it.
//
WINERROR LoadRegistryValueW(
	HKEY	ParentKey,	// parent key name
	LPWSTR	KeyName,	// target key name
	LPWSTR	ValueName,	// value name
	PCHAR*	pValue,		// receives pointer the allocated memory containing the specified value
	PULONG	pSize,		// receives size of the value
	PULONG	pType		// receives type of the value
	);

WINERROR LoadRegistryValueA(
	HKEY	ParentKey,	// parent key name
	LPSTR	KeyName,	// target key name
	LPSTR	ValueName,	// value name
	PCHAR*	pValue,		// receives pointer the allocated memory containing the specified value
	PULONG	pSize,		// receives size of the value
	PULONG	pType		// receives type of the value
	);

#if _UNICODE
	#define	LoadRegistryValue	LoadRegistryValueW
#else
	#define	LoadRegistryValue	LoadRegistryValueA
#endif

WINERROR RegReadDwordW(
	HKEY	ParentKey,	// parent key name
	LPWSTR	KeyName,	// target key name
	LPWSTR	ValueName,	// value name
	PDWORD	pValue
	);

WINERROR RegReadDwordA(
	HKEY	ParentKey,	// parent key name
	LPSTR	KeyName,	// target key name
	LPSTR	ValueName,	// value name
	PDWORD	pValue
	);

#if _UNICODE
	#define	RegReadDword	RegReadDwordW
#else
	#define	RegReadDword	RegReadDwordA
#endif

DWORD RegReadStringW(
	HKEY	ParentKey,	// parent key name
	LPWSTR	KeyName,	// target key name
	LPWSTR	ValueName,	// value name
	LPWSTR*	pValue,		// receives pointer the allocated memory containing the specified value
	PULONG	pSize		// receives size of the value
	);

DWORD RegReadStringA(
	HKEY	ParentKey,	// parent key name
	LPSTR	KeyName,	// target key name
	LPSTR	ValueName,	// value name
	PCHAR*	pValue,		// receives pointer the allocated memory containing the specified value
	PULONG	pSize		// receives size of the value
	);

#if _UNICODE
	#define	RegReadString	RegReadStringW
#else
	#define	RegReadString	RegReadStringA
#endif

WINERROR RegDeleteValueExA(
	HKEY	ParentKey,	// parent key name
	LPSTR	KeyName,	// target key name
	LPSTR	ValueName	// value name
	);

WINERROR RegDeleteValueExW(
	HKEY	ParentKey,	// parent key name
	LPWSTR	KeyName,	// target key name
	LPWSTR	ValueName	// value name
	);

#if _UNICODE
	#define	RegDeleteValueEx	RegDeleteValueExW
#else
	#define	RegDeleteValueEx	RegDeleteValueExA
#endif

#endif //__REG_H_