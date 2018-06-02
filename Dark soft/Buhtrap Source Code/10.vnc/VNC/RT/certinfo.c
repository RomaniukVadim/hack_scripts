//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: certinfo.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Tue, 23 Jul 2013) $
// description: 
//	functions for working with embedded certificates

#include "project.h"
#include <WinCrypt.h>
#include <WinTrust.h>
#include "certinfo.h"
#include "str.h"

#define ENCODING (X509_ASN_ENCODING | PKCS_7_ASN_ENCODING)

static PCRYPT_ATTRIBUTE GetOPUS(PCMSG_SIGNER_INFO pSignerInfo)
{
	DWORD n;
	// Loop through authenticated attributes and find
	// SPC_SP_OPUS_INFO_OBJID OID.
	for ( n = 0; n < pSignerInfo->AuthAttrs.cAttr; n++)
	{           
		if (lstrcmpA(SPC_SP_OPUS_INFO_OBJID, 
			pSignerInfo->AuthAttrs.rgAttr[n].pszObjId) == 0)
		{
			return &pSignerInfo->AuthAttrs.rgAttr[n];
		}
	}
	return NULL;
}

static
DWORD 
	GetProgAndPublisherInfo(
		PCMSG_SIGNER_INFO pSignerInfo,
		LPWSTR *lpszProgramName,
		LPWSTR *lpszPublisherLink,
		LPWSTR *lpszMoreInfoLink
		)
{
	BOOL fReturn = FALSE;
	PSPC_SP_OPUS_INFO OpusInfo = NULL;  
	DWORD dwData;
	BOOL fResult;
	PCRYPT_ATTRIBUTE rgAttr;
	DWORD Error = NO_ERROR;

	LPWSTR ProgramName   = NULL;
	LPWSTR PublisherLink = NULL;
	LPWSTR MoreInfoLink  = NULL;

	do 
	{
		// Loop through authenticated attributes and find
		// SPC_SP_OPUS_INFO_OBJID OID.
		rgAttr = GetOPUS(pSignerInfo);
		if ( rgAttr == NULL ){
			Error = SEC_E_CERT_UNKNOWN;
			DbgPrint("cannot find SPC_SP_OPUS_INFO_OBJID\n");
			break;
		}

		// Get Size of SPC_SP_OPUS_INFO structure.
		fResult = 
			CryptDecodeObject(
				ENCODING,
				SPC_SP_OPUS_INFO_OBJID,
				rgAttr->rgValue[0].pbData,
				rgAttr->rgValue[0].cbData,
				0,
				NULL,
				&dwData);
		if (!fResult){
			Error = GetLastError();
			DbgPrint("CryptDecodeObject failed with %x\n",Error);
			break;
		}

		// Allocate memory for SPC_SP_OPUS_INFO structure.
		OpusInfo = (PSPC_SP_OPUS_INFO)AppAlloc(dwData);
		if (!OpusInfo){
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("Unable to allocate memory for Publisher Info.\n");
			break;
		}

		// Decode and get SPC_SP_OPUS_INFO structure.
		fResult = 
			CryptDecodeObject(
				ENCODING,
				SPC_SP_OPUS_INFO_OBJID,
				rgAttr->rgValue[0].pbData,
				rgAttr->rgValue[0].cbData,
				0,
				OpusInfo,
				&dwData
				);
		if (!fResult){
			Error = GetLastError();
			DbgPrint("CryptDecodeObject failed with %x\n",Error);
			break;
		}

		// Fill in Program Name if present.
		if (OpusInfo->pwszProgramName){
			ProgramName = 
				AllocateAndCopyWideString(OpusInfo->pwszProgramName);
		}
		else
			ProgramName = NULL;

		// Fill in Publisher Information if present.
		if (OpusInfo->pPublisherInfo)
		{
			switch (OpusInfo->pPublisherInfo->dwLinkChoice)
			{
			case SPC_URL_LINK_CHOICE:
				PublisherLink = 
					AllocateAndCopyWideString(OpusInfo->pPublisherInfo->pwszUrl);
				break;

			case SPC_FILE_LINK_CHOICE:
				PublisherLink = 
					AllocateAndCopyWideString(OpusInfo->pPublisherInfo->pwszFile);
				break;

			default:
				PublisherLink = NULL;
				break;
			}
		}
		else
		{
			PublisherLink = NULL;
		}

		// Fill in More Info if present.
		if (OpusInfo->pMoreInfo)
		{
			switch (OpusInfo->pMoreInfo->dwLinkChoice)
			{
			case SPC_URL_LINK_CHOICE:
				MoreInfoLink = 
					AllocateAndCopyWideString(OpusInfo->pMoreInfo->pwszUrl);
				break;

			case SPC_FILE_LINK_CHOICE:
				MoreInfoLink = 
					AllocateAndCopyWideString(OpusInfo->pMoreInfo->pwszFile);
				break;

			default:
				MoreInfoLink = NULL;
				break;
			}
		}               
		else
		{
			MoreInfoLink = NULL;
		}
	} while ( FALSE );

	if (OpusInfo != NULL) {
		AppFree(OpusInfo);
	}

	*lpszProgramName   = ProgramName;
	*lpszPublisherLink = PublisherLink;
	*lpszMoreInfoLink  = MoreInfoLink;

	return Error;
}

DWORD GetSubjectName(PCCERT_CONTEXT pCertContext,LPWSTR *lpszSubjectName)
{
	DWORD Error = NO_ERROR;
	LPWSTR szName = NULL;
	DWORD dwData;

	do
	{
		// Get Subject name size.
		if (!(dwData = CertGetNameStringW(pCertContext, 
			CERT_NAME_SIMPLE_DISPLAY_TYPE,
			0,
			NULL,
			NULL,
			0)))
		{
			Error = GetLastError();
			DbgPrint("CertGetNameString failed, err=%#x\n",Error);
			break;
		}

		// Allocate memory for subject name.
		szName = (LPWSTR)AppAlloc(dwData * sizeof(WCHAR));
		if (!szName)
		{
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("Unable to allocate memory for subject name, err=%#x\n",Error);
			break;
		}

		// Get subject name.
		if (!(CertGetNameStringW(pCertContext, 
			CERT_NAME_SIMPLE_DISPLAY_TYPE,
			0,
			NULL,
			szName,
			dwData)))
		{
			Error = GetLastError();
			DbgPrint("CertGetNameString failed, err=%#x\n",Error);
			break;
		}

		// Print Subject Name.
		*lpszSubjectName = szName;
	}while ( FALSE );

	if ( Error != NO_ERROR && szName ){
		AppFree ( szName );
	}

	return Error;
}

DWORD 
	CryptExeSignerInfoW(
		IN LPWSTR FileName,
		IN OUT PCERT_PUBLISHERINFO PublisherInfo
		)
{
	HCERTSTORE hStore = NULL;
	HCRYPTMSG hMsg = NULL; 
	BOOL fResult;   
	DWORD dwEncoding, dwContentType, dwFormatType;
	CERT_INFO CertInfo;
	PCCERT_CONTEXT pCertContext = NULL;
	PCMSG_SIGNER_INFO pSignerInfo = NULL;
	DWORD dwSignerInfo;
	DWORD Error = NO_ERROR;

	LPWSTR lpszSubjectName   = NULL; // company
	LPWSTR lpszProgramName   = NULL;
	LPWSTR lpszPublisherLink = NULL;
	LPWSTR lpszMoreInfoLink  = NULL;

	do
	{

		// Get message handle and store handle from the signed file.
		fResult = 
			CryptQueryObject(
				CERT_QUERY_OBJECT_FILE,
				FileName,
				CERT_QUERY_CONTENT_FLAG_PKCS7_SIGNED_EMBED,
				CERT_QUERY_FORMAT_FLAG_BINARY,
				0,
				&dwEncoding,
				&dwContentType,
				&dwFormatType,
				&hStore,
				&hMsg,
				NULL);
		if (!fResult)
		{
			Error = GetLastError();
			DbgPrint("CryptQueryObject failed with %x\n", Error);
			break;
		}

		// Get signer information size.
		fResult = 
			CryptMsgGetParam(
				hMsg, 
				CMSG_SIGNER_INFO_PARAM, 
				0, 
				NULL, 
				&dwSignerInfo
				);
		if (!fResult)
		{
			Error = GetLastError();
			DbgPrint("CryptMsgGetParam failed with %x\n", Error);
			break;
		}

		// Allocate memory for signer information.
		pSignerInfo = (PCMSG_SIGNER_INFO)AppAlloc(dwSignerInfo);
		if (!pSignerInfo)
		{
			Error = ERROR_NOT_ENOUGH_MEMORY;
			DbgPrint("Unable to allocate memory for Signer Info.\n");
			break;
		}

		// Get Signer Information.
		fResult = 
			CryptMsgGetParam(
				hMsg, 
				CMSG_SIGNER_INFO_PARAM, 
				0, 
				(PVOID)pSignerInfo, 
				&dwSignerInfo
				);
		if (!fResult)
		{
			Error = GetLastError();
			DbgPrint("CryptMsgGetParam failed with %x\n", Error);
			break;
		}

		// Search for the signer certificate in the temporary 
		// certificate store.
		CertInfo.Issuer = pSignerInfo->Issuer;
		CertInfo.SerialNumber = pSignerInfo->SerialNumber;

		pCertContext = 
			CertFindCertificateInStore(
				hStore,
				ENCODING,
				0,
				CERT_FIND_SUBJECT_CERT,
				(PVOID)&CertInfo,
				NULL
				);
		if (pCertContext==NULL){
			Error = GetLastError();
			DbgPrint("CertFindCertificateInStore failed with %x\n",GetLastError());
			break;
		}

		Error = GetSubjectName(pCertContext,&lpszSubjectName);
		if (Error!=NO_ERROR){
			DbgPrint("FillIssuerName failed with %x\n", Error);
			break;
		}	
		// Get program name and publisher information from 
		// signer info structure.
		Error = 
			GetProgAndPublisherInfo(
				pSignerInfo, 
				&lpszProgramName,
				&lpszPublisherLink,
				&lpszMoreInfoLink
				);
	}
	while ( FALSE );

	if (pSignerInfo != NULL) {
		AppFree(pSignerInfo);
	}
	if ( pCertContext )
	{
		CertFreeCertificateContext ( pCertContext );
	}
	if (hStore != NULL) {
		CertCloseStore(hStore, 0);
	}
	if (hMsg != NULL) {
		CryptMsgClose(hMsg);
	}
	
	if ( Error != NO_ERROR )
	{
		if ( lpszSubjectName ){
			AppFree ( lpszSubjectName );
			lpszSubjectName = NULL;
		}
		if ( lpszProgramName ){
			AppFree ( lpszProgramName );
			lpszProgramName = NULL;
		}
		if ( lpszPublisherLink ){
			AppFree ( lpszPublisherLink );
			lpszPublisherLink = NULL;
		}
		if ( lpszMoreInfoLink ){
			AppFree ( lpszMoreInfoLink );
			lpszMoreInfoLink = NULL;
		}
	}
	else
	{
		PublisherInfo->lpszSubjectName   = lpszSubjectName; // company
		PublisherInfo->lpszProgramName   = lpszProgramName;
		PublisherInfo->lpszPublisherLink = lpszPublisherLink;
		PublisherInfo->lpszMoreInfoLink  = lpszMoreInfoLink;
	}
	return Error;
}