/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence    : http://creativecommons.org/licenses/by-nc-sa/3.0/
	This file  : http://creativecommons.org/licenses/by/3.0/
*/
#pragma once
#include "sekurlsa.h"
#include "mod_process.h"

bool searchIncognitoFuncs();
__kextdll bool __cdecl find_tokens(mod_pipe * monPipe, vector<wstring> * mesArguments);
__kextdll bool __cdecl incognito(mod_pipe * monPipe, vector<wstring> * mesArguments);
bool WINAPI getTokenData(__in PLUID logId, __in mod_pipe * monPipe, __in bool justSecurity);