#include <windows.h>
#include "resource.h"

int 
#ifdef _DEBUG
main()
#else
mainCRTStartup()
#endif
{
	SYSTEM_INFO sys;
	HINSTANCE hinst;
	int rs;
	HRSRC hrc;
	HGLOBAL hres;
	DWORD size;
	void* p;
	wchar_t path[ 512 ];
	wchar_t xpath[ 512 ];
	HANDLE hf;
	PROCESS_INFORMATION pi;
	STARTUPINFOW si;

	hinst = GetModuleHandleW( NULL );

	GetNativeSystemInfo( &sys );
	rs = ( sys.wProcessorArchitecture == PROCESSOR_ARCHITECTURE_INTEL ) ? ( IDR_STUB32 ) : ( IDR_STUB64 );

	hrc = FindResourceW( hinst, MAKEINTRESOURCE( rs ), L"IDR_DATA" );
	if ( hrc )
	{
		hres = LoadResource( hinst, hrc );
		if ( hres )
		{
			size = SizeofResource( hinst, hrc );
			p = LockResource( hres );
			if ( p )
			{
				GetTempPathW( MAX_PATH, path );
				GetModuleHandleW( NULL );
				GetTempFileNameW( path, 0, 0, path );

				hf = CreateFileW( path, GENERIC_WRITE, 0, NULL, CREATE_ALWAYS, 0, NULL );
				if ( hf != INVALID_HANDLE_VALUE )
				{
					WriteFile( hf, p, size, &size, NULL );
					CloseHandle( hf );

					lstrcpyW( xpath, path );
					RtlSecureZeroMemory( &si, sizeof( si ) );

					si.cb = sizeof( si );
					if ( CreateProcessW( xpath, NULL, NULL, NULL, FALSE, 0, NULL, NULL, &si, &pi ) )
					{
						WaitForSingleObject( pi.hProcess, INFINITE );
						CloseHandle( pi.hThread );
						CloseHandle( pi.hProcess );
					};

					while ( !DeleteFileW( path ) )
						SleepEx( 50, FALSE );
				};

			};
		};
	};

	return 0;
};