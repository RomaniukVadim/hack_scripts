//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: copy.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	routines for copying files and directories including
//	ones locked by some process

#ifndef __COPY_H_
#define __COPY_H_

DWORD XCopyDirectoryW( LPWSTR Src, LPWSTR Dst );
DWORD XCopyDirectoryA( LPSTR Src, LPSTR Dst );
DWORD XCopyDirectorySpecifyProcessW( LPWSTR ProcessName, LPWSTR Src, LPWSTR Dst );

BOOL XRemoveDirectoryW( LPWSTR szPath, LPWSTR Pattern, BOOL bRemoveDir);
BOOL XRemoveDirectoryA( LPSTR szPath, LPSTR Pattern, BOOL bRemoveDir);

#endif