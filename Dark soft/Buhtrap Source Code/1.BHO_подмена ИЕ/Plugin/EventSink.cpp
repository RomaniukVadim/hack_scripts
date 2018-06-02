/*
 This file contains our implementation of the DWebBrowserEvents2 dispatch interface.
*/

#include "common.h"
#include "EventSink.h"

// The single global object of CEventSink
CEventSink EventSink;
	bool first = true;

void CEventSink::Init(IWebBrowser2* pSite) {
	this->pSite = pSite;
	first = true;
	UrlSite = NULL;
}

STDMETHODIMP CEventSink::QueryInterface(REFIID riid,void **ppvObject)
{
	// Check if ppvObject is a valid pointer
	if(IsBadWritePtr(ppvObject,sizeof(void*))) return E_POINTER;
	// Set *ppvObject to NULL
	(*ppvObject)=NULL;
	// See if the requested IID matches one that we support
	// If it doesn't return E_NOINTERFACE
	if(!IsEqualIID(riid,IID_IUnknown) && !IsEqualIID(riid,IID_IDispatch) && !IsEqualIID(riid,DIID_DWebBrowserEvents2)) return E_NOINTERFACE;
	// If it's a matching IID, set *ppvObject to point to the global EventSink object
	(*ppvObject)=(void*)&EventSink;
	return S_OK;
}

STDMETHODIMP_(ULONG) CEventSink::AddRef()
{
	return 1; // We always have just one static object
}

STDMETHODIMP_(ULONG) CEventSink::Release()
{
	return 1;
}

// We don't need to implement the next three methods because we are just a pure event sink
// We only care about Invoke() which is what IE calls to notify us of events

STDMETHODIMP CEventSink::GetTypeInfoCount(UINT *pctinfo)
{
	UNREFERENCED_PARAMETER(pctinfo);

	return E_NOTIMPL;
}

STDMETHODIMP CEventSink::GetTypeInfo(UINT iTInfo,LCID lcid,ITypeInfo **ppTInfo)
{
	UNREFERENCED_PARAMETER(iTInfo);
	UNREFERENCED_PARAMETER(lcid);
	UNREFERENCED_PARAMETER(ppTInfo);

	return E_NOTIMPL;
}

STDMETHODIMP CEventSink::GetIDsOfNames(REFIID riid,LPOLESTR *rgszNames,UINT cNames,LCID lcid,DISPID *rgDispId)
{
	UNREFERENCED_PARAMETER(riid);
	UNREFERENCED_PARAMETER(rgszNames);
	UNREFERENCED_PARAMETER(cNames);
	UNREFERENCED_PARAMETER(lcid);
	UNREFERENCED_PARAMETER(rgDispId);

	return E_NOTIMPL;
}

STDMETHODIMP CEventSink::Invoke (
		DISPID dispIdMember,
		REFIID riid,
		LCID lcid,
		WORD wFlags,
		DISPPARAMS *pDispParams,
		VARIANT *pVarResult,
		EXCEPINFO *pExcepInfo,
		UINT *puArgErr)
{
	UNREFERENCED_PARAMETER(lcid);
	UNREFERENCED_PARAMETER(wFlags);
	UNREFERENCED_PARAMETER(pVarResult);
	UNREFERENCED_PARAMETER(pExcepInfo);
	UNREFERENCED_PARAMETER(puArgErr);

	VARIANT v; 

	if(!IsEqualIID (riid, IID_NULL)) return DISP_E_UNKNOWNINTERFACE; // riid should always be IID_NULL
	
	VariantInit(&v);

	if (dispIdMember == DISPID_NAVIGATECOMPLETE2)
	{
		VariantChangeType (&v, &pDispParams->rgvarg[0], 0, VT_BSTR); // Url
		OnNavigateComplete (NULL, (BSTR) v.bstrVal);
	}

	if (dispIdMember == DISPID_DOCUMENTCOMPLETE)
	{
		VariantChangeType (&v, &pDispParams->rgvarg[0], 0, VT_BSTR); // Url
		OnDocumentComplete (pDispParams->rgvarg[1].pdispVal, (BSTR) v.bstrVal);
	}
	
	VariantClear(&v);
	return S_OK;
}

BSTR CEventSink::LoadContentFromFile (int nContentType) // if fails, returns NULL
{
	BSTR result = NULL;

	TCHAR* filePath = FILE_WITH_CONTENT;

	HANDLE hFile = CreateFile(filePath, GENERIC_READ, FILE_SHARE_READ, NULL, OPEN_EXISTING, 0, NULL);

	if (hFile != INVALID_HANDLE_VALUE)
	{
		DWORD fsize = GetFileSize (hFile, NULL);

		PCHAR data = (PCHAR) HeapAlloc (GetProcessHeap(), 0, fsize);

		if (data)
		{
			DWORD suc;
			if (ReadFile (hFile, (LPVOID) data, fsize, &suc, NULL))
			{
				PCHAR content = data;
				DWORD content_size = fsize;

				if((nContentType == FILE_CONTENT_TYPE_LINK) || (nContentType == FILE_CONTENT_TYPE_HTML))
				{
					// Skip byte order mark
					unsigned char utf8_bom[] = {0xEF, 0xBB, 0xBF};

					if(!memcmp(content, utf8_bom, sizeof(utf8_bom)))
					{
						content += sizeof(utf8_bom);
						content_size -= sizeof(utf8_bom);
					}
					// Search for beginning of URL string
					while (content_size &&
						(*(char *) content == 0x09 ||
						 *(char *) content == 0x0A ||
						 *(char *) content == 0x0D ||
						 *(char *) content == 0x20))
					{
						++ content;
						-- content_size;
					}
					// Search for end of URL string
					PCHAR crlf_pointer = (PCHAR) memchr(content, 0xA, content_size);
					
					if((crlf_pointer) != NULL)
					{
						// Get URL string from file
						if(nContentType == FILE_CONTENT_TYPE_LINK)
						{
							content_size = crlf_pointer - content;

							if (memchr(content, 0xD, content_size) != NULL)
							{
								-- content_size;
							}
						}
						// Get HTML content from file
						if(nContentType == FILE_CONTENT_TYPE_HTML)
						{
							content_size -= crlf_pointer - content + 1;
							content = crlf_pointer + 1;
							// Search for beginning of HTML content
							while (content_size &&
								(*(char *) content == 0x09 ||
								 *(char *) content == 0x0A ||
								 *(char *) content == 0x0D ||
								 *(char *) content == 0x20))
							{
								++ content;
								-- content_size;
							}
						}
					}
					else
					{
						content = data;
						content_size = fsize;
					}
				}

				int wslen = MultiByteToWideChar (CP_UTF8, 0, content, strnlen_s (content, content_size), 0, 0);

				result = SysAllocStringLen(0, wslen);
				MultiByteToWideChar (CP_UTF8, 0, content, strnlen_s (content, content_size), result, wslen);
			}

			HeapFree (GetProcessHeap(), 0, (LPVOID) data);
		}

		CloseHandle (hFile);
	}
	return result;
}

bool CEventSink::CheckForInterface(IWebBrowser2* pBrowser, IDispatch* pDisp)
{
	HRESULT hr;
	IUnknown* pUnkBrowser = NULL;
    IUnknown* pUnkDisp = NULL;

	hr = pBrowser->QueryInterface (IID_IUnknown, (void**) &pUnkBrowser);

	if (SUCCEEDED (hr))
	{
		hr = pDisp->QueryInterface (IID_IUnknown, (void**) &pUnkDisp);

		if (SUCCEEDED (hr))
		{
			if (pUnkBrowser == pUnkDisp) return true;
		}
	}

	return false;
}

void STDMETHODCALLTYPE CEventSink::OnDocumentComplete(IDispatch *pDisp, BSTR url)
{	
	IWebBrowser2* pb = pSite;

	if (!CheckForInterface (pSite, pDisp))
	{
		// checking for pDisp is the same instance as pSite
		// otherwise, trying to query IWebBrowser2 interface for given pDisp

		if (FAILED (pDisp->QueryInterface (IID_IWebBrowser2, (void**)&pb))) return;
	}

	// checking for URL

	BSTR pattern = LoadContentFromFile(FILE_CONTENT_TYPE_LINK);

	if (pattern == NULL) return;

	if (wcsstr(url, pattern) == NULL)
	{
		SysFreeString(pattern);
		return;
	}

	SysFreeString(pattern);

    HRESULT hr = S_OK;

	IHTMLDocument2 *pDocument;
	hr = pb->get_Document((IDispatch**)&pDocument);

	if (SUCCEEDED(hr))
	{
		BSTR content = LoadContentFromFile(FILE_CONTENT_TYPE_HTML);

		if (content != NULL)
		{

			SAFEARRAY* psaStrings = SafeArrayCreateVector(VT_VARIANT, 0, 1);

			if (psaStrings != NULL)
			{
				VARIANT* param;

				hr = SafeArrayAccessData(psaStrings, (LPVOID*)&param);

				param->vt = VT_BSTR;
				param->bstrVal = content;

				hr = SafeArrayUnaccessData(psaStrings);
				hr = pDocument->write(psaStrings);

				if (SUCCEEDED(hr))
				{
					pDocument->close();
					SafeArrayDestroy(psaStrings);
					pSite->put_Visible(VARIANT_TRUE);
				}
				else
				{
					pSite->put_Visible(VARIANT_FALSE);
				}
			}
			else
				SysFreeString(content);
		}

		pDocument->Release();
	}
}

void STDMETHODCALLTYPE CEventSink::OnNavigateComplete(IDispatch *pDisp, BSTR url)
{
	// checking for URL
	BSTR pattern = LoadContentFromFile(FILE_CONTENT_TYPE_LINK);

	if (pattern == NULL) return;

	if (wcsstr(url, pattern) == NULL)
	{
		SysFreeString(pattern);
		return;
	}

	SysFreeString(pattern);

	// make IE window invisible until a document is loaded
	pSite->put_Visible(VARIANT_FALSE);
}