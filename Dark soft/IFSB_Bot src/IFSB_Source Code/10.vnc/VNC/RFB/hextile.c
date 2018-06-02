#include "main.h"
#include "vnc.h"
#include "rfb.h"
#include "renc.h"

/* __RFB_CONCAT2 concatenates its two arguments.  __RFB_CONCAT2E does the same
   but also expands its arguments if they are macros */

#ifndef __RFB_CONCAT2E
#define __RFB_CONCAT2(a,b) a##b
#define __RFB_CONCAT2E(a,b) __RFB_CONCAT2(a,b)
#endif

#ifndef __RFB_CONCAT3E
#define __RFB_CONCAT3(a,b,c) a##b##c
#define __RFB_CONCAT3E(a,b,c) __RFB_CONCAT3(a,b,c)
#endif

#define PIXEL_T __RFB_CONCAT2E(CARD,BPP)

static BOOL sendHextiles8(PRFB_SESSION RfbSession, int x, int y, int w, int h);
static BOOL sendHextiles16(PRFB_SESSION RfbSession, int x, int y, int w, int h);
static BOOL sendHextiles32(PRFB_SESSION RfbSession, int x, int y, int w, int h);


/*
 * rfbSendRectEncodingHextile - send a rectangle using hextile encoding.
 */

BOOL RfbSendRectEncodingHextile(PRFB_SESSION RfbSession, LPRECT Rect)
{
	RFB_FRAMEBUFFER_UPDATE_RECT_HEADER RectHeader;

	int w = Rect->right-Rect->left;
	int h = Rect->bottom-Rect->top;

	int x = Rect->left;
	int y = Rect->top;

	if (RfbSession->UpdateBufLen + sz_rfbFramebufferUpdateRectHeader > UPDATE_BUF_SIZE) {
		if (!RfbSendUpdateBuf(RfbSession))
			return FALSE;
	}

	RectHeader.Rect.x = Swap16IfLE(x);
	RectHeader.Rect.y = Swap16IfLE(y);
	RectHeader.Rect.w = Swap16IfLE(w);
	RectHeader.Rect.h = Swap16IfLE(h);
	RectHeader.Encoding = Swap32IfLE(RfbEncodingHextile);

	memcpy(&RfbSession->UpdateBuf[RfbSession->UpdateBufLen], (char *)&RectHeader,
		sz_rfbFramebufferUpdateRectHeader);
	RfbSession->UpdateBufLen += sz_rfbFramebufferUpdateRectHeader;

	switch (RfbSession->PixelFormat.BitsPerPixel) {
	case 8:
		return sendHextiles8(RfbSession, x, y, w, h);
	case 16:
		return sendHextiles16(RfbSession, x, y, w, h);
	case 32:
		return sendHextiles32(RfbSession, x, y, w, h);
	}
	return FALSE;
}


#define PUT_PIXEL8(pix) (RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = (pix))

#define PUT_PIXEL16(pix) (RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = ((char*)&(pix))[0], \
                          RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = ((char*)&(pix))[1])

#define PUT_PIXEL32(pix) (RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = ((char*)&(pix))[0], \
                          RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = ((char*)&(pix))[1], \
                          RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = ((char*)&(pix))[2], \
                          RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = ((char*)&(pix))[3])


#define DEFINE_SEND_HEXTILES(bpp)                                               \
                                                                                \
                                                                                \
static BOOL subrectEncode##bpp(PRFB_SESSION RfbSession, PIXEL_T *data,        \
		int w, int h, PIXEL_T bg, PIXEL_T fg, BOOL mono);\
static void testColours##bpp(PIXEL_T *data, int size, BOOL *mono,      \
                  BOOL *solid, PIXEL_T *bg, PIXEL_T *fg);        \
                                                                                \
                                                                                \
/*                                                                              \
 * rfbSendHextiles                                                              \
 */                                                                             \
                                                                                \
static BOOL                                                                  \
sendHextiles##bpp(PRFB_SESSION RfbSession, int rx, int ry, int rw, int rh) {    \
    int x, y, w, h;                                                             \
    int startUblen;                                                             \
    PIXEL_T bg = 0, fg = 0, newBg, newFg;                                 \
    BOOL mono, solid;                                                        \
    BOOL validBg = FALSE;                                                    \
    BOOL validFg = FALSE;                                                    \
    PIXEL_T clientPixelData[16*16*(bpp/8)];                               \
                                                                                \
    for (y = ry; y < ry+rh; y += 16) {                                          \
        for (x = rx; x < rx+rw; x += 16) {                                      \
            w = h = 16;                                                         \
            if (rx+rw - x < 16)                                                 \
                w = rx+rw - x;                                                  \
            if (ry+rh - y < 16)                                                 \
                h = ry+rh - y;                                                  \
                                                                                \
            if ((RfbSession->UpdateBufLen + 1 + (2 + 16 * 16) * (bpp/8)) >                     \
                UPDATE_BUF_SIZE) {                                              \
                if (!RfbSendUpdateBuf(RfbSession))                                      \
                    return FALSE;                                               \
            }                                                                   \
                                                                                \
            RfbTranslateRect(RfbSession,                                        \
                RfbSession->VncSession->Desktop.FrameBuffer,                    \
                (char *)clientPixelData,x,y,h,w);                               \
                                                                                \
            startUblen = RfbSession->UpdateBufLen;                              \
            RfbSession->UpdateBuf[startUblen] = 0;                              \
            RfbSession->UpdateBufLen++;                                         \
                                                                                \
            testColours##bpp(clientPixelData, w * h,                            \
                             &mono, &solid, &newBg, &newFg);                    \
                                                                                \
            if (!validBg || (newBg != bg)) {                                    \
                validBg = TRUE;                                                 \
                bg = newBg;                                                     \
                RfbSession->UpdateBuf[startUblen] |= rfbHextileBackgroundSpecified; \
                PUT_PIXEL##bpp(bg);                                             \
            }                                                                   \
                                                                                \
            if (solid) {                                                        \
                continue;                                                       \
            }                                                                   \
                                                                                \
            RfbSession->UpdateBuf[startUblen] |= rfbHextileAnySubrects;         \
                                                                                \
            if (mono) {                                                         \
                if (!validFg || (newFg != fg)) {                                \
                    validFg = TRUE;                                             \
                    fg = newFg;                                                 \
                    RfbSession->UpdateBuf[startUblen] |= rfbHextileForegroundSpecified; \
                    PUT_PIXEL##bpp(fg);                                         \
                }                                                               \
            } else {                                                            \
                validFg = FALSE;                                                \
                RfbSession->UpdateBuf[startUblen] |= rfbHextileSubrectsColoured; \
            }                                                                   \
                                                                                \
            if (!subrectEncode##bpp(RfbSession, clientPixelData, w, h, bg, fg, mono)) { \
                /* encoding was too large, use raw */                           \
                validBg = FALSE;                                                \
                validFg = FALSE;                                                \
                RfbSession->UpdateBufLen = startUblen;                          \
                RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = rfbHextileRaw; \
                RfbTranslateRect(RfbSession,                                    \
                   RfbSession->VncSession->Desktop.FrameBuffer,                 \
                   (char *)clientPixelData,x,y,h,w);                            \
                                                                                \
                memcpy(&RfbSession->UpdateBuf[RfbSession->UpdateBufLen], (char *)clientPixelData, \
                       w * h * (bpp/8));                                        \
                                                                                \
                RfbSession->UpdateBufLen += w * h * (bpp/8);                    \
            }                                                                   \
        }                                                                       \
    }                                                                           \
                                                                                \
    return TRUE;                                                                \
}                                                                               \
                                                                                \
                                                                                \
static BOOL                                                                  \
subrectEncode##bpp(PRFB_SESSION RfbSession, PIXEL_T *data, int w, int h,          \
                   PIXEL_T bg, PIXEL_T fg, BOOL mono)            \
{                                                                               \
    PIXEL_T cl2;                                                          \
    int x,y;                                                                    \
    int i,j;                                                                    \
    int hx=0,hy,vx=0,vy;                                                        \
    int hyflag;                                                                 \
    PIXEL_T *seg;                                                         \
    PIXEL_T *line;                                                        \
    int hw,hh,vw,vh;                                                            \
    int thex,they,thew,theh;                                                    \
    int numsubs = 0;                                                            \
    int newLen;                                                                 \
    int nSubrectsUblen;                                                         \
                                                                                \
    nSubrectsUblen = RfbSession->UpdateBufLen;                                  \
    RfbSession->UpdateBufLen++;                                                 \
                                                                                \
    for (y=0; y<h; y++) {                                                       \
        line = data+(y*w);                                                      \
        for (x=0; x<w; x++) {                                                   \
            if (line[x] != bg) {                                                \
                cl2 = line[x];                                                  \
                hy = y-1;                                                       \
                hyflag = 1;                                                     \
                for (j=y; j<h; j++) {                                           \
                    seg = data+(j*w);                                           \
                    if (seg[x] != cl2) {break;}                                 \
                    i = x;                                                      \
                    while ((seg[i] == cl2) && (i < w)) i += 1;                  \
                    i -= 1;                                                     \
                    if (j == y) vx = hx = i;                                    \
                    if (i < vx) vx = i;                                         \
                    if ((hyflag > 0) && (i >= hx)) {                            \
                        hy += 1;                                                \
                    } else {                                                    \
                        hyflag = 0;                                             \
                    }                                                           \
                }                                                               \
                vy = j-1;                                                       \
                                                                                \
                /* We now have two possible subrects: (x,y,hx,hy) and           \
                 * (x,y,vx,vy).  We'll choose the bigger of the two.            \
                 */                                                             \
                hw = hx-x+1;                                                    \
                hh = hy-y+1;                                                    \
                vw = vx-x+1;                                                    \
                vh = vy-y+1;                                                    \
                                                                                \
                thex = x;                                                       \
                they = y;                                                       \
                                                                                \
                if ((hw*hh) > (vw*vh)) {                                        \
                    thew = hw;                                                  \
                    theh = hh;                                                  \
                } else {                                                        \
                    thew = vw;                                                  \
                    theh = vh;                                                  \
                }                                                               \
                                                                                \
                if (mono) {                                                     \
                    newLen = RfbSession->UpdateBufLen - nSubrectsUblen + 2;     \
                } else {                                                        \
                    newLen = RfbSession->UpdateBufLen - nSubrectsUblen + bpp/8 + 2; \
                }                                                               \
                                                                                \
                if (newLen > (w * h * (bpp/8)))                                 \
                    return FALSE;                                               \
                                                                                \
                numsubs += 1;                                                   \
                                                                                \
                if (!mono) PUT_PIXEL##bpp(cl2);                                 \
                                                                                \
                RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = rfbHextilePackXY(thex,they); \
                RfbSession->UpdateBuf[RfbSession->UpdateBufLen++] = rfbHextilePackWH(thew,theh); \
                                                                                \
                /*                                                              \
                 * Now mark the subrect as done.                                \
                 */                                                             \
                for (j=they; j < (they+theh); j++) {                            \
                    for (i=thex; i < (thex+thew); i++) {                        \
                        data[j*w+i] = bg;                                       \
                    }                                                           \
                }                                                               \
            }                                                                   \
        }                                                                       \
    }                                                                           \
                                                                                \
    RfbSession->UpdateBuf[nSubrectsUblen] = numsubs;                            \
                                                                                \
    return TRUE;                                                                \
}                                                                               \
                                                                                \
                                                                                \
/*                                                                              \
 * testColours() tests if there are one (solid), two (mono) or more             \
 * colours in a tile and gets a reasonable guess at the best background         \
 * pixel, and the foreground pixel for mono.                                    \
 */                                                                             \
                                                                                \
static void                                                                     \
testColours##bpp(PIXEL_T *data, int size, BOOL *mono, BOOL *solid,  \
                 PIXEL_T *bg, PIXEL_T *fg) {                        \
    PIXEL_T colour1 = 0, colour2 = 0;                                     \
    int n1 = 0, n2 = 0;                                                         \
    *mono = TRUE;                                                               \
    *solid = TRUE;                                                              \
                                                                                \
    for (; size > 0; size--, data++) {                                          \
                                                                                \
        if (n1 == 0)                                                            \
            colour1 = *data;                                                    \
                                                                                \
        if (*data == colour1) {                                                 \
            n1++;                                                               \
            continue;                                                           \
        }                                                                       \
                                                                                \
        if (n2 == 0) {                                                          \
            *solid = FALSE;                                                     \
            colour2 = *data;                                                    \
        }                                                                       \
                                                                                \
        if (*data == colour2) {                                                 \
            n2++;                                                               \
            continue;                                                           \
        }                                                                       \
                                                                                \
        *mono = FALSE;                                                          \
        break;                                                                  \
    }                                                                           \
                                                                                \
    if (n1 > n2) {                                                              \
        *bg = colour1;                                                          \
        *fg = colour2;                                                          \
    } else {                                                                    \
        *bg = colour2;                                                          \
        *fg = colour1;                                                          \
    }                                                                           \
}

#undef BPP
#define BPP 8
DEFINE_SEND_HEXTILES(8)

#undef BPP
#define BPP 16
DEFINE_SEND_HEXTILES(16)

#undef BPP
#define BPP 32
DEFINE_SEND_HEXTILES(32)
