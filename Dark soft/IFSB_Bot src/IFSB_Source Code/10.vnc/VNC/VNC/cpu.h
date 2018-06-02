//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: cpu.h
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	code for calculating cpu usage by our application

#ifndef __CPU_H_
#define __CPU_H_

VOID CpuInit( VOID );
VOID CpuRelease( VOID );
SHORT CpuGetUsage( VOID );

#endif //__CPU_H_