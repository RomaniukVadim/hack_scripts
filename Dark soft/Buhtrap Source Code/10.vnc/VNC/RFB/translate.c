#include "main.h"
#include "vnc.h"
#include "rfb.h"
#include "translate.h"

#define CONCAT2(a,b) a##b
#define CONCAT2E(a,b) CONCAT2(a,b)
#define CONCAT4(a,b,c,d) a##b##c##d
#define CONCAT4E(a,b,c,d) CONCAT4(a,b,c,d)

#define OUTVNC 8
#include "tableinittctemplate.c"
#include "tableinitcmtemplate.c"
#define INVNC 8
#include "tabletranstemplate.c"
#undef INVNC
#define INVNC 16
#include "tabletranstemplate.c"
#undef INVNC
#define INVNC 32
#include "tabletranstemplate.c"
#undef INVNC
#undef OUTVNC

#define OUTVNC 16
#include "tableinittctemplate.c"
#include "tableinitcmtemplate.c"
#define INVNC 8
#include "tabletranstemplate.c"
#undef INVNC
#define INVNC 16
#include "tabletranstemplate.c"
#undef INVNC
#define INVNC 32
#include "tabletranstemplate.c"
#undef INVNC
#undef OUTVNC

#define OUTVNC 32
#include "tableinittctemplate.c"
#include "tableinitcmtemplate.c"
#define INVNC 8
#include "tabletranstemplate.c"
#undef INVNC
#define INVNC 16
#include "tabletranstemplate.c"
#undef INVNC
#define INVNC 32
#include "tabletranstemplate.c"
#undef INVNC
#undef OUTVNC

rfbInitTableFnType rfbInitTrueColourSingleTableFns[3] = {
	rfbInitTrueColourSingleTable8,
	rfbInitTrueColourSingleTable16,
	rfbInitTrueColourSingleTable32
};

rfbInitTableFnType rfbInitColourMapSingleTableFns[3] = {
	rfbInitColourMapSingleTable8,
	rfbInitColourMapSingleTable16,
	rfbInitColourMapSingleTable32
};

rfbInitTableFnType rfbInitTrueColourRGBTablesFns[3] = {
	rfbInitTrueColourRGBTables8,
	rfbInitTrueColourRGBTables16,
	rfbInitTrueColourRGBTables32
};

rfbTranslateFnType rfbTranslateWithSingleTableFns[3][3] = {
	{ rfbTranslateWithSingleTable8to8,
		rfbTranslateWithSingleTable8to16,
		rfbTranslateWithSingleTable8to32 },
	{ rfbTranslateWithSingleTable16to8,
		rfbTranslateWithSingleTable16to16,
		rfbTranslateWithSingleTable16to32 },
	{ rfbTranslateWithSingleTable32to8,
		rfbTranslateWithSingleTable32to16,
		rfbTranslateWithSingleTable32to32 }
};

rfbTranslateFnType rfbTranslateWithRGBTablesFns[3][3] = {
	{ rfbTranslateWithRGBTables8to8,
		rfbTranslateWithRGBTables8to16,
		rfbTranslateWithRGBTables8to32 },
	{ rfbTranslateWithRGBTables16to8,
		rfbTranslateWithRGBTables16to16,
		rfbTranslateWithRGBTables16to32 },
	{ rfbTranslateWithRGBTables32to8,
		rfbTranslateWithRGBTables32to16,
		rfbTranslateWithRGBTables32to32 }
};



// rfbTranslateNone is used when no translation is required.

void
rfbTranslateNone(char *table, PPIXEL_FORMAT in, PPIXEL_FORMAT out,
		 char *iptr, char *optr, int bytesBetweenInputLines,
		 int width, int height)
{
	int bytesPerOutputLine = width * (out->BitsPerPixel >> 3);

	while (height > 0) {
		memcpy(optr, iptr, bytesPerOutputLine);
		iptr += bytesBetweenInputLines;
		optr += bytesPerOutputLine;
		--height;
	}
}
