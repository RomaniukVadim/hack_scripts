#include "main.h"
#include "vnc.h"
#include "rfb.h"

#include "zrleoutstream.h"
#include "zrlepalettehelper.h"
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

#undef END_FIX
#if ZYWRLE_ENDIAN == ENDIAN_LITTLE
#  define END_FIX LE
#elif ZYWRLE_ENDIAN == ENDIAN_BIG
#  define END_FIX BE
#else
#  define END_FIX NE
#endif

#ifdef CPIXEL
#define PIXEL_T __RFB_CONCAT2E(CARD,BPP)
#define zrleOutStreamWRITE_PIXEL __RFB_CONCAT2E(zrleOutStreamWriteOpaque,CPIXEL)
#define ZRLE_ENCODE __RFB_CONCAT3E(zrleEncode,CPIXEL,END_FIX)
#define ZRLE_ENCODE_TILE __RFB_CONCAT3E(zrleEncodeTile,CPIXEL,END_FIX)
#define BPPOUT 24
#elif BPP==15
#define PIXEL_T __RFB_CONCAT2E(CARD,16)
#define zrleOutStreamWRITE_PIXEL __RFB_CONCAT2E(zrleOutStreamWriteOpaque,16)
#define ZRLE_ENCODE __RFB_CONCAT3E(zrleEncode,BPP,END_FIX)
#define ZRLE_ENCODE_TILE __RFB_CONCAT3E(zrleEncodeTile,BPP,END_FIX)
#define BPPOUT 16
#else
#define PIXEL_T __RFB_CONCAT2E(CARD,BPP)
#define zrleOutStreamWRITE_PIXEL __RFB_CONCAT2E(zrleOutStreamWriteOpaque,BPP)
#define ZRLE_ENCODE __RFB_CONCAT3E(zrleEncode,BPP,END_FIX)
#define ZRLE_ENCODE_TILE __RFB_CONCAT3E(zrleEncodeTile,BPP,END_FIX)
#define BPPOUT BPP
#endif

#ifndef ZRLE_ONCE
#define ZRLE_ONCE

static const int bitsPerPackedPixel[] = {
  0, 1, 2, 2, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4
};

#endif /* ZRLE_ONCE */

void ZRLE_ENCODE_TILE (PIXEL_T* data, int w, int h, zrleOutStream* os,
		int zywrle_level, int *zywrleBuf, void *paletteHelper);

#if BPP!=8
#define ZYWRLE_ENCODE
#include "zywrletemplate.c"
#endif

static void ZRLE_ENCODE (PRFB_SESSION RfbSession, int x, int y, int w, int h,
	zrleOutStream* os, void* buf
	)
{
	int ty;
	zrleOutStream *zos = (zrleOutStream *)RfbSession->hZrle;
	for (ty = y; ty < y+h; ty += rfbZRLETileHeight) {
		int tx, th = rfbZRLETileHeight;
		if (th > y+h-ty) th = y+h-ty;
		for (tx = x; tx < x+w; tx += rfbZRLETileWidth) {
			int tw = rfbZRLETileWidth;
			if (tw > x+w-tx) tw = x+w-tx;

			// translate rect colors
			RfbTranslateRect(RfbSession,RfbSession->VncSession->Desktop.FrameBuffer,(char*)buf,tx,ty,th,tw);

			// do encode
			ZRLE_ENCODE_TILE((PIXEL_T*)buf, tw, th, os,
				RfbSession->zywrleLevel, (int*)zos->zywrleBuf, (void *)&zos->paletteHelper);
		}
	}
	zrleOutStreamFlush(os);
}


void ZRLE_ENCODE_TILE(PIXEL_T* data, int w, int h, zrleOutStream* os,
	int zywrle_level, int *zywrleBuf,  void *paletteHelper)
{
	/* First find the palette and the number of runs */

	zrlePaletteHelper *ph;

	int runs = 0;
	int singlePixels = 0;

	BOOL useRle;
	BOOL usePalette;

	int estimatedBytes;
	int plainRleBytes;
	int i;

	PIXEL_T* ptr = data;
	PIXEL_T* end = ptr + h * w;
	*end = ~*(end-1); /* one past the end is different so the while loop ends */

	ph = (zrlePaletteHelper *) paletteHelper;
	zrlePaletteHelperInit(ph);

	while (ptr < end) {
		PIXEL_T pix = *ptr;
		if (*++ptr != pix) {
			singlePixels++;
		} else {
			while (*++ptr == pix) ;
			runs++;
		}
		zrlePaletteHelperInsert(ph, pix);
	}

	/* Solid tile is a special case */

	if (ph->size == 1) {
		zrleOutStreamWriteU8(os, 1);
		zrleOutStreamWRITE_PIXEL(os, (PIXEL_T)ph->palette[0]);
		return;
	}

	/* Try to work out whether to use RLE and/or a palette.  We do this by
	estimating the number of bytes which will be generated and picking the
	method which results in the fewest bytes.  Of course this may not result
	in the fewest bytes after compression... */

	useRle = FALSE;
	usePalette = FALSE;

	estimatedBytes = w * h * (BPPOUT/8); /* start assuming raw */

#if BPP!=8
	if (zywrle_level > 0 && !(zywrle_level & 0x80))
		estimatedBytes >>= zywrle_level;
#endif

	plainRleBytes = ((BPPOUT/8)+1) * (runs + singlePixels);

	if (plainRleBytes < estimatedBytes) {
		useRle = TRUE;
		estimatedBytes = plainRleBytes;
	}

	if (ph->size < 128) {
		int paletteRleBytes = (BPPOUT/8) * ph->size + 2 * runs + singlePixels;

		if (paletteRleBytes < estimatedBytes) {
			useRle = TRUE;
			usePalette = TRUE;
			estimatedBytes = paletteRleBytes;
		}

		if (ph->size < 17) {
			int packedBytes = ((BPPOUT/8) * ph->size +
				w * h * bitsPerPackedPixel[ph->size-1] / 8);

			if (packedBytes < estimatedBytes) {
				useRle = FALSE;
				usePalette = TRUE;
				estimatedBytes = packedBytes;
			}
		}
	}

	if (!usePalette) ph->size = 0;

	zrleOutStreamWriteU8(os, (useRle ? 128 : 0) | ph->size);

	for (i = 0; i < ph->size; i++) {
		zrleOutStreamWRITE_PIXEL(os, (PIXEL_T)ph->palette[i]);
	}

	if (useRle) {

		PIXEL_T* ptr = data;
		PIXEL_T* end = ptr + w * h;
		PIXEL_T* runStart;
		PIXEL_T pix;
		while (ptr < end) {
			int len;
			runStart = ptr;
			pix = *ptr++;
			while (*ptr == pix && ptr < end)
				ptr++;
			len = (int)(ptr - runStart);
			if (len <= 2 && usePalette) {
				int index = zrlePaletteHelperLookup(ph, pix);
				if (len == 2)
					zrleOutStreamWriteU8(os, index);
				zrleOutStreamWriteU8(os, index);
				continue;
			}
			if (usePalette) {
				int index = zrlePaletteHelperLookup(ph, pix);
				zrleOutStreamWriteU8(os, index | 128);
			} else {
				zrleOutStreamWRITE_PIXEL(os, pix);
			}
			len -= 1;
			while (len >= 255) {
				zrleOutStreamWriteU8(os, 255);
				len -= 255;
			}
			zrleOutStreamWriteU8(os, len);
		}

	} else {

		/* no RLE */

		if (usePalette) {
			int bppp;
			PIXEL_T* ptr = data;

			/* packed pixels */

			ASSERT (ph->size < 17);

			bppp = bitsPerPackedPixel[ph->size-1];

			for (i = 0; i < h; i++) {
				CARD8 nbits = 0;
				CARD8 byte = 0;

				PIXEL_T* eol = ptr + w;

				while (ptr < eol) {
					PIXEL_T pix = *ptr++;
					CARD8 index = zrlePaletteHelperLookup(ph, pix);
					byte = (byte << bppp) | index;
					nbits += bppp;
					if (nbits >= 8) {
						zrleOutStreamWriteU8(os, byte);
						nbits = 0;
					}
				}
				if (nbits > 0) {
					byte <<= 8 - nbits;
					zrleOutStreamWriteU8(os, byte);
				}
			}
		} else {

			/* raw */

#if BPP!=8
			if (zywrle_level > 0 && !(zywrle_level & 0x80)) {
				ZYWRLE_ANALYZE(data, data, w, h, w, zywrle_level, zywrleBuf);
				ZRLE_ENCODE_TILE(data, w, h, os, zywrle_level | 0x80, zywrleBuf, paletteHelper);
			}
			else
#endif
			{
#ifdef CPIXEL
				PIXEL_T *ptr;
				for (ptr = data; ptr < data+w*h; ptr++)
					zrleOutStreamWRITE_PIXEL(os, *ptr);
#else
				zrleOutStreamWriteBytes(os, (CARD8 *)data, w*h*(BPP/8));
#endif
			}
		}
	}
}

#undef PIXEL_T
#undef zrleOutStreamWRITE_PIXEL
#undef ZRLE_ENCODE
#undef ZRLE_ENCODE_TILE
#undef ZYWRLE_ENCODE_TILE
#undef BPPOUT
