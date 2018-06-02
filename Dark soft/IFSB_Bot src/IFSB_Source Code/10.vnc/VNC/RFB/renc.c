//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: rfb.c
// $Revision: 137 $
// $Date: 2013-07-23 16:57:05 +0400 (Вт, 23 июл 2013) $
// description:
//	encoded rect functions.

#include "main.h"
#include "vnc.h"
#include "rfb.h"
#include "translate.h"

// Translate a rectangle to remote color format
VOID RfbTranslateRect(PRFB_SESSION RfbSession, BYTE *source, BYTE *dest, int x, int y, int h, int w)
{
	DWORD dwWidthInBytes = RfbSession->VncSession->Desktop.dwWidthInBytes;
	// Calculate where in the source rectangle to read from
	BYTE *sourcepos = (BYTE *)(source + 
		(dwWidthInBytes * y)+
		(x * (RfbSession->LocalPixelFormat.BitsPerPixel / 8)));

	// Call the translation function
	((rfbTranslateFnType)(RfbSession->Transfunc)) (
		RfbSession->Transtable,
		&RfbSession->LocalPixelFormat,
		&RfbSession->TranslatePixelFormat,
		(char *)sourcepos,
		(char *)dest,
		dwWidthInBytes,
		w,h
		);
}

/*
 * Send a given rectangle in raw encoding (rfbEncodingRaw).
 */

BOOL
	RfbSendRectEncodingRaw(
		PRFB_SESSION RfbSession,
		LPRECT Rect
		)
{
	RFB_FRAMEBUFFER_UPDATE_RECT_HEADER RectHeader;
	int nlines;
	int w = Rect->right-Rect->left;
	int h = Rect->bottom-Rect->top;
	int x = Rect->left;
	int y = Rect->top;
	int bytesPerLine = w * (RfbSession->LocalPixelFormat.BitsPerPixel / 8);
	int bytesPerLineR = w * (RfbSession->PixelFormat.BitsPerPixel / 8);

	RectHeader.Rect.x   = Swap16IfLE(Rect->left);
	RectHeader.Rect.y   = Swap16IfLE(Rect->top);
	RectHeader.Rect.w   = Swap16IfLE(w);
	RectHeader.Rect.h   = Swap16IfLE(h);
	RectHeader.Encoding = Swap32IfLE(RfbEncodingRAW);

	memcpy(&RfbSession->UpdateBuf[RfbSession->UpdateBufLen], &RectHeader,sz_rfbFramebufferUpdateRectHeader);
	RfbSession->UpdateBufLen += sz_rfbFramebufferUpdateRectHeader;

	nlines = (UPDATE_BUF_SIZE - RfbSession->UpdateBufLen) / bytesPerLine;

	while (TRUE) 
	{
		if (nlines > h){
			nlines = h;
		}

		// translate colors
		// nlines lines of rect
		RfbTranslateRect(
			RfbSession,
			RfbSession->VncSession->Desktop.FrameBuffer,
			(char *)&RfbSession->UpdateBuf[RfbSession->UpdateBufLen],
			x,y,nlines,w
			);

		RfbSession->UpdateBufLen += nlines * bytesPerLineR;
		h -= nlines;
		y += nlines;

		if (h == 0) { /* rect fitted in buffer, do next one */
			return TRUE;
		}

		/* buffer full - flush partial rect and do another nlines */
		if (!RfbSendUpdateBuf(RfbSession))
			return FALSE;

		nlines = (UPDATE_BUF_SIZE - RfbSession->UpdateBufLen) / bytesPerLine;
		if (nlines == 0) {
			DbgPrint("rfbSendRectEncodingRaw: send buffer too small for %d "
				"bytes per line\n", bytesPerLine);
			return FALSE;
		}
	}
	return TRUE;
}


/*
 * Send an empty rectangle with encoding field set to value of
 * rfbEncodingLastRect to notify client that this is the last
 * rectangle in framebuffer update ("LastRect" extension of RFB
 * protocol).
 */

BOOL RfbSendLastRectMarker(PRFB_SESSION RfbSession)
{
	RFB_FRAMEBUFFER_UPDATE_RECT_HEADER RectHeader;

	if (RfbSession->UpdateBufLen + sz_rfbFramebufferUpdateRectHeader > UPDATE_BUF_SIZE) {
		if (!RfbSendUpdateBuf(RfbSession)){
				return FALSE;
		}
	}

	RectHeader.Encoding = Swap32IfLE(RfbEncodingLastRect);
	RectHeader.Rect.x = 0;
	RectHeader.Rect.y = 0;
	RectHeader.Rect.w = 0;
	RectHeader.Rect.h = 0;

	memcpy(&RfbSession->UpdateBuf[RfbSession->UpdateBufLen], (char *)&RectHeader,sz_rfbFramebufferUpdateRectHeader);
	RfbSession->UpdateBufLen += sz_rfbFramebufferUpdateRectHeader;

	return TRUE;
}

