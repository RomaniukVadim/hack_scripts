
#include "common.h"
#include "ObjectWithSite.h"
#include "EventSink.h"

const IID CObjectWithSite::SupportedIIDs[]={IID_IUnknown,IID_IObjectWithSite};

CObjectWithSite::CObjectWithSite() : CUnknown<IObjectWithSite>(SupportedIIDs,2)
{
	adviseCookie=0;
	pSite=NULL;
	pCP=NULL;
}

CObjectWithSite::~CObjectWithSite()
{
	DisconnectEventSink();
}

// Called by IE to give us IE's site object, through which we get access to IE itself
// If have a previous site set, we should unset it
// If pUnkSite is NULL, we should unset any previous site and not set a new site
STDMETHODIMP CObjectWithSite::SetSite(IUnknown *pUnkSite)
{
	HRESULT hr;

	if(pUnkSite) pUnkSite->AddRef(); // if a new site object is given, AddRef() it to make sure the object doesn't get deleted while we are working with it
	DisconnectEventSink(); // disconnect any previous connection with IE
	if(pUnkSite==NULL) return S_OK; // if only unsetting the site, return S_OK
	hr=pUnkSite->QueryInterface(IID_IWebBrowser2,(void**)&pSite); // query the site object for the IWebBrowser2 interface, from which we can access IE
	pUnkSite->Release(); // we are done working with pUnkSite, so call Release() since we called AddRef() before
	if(FAILED(hr)) return hr; // if we couldn't find the IWebBrowser2 interface, return the error
	EventSink.Init(pSite); // initialize EventSink with the pSite reference
	ConnectEventSink(); // connect the new connection with IE
	return S_OK;
}

// This is called by IE to get an interface from the currently set site object
STDMETHODIMP CObjectWithSite::GetSite(REFIID riid,void **ppvSite)
{
	// Validate the ppvSite pointer
	if(IsBadWritePtr(ppvSite,sizeof(void*))) return E_POINTER;
	// Set *ppvSite to NULL
	(*ppvSite)=NULL;
	// If we don't have a current site set we must return E_FAIL
	if(pSite==NULL) return E_FAIL;
	// Otherwise we let the site's QueryInterface method take care of it
	return pSite->QueryInterface(riid,ppvSite);
}

// This is called by us to get a connection to IE and start handling IE events
void CObjectWithSite::ConnectEventSink()
{
	HRESULT hr;
	IConnectionPointContainer* pCPC;

	if(pSite==NULL) return; // If we don't have a site, don't do anything
	// Get an IConnectionPointContainer interface pointer from the site
	hr=pSite->QueryInterface(IID_IConnectionPointContainer,(void**)&pCPC);
	if(FAILED(hr)) return; // If we couldn't get it, abort
	// Now we use the IConnectionPointContainer interface to get an IConnectionPoint interface pointer that will handle DWebBrowserEvents2 "dispatch interface" events.
	// That means we have to plug our implementation of DWebBrowserEvents2 into the returned IConnectionPoint interface using its Advise() method, as below
	hr=pCPC->FindConnectionPoint(DIID_DWebBrowserEvents2,&pCP);
	if(FAILED(hr)) { // If it failed, release the pCPC interface pointer and abort
		pCPC->Release();
		return;
	}
	// Finally we can plug our event handler object EventSink into the connection point and start receiving IE events
	// The advise cookie is just a return value we use when we want to "unplug" our event handler object from the connection point
	pCP->Advise((IUnknown*)&EventSink,&adviseCookie);
}

// This is called by us to remove our connection to IE, if it exists, and stop handling IE events
void CObjectWithSite::DisconnectEventSink()
{
	if(pCP) { // if we have a valid connection point, unplug the event handler from it, then Release() it
		pCP->Unadvise(adviseCookie);
		adviseCookie=0;
		pCP->Release();
		pCP=NULL;
	}
	if(pSite) { // if we have a valid site, Release() it
		pSite->Release();
		pSite=NULL;
	}
}
