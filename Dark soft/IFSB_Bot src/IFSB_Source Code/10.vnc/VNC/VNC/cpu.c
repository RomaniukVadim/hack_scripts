//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: cpu.c
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description: 
//	code for calculating cpu usage by our application

#include "project.h"
#include "ntdll.h"

typedef struct _SYSTEM_PROCESSOR_PERFORMANCE_INFORMATION {
	LARGE_INTEGER IdleTime;
	LARGE_INTEGER KernelTime;
	LARGE_INTEGER UserTime;
	LARGE_INTEGER Reserved1[2];
	ULONG Reserved2;
} SYSTEM_PROCESSOR_PERFORMANCE_INFORMATION, *PSYSTEM_PROCESSOR_PERFORMANCE_INFORMATION;

typedef BOOL (WINAPI * pfnGetSystemTimes)(LPFILETIME lpIdleTime, LPFILETIME lpKernelTime, LPFILETIME lpUserTime );

static DWORD s_TickMark = 0;
static __int64 s_time = 0;
static __int64 s_idleTime = 0;
static __int64 s_kernelTime = 0;
static __int64 s_userTime = 0;
static __int64 s_kernelTimeProcess = 0;
static __int64 s_userTimeProcess = 0;
static int s_count = 0;
static int s_index = 0;
static int s_lastCpu = 0;
static int s_cpu[5] = {0, 0, 0, 0, 0};
static int s_cpuProcess[5] = {0, 0, 0, 0, 0};

static pfnGetSystemTimes s_pfnGetSystemTimes = NULL;

static CRITICAL_SECTION m_cs;
static PSYSTEM_PROCESSOR_PERFORMANCE_INFORMATION m_pInfo = NULL;
static ULONG m_uInfoLength = sizeof(SYSTEM_PROCESSOR_PERFORMANCE_INFORMATION);
static BOOL m_bLocked = FALSE;

static HMODULE hNTDLL = NULL;
static HMODULE hKERNEL32 = NULL;

VOID CpuInit( VOID )
{
	InitializeCriticalSection(&m_cs);

	hKERNEL32  = GetModuleHandleW(wczKernel32);
	if(hKERNEL32){
		s_pfnGetSystemTimes = (pfnGetSystemTimes)GetProcAddress(hKERNEL32, "GetSystemTimes");
	}

	if(!s_pfnGetSystemTimes)
	{
			PSYSTEM_PROCESSOR_PERFORMANCE_INFORMATION pInfo = NULL;
			NtQuerySystemInformation(SystemPerformanceInformation, NULL, 0, &m_uInfoLength);
			m_pInfo = hAlloc ( m_uInfoLength );
	}
	s_TickMark = GetTickCount();
}

VOID CpuRelease( VOID )
{
	if ( m_pInfo ){
		hFree ( m_pInfo );
	}
	if ( hKERNEL32 ){
		FreeLibrary( hKERNEL32 );
	}
	if ( hNTDLL ){
		FreeLibrary( hNTDLL );
	}
	DeleteCriticalSection( &m_cs );
}

VOID CpuGetSysTimes(__int64 *pidleTime, __int64 *pkernelTime, __int64 *puserTime)
{
	if(s_pfnGetSystemTimes){
		s_pfnGetSystemTimes(
			(LPFILETIME)pidleTime, 
			(LPFILETIME)pkernelTime, 
			(LPFILETIME)puserTime
			);
	}
	else
	{
		__int64 idleTime = 0;
		__int64 kernelTime = 0;
		__int64 userTime = 0;
		if( m_uInfoLength && 
			m_pInfo && 
			( NtQuerySystemInformation(0x08, m_pInfo, m_uInfoLength, &m_uInfoLength) == STATUS_SUCCESS ))
		{
			// NtQuerySystemInformation returns information for all
			// CPU cores in the system, so we take the average here:
			int nCores = m_uInfoLength / sizeof(SYSTEM_PROCESSOR_PERFORMANCE_INFORMATION);
			int i;
			for( i = 0;i < nCores; i ++)
			{
				idleTime   += m_pInfo[i].IdleTime.QuadPart;
				kernelTime += m_pInfo[i].KernelTime.QuadPart;
				userTime   += m_pInfo[i].UserTime.QuadPart;
			}
			idleTime = idleTime / nCores;
			kernelTime = kernelTime/nCores;
			userTime = userTime/ nCores;
		}
		*pidleTime   = idleTime;
		*pkernelTime = kernelTime;
		*puserTime   = userTime;
	}
}

SHORT CpuGetUsage( VOID )
{
	__int64 sTime;
	int sLastCpu;

	__int64 time;
	__int64 idleTime;
	__int64 kernelTime;
	__int64 userTime;
	__int64 kernelTimeProcess;
	__int64 userTimeProcess;

	FILETIME createTime;
	FILETIME exitTime;
	__int64 div;

	int cpu;
	int cpuProcess;

	__int64 usr;
	__int64 ker;
	__int64 idl;
	__int64 sys;

	int i;

	if (m_bLocked) {
		return s_lastCpu;
	}

	EnterCriticalSection( &m_cs );
	m_bLocked = TRUE;
	sTime = s_time;
	sLastCpu = s_lastCpu;

	if(((GetTickCount() - s_TickMark) & 0x7FFFFFFF) <= 200)
	{
		if (m_bLocked) {
			LeaveCriticalSection( &m_cs );
		}
		m_bLocked = FALSE;
		return sLastCpu;
	}

	GetSystemTimeAsFileTime((LPFILETIME)&time);

	if(!sTime)
	{
		CpuGetSysTimes(&idleTime, &kernelTime, &userTime);
		GetProcessTimes(
			GetCurrentProcess(), 
			&createTime, 
			&exitTime, 
			(LPFILETIME)&kernelTimeProcess, 
			(LPFILETIME)&userTimeProcess
			);

		s_time = time;
		s_idleTime = idleTime;
		s_kernelTime = kernelTime;
		s_userTime = userTime;
		s_kernelTimeProcess = kernelTimeProcess;
		s_userTimeProcess = userTimeProcess;
		s_lastCpu = 0;
		s_TickMark = GetTickCount();
		if (m_bLocked) {
			LeaveCriticalSection( &m_cs );
		}
		m_bLocked = FALSE;
		return 0;
	}

	div = (time - sTime);

	CpuGetSysTimes(&idleTime, &kernelTime, &userTime);

	GetProcessTimes(GetCurrentProcess(), &createTime, &exitTime, (LPFILETIME)&kernelTimeProcess, (LPFILETIME)&userTimeProcess);

	usr = userTime   - s_userTime;
	ker = kernelTime - s_kernelTime;
	idl = idleTime   - s_idleTime;
	sys = (usr + ker);

	if(sys){
		cpu = (int)((sys - idl) * 100 / sys); // System Idle take 100 % of cpu;
	}
	else {
		cpu = 0;
	}

	cpuProcess = (int)((((userTimeProcess - s_userTimeProcess) + (kernelTimeProcess - s_kernelTimeProcess)) * 100 ) / div);
	s_time = time;
	s_idleTime = idleTime;
	s_kernelTime = kernelTime;
	s_userTime = userTime;
	s_kernelTimeProcess = kernelTimeProcess;
	s_userTimeProcess = userTimeProcess;
	s_cpu[s_index] = cpu;
	s_cpuProcess[s_index] = cpuProcess;
	s_index++;
	s_index%=5;

	s_count ++;

	if(s_count > 5) {
		s_count = 5;
	}

	cpu = 0;
	for ( i = 0; i < s_count; i++  ){
		cpu += s_cpu[i];
	}

	cpuProcess = 0;
	for ( i = 0; i < s_count; i++ ){
		cpuProcess += s_cpuProcess[i];
	}

	cpu /= s_count;
	cpuProcess /= s_count;
	s_lastCpu = cpuProcess;
	sLastCpu = s_lastCpu;
	s_TickMark = GetTickCount();
	if (m_bLocked) {
		LeaveCriticalSection( &m_cs );
	}
	m_bLocked = FALSE;
	return sLastCpu;
}