//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: ini.c
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//  INI-file management routines.

#include "main.h"
#include "ini.h"


//
//	Caclulates CRC32 hash of the data within the specified buffer
//
ULONG Crc32(
	PCHAR pMem,		// data buffer
	ULONG uLen		// length of the buffer in bytes
	)
{
  ULONG		i, c;
  ULONG		dwSeed =  -1;

  while( uLen-- )
  {
	  c = *pMem;
	  pMem = pMem + 1;
	  
	  for( i = 0; i < 8; i++ )
	  {
		  if ( (dwSeed ^ c) & 1 )
			  dwSeed = (dwSeed >> 1) ^ 0xEDB88320;
		  else
			  dwSeed = (dwSeed >> 1);
		  c >>= 1;
	  }
  }
  return(dwSeed);
}

//
//	Parses the specified parameter string of type: NAME=VALUE, devided by the specified delimiter.
//	Allocates and fills INI_PARAMETERS sructure, cotaining parameter hashes and pointers to specific values.
//
WINERROR __stdcall IniParseParamString(
	PCHAR	ParamStr,					// parameter string to parse
	CHAR	Delimiter,					// delimiter for the parameters
	PINI_PARAMETERS* ppParameters,	// variable to return pointer to INI_PARAMETERS structure
	BOOL	bCaseSensitive				// specifies how to parse parameter names and values: case sensitive or not
	)
{
	WINERROR Status = ERROR_NOT_ENOUGH_MEMORY;
	ULONG	Count = 1;
	PCHAR	cStr, cDelim;
	PINI_PARAMETERS	pParams;

	if (cStr = StrChr(ParamStr, '?'))
		ParamStr = cStr + 1;
	
	if (!bCaseSensitive)
		strupr(ParamStr);
	cStr = ParamStr;

	// Calculating total number of parameters in string
	while(cStr = StrChr(cStr, Delimiter))
	{
		Count += 1;
		cStr += 1;
	}

	// Allocating INI_PARAMETER structure
	if (pParams = AppAlloc(sizeof(INI_PARAMETERS) + Count * sizeof(INI_PARAMETER)))
	{
		PINI_PARAMETER pParam = (PINI_PARAMETER)&pParams->Parameter;

		memset(pParams, 0, (sizeof(INI_PARAMETERS) + Count * sizeof(INI_PARAMETER)));

		pParams->Count = Count;
		do 
		{
			if (cStr = StrChr(ParamStr, Delimiter))
				*cStr = 0;
			if (cDelim = StrChr(ParamStr, '='))
			{
				*cDelim = 0;
				pParam->pValue = cDelim + 1;
			}
			
			pParam->NameHash = Crc32(ParamStr, lstrlen(ParamStr));
			ParamStr = cStr + 1;
			pParam += 1;
		} while(cStr);

		*ppParameters = pParams;
		Status = NO_ERROR;

	}	// if (pParams = AppAlloc(sizeof(INI_PARAMETERS) + Count * sizeof(INI_PARAMETER)))

	return(Status);
}

//
//	Parces the specified file containing parameter strings of type: NAME=VALUE.
//	Each parameter string starts with a new line.
//
WINERROR __stdcall IniParseParamFile(
	PCHAR	ParamStr,				// parameter string to parse
	PINI_PARAMETERS* ppParameters,	// variable to return pointer to INI_PARAMETERS structure
	BOOL	bNameCaseSensitive,		// specifies how to parse parameter names: case sensitive or not
	BOOL	bValueCaseSensitive		// specifies how to parse parameter values: case sensitive or not
	)
{
	WINERROR Status = ERROR_NOT_ENOUGH_MEMORY;
	PCHAR	pStr, cStr = ParamStr;
	ULONG	Count = 0;
	PINI_PARAMETERS	pParams;

	// Calculating maximum number of parameters in the file
	while(cStr = StrChr(cStr, '='))
	{
		Count += 1;
		cStr += 1;
	}

	// Allocating INI_PARAMETER structure
	if (pParams = AppAlloc(sizeof(INI_PARAMETERS) + Count * sizeof(INI_PARAMETER)))
	{
		PINI_PARAMETER pParam = (PINI_PARAMETER)&pParams->Parameter;
		pParams->Count = 0;

		do 
		{
			if ((pStr = StrChr(ParamStr, '\r')) || (pStr = StrChr(ParamStr, '\n')))
			{
				*pStr = 0;
				pStr += 1;
			}

			if (cStr = StrChr(ParamStr, ';'))
				*cStr = 0;

			if (cStr = StrChr(ParamStr, '='))
			{
				*cStr = 0;
				cStr += 1;

				if (!bNameCaseSensitive)
					strupr(ParamStr);
				if (!bValueCaseSensitive)
					strupr(cStr);

				StrTrim(ParamStr, " \t\r\n");
				StrTrim(cStr, " \t");

				if (*ParamStr)
				{
					pParam->NameHash = Crc32(ParamStr, lstrlen(ParamStr));
					pParam->pValue = cStr;
					pParams->Count += 1;
					pParam += 1;
				}
			}	// if (cStr = StrChr(ParamStr, '='))
		} while(ParamStr = pStr);

		*ppParameters = pParams;
	}	// if (pParams = AppAlloc(sizeof(INI_PARAMETERS) + Count * sizeof(INI_PARAMETER)))

	return(Status);
}

//
//	Scans the specified INI_PARAMETERS structure for a parameter with the specified Name hash.
//	Returns pointer to the value of the parameter or NULL if the parameter not found.
//
PCHAR __stdcall IniGetParamValue(
	ULONG NameHash,					// CRC32 hash of the name to find a value for
	PINI_PARAMETERS	pParameters		// target parameters
	)
{
	PCHAR	pValue = NULL;
	ULONG	i;

	if (pParameters)
	{
		for (i=0; i<pParameters->Count; i++)
		{
			if (pParameters->Parameter[i].NameHash == NameHash)
			{
				pValue = pParameters->Parameter[i].pValue;
				break;
			}
		}	// for (i=0; i<pParameters->Count; i++)
	}	// if (pParameters)

	return(pValue);
}

//
//	Allocates a memory buffer of the specified MinimumLength and duplicates the specified source string into it.
//	If MinimumLength is larger then a length of the specified source string then unused buffer is filled with zeoroes. 
//
LPTSTR __stdcall IniDupStr(
	LPTSTR	SourceStr,		// a string to duplicate
	ULONG	MinimumLength	// minimum size of the string buffer in chars
	)
{
	LPTSTR	DestStr;
	ULONG	Size = max((lstrlen(SourceStr) + 1) * sizeof(_TCHAR), MinimumLength * sizeof(_TCHAR));

	if (DestStr = AppAlloc(Size))
	{
		memset(DestStr, 0, Size);
		lstrcpy(DestStr, SourceStr);
	}
	
	return(DestStr);
}

//
//	Allocates a buffer and loads the specified file into it.
//
WINERROR __stdcall IniLoadFile(
	LPTSTR	FileName,	// full path to the file to load
	PCHAR*	pBuffer,	// receives a pointer to the buffer containing the loaded file
	PULONG	pSize		// receives the size of the loaded file in bytes
	)
{
	WINERROR Status = ERROR_UNSUCCESSFULL;
	HANDLE	hFile;
	ULONG	Size, bRead;
	PCHAR	Buffer = NULL;

	do	// not a loop
	{
		hFile = CreateFile(FileName, GENERIC_READ, FILE_SHARE_READ, NULL, OPEN_EXISTING, FILE_ATTRIBUTE_NORMAL, 0);
		if (hFile == INVALID_HANDLE_VALUE)
			break;
	
		if ((Size = GetFileSize(hFile, NULL)) == 0)
		{
			Status = ERROR_NO_DATA;
			break;
		}

		// Allocating a buffer with one extra char at the end for a NULL-char to be able to work with a text file.
		if (!(Buffer = AppAlloc(Size + sizeof(_TCHAR))))
			break;
			
		if (!ReadFile(hFile, Buffer, Size, &bRead, NULL))
			break;

		if (Size != bRead)
		{
			Status = ERROR_READ_FAULT;
			break;
		}

		Buffer[Size] = 0;

		*pBuffer = Buffer;
		*pSize = Size;
		Status = NO_ERROR;
	} while(FALSE);

	if (Status == ERROR_UNSUCCESSFULL)
		Status = GetLastError();

	if (hFile != INVALID_HANDLE_VALUE)
		CloseHandle(hFile);

	if (Buffer && (Status != NO_ERROR))
		AppFree(Buffer);

	return(Status);
}


//
//	Returns a full path to the module of the current process specified by handle.
//
WINERROR IniGetModulePath(
	HMODULE		hModule,		// a handle of the module within current process
	LPTSTR*		pModulePath		// returned module path
)
{
	WINERROR Status = NO_ERROR;
	ULONG bSize = MAX_PATH;
	ULONG rSize = 0;
	LPTSTR ModulePath = (LPTSTR)AppAlloc(MAX_PATH_BYTES);

	while ((ModulePath) && ((rSize = GetModuleFileName(hModule, ModulePath, bSize))!=0) && (bSize == rSize))
	{
		bSize += MAX_PATH;
		AppFree(ModulePath);
		ModulePath = (LPTSTR)AppAlloc(bSize*sizeof(_TCHAR));
	}

	if (ModulePath)
	{
		if (rSize)
		{
			*pModulePath = ModulePath;
		}
		else
		{
			Status = GetLastError();
			AppFree(ModulePath);
		}
	}
	else
		Status = ERROR_NOT_ENOUGH_MEMORY;

	return(Status);
}
