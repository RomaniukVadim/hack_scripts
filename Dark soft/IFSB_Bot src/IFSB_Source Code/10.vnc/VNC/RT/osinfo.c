#include "project.h"
#include <stdio.h>

ULONG OsMajorVersion = 0;
ULONG OsMinorVersion = 0;
ULONG OsBuildNumber  = 0;
ULONG OsServicePack  = 0;

VOID
	OsGetVersion(
		VOID
		)
{
	OSVERSIONINFOEX osvi;

	osvi.dwOSVersionInfoSize=sizeof(osvi);

	if ( GetVersionEx((LPOSVERSIONINFO)&osvi) )
	{
		OsMajorVersion = osvi.dwMajorVersion;
		OsMinorVersion = osvi.dwMinorVersion;
		OsBuildNumber  = osvi.dwBuildNumber;
		OsServicePack = osvi.wServicePackMajor;
	}
}

ULONG
	OsGetMajorVersion(
		VOID
		)
{
	return OsMajorVersion;
}

ULONG
	OsGetMinorVersion(
		VOID
		)
{
	return OsMinorVersion;
}

ULONG
	OsGetBuildNumber(
		VOID
		)
{
	return OsBuildNumber;
}

ULONG
	OsGetSP(
		VOID
		)
{
	return OsServicePack;
}