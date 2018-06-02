/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: dbg.h
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
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
	void* mem = malloc(Size + sizeof(DBG_ALLOC) + sizeof(long));
	if (mem)
	{
		PDBG_ALLOC pd = (PDBG_ALLOC) mem;
		memset(mem, 0xcc, Size + sizeof(DBG_ALLOC) + sizeof(long));
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
	void* mem = malloc(Size + sizeof(DBG_ALLOC) + sizeof(long));
	if (mem)
	{
		PDBG_ALLOC pd = (PDBG_ALLOC) mem;
		PDBG_ALLOC pd1 = CONTAINING_RECORD(Mem, DBG_ALLOC, Buffer);
		if (pd1->Sing != 0xcccccccc)
			__debugbreak();

		memset(mem, 0xcc, Size + sizeof(DBG_ALLOC) + sizeof(long));
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

	mem = HeapAlloc(g_AppHeap, 0, Size + sizeof(DBG_ALLOC) + sizeof(long));
	if (mem)
	{
		PDBG_ALLOC pd = (PDBG_ALLOC) mem;
		memset(mem, 0x99, Size + sizeof(DBG_ALLOC) + sizeof(long));
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
	void* mem = HeapAlloc(g_AppHeap, 0, Size + sizeof(DBG_ALLOC) + sizeof(long));
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

		memset(mem, 0xcc, Size + sizeof(DBG_ALLOC) + sizeof(long));
		pd->Size = (unsigned long) Size;
		memcpy(&pd->Buffer, &pd1->Buffer, pd1->Size);
	
		DbgHeapFree(Mem);
		return(&pd->Buffer);
	}

	return(mem);
}




#define Alloc(x)		DbgAlloc(x)
#define Free(x)			{DbgFree(x); *(PVOID*)&x = (PVOID)BAD_PTR;}
#define Realloc(x,y)	DbgRealloc(x,y)

#define hAlloc(x)		DbgHeapAlloc(x)
#define hFree(x)		{DbgHeapFree(x);*(PVOID*)&x = (PVOID)BAD_PTR;}
#define hRealloc(x,y)	DbgHeapRealloc(x,y)

#else

#define Alloc(x)		malloc(x)
#define Free(x)			free(x)
#define Realloc(x,y)	realloc(x,y)

#define hAlloc(x)		HeapAlloc(g_AppHeap, 0, x)
#define hFree(x)		HeapFree(g_AppHeap, 0, x)
#define hRealloc(x,y)	HeapReAlloc(g_AppHeap, 0, x, y)


#endif

// MEM_COMMIT function guarantees that when the caller later initially accesses the memory, the contents will be zero. 
#define vAlloc(x)	VirtualAlloc(0, x, MEM_COMMIT | MEM_RESERVE, PAGE_READWRITE)
#define vFree(x)	VirtualFree(x, 0, MEM_RELEASE)


#ifdef _DBG



#pragma warning(disable:4996) // 'sprintf': This function or variable may be unsafe.
#define  DbgPrint(args, ...) \
		{ char buff[0x400]; \
		  wsprintfA((char*)&buff, args, __VA_ARGS__); \
		  OutputDebugStringA((LPCSTR)&buff); } 

#define  DbgPrintW(args, ...) \
		{ wchar_t buff[0x800]; \
		  wsprintfW((wchar_t*)&buff, args, __VA_ARGS__); \
		  OutputDebugStringW((LPCWSTR)&buff); } 

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

