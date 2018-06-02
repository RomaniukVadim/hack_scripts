#ifndef __UTILS_H_
#define __UTILS_H_

#ifndef NANOSECONDS
#define NANOSECONDS(nanos) \
	(((signed __int64)(nanos)) / 100L)
#endif

#ifndef MICROSECONDS
#define MICROSECONDS(micros) \
	(((signed __int64)(micros)) * NANOSECONDS(1000L))
#endif

#ifndef MILLISECONDS
#define MILLISECONDS(milli) \
	(((signed __int64)(milli)) * MICROSECONDS(1000L))
#endif

#ifndef SECONDS
#define SECONDS(seconds) \
	(((signed __int64)(seconds)) * MILLISECONDS(1000L))
#endif

LONGLONG Now( VOID );
DWORD GetProcessNameHash( IN DWORD ProcessID );

#define _abs(x) ((x > 0) ? x : -x)

//#define _countof(array) sizeof(array)/sizeof(array[0])

#endif //__UTILS_H_