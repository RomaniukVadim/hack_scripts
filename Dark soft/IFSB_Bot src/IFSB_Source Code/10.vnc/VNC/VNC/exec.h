//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: exec.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	executes command with arguments

#ifndef __EXEC_H_
#define __EXEC_H_

BOOL ExecuteCommandA(LPCSTR szPath,LPCSTR szArgs);
BOOL ExecuteCommandW(LPCWSTR szPath,LPCWSTR szArgs);

#endif //__EXEC_H_