//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: ini.c
// $Revision: 204 $
// $Date: 2014-07-16 12:49:15 +0400 (Ср, 16 июл 2014) $
// description:
//  INI-file management routines.

#include "..\common\common.h"
#include "..\rt\crc32.h"
#include "ini.h"


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
	if (pParams = hAlloc(sizeof(INI_PARAMETERS) + Count * sizeof(INI_PARAMETER)))
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
	if (pParams = hAlloc(sizeof(INI_PARAMETERS) + Count * sizeof(INI_PARAMETER)))
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
		Status = NO_ERROR;
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

	if (DestStr = hAlloc(Size))
	{
		memset(DestStr, 0, Size);
		lstrcpy(DestStr, SourceStr);
	}
	
	return(DestStr);
}

//
//	Converts the specified address string of an IP:PORT format into two integers specifying IP-address and TCP-port values.
//
BOOL StringToTcpAddress(
	LPTSTR			pIpStr,		// address string of an IP:PORT format
	SOCKADDR_IN*	pAddress	// pointer to the structure that receives TCP/IP address
	)
{
	BOOL	Ret = FALSE;
	PCHAR	pStr, pPort;
	struct hostent* pHostEnt;

	WSADATA		WsaData;

	if (!WSAStartup(0x0201, &WsaData))
	{
		memset(pAddress, 0, sizeof(SOCKADDR_IN));
		pAddress->sin_family = AF_INET;

		if (pStr = hAlloc((_tcslen(pIpStr) + 1) * sizeof(_TCHAR)))
		{
	#ifdef _UNICODE
			wcstombs(pStr, pIpStr, _tcslen(pIpStr) + 1);
	#else
			_tcscpy(pStr, pIpStr);
	#endif

			if (!(pPort = strchr(pStr, ':')))
				pPort = pStr;
			else
			{		
				*pPort = 0;
				pPort += 1;
			}

			pAddress->sin_port = htons((USHORT)strtoul(pPort, 0, 0));

			if (!(pPort == pStr))
			{
				if (pHostEnt = gethostbyname(pStr))
				{
					pAddress->sin_addr.S_un.S_addr = *(PULONG)(*pHostEnt->h_addr_list);
					Ret = TRUE;
				}
			}
			else
			{
				pAddress->sin_addr.S_un.S_addr = 0;
				if (pAddress->sin_port)
					Ret = TRUE;
			}
			hFree(pStr);
		}	// if (pStr = AppAlloc((_tcslen(pIpStr) + 1) * sizeof(_TCHAR)))

		WSACleanup();
	}	// if (!WSAStartup(0x0201, &WsaData))

	return(Ret);
}
