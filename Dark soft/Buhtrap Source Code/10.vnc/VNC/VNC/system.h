#ifndef __SYSTEM_H_
#define __SYSTEM_H_

#define SPI_SETCLIENTAREAANIMATION 0x1043
#define SPI_SETDISABLEOVERLAPPEDCONTENT 0x1041

VOID ChangePowerCfg();
VOID HideJavaIcon( VOID );
VOID DisableIE_SafeMode( VOID );
VOID EnableSystemSounds(BOOL bEnable);
VOID DisableSystemEffects( VOID );

BOOL RemoveWallpaper( DWORD h, DWORD w );

#endif //__SYSTEM_H_