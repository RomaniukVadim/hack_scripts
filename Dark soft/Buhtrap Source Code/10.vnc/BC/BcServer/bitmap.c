//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: bitmap.c
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//  Simple bitmap manipulation engine


#include "main.h"


//
//	Allocates an empty bitmap of the specified size
//
PCHAR BmAllocate(
	ULONG Size	// number of bits
	)
{
	PCHAR pBitmap;

	if (pBitmap = AppAlloc((Size >> 3) + 1))
		memset(pBitmap, 0, (Size >> 3) + 1);

	return(pBitmap);
}


//
//	Releases the specified bitmap, frees memory.
//
VOID BmFree(
	PCHAR pBitmap
	)
{
	AppFree(pBitmap);
}


//
//	Returns TRUE if the specified field of the specified bitmap is busy
//
BOOL BmCheck(
	PCHAR	pBitmap,
	ULONG	Index
	)
{
	return((BOOL)BitTest((PULONG)pBitmap[Index >> 5], (Index & 0xffffffe0)));
}


//
//	Searches for the first empty index within the specified bitmap.
//
ULONG BmGetIndex(
	PCHAR	pBitmap,
	ULONG	Size
	)
{
	ULONG Mask, Index = INVALID_INDEX, IndexDd = 0, SizeDd = (Size >> 5) + 1;

	do
	{
		while(IndexDd < SizeDd && ((Mask = *(ULONG volatile *)pBitmap) == INVALID_INDEX))
		{
			pBitmap += sizeof(ULONG);
			IndexDd += 1;
		}

		if (IndexDd >= SizeDd)
		{
			ASSERT(Index == INVALID_INDEX);
			break;
		}

		Mask = ~Mask;

		do 
		{
			if (BitScanForward(&Index, Mask) && !InterlockedBitTestAndSet((ULONG volatile *)pBitmap, Index))
			{
				Index += (IndexDd << 5);
				break;
			}
			else
			{
				Mask &= ~(1 << Index);
				Index = INVALID_INDEX;
			}
		} while(Mask);

	} while(Index == INVALID_INDEX);

	return(Index);
}


//
//	Frees the specified index of the specified bitmap.
//
VOID BmFreeIndex(
	PCHAR	pBitmap,
	ULONG	Index
	)
{
	ULONG Old = InterlockedBitTestAndReset((ULONG volatile*)pBitmap + (Index >> 5), (Index & 0x1f));
	ASSERT(Old);
}