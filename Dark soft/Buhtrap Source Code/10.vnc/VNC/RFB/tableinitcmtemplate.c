#if !defined(OUTVNC)
#error "This file shouldn't be compiled."
#error "It is included as part of translate.c"
#endif

#define OUT_T CONCAT2E(CARD,OUTVNC)
#define SwapOUTVNC(x) CONCAT2E(Swap,OUTVNC) (x)
#define rfbInitColourMapSingleTableOUTVNC \
				CONCAT2E(rfbInitColourMapSingleTable,OUTVNC)

// THIS CODE HAS BEEN MODIFIED FROM THE ORIGINAL UNIX SOURCE
// TO WORK FOR WINVNC.  THE PALETTE SHOULD REALLY BE RETRIEVED
// FROM THE VNCDESKTOP OBJECT, RATHER THAN FROM THE OS DIRECTLY

static void
rfbInitColourMapSingleTableOUTVNC (char **table, PPIXEL_FORMAT in, PPIXEL_FORMAT out)
{
	// ALLOCATE SPACE FOR COLOUR TABLE
	unsigned int nEntries = 1 << in->BitsPerPixel;
	PALETTEENTRY palette[256];
	HDC hDC;
	UINT entries;
	unsigned int i;

	int r, g, b;
	OUT_T *t;

	// Allocate the table
	if (*table) {
		hFree(*table);
	}
	*table = (char *)hAlloc(nEntries * sizeof(OUT_T));
	if (*table == NULL){
		return;
	}

	// Obtain the system palette
	hDC = GetDC(NULL);
	entries = GetSystemPaletteEntries(hDC, 0, 256, palette);
	ReleaseDC(NULL, hDC);

	// - Set the rest of the palette to something nasty but usable
	for (i=entries;i<256;i++) {
		palette[i].peRed = i % 2 ? 255 : 0;
		palette[i].peGreen = i/2 % 2 ? 255 : 0;
		palette[i].peBlue = i/4 % 2 ? 255 : 0;
	}

	// COLOUR TRANSLATION

	// We now have the colour table intact.  Map it into a translation table
	t = (OUT_T *)*table;
	for (i = 0; i < nEntries; i++)
	{
		// Split down the RGB data
		r = palette[i].peRed;
		g = palette[i].peGreen;
		b = palette[i].peBlue;

		// Now translate it
		t[i] = ((((r * out->RedMax + 127) / 255) << out->RedShift) |
			(((g * out->GreenMax + 127) / 255) << out->GreenShift) |
			(((b * out->BlueMax + 127) / 255) << out->BlueShift));
#if (OUTVNC != 8)
		if (out->BigEndianFlag != in->BigEndianFlag){
			t[i] = SwapOUTVNC(t[i]);
		}
#endif
	}
}

#undef OUT_T
#undef SwapOUT
#undef rfbInitColourMapSingleTableOUTVNC
