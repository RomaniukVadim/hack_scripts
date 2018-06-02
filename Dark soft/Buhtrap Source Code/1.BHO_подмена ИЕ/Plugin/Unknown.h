
#ifndef __UNKNOWN_H__
#define __UNKNOWN_H__

#include <Unknwn.h>

template <typename T>
class CUnknown : public T {
public:
	// Constructor and destructor
	CUnknown(const IID* _supported_iids,int _num_supported_iids);
	virtual ~CUnknown();
	// IUnknown methods
	STDMETHODIMP QueryInterface(REFIID riid,void **ppvObject);
	STDMETHODIMP_(ULONG) AddRef();
	STDMETHODIMP_(ULONG) Release();
protected:
	// This object's reference count, when it reaches zero we delete it
	volatile LONG refcount;
	// These are the IIDs we support for QueryInterface
	const IID* supported_iids;
	int num_supported_iids;
};

template <typename T> CUnknown<T>::CUnknown(const IID* _supported_iids,int _num_supported_iids)
{
	// Initialize our reference count to zero
	refcount=0;
	// Atomically increment the DLL's reference count
	InterlockedIncrement(&DllRefCount);
	// Initialize the supported IIDs
	supported_iids=_supported_iids;
	num_supported_iids=_num_supported_iids;
}

template <typename T> CUnknown<T>::~CUnknown()
{
	// Atomically decrement the DLL's reference count
	InterlockedDecrement(&DllRefCount);
}

template <typename T> STDMETHODIMP CUnknown<T>::QueryInterface(REFIID riid,void **ppvObject)
{
	int n;

	// Check if the ppvObject pointer is valid
	if(IsBadWritePtr(ppvObject,sizeof(void*))) return E_POINTER;
	// Set *ppvObject to NULL first
	(*ppvObject)=NULL;
	// Check if the given IID is a match with our supported IIDs
	for(n=0;n<num_supported_iids;n++) {
		if(IsEqualIID(riid,supported_iids[n])) {
			// If it's a match, fill in the interface pointer, call AddRef(), and return S_OK
			(*ppvObject)=(void*)this;
			AddRef();
			return S_OK;
		}
	}
	// If we didn't find a match, return E_NOINTERFACE
	return E_NOINTERFACE;
}

template <typename T> STDMETHODIMP_(ULONG) CUnknown<T>::AddRef()
{
	// Atomically increment the reference count and return the new count value
	return InterlockedIncrement(&refcount);
}

template <typename T> STDMETHODIMP_(ULONG) CUnknown<T>::Release()
{
	LONG newrefcount;

	// Atomically decrement the reference count
	newrefcount=InterlockedDecrement(&refcount);
	// If the last reference to this object is being released, the new reference count will be zero. If it is, delete this object.
	if(newrefcount==0) delete this;
	// Return the new count
	return newrefcount;
}

#endif // __UNKNOWN_H__
