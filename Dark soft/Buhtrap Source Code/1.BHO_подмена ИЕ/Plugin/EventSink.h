#include <Exdisp.h>
#include <Exdispid.h>
#include <shlguid.h>  
#include <mshtml.h>
#include <atlbase.h>
#include <atlcom.h >


class CEventSink : public DWebBrowserEvents2 {
public:
	// No constructor or destructor is needed

	void Init(IWebBrowser2* pSite); // initialize EventSink with the pSite 
	// IUnknown methods
	STDMETHODIMP QueryInterface(REFIID riid,void **ppvObject);
	STDMETHODIMP_(ULONG) AddRef();
	STDMETHODIMP_(ULONG) Release();
	// IDispatch methods
	STDMETHODIMP GetTypeInfoCount(UINT *pctinfo);
	STDMETHODIMP GetTypeInfo(UINT iTInfo,LCID lcid,ITypeInfo **ppTInfo);
	STDMETHODIMP GetIDsOfNames(REFIID riid,LPOLESTR *rgszNames,UINT cNames,LCID lcid,DISPID *rgDispId);
	STDMETHODIMP Invoke(DISPID dispIdMember,REFIID riid,LCID lcid,WORD wFlags,DISPPARAMS *pDispParams,VARIANT *pVarResult,EXCEPINFO *pExcepInfo,UINT *puArgErr);
	
	// DWebBrowserEvents2 does not have any methods, IE calls our Invoke() method to notify us of events
protected:
	// Event handling methods	
	void STDMETHODCALLTYPE OnDocumentComplete(IDispatch *pDisp, BSTR url);
	void STDMETHODCALLTYPE OnNavigateComplete(IDispatch *pDisp, BSTR url);
	bool first;
	IWebBrowser2 *pSite; // store reference to current site
	BSTR UrlSite;

private:
	bool CheckForInterface(IWebBrowser2* pBrowser, IDispatch* pDisp);
	BSTR LoadContentFromFile (int nContentType);	
};


// We only have one global object of this
extern CEventSink EventSink;

