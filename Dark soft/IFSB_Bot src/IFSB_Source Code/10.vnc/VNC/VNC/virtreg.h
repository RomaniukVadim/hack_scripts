//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: virtreg.h
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Tue, 23 Jul 2013) $
// description:
//	registry keys virtualization

#ifndef __VIRT_REG_H_
#define __VIRT_REG_H_

BOOL 
	RegQueryValueExW_Before(
		IN HKEY hKey,
		IN LPWSTR lpSubKey,
		IN LPWSTR lpValueName,
		OUT LPDWORD lpType,
		OUT LPBYTE lpData,
		OUT LPDWORD lpcbData,
		OUT LPLONG ErrorCode,
		IN BOOL bIE, BOOL bShell
		);

#endif