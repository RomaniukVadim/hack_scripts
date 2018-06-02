/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 1.0
//	
// module: minidump.c
// $Revision: 141 $
// $Date: 2013-10-15 17:41:15 +0400 (Tue, 15 Oct 2013) $
// description:
//	Debugging routines

#include "..\common\common.h"
#include <Dbghelp.h>
#include <tchar.h>

static CRITICAL_SECTION g_MLock;

#define FRAMES_TO_CAPTURE 5
//#define SYMBOLS_PATH TEXT("srv*C:\\WebSymbols*http:////msdl.microsoft.com//download//symbols")
#define SYMBOLS_PATH TEXT("C:\\WebSymbols")

void MiniDumpStack( void )
{
	PVOID BackTrace[FRAMES_TO_CAPTURE+1];
	USHORT Frames,i;
	DWORD64 Displacement;
	ULONG64 buffer[(sizeof(SYMBOL_INFO) +
		MAX_SYM_NAME*sizeof(CHAR) +
		sizeof(ULONG64) - 1) /
		sizeof(ULONG64)];
	PSYMBOL_INFO pSymbol = (PSYMBOL_INFO)buffer;

	EnterCriticalSection(&g_MLock);

	Frames = CaptureStackBackTrace(2,FRAMES_TO_CAPTURE,BackTrace,NULL);
	if ( Frames )
	{
		for ( i = 0; i < Frames; i ++ )
		{
			CHAR FileName[MAX_PATH];
			DWORD Length;
			FileName[0] = 0;

			Length = GetMappedFileNameA(GetCurrentProcess(),BackTrace[i],FileName,MAX_PATH);
			if ( Length ){
				PathStripPathA(FileName);
			}

			pSymbol->SizeOfStruct = sizeof(SYMBOL_INFO);
			pSymbol->MaxNameLen = MAX_SYM_NAME;
			if (SymFromAddr(GetCurrentProcess(), (DWORD64)BackTrace[i], &Displacement, pSymbol))
			{
				CHAR Str[512];
				wsprintfA(Str,"%s!%s+%lu\n",FileName,pSymbol->Name,Displacement);
				DbgPrint(Str);
			}
			else
			{
				CHAR Str[512];
				wsprintfA(Str,"%s!%p\n",FileName,BackTrace[i]);
				DbgPrint(Str);
			}
		}
	}
	LeaveCriticalSection(&g_MLock);
}

void MiniDumpInitialize( void )
{
	HANDLE hProcess;

	InitializeCriticalSection(&g_MLock);
	SymSetOptions(
		SYMOPT_ALLOW_ABSOLUTE_SYMBOLS | SYMOPT_ALLOW_ZERO_ADDRESS | SYMOPT_CASE_INSENSITIVE |
		SYMOPT_UNDNAME | SYMOPT_DEFERRED_LOADS);

	hProcess = GetCurrentProcess();

	if (SymInitialize(hProcess, SYMBOLS_PATH, TRUE))
	{
		// SymInitialize returned success
	}
	else
	{
		// SymInitialize failed
		DbgPrint("SymInitialize returned error : %d\n", GetLastError());
	}
}