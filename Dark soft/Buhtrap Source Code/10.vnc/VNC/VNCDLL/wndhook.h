//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: wndhook.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description: 
//	Windows hook.


// Specifies a thread for wich a hook routine was set by calling SetWindowsHookEx()
typedef struct _WND_THREAD
{
#if _DEBUG
	ULONG		Magic;
#endif
	LIST_ENTRY	Entry;		// Global thread list's entry
	HHOOK		hHook;		// Hook routine handle
	ULONG		ThreadId;	// Thread ID
} WND_THREAD, *PWND_THREAD;

#define WND_THREAD_MAGIC		'rhTK'
#define	ASSERT_WND_THREAD(x)	ASSERT(x->Magic == WND_THREAD_MAGIC)


WINERROR	WndHookStartup(VOID);
VOID		WndHookCleanup(VOID);
WINERROR	WndHookOnThreadAttach(ULONG ThreadId);
WINERROR	WndHookOnThreadDetach(ULONG ThreadId);

WINERROR WndHookOnThreadHookAttach(ULONG ThreadId);
