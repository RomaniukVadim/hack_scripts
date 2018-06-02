//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// BCSRV project. Version 2.7.17.1
//	
// module: bitmap.c
// $Revision: 70 $
// $Date: 2014-07-22 14:01:15 +0400 (Вт, 22 июл 2014) $
// description:
//  Simple bitmap manipulation engine


//
//	Allocates an empty bitmap of the specified size
//
PCHAR BmAllocate(
	ULONG Size	// number of bits
	);


//
//	Releases the specified bitmap, frees memory.
//
VOID BmFree(
	PCHAR pBitmap
	);


//
//	Searches for the first empty index within the specified bitmap.
//
ULONG BmGetIndex(
	PCHAR	pBitmap,
	ULONG	Size
	);


//
//	Frees the specified index of the specified bitmap.
//
VOID BmFreeIndex(
	PCHAR	pBitmap,
	ULONG	Index
	);