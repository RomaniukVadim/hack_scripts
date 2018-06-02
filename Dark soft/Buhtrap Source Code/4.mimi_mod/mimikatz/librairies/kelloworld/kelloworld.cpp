/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence : http://creativecommons.org/licenses/by-nc-sa/3.0/
*/
#include "kelloworld.h"

__kextdll bool __cdecl helloworld(mod_pipe * monPipe, vector<wstring> * mesArguments)
{
	MessageBox(NULL, L"Hello World!", MIMIKATZ_FULL, MB_ICONINFORMATION | MB_OK);
	return true;
}
