#ifndef __RENC_H_
#define __RENC_H_

VOID RfbTranslateRect(PRFB_SESSION RfbSession, BYTE *source, BYTE *dest, int x, int y, int h, int w);
BOOL RfbSendRectEncodingRaw( PRFB_SESSION RfbSession, LPRECT Rect );
BOOL RfbSendLastRectMarker(PRFB_SESSION RfbSession);

#endif //__RENC_H_