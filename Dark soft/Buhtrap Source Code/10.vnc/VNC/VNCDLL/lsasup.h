//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: lsasup.h
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//	 NT security support routines: user SID and so on.


// Since there's kinda shit with sddl definitions together with ntdll, we have to redefine theese two functions manually
#if !defined(ConvertStringSecurityDescriptorToSecurityDescriptor)

WINADVAPI
BOOL
WINAPI
ConvertStringSecurityDescriptorToSecurityDescriptorA(
    IN  LPCSTR StringSecurityDescriptor,
    IN  DWORD StringSDRevision,
    OUT PSECURITY_DESCRIPTOR  *SecurityDescriptor,
    OUT PULONG  SecurityDescriptorSize OPTIONAL
    );
WINADVAPI
BOOL
WINAPI
ConvertStringSecurityDescriptorToSecurityDescriptorW(
    IN  LPCWSTR StringSecurityDescriptor,
    IN  DWORD StringSDRevision,
    OUT PSECURITY_DESCRIPTOR  *SecurityDescriptor,
    OUT PULONG  SecurityDescriptorSize OPTIONAL
    );
#ifdef UNICODE
#define ConvertStringSecurityDescriptorToSecurityDescriptor  ConvertStringSecurityDescriptorToSecurityDescriptorW
#else
#define ConvertStringSecurityDescriptorToSecurityDescriptor  ConvertStringSecurityDescriptorToSecurityDescriptorA
#endif // !UNICODE

#endif

typedef union _GUID_EX
{
	GUID	Guid;
	struct
	{
		ULONG	Data1;
		ULONG	Data2;
		ULONG	Data3;
		ULONG	Data4;
	};
} GUID_EX, *PGUID_EX;


typedef struct _STRING_LIST_ENTRY
{	
	LIST_ENTRY	Entry;
	_TCHAR		Data[];
} STRING_LIST_ENTRY, *PSTRING_LIST_ENTRY;

#define MOUNTH_SHIFT	10000

#define GUID_STR_LEN	16*2+4+2	// length of the GUID string in chars
#define szGuidStrTemp1	_T("{%08X-%04X-%04X-%04X-%08X%04X}")
#define szGuidStrTemp2	_T("%08X-%04X-%04X-%04X-%08X%04X")

#define  g_DefaultDaclStr 			_T("D:(D;OICI;GA;;;BG)(D;OICI;GA;;;AN)(A;OICI;GA;;;AU)(A;OICI;GA;;;BA)")
#define	 g_LowIntegrityDaclStr		_T("S:(ML;;NW;;;LW)")

// Application default security attributes.
extern	SECURITY_ATTRIBUTES		g_DefaultSA;

// Allocates a SECURITY_ATTRIBUTES structure with the specified security descriptor.
BOOL LsaSupInitializeSecurityAttributes(PSECURITY_ATTRIBUTES pSa, LPTSTR DaclStr);

// Frees the default security descriptor previously allocated by the LsaSupInitializeDefaultSecurityAttributes()
VOID	LsaSupFreeSecurityAttributes(PSECURITY_ATTRIBUTES pSa);

// Allocates a SECURITY_ATTRIBUTES structure with the default security descriptor.
#define LsaSupInitializeDefaultSecurityAttributes(x)	LsaSupInitializeSecurityAttributes(x, g_DefaultDaclStr)

// Allocates a SECURITY_ATTRIBUTES structure with the Low-integrity security descriptor.
#define LsaSupInitializeLowSecurityAttributes(x)		LsaSupInitializeSecurityAttributes(x, g_LowIntegrityDaclStr)

