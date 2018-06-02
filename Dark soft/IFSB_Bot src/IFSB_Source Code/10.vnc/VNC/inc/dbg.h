//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: dbg.h
// $Revision: 187 $
// $Date: 2014-07-11 16:48:53 +0400 (Пт, 11 июл 2014) $
// description:
//	Debug-buld support routines: DbgPrint(), ASSERT() and checked pool allocations.

#pragma once

#include <crtdbg.h>

#if _DEBUG
	#define _DBG			TRUE
	#define _TRACE_ALLOC	TRUE
#endif


#define BAD_PTR		(LONG_PTR)0xBAADF00D
#define	PAGE_SIZE	0x1000

extern	HANDLE	g_AppHeap;


#ifdef _TRACE_ALLOC

typedef struct _DBG_ALLOC
{
	unsigned long Size;
	unsigned long Sing;
	char  Buffer[1];
}DBG_ALLOC,*PDBG_ALLOC;


__inline void* DbgAlloc(size_t Size)
{
	void* mem = malloc(Size + 12);
	if (mem)
	{
		PDBG_ALLOC pd = (PDBG_ALLOC) mem;
		memset(mem, 0xcc, Size + 12);
		pd->Size = (unsigned long) Size;
		return(&pd->Buffer);
	}
	return(mem);
}

__inline void DbgFree(void* mem)
{
	PDBG_ALLOC pd = CONTAINING_RECORD(mem, DBG_ALLOC, Buffer);
	if (*(unsigned long*)((PCHAR)pd->Buffer + pd->Size)!= 0xcccccccc)
		__debugbreak();
	if (pd->Sing != 0xcccccccc)
		__debugbreak();
	free(pd);

}


__inline void* DbgRealloc(void* Mem, size_t Size)
{
	void* mem = malloc(Size + 12);
	if (mem)
	{
		PDBG_ALLOC pd = (PDBG_ALLOC) mem;
		PDBG_ALLOC pd1 = CONTAINING_RECORD(Mem, DBG_ALLOC, Buffer);
		if (pd1->Sing != 0xcccccccc)
			__debugbreak();

		memset(mem, 0xcc, Size + 12);
		pd->Size = (unsigned long) Size;
		memcpy(&pd->Buffer, &pd1->Buffer, pd1->Size);
	
		DbgFree(Mem);
		return(&pd->Buffer);
	}

	return(mem);
}

__inline void* DbgHeapAlloc(size_t Size)
{
	void* mem;
	if (g_AppHeap == 0)
		__debugbreak();
	if (Size == 0)
		__debugbreak();

	mem = HeapAlloc(g_AppHeap, 0, Size + 12);
	if (mem)
	{
		PDBG_ALLOC pd = (PDBG_ALLOC) mem;
		memset(mem, 0x99, Size + 12);
		pd->Size = (unsigned long) Size;
		return(&pd->Buffer);
	}
	return(mem);
}

__inline void DbgHeapFree(void* mem)
{
	PDBG_ALLOC pd = CONTAINING_RECORD(mem, DBG_ALLOC, Buffer);
	if (g_AppHeap == 0)
		__debugbreak();
	if (*(unsigned long*)((PCHAR)pd->Buffer + pd->Size)!= 0x99999999)
		__debugbreak();
	if (pd->Sing != 0x99999999)
		__debugbreak();
	HeapFree(g_AppHeap, 0, pd);
}

__inline void* DbgHeapRealloc(void* Mem, size_t Size)
{
	void* mem = HeapAlloc(g_AppHeap, 0, Size + 12);
	if (mem)
	{
		PDBG_ALLOC pd = (PDBG_ALLOC) mem;
		PDBG_ALLOC pd1 = CONTAINING_RECORD(Mem, DBG_ALLOC, Buffer);
		if (g_AppHeap == 0)
			__debugbreak();

		if (pd1->Sing != 0x99999999)
			__debugbreak();
		if (*(unsigned long*)((PCHAR)pd1->Buffer + pd1->Size)!= 0x99999999)
			__debugbreak();

		memset(mem, 0x99, Size + 12);
		pd->Size = (unsigned long) Size;
		memcpy(&pd->Buffer, &pd1->Buffer, pd1->Size);
	
		DbgHeapFree(Mem);
		return(&pd->Buffer);
	}

	return(mem);
}




#define Alloc(x)		DbgAlloc(x)
#define Free(x)			{DbgFree(x);x = (PVOID)BAD_PTR;}
#define Realloc(x,y)	DbgRealloc(x,y)

#define hAlloc(x)		DbgHeapAlloc(x)
#define hFree(x)		{DbgHeapFree(x);x = (PVOID)BAD_PTR;}
#define hRealloc(x,y)	DbgHeapRealloc(x,y)

#else

#define Alloc(x)		LocalAlloc(LMEM_FIXED, x)
#define Free(x)			LocalFree(x)
#define Realloc(x,y)	LocalReAlloc(x,y, LMEM_FIXED)

#define hAlloc(x)		HeapAlloc(g_AppHeap, 0, x)
#define hFree(x)		HeapFree(g_AppHeap, 0, x)
#define hRealloc(x,y)	HeapReAlloc(g_AppHeap, 0, x, y)


#endif


#define vAlloc(x)	VirtualAlloc(0, x, MEM_COMMIT | MEM_RESERVE, PAGE_READWRITE)
#define vFree(x)	VirtualFree(x, 0, MEM_RELEASE)

__inline void vProtect ( PVOID Ptr, ULONG Size )
{
	DWORD OldProtect = 0;

	VirtualProtect(Ptr,Size,PAGE_EXECUTE_READWRITE,&OldProtect);
}

#ifdef _DBG
#pragma warning(disable : 4995)
#include <strsafe.h>

#define DBG_BUFFER_SIZE 0x10000
#define DBG_PRINT_FORMAT "[VNCDLL][%s:%u][%s:%u] " //[VNCDLL][proc:id][func:line] msg

extern	LPTSTR g_CurrentProcessName; 

FORCEINLINE VOID _DbgPrintA(LPSTR Func, int Line, PCHAR fmt,...)
{
	va_list args;
	HRESULT r;
	size_t Remaining = 0;
	STRSAFE_LPSTR buffEnd = NULL;
	STRSAFE_LPSTR buff = (STRSAFE_LPSTR)LocalAlloc(LPTR, DBG_BUFFER_SIZE);

	if ( buff )
	{
		r = 
			StringCbPrintfExA(
				buff, DBG_BUFFER_SIZE, 
				&buffEnd, &Remaining, 0, 
				DBG_PRINT_FORMAT, 
				g_CurrentProcessName ? g_CurrentProcessName : "",
				GetCurrentProcessId(),
				Func, Line
				);

		if ( SUCCEEDED(r) && buffEnd && Remaining )
		{
			va_start(args,fmt);
			StringCbVPrintfA(buffEnd, Remaining, fmt, args);
			va_end(args);

			OutputDebugStringA((LPCSTR)buff);
		}
		LocalFree(buff);
	}
}

#define DbgPrint(fmt, ...) _DbgPrintA(__FUNCTION__,__LINE__,fmt,__VA_ARGS__)

#define ASSERT(x) _ASSERT(x)
  

#else
	#define DbgPrint(x, ...) 
	#define DbgPrintW(x, ...) 
	#define ASSERT(x)
/*
#define Alloc(x)		malloc(x)
#define Free(x)			free(x)
#define Realloc(x,y)	realloc(x,y)

*/	
#endif

