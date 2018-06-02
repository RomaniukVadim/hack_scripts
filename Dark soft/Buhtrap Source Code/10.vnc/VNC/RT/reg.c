#include "project.h"
#include "str.h"

static TCHAR szPrevSchemeKey[]=_T(".Prev");
static TCHAR szCurSchemeKey[]=_T(".current");

void RegMoveFromKeyToKey(TCHAR *lpFrom,TCHAR *lpTo)
{
	HKEY hRootKey;
	DWORD i,j;
	if (RegOpenKeyEx(HKEY_CURRENT_USER,_T("AppEvents\\Schemes\\Apps"),0,KEY_WOW64_64KEY+KEY_READ+KEY_WRITE,&hRootKey) == ERROR_SUCCESS)
	{
		DWORD dwKeysNum;
		RegQueryInfoKey(hRootKey,NULL,NULL,NULL,&dwKeysNum,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
		if (dwKeysNum)
		{
			for ( i=0; i < dwKeysNum; i++)
			{
				TCHAR szSubKey[260];
				DWORD dwSize=sizeof(szSubKey);
				if (RegEnumKeyEx(hRootKey,i,szSubKey,&dwSize,NULL,NULL,NULL,NULL) == ERROR_SUCCESS)
				{
					HKEY hSubKey;
					if (RegOpenKeyEx(hRootKey,szSubKey,0,KEY_WOW64_64KEY+KEY_READ+KEY_WRITE,&hSubKey) == ERROR_SUCCESS)
					{
						DWORD dwSubKeysNum;
						RegQueryInfoKey(hSubKey,NULL,NULL,NULL,&dwSubKeysNum,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
						if (dwSubKeysNum)
						{
							for ( j=0; j < dwSubKeysNum; j++)
							{
								dwSize=sizeof(szSubKey);
								if (RegEnumKeyEx(hSubKey,j,szSubKey,&dwSize,NULL,NULL,NULL,NULL) == ERROR_SUCCESS)
								{
									HKEY hSubSubKey;
									if (RegOpenKeyEx(hSubKey,szSubKey,0,KEY_WOW64_64KEY+KEY_READ+KEY_WRITE,&hSubSubKey) == ERROR_SUCCESS)
									{
										TCHAR szData[260];
										LONG dwSize=sizeof(szData);
										if (RegQueryValue(hSubSubKey,lpFrom,szData,&dwSize) == ERROR_SUCCESS)
										{
											TCHAR cFakeBuf=0;
											RegSetValue(hSubSubKey,lpTo,REG_SZ,szData,dwSize);											
											RegSetValue(hSubSubKey,lpFrom,REG_SZ,&cFakeBuf,sizeof(cFakeBuf));
										}
										RegCloseKey(hSubSubKey);
									}
								}
							}
						}
						RegCloseKey(hSubKey);
					}
				}
			}
		}
		RegCloseKey(hRootKey);
	}
	return;
}

typedef struct _KNOWN_KEY
{
	HANDLE hKey;
	LPWSTR KeyName;
}KNOWN_KEY,*PKNOWN_KEY;

LPWSTR RegQueryKeyNameW(IN HKEY hKey )
{
	LPWSTR szKeyName = NULL;
	PKEY_NAME_INFORMATION pKeyInfo = NULL;
	LONG ntStatus;
	ULONG ReturnLength;
	ULONG  NameLength;
	do 
	{
		/*
		*	get driver registry entry info size
		*/
		ntStatus =
			ZwQueryKey(
				hKey,
				KeyNameInformation,
				NULL,0,
				&ReturnLength
				);

		if ( ntStatus != STATUS_BUFFER_OVERFLOW && 
			ntStatus != STATUS_BUFFER_TOO_SMALL )
		{
			break;
		}
		
		pKeyInfo = (PKEY_NAME_INFORMATION)AppAlloc(ReturnLength);
		if ( !pKeyInfo ) {
			break;
		}

		/*
		*	query driver registry entry info
		*/
		ntStatus =
			ZwQueryKey(
				hKey,
				KeyNameInformation,
				pKeyInfo,
				ReturnLength,
				&ReturnLength
				);

		if ( !NT_SUCCESS( ntStatus ) ){
			break;
		}

		NameLength = pKeyInfo->NameLength;
		RtlCopyMemory(pKeyInfo,pKeyInfo->Name,NameLength);

		szKeyName = (LPWSTR)pKeyInfo;
		szKeyName[NameLength/sizeof(WCHAR)] = 0;

	} while ( FALSE );

	if ( ntStatus != 0 ){
		if ( pKeyInfo ){
			AppFree ( pKeyInfo );
		}
	}
	return szKeyName;
}

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
	)
{
	WINERROR Status = NO_ERROR;
	HANDLE	hKey = 0;	
	ULONG	Type, Size = 0;
	PCHAR	Value = NULL;

	if ((Status = RegOpenKeyExW(ParentKey, KeyName, 0, KEY_QUERY_VALUE, (PHKEY)&hKey)) == NO_ERROR)
	{
		Status = RegQueryValueExW(hKey, ValueName, 0, &Type, NULL, &Size);
		if (Size)
		{
			if (Value = AppAlloc(Size))
			{
				if ((Status = RegQueryValueExW(hKey, ValueName, 0, &Type, Value, &Size)) == NO_ERROR)
				{
					*pValue = Value;
					if (pSize)
						*pSize = Size;
					if (pType)
						*pType = Type;
				}
				else
					AppFree(Value);
			}	// if (Value = AppAlloc(Size))
			else
				Status = ERROR_NOT_ENOUGH_MEMORY;
		}	// if (Size)

		RegCloseKey(hKey);
	}	// if ((Status = RegOpenKey(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)

	return(Status);
}

WINERROR LoadRegistryValueA(
	HKEY	ParentKey,	// parent key name
	LPSTR	KeyName,	// target key name
	LPSTR	ValueName,	// value name
	PCHAR*	pValue,		// receives pointer the allocated memory containing the specified value
	PULONG	pSize,		// receives size of the value
	PULONG	pType		// receives type of the value
	)
{
	WINERROR Status = NO_ERROR;
	HANDLE	hKey = 0;	
	ULONG	Type, Size = 0;
	PCHAR	Value = NULL;

	if ((Status = RegOpenKeyA(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)
	{
		Status = RegQueryValueExA(hKey, ValueName, 0, &Type, NULL, &Size);
		if (Size)
		{
			if (Value = AppAlloc(Size))
			{
				if ((Status = RegQueryValueExA(hKey, ValueName, 0, &Type, Value, &Size)) == NO_ERROR)
				{
					*pValue = Value;
					if (pSize)
						*pSize = Size;
					if (pType)
						*pType = Type;
				}
				else
					AppFree(Value);
			}	// if (Value = AppAlloc(Size))
			else
				Status = ERROR_NOT_ENOUGH_MEMORY;
		}	// if (Size)

		RegCloseKey(hKey);
	}	// if ((Status = RegOpenKey(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)

	return(Status);
}

//
//	Reads dword value from registry.
//
WINERROR RegReadDwordW(
	HKEY	ParentKey,	// parent key name
	LPWSTR	KeyName,	// target key name
	LPWSTR	ValueName,	// value name
	PDWORD	pValue
	)
{
	WINERROR Status = NO_ERROR;
	HANDLE	hKey = 0;	
	ULONG	Type, Size = sizeof(DWORD);
	DWORD	Value;

	if ((Status = RegOpenKeyW(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)
	{
		Status = RegQueryValueExW(hKey, ValueName, 0, &Type, (LPBYTE)&Value, &Size);
		if (Status == NO_ERROR)
		{
			if ( Type == REG_DWORD && Size == sizeof(DWORD) ){
				*pValue = Value;
			}else{
				Status = ERROR_INVALID_PARAMETER;
			}	
		}	// if (Status == NO_ERROR)

		RegCloseKey(hKey);
	}	// if ((Status = RegOpenKey(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)

	return(Status);
}

WINERROR RegReadDwordA(
	HKEY	ParentKey,	// parent key name
	LPSTR	KeyName,	// target key name
	LPSTR	ValueName,	// value name
	PDWORD	pValue
	)
{
	WINERROR Status = NO_ERROR;
	HANDLE	hKey = 0;	
	ULONG	Type, Size = sizeof(DWORD);
	DWORD	Value;

	if ((Status = RegOpenKeyA(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)
	{
		Status = RegQueryValueExA(hKey, ValueName, 0, &Type, (LPBYTE)&Value, &Size);
		if (Status == NO_ERROR)
		{
			if ( Type == REG_DWORD && Size == sizeof(DWORD) ){
				*pValue = Value;
			}else{
				Status = ERROR_INVALID_PARAMETER;
			}	
		}	// if (Status == NO_ERROR)

		RegCloseKey(hKey);
	}	// if ((Status = RegOpenKey(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)

	return(Status);
}

DWORD RegReadStringW(
	HKEY	ParentKey,	// parent key name
	LPWSTR	KeyName,	// target key name
	LPWSTR	ValueName,	// value name
	LPWSTR*	pValue,		// receives pointer the allocated memory containing the specified value
	PULONG	pSize		// receives size of the value
	)
{
	WINERROR Status;
	ULONG	Type, Size = 0;
	LPWSTR	Value = NULL;
	DWORD i;

	// load value
	Status = 
		LoadRegistryValueW(
			ParentKey,
			KeyName,
			ValueName,
			(PCHAR*)&Value,
			&Size,
			&Type
			);

	if ( Status == NO_ERROR )
	{
		if ( (Size % sizeof(WCHAR)) == 0 && (Type == REG_SZ || Type == REG_EXPAND_SZ)){

			//If the data has the REG_SZ, REG_MULTI_SZ or REG_EXPAND_SZ type, the string may not have been
			//stored with the proper terminating null characters.
			if(Size == 0)
			{
				*Value = 0;
			}
			else
			{
				i = (Size / sizeof(WCHAR)) - 1; //the last symbol's index
				// the last symbol is \0, it means that the size is equal to the symbol's position
				if ( Value[i] == 0 ) {
					Size = i; 
				} else {
					Value[i] = 0;
					Size = i; 
				}
			}

			if ( Size > 2 )
			{
				LPWSTR szExpandValue = StrExpandEnvironmentVariablesW(Value);
				if ( Value != szExpandValue ){
					AppFree ( Value );
				}
				Value = szExpandValue;
			}
		}else{
			Status = ERROR_INVALID_PARAMETER;
		}
	}

	if ( Status == NO_ERROR ){
		*pValue = Value;
		*pSize = Size;
	}else{
		if ( Value ){
			AppFree ( Value );
		}
		*pValue = NULL;
		*pSize = 0;
	}
	return Status;
}

DWORD RegReadStringA(
	HKEY	ParentKey,	// parent key name
	LPSTR	KeyName,	// target key name
	LPSTR	ValueName,	// value name
	PCHAR*	pValue,		// receives pointer the allocated memory containing the specified value
	PULONG	pSize		// receives size of the value
	)
{
	WINERROR Status;
	ULONG	Type, Size = 0;
	LPSTR	Value = NULL;
	DWORD i;

	// load value
	Status = 
		LoadRegistryValueA(
			ParentKey,
			KeyName,
			ValueName,
			&Value,
			&Size,
			&Type
			);

	if ( Status == NO_ERROR )
	{
		if ( (Size % sizeof(CHAR)) == 0 && (Type == REG_SZ || Type == REG_EXPAND_SZ)){

			//If the data has the REG_SZ, REG_MULTI_SZ or REG_EXPAND_SZ type, the string may not have been
			//stored with the proper terminating null characters.
			if(Size == 0)
			{
				*Value = 0;
			}
			else
			{
				i = (Size / sizeof(CHAR)) - 1; //the last symbol's index
				// the last symbol is \0, it means that the size is equal to the symbol's position
				if ( Value[i] == 0 ) {
					Size = i; 
				} else {
					Value[i] = 0;
					Size = i; 
				}
			}

			if( Size > 2 )
			{
				LPSTR szExpandValue = StrExpandEnvironmentVariablesA(Value);
				if ( Value != szExpandValue ){
					AppFree ( Value );
				}
				Value = szExpandValue;
			}
		}else{
			Status = ERROR_INVALID_PARAMETER;
		}
	}

	if ( Status == NO_ERROR ){
		*pValue = Value;
		*pSize = Size;
	}else{
		if ( Value ){
			AppFree ( Value );
		}
		*pValue = NULL;
		*pSize = 0;
	}
	return Status;
}

//
// deletes single value from key
// 
WINERROR RegDeleteValueExA(
	HKEY	ParentKey,	// parent key name
	LPSTR	KeyName,	// target key name
	LPSTR	ValueName	// value name
	)
{
	WINERROR Status = NO_ERROR;
	HANDLE	hKey = 0;	

	if ((Status = RegOpenKeyA(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)
	{
		Status = RegDeleteValueA(hKey, ValueName);
		RegCloseKey(hKey);
	}	// if ((Status = RegOpenKey(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)

	return(Status);
}

WINERROR RegDeleteValueExW(
	HKEY	ParentKey,	// parent key name
	LPWSTR	KeyName,	// target key name
	LPWSTR	ValueName	// value name
	)
{
	WINERROR Status = NO_ERROR;
	HANDLE	hKey = 0;	

	if ((Status = RegOpenKeyW(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)
	{
		Status = RegDeleteValueW(hKey, ValueName);
		RegCloseKey(hKey);
	}	// if ((Status = RegOpenKey(ParentKey, KeyName, (PHKEY)&hKey)) == NO_ERROR)

	return(Status);
}