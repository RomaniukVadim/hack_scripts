/*
 * zrle.c
 *
 * Routines to implement Zlib Run-length Encoding (ZRLE).
 */

#include "main.h"
#include "vnc.h"
#include "rfb.h"

#include "zrlepalettehelper.h"
#include "zrleoutstream.h"


#define ENDIAN_LITTLE 0
#define ENDIAN_BIG 1
#define ENDIAN_NO 2
#define BPP 8
#define ZYWRLE_ENDIAN ENDIAN_NO
#include "zrleencodetemplate.c"
#undef BPP
#define BPP 15
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_LITTLE
#include "zrleencodetemplate.c"
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_BIG
#include "zrleencodetemplate.c"
#undef BPP
#define BPP 16
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_LITTLE
#include "zrleencodetemplate.c"
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_BIG
#include "zrleencodetemplate.c"
#undef BPP
#define BPP 32
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_LITTLE
#include "zrleencodetemplate.c"
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_BIG
#include "zrleencodetemplate.c"
#define CPIXEL 24A
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_LITTLE
#include "zrleencodetemplate.c"
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_BIG
#include "zrleencodetemplate.c"
#undef CPIXEL
#define CPIXEL 24B
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_LITTLE
#include "zrleencodetemplate.c"
#undef ZYWRLE_ENDIAN
#define ZYWRLE_ENDIAN ENDIAN_BIG
#include "zrleencodetemplate.c"
#undef CPIXEL
#undef BPP


/*
 * zrleBeforeBuf contains pixel data in the client's format.  It must be at
 * least one pixel bigger than the largest tile of pixel data, since the
 * ZRLE encoding algorithm writes to the position one past the end of the pixel
 * data.
 */


/*
 * rfbSendRectEncodingZRLE - send a given rectangle using ZRLE encoding.
 */

BOOL RfbSendRectEncodingZRLE(PRFB_SESSION RfbSession, LPRECT Rect)
{
	zrleOutStream* zos;
	RFB_FRAMEBUFFER_UPDATE_RECT_HEADER RectHeader;
	RFB_ZRLE_HEADER hdr;
	int i;
	char *zrleBeforeBuf;

	int w = Rect->right-Rect->left;
	int h = Rect->bottom-Rect->top;

	int x = Rect->left;
	int y = Rect->top;

	if (RfbSession->hZrle  == NULL) {
		RfbSession->hZrle = (HANDLE)zrleOutStreamNew();
	}
	if (RfbSession->preferredEncoding == RfbEncodingZYWRLE) {
		if (RfbSession->tightQualityLevel < 0) {
			RfbSession->zywrleLevel = 1;
		} else if (RfbSession->tightQualityLevel < 3) {
			RfbSession->zywrleLevel = 3;
		} else if (RfbSession->tightQualityLevel < 6) {
			RfbSession->zywrleLevel = 2;
		} else {
			RfbSession->zywrleLevel = 1;
		}
	} else{
		RfbSession->zywrleLevel = 0;
	}

	zos = (zrleOutStream*)RfbSession->hZrle;
	zrleBeforeBuf = zos->zrleBeforeBuf;
	zos->in.ptr = zos->in.start;
	zos->out.ptr = zos->out.start;

	switch ( RfbSession->PixelFormat.BitsPerPixel ) 
	{
	case 8:
		zrleEncode8NE(RfbSession,x, y, w, h, zos, zrleBeforeBuf);
		break;

	case 16:
		if (RfbSession->PixelFormat.GreenMax > 0x1F) {
			if (RfbSession->PixelFormat.BigEndianFlag)	
				zrleEncode16BE(RfbSession,x, y, w, h, zos, zrleBeforeBuf);
			else
				zrleEncode16LE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
		} else {
			if (RfbSession->PixelFormat.BigEndianFlag)
				zrleEncode15BE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
			else
				zrleEncode15LE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
		}
		break;

	case 32: 
		{
			BOOL fitsInLS3Bytes
				= ((RfbSession->PixelFormat.RedMax   << RfbSession->PixelFormat.RedShift)   < (1<<24) &&
				(RfbSession->PixelFormat.GreenMax << RfbSession->PixelFormat.GreenShift) < (1<<24) &&
				(RfbSession->PixelFormat.BlueMax  << RfbSession->PixelFormat.BlueShift)  < (1<<24));

			BOOL fitsInMS3Bytes = (RfbSession->PixelFormat.RedShift   > 7  &&
				RfbSession->PixelFormat.GreenShift > 7  &&
				RfbSession->PixelFormat.BlueShift  > 7);

			if ((fitsInLS3Bytes && !RfbSession->PixelFormat.BigEndianFlag) ||
				(fitsInMS3Bytes && RfbSession->PixelFormat.BigEndianFlag)) {
					if (RfbSession->PixelFormat.BigEndianFlag)
						zrleEncode24ABE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
					else
						zrleEncode24ALE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
			}
			else if ((fitsInLS3Bytes && RfbSession->PixelFormat.BigEndianFlag) ||
				(fitsInMS3Bytes && !RfbSession->PixelFormat.BigEndianFlag)) {
					if (RfbSession->PixelFormat.BigEndianFlag)
						zrleEncode24BBE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
					else
						zrleEncode24BLE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
			}
			else {
				if (RfbSession->PixelFormat.BigEndianFlag)
					zrleEncode32BE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
				else
					zrleEncode32LE(RfbSession,x, y, w, h, zos, zrleBeforeBuf );
			}
		}
		break;
	}

	if (RfbSession->UpdateBufLen + sz_rfbFramebufferUpdateRectHeader + sz_rfbZRLEHeader
		> UPDATE_BUF_SIZE)
	{
		if (!RfbSendUpdateBuf(RfbSession))
			return FALSE;
	}

	RectHeader.Rect.x = Swap16IfLE(x);
	RectHeader.Rect.y = Swap16IfLE(y);
	RectHeader.Rect.w = Swap16IfLE(w);
	RectHeader.Rect.h = Swap16IfLE(h);
	RectHeader.Encoding = Swap32IfLE(RfbSession->preferredEncoding);

	memcpy(RfbSession->UpdateBuf+RfbSession->UpdateBufLen, (char *)&RectHeader,sz_rfbFramebufferUpdateRectHeader);
	RfbSession->UpdateBufLen += sz_rfbFramebufferUpdateRectHeader;

	hdr.length = Swap32IfLE(ZRLE_BUFFER_LENGTH(&zos->out));

	memcpy(RfbSession->UpdateBuf+RfbSession->UpdateBufLen, (char *)&hdr, sz_rfbZRLEHeader);
	RfbSession->UpdateBufLen += sz_rfbZRLEHeader;

	/* copy into updateBuf and send from there.  Maybe should send directly? */

	for (i = 0; i < ZRLE_BUFFER_LENGTH(&zos->out);) {

		int bytesToCopy = UPDATE_BUF_SIZE - RfbSession->UpdateBufLen;

		if (i + bytesToCopy > ZRLE_BUFFER_LENGTH(&zos->out)) {
			bytesToCopy = ZRLE_BUFFER_LENGTH(&zos->out) - i;
		}

		memcpy(RfbSession->UpdateBuf+RfbSession->UpdateBufLen, (CARD8*)zos->out.start + i, bytesToCopy);

		RfbSession->UpdateBufLen += bytesToCopy;
		i += bytesToCopy;

		if (RfbSession->UpdateBufLen == UPDATE_BUF_SIZE) {
			if (!RfbSendUpdateBuf(RfbSession))
				return FALSE;
		}
	}

	return TRUE;
}


void rfbFreeZrleData(PRFB_SESSION RfbSession)
{
	if (RfbSession->hZrle) {
		zrleOutStreamFree((zrleOutStream *)RfbSession->hZrle);
	}
	RfbSession->hZrle = NULL;
}

