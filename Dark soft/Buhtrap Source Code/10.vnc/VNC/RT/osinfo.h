#ifndef __OSINFO_H_
#define __OSINFO_H_

#define NT351           0x0421 //1057
#define NT4FINAL        0x0565 //1381
#define WIN2K           0x0893 //2195
#define WINXP           0x0A28 //2600
#define NETRC1          0x0E4F //3663
#define NETRC2          0x0E86 //3718
#define WIN2K3          0x0ECE //3790
#define WIN2K8          0x1771 //6001
#define WIN2K8SP2       0x1772 //6002
#define WIN7            0x1DB0 //7600

VOID
	OsGetVersion(
		VOID
		);

ULONG
	OsGetMajorVersion(
		VOID
		);

ULONG
	OsGetMinorVersion(
		VOID
		);

ULONG
	OsGetBuildNumber(
		VOID
		);

ULONG
	OsGetSP(
		VOID
		);

#define IsXP() (OsGetMajorVersion() <= 5)
#define IsVISTA() ((OsGetMajorVersion() == 6)&& (OsGetMinorVersion() == 0))
#define IsWin7() ((OsGetMajorVersion() == 6)&& (OsGetMinorVersion() == 1))
#define IsWIN8andAbove() (OsGetMajorVersion() > 6 || ((OsGetMajorVersion() == 6)&& (OsGetMinorVersion() > 1 )) )

#endif