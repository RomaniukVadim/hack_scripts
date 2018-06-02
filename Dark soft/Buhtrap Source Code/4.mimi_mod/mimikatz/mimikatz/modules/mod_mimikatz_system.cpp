/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence : http://creativecommons.org/licenses/by-nc-sa/3.0/
*/
#include "mod_mimikatz_system.h"

vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> mod_mimikatz_system::getMimiKatzCommands()
{
	vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> monVector;
	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(user,		L"user",		L"Displays the current user"));
	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(computer,	L"computer",	L"Displays the current computer name"));
	return monVector;
}

bool mod_mimikatz_system::user(vector<wstring> * arguments)
{
	wstring monUser;
	
	if(mod_system::getUserName(&monUser))
		wcout << L"User : " << monUser << endl;
	else
		wcout << L"mod_system::getUserName : " << mod_system::getWinError();

	return true;
}

bool mod_mimikatz_system::computer(vector<wstring> * arguments)
{
	wstring monComputer;
	
	if(mod_system::getComputerName(&monComputer))
		wcout << L"Computer : " << monComputer << endl;
	else
		wcout << L"mod_system::getComputerName : " << mod_system::getWinError();

	return true;
}

