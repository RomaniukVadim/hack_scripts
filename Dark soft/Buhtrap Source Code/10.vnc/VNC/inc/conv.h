#ifndef _CONV_H_
#define _CONV_H_

#include <crtdbg.h>

#define USES_CONVERSION int _convert = 0;  LPCWSTR _lpw = NULL; LPCSTR _lpa = NULL; _lpa; _convert; _lpw

FORCEINLINE LPSTR WINAPI W2AHelper(LPSTR lpa, LPCWSTR lpw, int nChars)
{
	_ASSERT(lpw != NULL);
	_ASSERT(lpa != NULL);
	// verify that no illegal character present
	// since lpa was allocated based on the size of lpw
	// don't worry about the number of chars
	lpa[0] = '\0';
	WideCharToMultiByte(CP_ACP, 0, lpw, -1, lpa, nChars, NULL, NULL);
	return lpa;
}

FORCEINLINE LPWSTR WINAPI A2WHelper(LPWSTR lpw, LPCSTR lpa, int nChars)
{
	_ASSERT(lpa != NULL);
	_ASSERT(lpw != NULL);
	// verify that no illegal character present
	// since lpw was allocated based on the size of lpa
	// don't worry about the number of chars
	lpw[0] = '\0';
	MultiByteToWideChar(CP_ACP, 0, lpa, -1, lpw, nChars);
	return lpw;
}

#define W2A(lpw) (\
		((_lpw = lpw) == NULL) ? NULL : (\
			_convert = (lstrlenW(_lpw)+1)*2,\
			W2AHelper((LPSTR) alloca(_convert), _lpw, _convert)))

#define A2W(lpa) (\
		((_lpa = lpa) == NULL) ? NULL : (\
			_convert = (lstrlenA(_lpa)+1),\
			A2WHelper((LPWSTR) alloca(_convert*2), _lpa, _convert)))


#ifndef _UNICODE

FORCEINLINE LPSTR T2A(LPTSTR lp) { return lp; }
FORCEINLINE LPSTR A2T(LPTSTR lp) { return lp; }

#define W2T(lpw) W2A(lpw)
#define T2W(lpa) A2W(lpa)

#else

FORCEINLINE LPWSTR T2W(LPTSTR lp) { return lp; }
FORCEINLINE LPTSTR W2T(LPWSTR lp) { return lp; }

#define T2A(lpw) W2A(lpw)
#define A2T(lpa) A2W(lpa)

#endif

#endif