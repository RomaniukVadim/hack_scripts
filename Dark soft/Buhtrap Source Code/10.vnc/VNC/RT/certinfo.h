//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.6
//	
// module: certinfo.h
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Tue, 23 Jul 2013) $
// description: 
//	functions for working with embedded certificates

#ifndef __CERTINFO_H_
#define __CERTINFO_H_

typedef struct _CERT_PUBLISHERINFO
{
	LPWSTR lpszSubjectName; // company
	LPWSTR lpszProgramName;
	LPWSTR lpszPublisherLink;
	LPWSTR lpszMoreInfoLink;
} CERT_PUBLISHERINFO, *PCERT_PUBLISHERINFO;

DWORD 
	CryptExeSignerInfoW(
		IN LPWSTR FileName,
		IN OUT PCERT_PUBLISHERINFO PublisherInfo
		);

#endif //__CERTINFO_H_