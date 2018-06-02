/*
WinVer2.nsh
bó¯®º kotkovets
http://forum.oszone.net/thread-67386.html
....................................................................

ò³±³ë³³ñžŸSVERSIONINFOEX

typedef struct _OSVERSIONINFOEX {
  DWORD dwOSVersionInfoSize;____ $R2 ;ñ¡¨¬æ® ò³±³ë³³ñ¹¡¢ â¡©ó¡³
  DWORD dwMajorVersion;__________$0  ;ò³¡°á½ í½§íµ°ížºí±¢æ±±é¦ Windows
  DWORD dwMinorVersion;__________$1  ;å¹  í½§íµ°ížºí±¢æ±±é¦ Windows
  DWORD dwBuildNumber;___________$2  ;î¯¬æ® ò¢¯°ë¦ Windows
  DWORD dwPlatformId;____________$3  ;é¥¥î³¨íµ¦í» ó¯® ð¬ óµ¯°í¹ Windows
  TCHAR szCSDVersion[128];_______$4  ;ã¦°ò©½ ð¡ªæ³  ï¢­ï£«æ®¨ Windows
  WORD  wServicePackMajor;_______$5  ;ò³¡°á½ í½§íµ°ížºí±¢æ±±é¦ ð¡ªæ³  ï¢­ï£«æ®¨ Windows
  WORD  wServicePackMinor;_______$6  ;å¹  í½§íµ°ížºí±¢æ±±é¦ ð¡ªæ³  ï¢­ï£«æ®¨ Windows
  WORD  wSuiteMask;______________$7  ;â©²ï£  ëž î¡§ã¡­é½ ñ¦¥ ë·¨æŸ—indows (ï°°. å¬ Win 2003)
  BYTE  wProductType;____________$8  ;å¯¯ï¬­é³¥ì½­ã¡±ã¦¤æ®¨ ì¡±é²²æ­¥
  BYTE  wReserved;_________________  ;ñ¦¨¥ñ ‹Š} OSVERSIONINFOEX, *POSVERSIONINFOEX, *LPOSVERSIONINFOEX;
*/

!ifndef WINVER2_INCLUDED
!define WINVER2_INCLUDED

!include "logiclib.nsh"
!include "Util.nsh"

;====================================================================================
!define WinName                 "!insertmacro FUNC_WindowsName"
!define WinType                 "!insertmacro FUNC_WindowsType"
!define WinServerName           "!insertmacro FUNC_WindowsServerName"
!define WinVersion              "!insertmacro FUNC_WindowsVersion"
!define WinPlatformArchitecture "!insertmacro FUNC_WindowsPlatformArchitecture"
!define WinPlatformId           "!insertmacro FUNC_WindowsPlatformId"
!define WinServicePack          "!insertmacro FUNC_WindowsServicePack"
!define WinServicePackMajor     "!insertmacro FUNC_WindowsServicePackMajor"
!define WinServicePackMinor     "!insertmacro FUNC_WindowsServicePackMinor"
!define WinBuildNumber          "!insertmacro FUNC_WindowsBuildNumber"
!define WinVersionMajor         "!insertmacro FUNC_WindowsVersionMajor"
!define WinVersionMinor         "!insertmacro FUNC_WindowsVersionMinor"
;====================================================================================

!ifdef NSIS_UNICODE
  !define OSVERSIONINFOEX_SIZE 284
  !define OSVERSIONINFO_SIZE   276
!else
  !define OSVERSIONINFOEX_SIZE 156
  !define OSVERSIONINFO_SIZE   148
!endif

!define VER_PLATFORM_WIN32s        0
!define VER_PLATFORM_WIN32_WINDOWS 1
!define VER_PLATFORM_WIN32_NT      2
!define VER_PLATFORM_WIN32_CE      3

!define VER_NT_WORKSTATION       1
!define VER_NT_DOMAIN_CONTROLLER 2
!define VER_NT_SERVER            3

!define PROCESSOR_ARCHITECTURE_INTEL   0
!define PROCESSOR_ARCHITECTURE_IA64    6
!define PROCESSOR_ARCHITECTURE_AMD64   9
!define PROCESSOR_ARCHITECTURE_UNKNOWN 0xFFFF

!define SM_TABLETPC    86
!define SM_MEDIACENTER 87
!define SM_STARTER     88
!define SM_SERVERR2    89

!define VER_SUITE_EMBEDDEDNT                        0x00000040
!define VER_SUITE_PERSONAL                          0x00000200
!define VER_SUITE_SINGLEUSERTS                      0x00000100
!define VER_SUITE_ENTERPRISE                        0x00000002
!define VER_SUITE_DATACENTER                        0x00000080
!define VER_SUITE_BACKOFFICE                        0x00000004
!define VER_SUITE_BLADE                             0x00000400
!define VER_SUITE_COMPUTE_SERVER                    0x00004000
!define VER_SUITE_SMALLBUSINESS                     0x00000001
!define VER_SUITE_SMALLBUSINESS_RESTRICTED          0x00000020
!define VER_SUITE_STORAGE_SERVER                    0x00002000
!define VER_SUITE_TERMINAL                          0x00000010
!define VER_SUITE_WH_SERVER                         0x00008000

!define PRODUCT_UNDEFINED                           0
!define PRODUCT_ULTIMATE                            1
!define PRODUCT_HOME_BASIC                          2
!define PRODUCT_HOME_PREMIUM                        3
!define PRODUCT_ENTERPRISE                          4
!define PRODUCT_HOME_BASIC_N                        5
!define PRODUCT_BUSINESS                            6
!define PRODUCT_STANDARD_SERVER                     7
!define PRODUCT_DATACENTER_SERVER                   8
!define PRODUCT_SMALLBUSINESS_SERVER                9
!define PRODUCT_ENTERPRISE_SERVER                   0x0000000A
!define PRODUCT_STARTER                             0x0000000B
!define PRODUCT_DATACENTER_SERVER_CORE              0x0000000C
!define PRODUCT_STANDARD_SERVER_CORE                0x0000000D
!define PRODUCT_ENTERPRISE_SERVER_CORE              0x0000000E
!define PRODUCT_ENTERPRISE_SERVER_IA64              0x0000000F
!define PRODUCT_BUSINESS_N                          0x00000010
!define PRODUCT_WEB_SERVER                          0x00000011
!define PRODUCT_CLUSTER_SERVER                      0x00000012
!define PRODUCT_HOME_SERVER                         0x00000013
!define PRODUCT_STORAGE_EXPRESS_SERVER              0x00000014
!define PRODUCT_STORAGE_STANDARD_SERVER             0x00000015
!define PRODUCT_STORAGE_WORKGROUP_SERVER            0x00000016
!define PRODUCT_STORAGE_ENTERPRISE_SERVER           0x00000017
!define PRODUCT_SERVER_FOR_SMALLBUSINESS            0x00000018
!define PRODUCT_SMALLBUSINESS_SERVER_PREMIUM        0x00000019
!define PRODUCT_HOME_PREMIUM_N                      0x0000001A
!define PRODUCT_ENTERPRISE_N                        0x0000001B
!define PRODUCT_ULTIMATE_N                          0x0000001C
!define PRODUCT_WEB_SERVER_CORE                     0x0000001D
!define PRODUCT_MEDIUMBUSINESS_SERVER_MANAGEMENT    0x0000001E
!define PRODUCT_MEDIUMBUSINESS_SERVER_SECURITY      0x0000001F
!define PRODUCT_MEDIUMBUSINESS_SERVER_MESSAGING     0x00000020
!define PRODUCT_SERVER_FOUNDATION                   0x00000021
!define PRODUCT_HOME_PREMIUM_SERVER                 0x00000022
!define PRODUCT_SERVER_FOR_SMALLBUSINESS_V          0x00000023
!define PRODUCT_STANDARD_SERVER_V                   0x00000024
!define PRODUCT_DATACENTER_SERVER_V                 0x00000025
!define PRODUCT_ENTERPRISE_SERVER_V                 0x00000026
!define PRODUCT_DATACENTER_SERVER_CORE_V            0x00000027
!define PRODUCT_STANDARD_SERVER_CORE_V              0x00000028
!define PRODUCT_ENTERPRISE_SERVER_CORE_V            0x00000029
!define PRODUCT_HYPERV                              0x0000002A
!define PRODUCT_STORAGE_EXPRESS_SERVER_CORE         0x0000002B
!define PRODUCT_STORAGE_STANDARD_SERVER_CORE        0x0000002C
!define PRODUCT_STORAGE_WORKGROUP_SERVER_CORE       0x0000002D
!define PRODUCT_STORAGE_ENTERPRISE_SERVER_CORE      0x0000002E
!define PRODUCT_STARTER_N                           0x0000002F
!define PRODUCT_PROFESSIONAL                        0x00000030
!define PRODUCT_PROFESSIONAL_N                      0x00000031
!define PRODUCT_SB_SOLUTION_SERVER                  0x00000032
!define PRODUCT_SERVER_FOR_SB_SOLUTIONS             0x00000033
!define PRODUCT_STANDARD_SERVER_SOLUTIONS           0x00000034
!define PRODUCT_STANDARD_SERVER_SOLUTIONS_CORE      0x00000035
!define PRODUCT_SB_SOLUTION_SERVER_EM               0x00000036
!define PRODUCT_SERVER_FOR_SB_SOLUTIONS_EM          0x00000037
!define PRODUCT_SOLUTION_EMBEDDEDSERVER             0x00000038
!define PRODUCT_SOLUTION_EMBEDDEDSERVER_CORE        0x00000039
!define PRODUCT_SMALLBUSINESS_SERVER_PREMIUM_CORE   0x0000003F
!define PRODUCT_ESSENTIALBUSINESS_SERVER_MGMT       0x0000003B
!define PRODUCT_ESSENTIALBUSINESS_SERVER_ADDL       0x0000003C
!define PRODUCT_ESSENTIALBUSINESS_SERVER_MGMTSVC    0x0000003D
!define PRODUCT_ESSENTIALBUSINESS_SERVER_ADDLSVC    0x0000003E
!define PRODUCT_CLUSTER_SERVER_V                    0x00000040
!define PRODUCT_EMBEDDED                            0x00000041
!define PRODUCT_STARTER_E                           0x00000042
!define PRODUCT_HOME_BASIC_E                        0x00000043
!define PRODUCT_HOME_PREMIUM_E                      0x00000044
!define PRODUCT_PROFESSIONAL_E                      0x00000045
!define PRODUCT_ENTERPRISE_E                        0x00000046
!define PRODUCT_ULTIMATE_E                          0x00000047
;;; win8
!define PRODUCT_CORE_N                              0x00000062 ;Windows 8 N
!define PRODUCT_CORE_COUNTRYSPECIFIC                0x00000063 ;Windows 8 China
!define PRODUCT_CORE_SINGLELANGUAGE                 0x00000064 ;Windows 8 Single Language
!define PRODUCT_CORE                                0x00000065 ;Windows 8

!macro FUNC_GetVersionEx STRUCT_SIZE
    System::Call "*$9(i${STRUCT_SIZE})"
    System::Call "kernel32::GetVersionEx(ir9)i.R0"
!macroend

!macro FUNC_OSVERSIONINFOEX
    System::Alloc ${OSVERSIONINFOEX_SIZE}
    Pop $9
    !insertmacro FUNC_GetVersionEx ${OSVERSIONINFOEX_SIZE}
    ${IfThen} $R0 == 0 ${|} !insertmacro FUNC_GetVersionEx ${OSVERSIONINFO_SIZE} ${|}
    System::Call "*$9(i.R2, i.r0, i.r1, i.r2, i.r3, &t128.r4, &i2.r5, &i2.r6, &i2.r7, &i1.r8, &i1)"
    System::Free $9
!macroend

!macro FUNC_IWbemClassObject->Get NameObject OUT
    System::Call "*(i,i,i)i.R4"
    System::Call "$R3->4(w'${NameObject}',i0,iR4,i0,i0)"
    System::Call "*$R4(i.R6,i,i.R5)"
    StrCmp $R6 3 0 +2
    IntFmt $R5 %u $R5
    StrCmp $R6 8 0 +2
    !ifdef NSIS_UNICODE
      IntFmt $R5 %s $R5
    !else
      IntFmt $R5 %S $R5
    !endif
    System::Call "ole32::VariantClear(iR4)"
    System::Free $R4
    StrCpy ${OUT} $R5
!macroend

!macro FUNC_Win32_OperatingSystem
   System::Call "ole32::CoInitializeEx(i0,i2)"
   System::Call "ole32::CoCreateInstance(g'{4590f811-1d3a-11d0-891f-00aa004b2e24}',i0,i1,g'{dc12a687-737f-11cf-884d-00aa004b2e24}',*i.R0)i.R7"
   ${If} $R7 == 0
      System::Call "ole32::CoInitializeSecurity(i0,i-1,i0,i0,i2,i3,i0,i0,i0)"
      System::Call "$R0->3(w'root\CIMV2',i0,i0,i0,i0,i0,i0,*i.R1)i.R7"
      ${If} $R7 == 0
         System::Call "$R1->20(w'WQL', w'SELECT * FROM Win32_OperatingSystem',i48,i0,*i.R2)i.R7"
         System::Call "$R2->4(i0xffffffff,i1,*i.R3,*i)i.R7"
         ${If} $R7 == 0
            !insertmacro FUNC_IWbemClassObject->Get "Version" $7
            StrCpy $0 $7 1   ;dwMajorVersion
            ${If} $0 >= 6
               StrCpy $1 $7 1 2 ;dwMinorVersion
               !insertmacro FUNC_IWbemClassObject->Get "BuildNumber" $2 ;dwBuildNumber
               !insertmacro FUNC_IWbemClassObject->Get "OSType" $7
               ${If} $7 == 18
                   StrCpy $3 2    ;dwPlatformId
               ${ElseIf} $7 == 19
                   StrCpy $3 3    ;dwPlatformId
               ${Else}
                   StrCpy $3 0    ;dwPlatformId
               ${EndIf}
               !insertmacro FUNC_IWbemClassObject->Get "CSDVersion" $4              ;szCSDVersion
			   StrCmp $4 0 0 +2
			   StrCpy $4 ""
               !insertmacro FUNC_IWbemClassObject->Get "ServicePackMajorVersion" $5 ;wServicePackMajor
               !insertmacro FUNC_IWbemClassObject->Get "ServicePackMinorVersion" $6 ;wServicePackMinor
               !insertmacro FUNC_IWbemClassObject->Get "ProductType" $8             ;wProductType
               !insertmacro FUNC_IWbemClassObject->Get "OperatingSystemSKU" $9      ;wSuiteMask
               System::Call "$R0->2()"
               System::Call "$R1->2()"
               System::Call "$R2->2()"
               System::Call "$R3->2()"
            ${Else}
               System::Call "$R0->2()"
               System::Call "$R1->2()"
               System::Call "$R2->2()"
               System::Call "$R3->2()"
               StrCpy $R0 ""
               StrCpy $R1 ""
               StrCpy $R2 ""
               StrCpy $R3 ""
               StrCpy $7 ""
               !insertmacro FUNC_OSVERSIONINFOEX
            ${EndIf}
         ${Else}
            System::Call "$R0->2()"
            System::Call "$R1->2()"
            System::Call "$R2->2()"
            !insertmacro FUNC_OSVERSIONINFOEX
         ${EndIf}
      ${Else}
        System::Call "$R0->2()"
        System::Call "$R1->2()"
        !insertmacro FUNC_OSVERSIONINFOEX
      ${EndIf}
  ${Else}
        !insertmacro FUNC_OSVERSIONINFOEX
  ${EndIf}
!macroend

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

!macro FUNC_WindowsServicePackMajor Major
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    Push "$5"
    System::Store L
    Pop "${Major}"
!macroend

!macro FUNC_WindowsServicePackMinor Minor
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    Push "$6"
    System::Store L
    Pop "${Minor}"
!macroend

!macro FUNC_WindowsBuildNumber Build
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    Push "$2"
    System::Store L
    Pop "${Build}"
!macroend

!macro FUNC_WindowsVersionMajor Major
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    Push "$0"
    System::Store L
    Pop "${Major}"
!macroend

!macro FUNC_WindowsVersionMinor Minor
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    Push "$1"
    System::Store L
    Pop "${Minor}"
!macroend

!macro FUNC_WindowsVersion WinVer
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    Push "$0.$1"
    System::Store L
    Pop ${WinVer}
!macroend

!macro FUNC_WindowsPlatformId WinId
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    Push "$3"
    System::Store L
    ClearErrors
    Pop "${WinId}"
    IfErrors 0 +2
    StrCpy "${WinId}" ""
!macroend

!macro FUNC_WindowsName WinName
   System::Store S
   ${CallArtificialFunction} FUNC_Win32_OperatingSystem
   ${Select} $3 ;$3 PlatformId
         ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
         ${Case} ${VER_PLATFORM_WIN32_NT}
              ;;;
              ${If} $0 == 6 ;MajorVersion
              ${AndIf} $1 == 3 ;MinorVersion
                   ${If} $8 == ${VER_NT_WORKSTATION}
                       Push "Win8.1"
                   ${Else}
                       Push "Server 2012 R2"
                   ${EndIf}
              ${EndIf}
              ;;;
              ${If} $0 == 6 ;MajorVersion
              ${AndIf} $1 == 2 ;MinorVersion
                   ${If} $8 == ${VER_NT_WORKSTATION}
                       Push "Win8"
                   ${Else}
                       Push "Server 2012"
                   ${EndIf}
              ${EndIf}
              ;;;
              ${If} $0 == 6 ;MajorVersion
              ${AndIf} $1 == 1 ;MinorVersion
                   ${If} $8 == ${VER_NT_WORKSTATION}
                       Push "Win7"
                   ${Else}
                       Push "Server 2008 R2"
                   ${EndIf}
              ${EndIf}
              ;;;
              ${If} $0 == 6 ;MajorVersion
              ${AndIf} $1 == 0 ;MinorVersion
                   ${If} $8 == ${VER_NT_WORKSTATION}
                       Push "Vista"
                   ${Else}
                       Push "Server 2008"
                   ${EndIf}
              ${EndIf}
              ;;;
              ${If} $0 == 5 ;MajorVersion
              ${AndIf} $1 == 2 ;MinorVersion
                  System::Alloc 64
                  Pop $9
                  System::Call "kernel32::GetNativeSystemInfo(ir9)v"
                  System::Call "*$9(&i2.R0)"
                  System::Free $9
                  System::Call "user32::GetSystemMetrics(i${SM_SERVERR2})i.R1"
                  ${If} $R1 != 0
                       Push "Server 2003 R2"
                  ${ElseIf} $8 == ${VER_NT_WORKSTATION}
                  ${AndIf} $R0 == ${PROCESSOR_ARCHITECTURE_AMD64}
                       Push "WinXP x64"
                  ${Else}
                       Push "Server 2003"
                  ${EndIf}
              ${EndIf}
              ;;;
              ${If} $0 == 5 ;MajorVersion
              ${AndIf} $1 == 1 ;MinorVersion
                  Push "WinXP"
              ${EndIf}
              ;;;
              ${If} $0 == 5
              ${AndIf} $1 == 0
                  Push "Win2000"
              ${EndIf}
              ;;;
              ${IfThen} $0 <= 4 ${|}Push "NT"${|}
          ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
         ${Case} ${VER_PLATFORM_WIN32_CE}
              Push "CE"
          ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
         ${Case} ${VER_PLATFORM_WIN32_WINDOWS}
              ${If} $0 == 4 ;MajorVersion
              ${AndIf} $1 == 0 ;MinorVersion
                  StrCpy `$4` `$4` 1 1 ;$4 szCSDVersion
                  ${If} $4 == 'C'
                  ${OrIf} $4 == 'B'
                      Push "95 OSR2"
                  ${Else}
                      Push "95"
                  ${EndIf}
              ${EndIf}
              ;;;
              ${If} $0 == 4 ;MajorVersion
              ${AndIf} $1 == 10 ;MinorVersion
                  StrCpy `$4` `$4` 1 1 ;$4 szCSDVersion
                  ${If} $4 == 'A'
                     Push "98 SE"
                  ${Else}
                     Push "98"
                  ${EndIf}
              ${EndIf}
              ;;;
              ${If} $0 == 4 ;MajorVersion
              ${AndIf} $1 == 90 ;MinorVersion
                  Push "ME"
              ${EndIf}
          ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
         ${Case} ${VER_PLATFORM_WIN32s}
              Push "Win32s"
         ${Default}
   ${EndSelect}
   System::Store L
   ClearErrors
   Pop "${WinName}"
   IfErrors 0 +2
   StrCpy "${WinName}" ""
!macroend

!macro FUNC_WindowsType Type
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    ${If} $3 == ${VER_PLATFORM_WIN32_NT}
       ${If} $0 == 6
          ${IfThen} $9 == ${PRODUCT_ULTIMATE} ${|}Push "Ultimate"${|}
          ${IfThen} $9 = ${PRODUCT_ULTIMATE_N} ${|}Push "Ultimate N"${|}
          ${IfThen} $9 = ${PRODUCT_PROFESSIONAL} ${|}Push "Professional"${|}
          ${IfThen} $9 = ${PRODUCT_PROFESSIONAL_N} ${|}Push  "Professional N"${|}
          ${IfThen} $9 == ${PRODUCT_HOME_PREMIUM} ${|}Push  "Home Premium"${|}
          ${IfThen} $9 = ${PRODUCT_HOME_PREMIUM_N} ${|}Push  "Home Premium N"${|}
          ${IfThen} $9 == ${PRODUCT_HOME_BASIC} ${|}Push  "Home Basic"${|}
          ${IfThen} $9 == ${PRODUCT_HOME_BASIC_N} ${|}Push  "Home Basic N"${|}
          ${IfThen} $9 == ${PRODUCT_ENTERPRISE} ${|}Push  "Enterprise"${|}
          ${IfThen} $9 = ${PRODUCT_ENTERPRISE_N} ${|}Push  "Enterprise N"${|}
          ${IfThen} $9 == ${PRODUCT_BUSINESS} ${|}Push  "Business"${|}
          ${IfThen} $9 = ${PRODUCT_BUSINESS_N} ${|}Push  "Business N"${|}
          ${IfThen} $9 = ${PRODUCT_STARTER} ${|}Push  "Starter"${|}
          ${IfThen} $9 = ${PRODUCT_STARTER_N} ${|}Push  "Starter N"${|}
          ;win8
          ${IfThen} $9 = ${PRODUCT_CORE_N} ${|}Push  "N"${|}
          ${IfThen} $9 = ${PRODUCT_CORE_COUNTRYSPECIFIC} ${|}Push  "China"${|}
          ${IfThen} $9 = ${PRODUCT_CORE_SINGLELANGUAGE} ${|}Push  "Single Language"${|}
          ${IfThen} $9 = ${PRODUCT_CORE} ${|}Push  "Windows 8"${|}
          ;
       ${EndIf}
       ;;;
       ${If} $0 == 5
       ${AndIf} $1 == 2
           System::Alloc 64
           Pop $9
           System::Call "kernel32::GetNativeSystemInfo(ir9)v"
           System::Call "*$9(&i2.R0)"
           System::Free $9
           ${If} $R0 == ${PROCESSOR_ARCHITECTURE_AMD64}
	      IntOp $R1 $7 & ${VER_SUITE_SINGLEUSERTS} ;$7 > SuiteMask
	      IntCmp $R1 0 +2
	      Push "Professional x64 Edition"
           ${EndIf}
       ${EndIf}
       ;;;
       ${If} $0 == 5
       ${AndIf} $1 == 1
           System::Call "user32::GetSystemMetrics(i${SM_TABLETPC})i.R0"
           ${If} $R0 != 0
               Push "Tablet PC Edition"
           ${Else}
               System::Call "user32::GetSystemMetrics(i${SM_MEDIACENTER})i.R0"
               ${If} $R0 != 0
                   Push "Media Center Edition"
               ${Else}
                   System::Alloc 64
                   Pop $9
                   System::Call "kernel32::GetNativeSystemInfo(ir9)v"
                   System::Call "*$9(&i2.R0)"
                   System::Free $9
                   ${If} $R0 == ${PROCESSOR_ARCHITECTURE_AMD64}
                       Push "Professional x64 Edition"
                   ${Else}
                       ;--
                       IntOp $R1 $7 & ${VER_SUITE_EMBEDDEDNT}
                       IntCmp $R1 0 +2
                       Push "Embedded"
                       ;--
                       IntOp $R1 $7 & ${VER_SUITE_PERSONAL}
                       IntCmp $R1 0 +2
                       Push "Home Edition"
                       StrCmp $R1 512 0 +2
                       StrCpy $7 ${VER_SUITE_PERSONAL}
                       ;--
                       IntOp $R1 $7 & ${VER_SUITE_SINGLEUSERTS}
                       IntCmp $R1 0 +2
                       Push "Professional"
                   ${EndIf}
               ${EndIf}
           ${EndIf}
       ${EndIf}
       ;;;
       ${If} $0 == 5
       ${AndIf} $1 == 0
           Push "Professional"
       ${EndIf}
       ;;;
       ${IfThen} $0 == 4 ${|}Push "Workstation 4.0"${|}
    ${EndIf}
    System::Store L
    ClearErrors
    Pop ${Type}
    IfErrors 0 +2
    StrCpy ${Type} ""
!macroend

!macro FUNC_WindowsServerName Server
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    StrCpy $R3 ""
    ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
    ${If} $3 == ${VER_PLATFORM_WIN32_NT}
            ${If} $8 == ${VER_NT_SERVER}
            ${OrIf} $8 == ${VER_NT_DOMAIN_CONTROLLER}
                ${If} $0 == 6
                    ${IfThen} $9 = ${PRODUCT_CLUSTER_SERVER} ${|}Push "HPC Edition"${|}
                    ${IfThen} $9 == ${PRODUCT_DATACENTER_SERVER} ${|}Push "Datacenter"${|}
                    ${IfThen} $9 = ${PRODUCT_DATACENTER_SERVER_V} ${|}Push "Datacenter without Hyper-V"${|}
                    ${IfThen} $9 = ${PRODUCT_DATACENTER_SERVER_CORE} ${|}Push "Datacenter (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_DATACENTER_SERVER_CORE_V} ${|}Push "Datacenter without Hyper-V (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_ENTERPRISE_SERVER} ${|}Push "Enterprise"${|}
                    ${IfThen} $9 = ${PRODUCT_ENTERPRISE_SERVER_V} ${|}Push "Enterprise without Hyper-V"${|}
                    ${IfThen} $9 = ${PRODUCT_ENTERPRISE_SERVER_CORE} ${|}Push "Enterprise (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_ENTERPRISE_SERVER_CORE_V} ${|}Push "Enterprise without Hyper-V (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_ENTERPRISE_SERVER_IA64} ${|}Push "Enterprise for Itanium-based Systems"${|}
                    ${IfThen} $9 = ${PRODUCT_HYPERV} ${|}Push "Hyper-V Server"${|}
                    ${IfThen} $9 = ${PRODUCT_MEDIUMBUSINESS_SERVER_MANAGEMENT} ${|}Push "Essential Business Server Management Server"${|}
                    ${IfThen} $9 = ${PRODUCT_MEDIUMBUSINESS_SERVER_MESSAGING} ${|}Push "Essential Business Server Messaging Server"${|}
                    ${IfThen} $9 = ${PRODUCT_MEDIUMBUSINESS_SERVER_SECURITY} ${|}Push "Essential Business Server Security Server"${|}
                    ${IfThen} $9 = ${PRODUCT_SERVER_FOR_SMALLBUSINESS} ${|}Push "for Windows Essential Server Solutions"${|}
                    ${IfThen} $9 = ${PRODUCT_SERVER_FOR_SMALLBUSINESS_V} ${|}Push "without Hyper-V for Windows Essential Server Solutions"${|}
                    ${IfThen} $9 = ${PRODUCT_SERVER_FOUNDATION} ${|}Push "Foundation"${|}
                    ${IfThen} $9 == ${PRODUCT_SMALLBUSINESS_SERVER} ${|}Push "Small Business Server"${|}
                    ${IfThen} $9 = ${PRODUCT_SMALLBUSINESS_SERVER_PREMIUM} ${|}Push "Small Business Server Premium"${|}
                    ${IfThen} $9 = ${PRODUCT_SOLUTION_EMBEDDEDSERVER} ${|}Push "MultiPoint Server"${|}
                    ${IfThen} $9 == ${PRODUCT_STANDARD_SERVER} ${|}Push "Standard"${|}
                    ${IfThen} $9 = ${PRODUCT_STANDARD_SERVER_V} ${|}Push "Standard without Hyper-V"${|}
                    ${IfThen} $9 = ${PRODUCT_STANDARD_SERVER_CORE} ${|}Push "Standard (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_STANDARD_SERVER_CORE_V} ${|}Push "Standard without Hyper-V (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_STORAGE_ENTERPRISE_SERVER} ${|}Push "Storage Server Enterprise"${|}
                    ${IfThen} $9 = ${PRODUCT_STORAGE_ENTERPRISE_SERVER_CORE} ${|}Push "Storage Server Enterprise (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_STORAGE_EXPRESS_SERVER} ${|}Push "Storage Server Express"${|}
                    ${IfThen} $9 = ${PRODUCT_STORAGE_EXPRESS_SERVER_CORE} ${|}Push "Storage Server Express (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_STORAGE_STANDARD_SERVER} ${|}Push "Storage Server Standard"${|}
                    ${IfThen} $9 = ${PRODUCT_STORAGE_STANDARD_SERVER_CORE} ${|}Push "Storage Server Standard (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_STORAGE_WORKGROUP_SERVER} ${|}Push "Storage Server Workgroup"${|}
                    ${IfThen} $9 = ${PRODUCT_STORAGE_WORKGROUP_SERVER_CORE} ${|}Push "Storage Server Workgroup (core installation)"${|}
                    ${IfThen} $9 = ${PRODUCT_WEB_SERVER} ${|}Push "Web Server Edition"${|}
                    ${IfThen} $9 = ${PRODUCT_WEB_SERVER_CORE} ${|}Push "Web Server Edition (core installation)"${|}
                ${EndIf}
                ;;;
                ${If} $0 == 5
                ${AndIf} $1 == 2
                    System::Alloc 64
                    Pop $9
                    System::Call "kernel32::GetNativeSystemInfo(ir9)v"
                    System::Call "*$9(&i2.R0)"
                    System::Free $9
                    ${If} $R0 == ${PROCESSOR_ARCHITECTURE_IA64}
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_DATACENTER}
                         IntCmp $R1 0 +2
                         Push "Datacenter Edition for Itanium-based Systems"
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_ENTERPRISE}
                         IntCmp $R1 0 +2
                         Push "Enterprise Edition for Itanium-based Systems"
                    ${ElseIf} $R0 == ${PROCESSOR_ARCHITECTURE_AMD64}
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_DATACENTER}
                         IntCmp $R1 0 +2
                         StrCpy $R3 "Datacenter x64 Edition"
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_ENTERPRISE}
                         IntCmp $R1 0 +2
                         StrCpy $R3 "Enterprise x64 Edition"
                         ;--
                         ${IfNot} $R3 == ""
                            Push "$R3"
                         ${Else}
                            Push "Standard x64 Edition"
                         ${EndIf}
                    ${Else}
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_DATACENTER}
                         IntCmp $R1 0 +2
                         StrCpy $R3 "Datacenter Edition"
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_ENTERPRISE}
                         IntCmp $R1 0 +2
                         StrCpy $R3 "Enterprise Edition"
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_BLADE}
                         IntCmp $R1 0 +2
                         StrCpy $R3 "Web Edition"
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_STORAGE_SERVER}
                         IntCmp $R1 0 +2
                         StrCpy $R2 "Storage Server"
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_COMPUTE_SERVER}
                         IntCmp $R1 0 +2
                         StrCpy $R3 "Compute Cluster Edition"
                         ;--
                         IntOp $R1 $7 & ${VER_SUITE_SMALLBUSINESS}
                         IntCmp $R1 0 +2
                         StrCpy $R3 "Small Business Server"
                         ;--
                         ${IfNot} $R3 == ""
                            Push "$R3"
                         ${Else}
                            Push "Standard Edition"
                         ${EndIf}
                    ${EndIf}
                ${EndIf}
               ;;;
                ${If} $0 == 5
                ${AndIf} $1 == 0
                    ;--
                    IntOp $R1 $7 & ${VER_SUITE_DATACENTER}
                    IntCmp $R1 0 +2
                    StrCpy $R3 "Datacenter Server"
                    ;--
                    IntOp $R1 $7 & ${VER_SUITE_ENTERPRISE}
                    IntCmp $R1 0 +2
                    StrCpy $R3 "Advanced Server"
                    ;--
                    ${IfNot} $R3 == ""
                        Push "$R3"
                    ${Else}
                        Push "Server"
                    ${EndIf}
                ${EndIf}
                ;;;
                ${If} $0 == 4
                    ;--
                    IntOp $R1 $7 & ${VER_SUITE_ENTERPRISE}
                    IntCmp $R1 0 +2
                    StrCpy $R3 "Server 4.0 Enterprise Edition"
                   ;--
                    ${IfNot} $R3 == ""
                        Push "$R3"
                    ${Else}
                        Push "Server 4.0"
                    ${EndIf}
                ${EndIf}
            ${EndIf}
        ;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
        ${If} $R2 == ${OSVERSIONINFO_SIZE}
           ReadRegStr $9 HKLM "SYSTEM\CurrentControlSet\Control\ProductOptions" "ProductType"
           StrCmp $9 "WinNT" 0 +2
           Push "Workstation"
           StrCmp $9 "LANMANNT" 0 +2
           Push "Server"
           StrCmp $9 "SERVERNT" 0 +2
           Push "Advanced Server"
        ${EndIf}
    ${EndIf}
    System::Store L
    ClearErrors
    Pop "${Server}"
    IfErrors 0 +2
    StrCpy "${Server}" ""
!macroend

!macro FUNC_WindowsPlatformArchitecture Bit
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    ${If} $3 == ${VER_PLATFORM_WIN32_NT}
         System::Alloc 64
         Pop $9
         System::Call "kernel32::GetNativeSystemInfo(ir9)v"
         System::Call "*$9(&i2.R0)"
         System::Free $9
         ${If} $R0 == ${PROCESSOR_ARCHITECTURE_AMD64}
         ${OrIf} $R0 == ${PROCESSOR_ARCHITECTURE_IA64}
             Push "64"
         ${Else}
             Push "32"
         ${EndIf}
    ${EndIf}
    System::Store L
    ClearErrors
    Pop "${Bit}"
    IfErrors 0 +2
    StrCpy "${Bit}" ""
!macroend

!macro FUNC_WindowsServicePack SP
    System::Store S
    ${CallArtificialFunction} FUNC_Win32_OperatingSystem
    ${If} $3 == ${VER_PLATFORM_WIN32_NT}
        ${If} $0 == 4
	   ${If} "$4" == "Service Pack 6"
	      Push "Service Pack 6"
           ${Else}
	      ReadRegStr $9 HKLM "SOFTWARE\Microsoft\Windows NT\CurrentVersion\Hotfix\Q246009" ""
	      ${If} ${Errors}
	         Push "$4"
	      ${Else}
	         Push "Service Pack 6a"
	      ${EndIf}
	   ${EndIf}
	${Else}
           Push "$4"
	${EndIf}
     ${Else}
        Push "$4"
     ${EndIf}
     System::Store L
     ClearErrors
     Pop "${SP}"
     IfErrors 0 +2
     StrCpy "${SP}" ""
!macroend

!endif