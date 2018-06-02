//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 2.5
//	
// module: str.c
// $Revision: 34 $
// $Date: 2012-12-12 01:28:40 +0400 (—р, 12 дек 2012) $
// description:
//	Strings manipulation functions. 

#include "project.h"
#include "str.h"
#include <tchar.h>
#include "crc32.h"
//
// converts hex string to binary 
//

BOOL HexStrToBufferW(
	LPWSTR	HexStr,
	PCHAR	Buffer
	)
{
	BOOL	Ret = FALSE;
	ULONG	Len = lstrlenW(HexStr);
	CHAR	Byte = 0;

	while(Len)
	{
		WCHAR	c = 0;
		CHAR	b = 0;

		c = *HexStr;

		if (c >= L'0' && c <= L'9')
			b = (CHAR)(c - L'0');
		else if (c >= L'A' && c <= L'F')
			b = (CHAR)(c - L'A' + 0xa);
		else if (c >= L'a' && c <= L'f')
			b = (CHAR)(c - L'a' + 0xa);
		else
			break;

		if (Len % 2)
		{
			Byte += b;
			*Buffer = Byte;
			Buffer += 1;
		}
		else
			Byte = (b << 4);

		HexStr += 1;
		Len -= 1;
	}	// while(Len)

	if (Len == 0)
		Ret = TRUE;

	return(Ret);
}

BOOL HexStrToBufferA(
	LPSTR	HexStr,
	PCHAR	Buffer
	)
{
	BOOL	Ret = FALSE;
	ULONG	Len = lstrlenA(HexStr);
	CHAR	Byte;

	while(Len)
	{
		CHAR	c;
		CHAR	b;

		c = *HexStr;

		if (c >= '0' && c <= '9')
			b = (CHAR)(c - '0');
		else if (c >= 'A' && c <= 'F')
			b = (CHAR)(c - 'A' + 0xa);
		else if (c >= 'a' && c <= 'f')
			b = (CHAR)(c - 'a' + 0xa);
		else
			break;

		if (Len % 2)
		{
			Byte += b;
			*Buffer = Byte;
			Buffer += 1;
		}
		else
			Byte = (b << 4);

		HexStr += 1;
		Len -= 1;
	}	// while(Len)

	if (Len == 0)
		Ret = TRUE;

	return(Ret);
}

// converts hex char to digit
BYTE HexToByteW(WCHAR c )
{
	BYTE	b = 0;
	if (c >= L'0' && c <= L'9')
		b = (CHAR)(c - '0');
	else if (c >= L'A' && c <= L'F')
		b = (CHAR)(c - L'A' + 0xa);
	else if (c >= L'a' && c <= L'f')
		b = (CHAR)(c - L'a' + 0xa);
	return b;
}

// byte to wchar
void StrByteToCharW(BYTE bt, LPWSTR buf)
{
	buf[0] = (BYTE)(bt >> 4);
	buf[1] = (BYTE)(bt & 0xF);

	buf[0] += (buf[0] > 0x9 ? ('A' - 0xA) : '0');
	buf[1] += (buf[1] > 0x9 ? ('A' - 0xA) : '0');
}

void StrByteToCharA(BYTE bt, LPSTR buf)
{
	buf[0] = (BYTE)(bt >> 4);
	buf[1] = (BYTE)(bt & 0xF);

	buf[0] += (buf[0] > 0x9 ? ('A' - 0xA) : '0');
	buf[1] += (buf[1] > 0x9 ? ('A' - 0xA) : '0');
}

// convert buffer to hex char string
void StrBufferToHexW(const void *binary, DWORD binarySize, LPWSTR string)
{
	DWORD i;
	for( i = 0; i < binarySize; i++, string += 2)StrByteToCharW(((LPBYTE)binary)[i], string);
	*string = 0;
}

void StrBufferToHexA(const void *binary, DWORD binarySize, LPSTR string)
{
	DWORD i;
	for( i = 0; i < binarySize; i++, string += 2)StrByteToCharA(((LPBYTE)binary)[i], string);
	*string = 0;
}

// 
// validates multisz string
// 
BOOL StrIsValidMultiStringW(const LPWSTR string, DWORD size)
{
	return (string != NULL && size >= 2 && string[size - 1] == 0 && (string)[size - 2] == 0);
}

BOOL StrIsValidMultiStringA(const LPSTR string, DWORD size)
{
	return (string != NULL && size >= 2 && string[size - 1] == 0 && (string)[size - 2] == 0);
}

//
// returns substring from multisz
//
LPSTR StrMultiStringGetIndexA(LPSTR string, int index)
{
	int i;
	if(index == 0)return string;
	for(i = 0; ; string++){
		if(*string == 0)
		{
			LPSTR c = string + 1;
			if(*c == 0)break; //eol.
			if(++i == index)return c;
		}
	}
	return NULL;
}

LPWSTR StrMultiStringGetIndexW(LPWSTR string, int index)
{
	int i;
	if(index == 0){
		return string;
	}
	for( i = 0; ; string++){
		if(*string == 0)
		{
			LPWSTR c = string + 1;
			if(*c == 0)break; //eol.
			if(++i == index)return c;
		}
	}
	return NULL;
}

//
//	Checks the specified path string if it contains an environment variable and if so resolves it's value.
//	Returns new resolved path string or NULL.
//
LPWSTR	StrExpandEnvironmentVariablesW(
	LPWSTR	Path	// target path string to resolve
	)
{
	LPWSTR	NewPath = NULL;
	ULONG	Len;

	if (Len = ExpandEnvironmentStringsW(Path, NULL, 0))
	{
		if (NewPath = AppAlloc(Len * sizeof(WCHAR)))
		{
			if (!ExpandEnvironmentStringsW(Path, NewPath, Len))
			{
				AppFree(NewPath);
				NewPath = NULL;
			}	// if (!ExpandEnvironmentStringsW(Path, NewPath, Len))
		}	// if (NewPath = AppAlloc(Len))
	}	// if ((Len = ExpandEnvironmentStringsW(Path, NULL, 0)) && Len > OldLen)

	return(NewPath);
}


//
//	Checks the specified path string if it contains an environment variable and if so resolves it's value.
//	Returns new resolved path string or NULL.
//
LPSTR	StrExpandEnvironmentVariablesA(
	LPSTR	Path	// target path string to resolve
	)
{
	LPSTR	NewPath = NULL;
	ULONG	Len;

	if (Len = ExpandEnvironmentStringsA(Path, NULL, 0))
	{
		if (NewPath = AppAlloc(Len))
		{
			if (!ExpandEnvironmentStringsA(Path, NewPath, Len))
			{
				AppFree(NewPath);
				NewPath = NULL;
			}	// if (!ExpandEnvironmentStringsW(Path, NewPath, Len))
		}	// if (NewPath = AppAlloc(Len))
	}	// if ((Len = ExpandEnvironmentStringsW(Path, NULL, 0)) && Len > OldLen)

	return(NewPath);
}

// equals to strdup
LPWSTR AllocateAndCopyWideString(LPCWSTR inputString)
{
	LPWSTR outputString = NULL;

	outputString = (LPWSTR)AppAlloc((wcslen(inputString) + 1) * sizeof(WCHAR));
	if (outputString != NULL)
	{
		lstrcpyW(outputString, inputString);
	}
	return outputString;
}

LPSTR AllocateAndCopyWideStringToString(LPCWSTR inputString)
{
	LPSTR outputString = NULL;
	int Length = wcslen(inputString) + 1;

	outputString = (LPSTR)AppAlloc(Length * sizeof(CHAR));
	if (outputString != NULL)
	{
		W2AHelper(outputString, inputString,Length);
	}
	return outputString;
}

// Compare the two strings for lexical order.  Stops the comparison
// when the following occurs: (1) strings differ, (2) the end of the
// strings is reached, or (3) count characters have been compared.
// For the purposes of the comparison, upper case characters are
// converted to lower case.
 
int StrCompareNIWA(LPWSTR first,LPSTR last,int count)
{
	if(count)
	{
		int f=0;
		int l=0;

		do
		{

			if ( ((f = (wchar_t)(*(first++))) >= L'A') &&
				(f <= L'Z') )
				f -= L'A' - L'a';

			if ( ((l = (unsigned char)(*(last++))) >= 'A') &&
				(l <= 'Z') )
				l -= 'A' - 'a';

		}
		while ( --count && f && (f == l) );

		return ( f - l );
	} //else

	return 0;
}

int StrCmpIWA (LPCWSTR dst,LPCSTR src )
{
    int f, l;

    do
    {
        if ( ((f = (wchar_t)(*(dst++))) >= L'A') && (f <= L'Z') )
            f -= L'A' - L'a';
        if ( ((l = (unsigned char)(*(src++))) >= 'A') && (l <= 'Z') )
            l -= 'A' - 'a';
    }
    while ( f && (f == l) );

    return(f - l);
}

DWORD StrHashA(LPSTR str)
{
	DWORD Hash = 0;
	LPSTR strUPR = NULL;
	if ( str )
	{
		strUPR = StrDupA(str);
		if ( strUPR ){
			strupr(strUPR);
			Hash = Crc32(strUPR,strlen(strUPR));
			LocalFree ( strUPR );
		}
	}
	return Hash;
}
