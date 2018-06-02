#if !defined(OUTVNC)
#error "This file shouldn't be compiled."
#error "It is included as part of translate.c"
#endif

#define OUT_T CONCAT2E(CARD,OUTVNC)
#define SwapOUTVNC(x) CONCAT2E(Swap,OUTVNC) (x)
#define rfbInitTrueColourSingleTableOUTVNC \
				CONCAT2E(rfbInitTrueColourSingleTable,OUTVNC)
#define rfbInitTrueColourRGBTablesOUTVNC CONCAT2E(rfbInitTrueColourRGBTables,OUTVNC)
#define rfbInitOneRGBTableOUTVNC CONCAT2E(rfbInitOneRGBTable,OUTVNC)

static void
rfbInitOneRGBTableOUTVNC (OUT_T *table, int inMax, int outMax, int outShift, int swap);


/*
 * rfbInitTrueColourSingleTable sets up a single lookup table for truecolour
 * translation.
 */

static void
rfbInitTrueColourSingleTableOUTVNC (char **table, PPIXEL_FORMAT in, PPIXEL_FORMAT out)
{
	int i;
	int inRed, inGreen, inBlue, outRed, outGreen, outBlue;
	OUT_T *t=NULL;
	int nEntries = 1 << in->BitsPerPixel;

	if (*table) 
	{
		hFree(*table);
	}
	*table = (char *)hAlloc(nEntries * sizeof(OUT_T));
	if (table == NULL) return;
	t = (OUT_T *)*table;

	for (i = 0; i < nEntries; i++) {
		inRed   = (i >> in->RedShift)   & in->RedMax;
		inGreen = (i >> in->GreenShift) & in->GreenMax;
		inBlue  = (i >> in->BlueShift)  & in->BlueMax;

		outRed   = (inRed   * out->RedMax   + in->RedMax / 2)   / in->RedMax;
		outGreen = (inGreen * out->GreenMax + in->GreenMax / 2) / in->GreenMax;
		outBlue  = (inBlue  * out->BlueMax  + in->BlueMax / 2)  / in->BlueMax;

		if (t) t[i] = ((outRed   << out->RedShift)   |
			(outGreen << out->GreenShift) |
			(outBlue  << out->BlueShift));
#if (OUTVNC != 8)
		if (t) if (out->BigEndianFlag != in->BigEndianFlag) {
			t[i] = SwapOUTVNC(t[i]);
		}
#endif
	}
}


/*
 * rfbInitTrueColourRGBTables sets up three separate lookup tables for the
 * red, green and blue values.
 */

static void
rfbInitTrueColourRGBTablesOUTVNC (char **table, PPIXEL_FORMAT in, PPIXEL_FORMAT out)
{
	OUT_T *redTable;
	OUT_T *greenTable;
	OUT_T *blueTable;

	if (*table) hFree(*table);
	*table = (char *)hAlloc((in->RedMax + in->GreenMax + in->BlueMax + 3) * sizeof(OUT_T));
	redTable = (OUT_T *)*table;
	greenTable = redTable + in->RedMax + 1;
	blueTable = greenTable + in->GreenMax + 1;

	rfbInitOneRGBTableOUTVNC (redTable, in->RedMax, out->RedMax,
		out->RedShift, (out->BigEndianFlag != in->BigEndianFlag));
	rfbInitOneRGBTableOUTVNC (greenTable, in->GreenMax, out->GreenMax,
		out->GreenShift, (out->BigEndianFlag != in->BigEndianFlag));
	rfbInitOneRGBTableOUTVNC (blueTable, in->BlueMax, out->BlueMax,
		out->BlueShift, (out->BigEndianFlag != in->BigEndianFlag));
}

static void
rfbInitOneRGBTableOUTVNC (OUT_T *table, int inMax, int outMax, int outShift, int swap)
{
	int i;
	int nEntries = inMax + 1;

	for (i = 0; i < nEntries; i++) {
		table[i] = ((i * outMax + inMax / 2) / inMax) << outShift;
#if (OUTVNC != 8)
		if (swap) {
			table[i] = SwapOUTVNC(table[i]);
		}
#endif
	}
}

#undef OUT_T
#undef SwapOUTVNC
#undef rfbInitTrueColourSingleTableOUTVNC
#undef rfbInitTrueColourRGBTablesOUTVNC
#undef rfbInitOneRGBTableOUTVNC
