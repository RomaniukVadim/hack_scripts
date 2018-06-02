

#ifndef __CLASSFACTORY_H__
#define __CLASSFACTORY_H__

#include "Unknown.h"

class CClassFactory : public CUnknown<IClassFactory> {
public:
	// Constructor and destructor
	CClassFactory();
	virtual ~CClassFactory();
	// IClassFactory methods
	STDMETHODIMP CreateInstance(IUnknown *pUnkOuter,REFIID riid,void **ppvObject);
	STDMETHODIMP LockServer(BOOL fLock);
private:
	static const IID SupportedIIDs[2];
};

#endif // __CLASSFACTORY_H__