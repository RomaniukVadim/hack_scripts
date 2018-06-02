#ifndef __FILE_H_
#define __FILE_H_

DWORD FileReadStringA(HANDLE hFile, LPSTR Buffer, DWORD Length );
DWORD FileReadStringW(HANDLE hFile, LPWSTR Buffer, DWORD Length );

void FileFixSlashA( LPSTR szPath );
void FileFixSlashW( LPWSTR szPath );

DWORD 
	FileGetVersionA( 
		IN LPSTR FileName, 
		OUT LPWORD Major, 
		OUT LPWORD Minor,
		OUT LPWORD Build, 
		OUT LPWORD QFE
		);
DWORD 
	FileGetVersionW( 
		IN LPWSTR FileName, 
		OUT LPWORD Major, 
		OUT LPWORD Minor,
		OUT LPWORD Build, 
		OUT LPWORD QFE
		);

#endif