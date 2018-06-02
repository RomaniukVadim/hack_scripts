#include "main.h"
#include "vnc.h"
#include "rfb.h"

#include "zrlepalettehelper.h"
#include "zrleoutstream.h"

#define ZRLE_IN_BUFFER_SIZE  16384
#define ZRLE_OUT_BUFFER_SIZE 1024

static BOOL zrleBufferAlloc(zrleBuffer *buffer, int size)
{
	buffer->ptr = buffer->start = hAlloc(size);
	if (buffer->start == NULL) {
		buffer->end = NULL;
		return FALSE;
	}

	buffer->end = buffer->start + size;

	return TRUE;
}

static void zrleBufferFree(zrleBuffer *buffer)
{
	if (buffer->start){
		hFree(buffer->start);
	}
	buffer->start = buffer->ptr = buffer->end = NULL;
}

static BOOL zrleBufferGrow(zrleBuffer *buffer, int size)
{
	int offset;

	size  += (int)(buffer->end - buffer->start);
	offset = ZRLE_BUFFER_LENGTH (buffer);

	buffer->start = hRealloc(buffer->start, size);
	if (!buffer->start) {
		return FALSE;
	}

	buffer->end = buffer->start + size;
	buffer->ptr = buffer->start + offset;

	return TRUE;
}

zrleOutStream *zrleOutStreamNew(void)
{
	zrleOutStream *os;

	os = hAlloc(sizeof(zrleOutStream));
	if (os == NULL)
		return NULL;

	if (!zrleBufferAlloc(&os->in, ZRLE_IN_BUFFER_SIZE)) {
		hFree(os);
		return NULL;
	}

	if (!zrleBufferAlloc(&os->out, ZRLE_OUT_BUFFER_SIZE)) {
		zrleBufferFree(&os->in);
		hFree(os);
		return NULL;
	}

	os->zs.zalloc = Z_NULL;
	os->zs.zfree  = Z_NULL;
	os->zs.opaque = Z_NULL;
	if (deflateInit(&os->zs, Z_DEFAULT_COMPRESSION) != Z_OK) {
		zrleBufferFree(&os->in);
		hFree(os);
		return NULL;
	}

	return os;
}

void zrleOutStreamFree (zrleOutStream *os)
{
	deflateEnd(&os->zs);
	zrleBufferFree(&os->in);
	zrleBufferFree(&os->out);
	hFree(os);
}

BOOL zrleOutStreamFlush(zrleOutStream *os)
{
	os->zs.next_in = os->in.start;
	os->zs.avail_in = ZRLE_BUFFER_LENGTH (&os->in);

	while (os->zs.avail_in != 0) {
		do {
			int ret;

			if (os->out.ptr >= os->out.end &&
				!zrleBufferGrow(&os->out, (int)(os->out.end - os->out.start))) 
			{
					return FALSE;
			}

			os->zs.next_out = os->out.ptr;
			os->zs.avail_out = (int)(os->out.end - os->out.ptr);

			if ((ret = deflate(&os->zs, Z_SYNC_FLUSH)) != Z_OK) {
				return FALSE;
			}

			os->out.ptr = os->zs.next_out;
		} while (os->zs.avail_out == 0);
	}
	os->in.ptr = os->in.start;
	return TRUE;
}

static int zrleOutStreamOverrun(zrleOutStream *os, int size)
{
	while (os->in.end - os->in.ptr < size && os->in.ptr > os->in.start) {
		os->zs.next_in = os->in.start;
		os->zs.avail_in = ZRLE_BUFFER_LENGTH (&os->in);

		do {
			int ret;
			if (os->out.ptr >= os->out.end &&
				!zrleBufferGrow(&os->out, (int)(os->out.end - os->out.start))) {
					return FALSE;
			}

			os->zs.next_out = os->out.ptr;
			os->zs.avail_out = (int)(os->out.end - os->out.ptr);

			if ((ret = deflate(&os->zs, 0)) != Z_OK) {
				return 0;
			}

			os->out.ptr = os->zs.next_out;
		} while (os->zs.avail_out == 0);

		/* output buffer not full */
		if (os->zs.avail_in == 0) {
			os->in.ptr = os->in.start;
		} else {
			/* but didn't consume all the data?  try shifting what's left to the
			* start of the buffer.
			*/
			memcpy(os->in.start, os->zs.next_in, os->in.ptr - os->zs.next_in);
			os->in.ptr -= os->zs.next_in - os->in.start;
		}
	}
	if (size > (int)(os->in.end - os->in.ptr)){
		size = (int)(os->in.end - os->in.ptr);
	}
	return size;
}

static int zrleOutStreamCheck(zrleOutStream *os, int size)
{
	if (os->in.ptr + size > os->in.end) {
		return zrleOutStreamOverrun(os, size);
	}
	return size;
}

void zrleOutStreamWriteBytes(
	zrleOutStream *os,
	const CARD8 *data,
	int length
	)
{
	const CARD8* dataEnd = data + length;
	while (data < dataEnd) {
		int n = zrleOutStreamCheck(os, (int)(dataEnd - data));
		memcpy(os->in.ptr, data, n);
		os->in.ptr += n;
		data += n;
	}
}

void zrleOutStreamWriteU8(zrleOutStream *os, CARD8 u)
{
	zrleOutStreamCheck(os, 1);
	*os->in.ptr++ = u;
}

void zrleOutStreamWriteOpaque8(zrleOutStream *os, CARD8 u)
{
	zrleOutStreamCheck(os, 1);
	*os->in.ptr++ = u;
}

void zrleOutStreamWriteOpaque16 (zrleOutStream *os, CARD16 u)
{
	zrleOutStreamCheck(os, 2);
	*os->in.ptr++ = ((CARD8*)&u)[0];
	*os->in.ptr++ = ((CARD8*)&u)[1];
}

void zrleOutStreamWriteOpaque32 (zrleOutStream *os, CARD32 u)
{
	zrleOutStreamCheck(os, 4);
	*os->in.ptr++ = ((CARD8*)&u)[0];
	*os->in.ptr++ = ((CARD8*)&u)[1];
	*os->in.ptr++ = ((CARD8*)&u)[2];
	*os->in.ptr++ = ((CARD8*)&u)[3];
}

void zrleOutStreamWriteOpaque24A(zrleOutStream *os, CARD32 u)
{
	zrleOutStreamCheck(os, 3);
	*os->in.ptr++ = ((CARD8*)&u)[0];
	*os->in.ptr++ = ((CARD8*)&u)[1];
	*os->in.ptr++ = ((CARD8*)&u)[2];
}

void zrleOutStreamWriteOpaque24B(zrleOutStream *os, CARD32 u)
{
	zrleOutStreamCheck(os, 3);
	*os->in.ptr++ = ((CARD8*)&u)[1];
	*os->in.ptr++ = ((CARD8*)&u)[2];
	*os->in.ptr++ = ((CARD8*)&u)[3];
}
