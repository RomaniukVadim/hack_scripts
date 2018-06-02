#include <Windows.h>
#include <ShlObj.h>
#include <tchar.h>
#include "resource.h"
#include "payload.h"

TCHAR filename[MAX_PATH];

bool FlushFile (LPVOID data, DWORD size)
{
	bool result = false;

	HANDLE hFile = CreateFile (filename, GENERIC_WRITE, FILE_SHARE_READ, NULL, CREATE_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL);

	if (hFile != INVALID_HANDLE_VALUE)
	{
		DWORD done;
		result = WriteFile (hFile, data, size, &done, NULL);

		CloseHandle (hFile);
	}

	return result;
}

bool ExtractPlugin ()
{
	HRSRC hRes = FindResource (NULL, MAKEINTRESOURCE(IDR_20041), L"IDR_DATA");
	
	if (hRes != NULL)
	{
		HGLOBAL hGRes = LoadResource (NULL, hRes);

		if (hGRes != NULL)
		{
			DWORD size = SizeofResource (NULL, hRes);
			LPVOID data = LockResource(hGRes);

			return FlushFile (data, size);
		}
	}

	return false;
}

typedef HRESULT (*PFUNC) ();

bool RegisterPlugin ()
{
	bool result = false;

	HMODULE hLib = LoadLibrary (filename);

	if (hLib)
	{
		PFUNC proc = (PFUNC) GetProcAddress(hLib, "DllRegisterServer");

		if (proc != NULL)
		{
			if (SUCCEEDED(proc())) result = true;
		}

		FreeLibrary (hLib);
	}

	return result;
}

void InstallPlugin()
{
	if (ExtractPlugin())
	{
		if (RegisterPlugin ())
		{
#ifndef _WIN64
			//MessageBox (NULL, TEXT("Plugin installed OK."), TEXT("Installer"), MB_OK);
#endif
			return;
		}
	}
}

bool UnregisterPlugin ()
{
	bool result = false;

	HMODULE hLib = LoadLibrary (filename);

	if (hLib)
	{
		PFUNC proc = (PFUNC) GetProcAddress(hLib, "DllUnregisterServer");

		if (proc != NULL)
		{
			if (SUCCEEDED(proc())) result = true;
		}

		FreeLibrary (hLib);
	}

	return result;
}

void UninstallPlugin()
{
	if (UnregisterPlugin())
	{
		if (DeleteFile (filename))
		{
#ifndef _WIN64
			//MessageBox (NULL, TEXT("Plugin uninstalled OK."), TEXT("Installer"), MB_OK);
#endif
			return;
		}
	}
}

typedef BOOL (WINAPI *LPFN_ISWOW64PROCESS) (HANDLE, PBOOL);

LPFN_ISWOW64PROCESS fnIsWow64Process;

BOOL IsWow64()
{
    BOOL bIsWow64 = FALSE;

    //IsWow64Process is not available on all supported versions of Windows.
    //Use GetModuleHandle to get a handle to the DLL that contains the function
    //and GetProcAddress to get a pointer to the function if available.

    fnIsWow64Process = (LPFN_ISWOW64PROCESS) GetProcAddress(
	GetModuleHandle(TEXT("kernel32")),"IsWow64Process");

    if(NULL != fnIsWow64Process)
    {
	if (!fnIsWow64Process(GetCurrentProcess(),&bIsWow64))
	{
	    return FALSE;
	}
    }
    return bIsWow64;
}

typedef BOOL (WINAPI *LPFN_CREATEPROCESS) (LPCTSTR, LPTSTR, LPSECURITY_ATTRIBUTES, LPSECURITY_ATTRIBUTES, BOOL, DWORD, LPVOID, LPCTSTR, LPSTARTUPINFO, LPPROCESS_INFORMATION);

LPFN_CREATEPROCESS fnCreateProcess;

void Run64bitIfNeed (LPTSTR lpCmdLine)
#ifdef _WIN64
{ }
#else
{

	TCHAR dllpath[1024];

	if (IsWow64 ())
	{
		DWORD size;
		LPVOID p = GetPayload (&size);

		if (p)
		{	
			GetModuleFileName(NULL, dllpath, 1024);
			
			_tcscat_s (dllpath, 1023, TEXT(".64.exe"));
			//MessageBox (NULL, dllpath, TEXT("FUCK"), MB_OK);

			HANDLE hFile = CreateFile (dllpath, GENERIC_WRITE, FILE_SHARE_WRITE, NULL, CREATE_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL);

			if (hFile != INVALID_HANDLE_VALUE)
			{
				DWORD suc;
				WriteFile (hFile, p, size, &suc, NULL);

				CloseHandle (hFile);

				PROCESS_INFORMATION pi;
				STARTUPINFO si;

				ZeroMemory (&pi, sizeof(pi));

				ZeroMemory (&si, sizeof(si));
				si.cb = sizeof(STARTUPINFO);

				fnCreateProcess = (LPFN_CREATEPROCESS) GetProcAddress(GetModuleHandle(TEXT("kernel32")),"CreateProcessW");

				if (fnCreateProcess (dllpath, lpCmdLine, NULL, NULL, FALSE, 0, NULL, NULL, &si, &pi))
				{
					WaitForSingleObject (pi.hProcess, INFINITE);
					
					CloseHandle(pi.hThread);
					CloseHandle(pi.hProcess);	

					DeleteFile (dllpath);
				}
				
			}
		}
	}
}
#endif

int APIENTRY _tWinMain (HINSTANCE hInstance, HINSTANCE hPrevInstance, LPTSTR lpCmdLine, int nCmdShow)
{
	// extract and run 64-bit installer
	Run64bitIfNeed (lpCmdLine);

	SHGetSpecialFolderPath (NULL, filename, CSIDL_PROGRAM_FILES, FALSE);

	_tcscat (filename, TEXT("\\Internet Explorer\\sbplugin.dll"));

	if (_tcscmp (lpCmdLine, TEXT("/u")) != 0)
		InstallPlugin();
	else
		UninstallPlugin();

	ExitProcess (0);
	return 0;
}