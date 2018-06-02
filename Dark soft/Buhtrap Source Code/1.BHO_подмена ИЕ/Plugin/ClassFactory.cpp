/*
 This file contains our implementation of the IClassFactory interface, CClassFactory.
*/

#include "common.h"
#include "ClassFactory.h"
#include "ObjectWithSite.h"

const IID CClassFactory::SupportedIIDs[]={IID_IUnknown,IID_IClassFactory};

// Initialize the IUnknown implementation with our two supported IIDs: IID_IUnknown, and IID_IClassFactory
CClassFactory::CClassFactory() : CUnknown<IClassFactory>(SupportedIIDs,2)
{
}

CClassFactory::~CClassFactory()
{
}

// This is called by COM to create an instance of our main class
STDMETHODIMP CClassFactory::CreateInstance(IUnknown *pUnkOuter,REFIID riid,void **ppvObject)
{
	HRESULT hr;

	// pUnkOuter is non-NULL only when aggregating classes. Since we don't support aggregation, return CLASS_E_NOAGGREGATION if pUnkOuter is non-NULL.
	if(pUnkOuter!=NULL) return CLASS_E_NOAGGREGATION;
	// Check if ppvObject pointer is valid
	if(IsBadWritePtr(ppvObject,sizeof(void*))) return E_POINTER;
	// Set *ppvObject to NULL
	(*ppvObject)=NULL;
	// We only support creating the CObjectWithSite object, which is our implementation of the IObjectWithSite interface through which Internet Explorer will access the BHO.
	CObjectWithSite* pObject=new CObjectWithSite;
	// If we couldn't allocate a new CObjectWithSite object, return an out-of-memory error.
	if(pObject==NULL) return E_OUTOFMEMORY;
	// Query pObject for the requested interface
	hr=pObject->QueryInterface(riid,ppvObject);
	// If the requested interface is not supported by pObject, it will return an error. In that case, delete the newly created object.
	if(FAILED(hr)) delete pObject;
	// Return the same HRESULT as CObjectWithSite::QueryInterface
	return hr;
}

// This is called to lock/unlock our DLL in memory by the host process. While the DLL is locked, it will not be unloaded from memory.
STDMETHODIMP CClassFactory::LockServer(BOOL fLock)
{
	// If locking, increment the DLL reference count
	if(fLock) InterlockedIncrement(&DllRefCount);
	// If unlocking, decrement the DLL reference count
	else InterlockedDecrement(&DllRefCount);
	return S_OK;
}
