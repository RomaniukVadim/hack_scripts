//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: project.h
// $Revision: 194 $
// $Date: 2014-07-11 16:55:06 +0400 (Пт, 11 июл 2014) $
// description:
//	Project global includes and definitions.


#ifndef __PROJECT_H_
#define __PROJECT_H_

#include "main.h"
#include "vnc.h"
#include "rfb.h"
#include "vncdll.h"
#include "common\strings.h"
#include "common\globals.h"

#include "rt\osinfo.h"
#include "rt\utils.h"
#include "rt\crc32.h"
#include "rt\reg.h"

#include "names.h"
#include "mouse.h"
#include "kbd.h"
#include "wnd.h"
#include "layout.h"
#include "wnd_watcher.h"

// Default security attributes for our global objects
// Should be defined anywhere in the application
extern	SECURITY_ATTRIBUTES		g_DefaultSA;

#endif //__PROJECT_H_