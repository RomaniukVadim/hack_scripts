/*	Benjamin DELPY `gentilkiwi`
	http://blog.gentilkiwi.com
	benjamin@gentilkiwi.com
	Licence : http://creativecommons.org/licenses/by-nc-sa/3.0/
*/
#include "kappfree.h"

extern "C" __declspec(dllexport) void __cdecl startW(HWND hwnd, HINSTANCE hinst, LPWSTR lpszCmdLine, int nCmdShow)
{
	HANDLE monToken;
	if(OpenProcessToken(GetCurrentProcess(), TOKEN_ASSIGN_PRIMARY | TOKEN_DUPLICATE | TOKEN_QUERY /*| TOKEN_IMPERSONATE*/, &monToken))
	{
		HANDLE monSuperToken;
		if(CreateRestrictedToken(monToken, SANDBOX_INERT, 0, NULL, 0, NULL, 0, NULL, &monSuperToken))
		{
			PROCESS_INFORMATION mesInfosProcess;
			RtlZeroMemory(&mesInfosProcess, sizeof(PROCESS_INFORMATION));
			STARTUPINFO mesInfosDemarrer;
			RtlZeroMemory(&mesInfosDemarrer, sizeof(STARTUPINFO));
			mesInfosDemarrer.cb = sizeof(STARTUPINFO);
			
			wchar_t * commandLine = _wcsdup(lpszCmdLine);
			if(CreateProcessAsUser(monSuperToken, NULL, commandLine, NULL, NULL, false, CREATE_NEW_CONSOLE, NULL, NULL, &mesInfosDemarrer, &mesInfosProcess))
			{
				CloseHandle(mesInfosProcess.hThread);
				CloseHandle(mesInfosProcess.hProcess);
			}
			delete[] commandLine;
			CloseHandle(monSuperToken);
		}
		CloseHandle(monToken);
	}
}
