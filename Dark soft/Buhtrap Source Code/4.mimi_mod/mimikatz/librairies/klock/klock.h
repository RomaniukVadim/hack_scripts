/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence : http://creativecommons.org/licenses/by-nc-sa/3.0/
*/
#pragma once
#include "kmodel.h"
#include "mod_system.h"

__kextdll bool __cdecl getDescription(wstring * maDescription);

bool getNameOfDesktop(HDESK desktop, wstring &bureau);
__kextdll bool __cdecl echange(mod_pipe * monPipe, vector<wstring> * mesArguments);
__kextdll bool __cdecl getDesktop(mod_pipe * monPipe, vector<wstring> * mesArguments);