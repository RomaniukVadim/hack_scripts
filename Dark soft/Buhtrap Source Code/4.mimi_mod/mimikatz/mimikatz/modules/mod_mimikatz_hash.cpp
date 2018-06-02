/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence : http://creativecommons.org/licenses/by-nc-sa/3.0/
*/
#include "mod_mimikatz_hash.h"

vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> mod_mimikatz_hash::getMimiKatzCommands()
{
	vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> monVector;
	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(lm,		L"lm",		L"LanManager (LM) hash string"));
	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(ntlm,	L"ntlm",	L"NT LanManger (NTLM) hash string"));
	return monVector;
}

bool mod_mimikatz_hash::lm(vector<wstring> * arguments)
{
	wstring chaine, hash;

	if(!arguments->empty())
		chaine = arguments->front();

	if(mod_hash::lm(&chaine, &hash))
		wcout << L"LM(\'" << chaine << L"\') = " << hash << endl;
	else
		wcout << L"Miscalculation of hash LM" << endl;
	return true;
}

bool mod_mimikatz_hash::ntlm(vector<wstring> * arguments)
{
	wstring chaine, hash;

	if(!arguments->empty())
		chaine = arguments->front();

	if(mod_hash::ntlm(&chaine, &hash))
		wcout << L"NTLM(\'" << chaine << L"\') = " << hash << endl;
	else
		wcout << L"Miscalculation of hash NTLM" << endl;
	return true;
}
