#ifndef __ZRLE_OUT_STREAM_H__
#define __ZRLE_OUT_STREAM_H__

#include <zlib.h>

typedef struct {
	CARD8 *start;
	CARD8 *ptr;
	CARD8 *end;
} zrleBuffer;

typedef struct {
	zrleBuffer in;
	zrleBuffer out;

	z_stream   zs;
	zrlePaletteHelper paletteHelper;
	BYTE zywrleBuf[(64 * 64 + 1 ) * 4];
	BYTE zrleBeforeBuf[(64 * 64 + 1 ) * 4];
} zrleOutStream;

#define ZRLE_BUFFER_LENGTH(b) ((int)((b)->ptr - (b)->start))

zrleOutStream *zrleOutStreamNew           (void);
void           zrleOutStreamFree          (zrleOutStream *os);
BOOL           zrleOutStreamFlush         (zrleOutStream *os);
void           zrleOutStreamWriteBytes    (zrleOutStream *os, const CARD8 *data, int length);
void           zrleOutStreamWriteU8       (zrleOutStream *os, CARD8 u);
void           zrleOutStreamWriteOpaque8  (zrleOutStream *os, CARD8 u);
void           zrleOutStreamWriteOpaque16 (zrleOutStream *os, CARD16 u);
void           zrleOutStreamWriteOpaque32 (zrleOutStream *os, CARD32 u);
void           zrleOutStreamWriteOpaque24A(zrleOutStream *os, CARD32 u);
void           zrleOutStreamWriteOpaque24B(zrleOutStream *os, CARD32 u);

#endif /* __ZRLE_OUT_STREAM_H__ */
