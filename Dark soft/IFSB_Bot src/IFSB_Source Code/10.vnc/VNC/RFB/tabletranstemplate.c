#if !defined(INVNC) || !defined(OUTVNC)
#error "This file shouldn't be compiled."
#error "It is included as part of translate.c"
#endif

#define IN_T CONCAT2E(CARD,INVNC)
#define OUT_T CONCAT2E(CARD,OUTVNC)
#define rfbTranslateWithSingleTableINVNCtoOUTVNC \
				CONCAT4E(rfbTranslateWithSingleTable,INVNC,to,OUTVNC)
#define rfbTranslateWithRGBTablesINVNCtoOUTVNC \
				CONCAT4E(rfbTranslateWithRGBTables,INVNC,to,OUTVNC)

/*
 * rfbTranslateWithSingleTableINVNCtoOUTVNC translates a rectangle of pixel data
 * using a single lookup table.
 */

static void
rfbTranslateWithSingleTableINVNCtoOUTVNC (char *table, PPIXEL_FORMAT in,
	PPIXEL_FORMAT out,
	char *iptr, char *optr,
	int bytesBetweenInputLines,
	int width, int height)
{
	IN_T *ip = (IN_T *)iptr;
	OUT_T *op = (OUT_T *)optr;
	int ipextra = bytesBetweenInputLines / sizeof(IN_T) - width;
	OUT_T *opLineEnd;
	OUT_T *t = (OUT_T *)table;

	while (height > 0) {
		opLineEnd = op + width;

		while (op < opLineEnd) {
			*(op++) = t[*(ip++)];
		}

		ip += ipextra;
		height--;
	}
}


/*
 * rfbTranslateWithRGBTablesINVNCtoOUTVNC translates a rectangle of pixel data
 * using three separate lookup tables for the red, green and blue values.
 */

static void
rfbTranslateWithRGBTablesINVNCtoOUTVNC (char *table, PPIXEL_FORMAT in,
	PPIXEL_FORMAT out,
	char *iptr, char *optr,
	int bytesBetweenInputLines,
	int width, int height)
{
	IN_T *ip = (IN_T *)iptr;
	OUT_T *op = (OUT_T *)optr;
	int ipextra = bytesBetweenInputLines / sizeof(IN_T) - width;
	OUT_T *opLineEnd;
	OUT_T *redTable = (OUT_T *)table;
	OUT_T *greenTable = redTable + in->RedMax + 1;
	OUT_T *blueTable = greenTable + in->GreenMax + 1;

	while (height > 0) {
		opLineEnd = op + width;

		while (op < opLineEnd) {
			*(op++) = (redTable[(*ip >> in->RedShift) & in->RedMax] |
				greenTable[(*ip >> in->GreenShift) & in->GreenMax] |
				blueTable[(*ip >> in->BlueShift) & in->BlueMax]);
			ip++;
		}
		ip += ipextra;
		height--;
	}
}

#undef IN_T
#undef OUT_T
#undef rfbTranslateWithSingleTableINVNCtoOUTVNC
#undef rfbTranslateWithRGBTablesINVNCtoOUTVNC
