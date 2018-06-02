/* translate.h - prototypes of functions in translate.cpp */

#ifndef TRANSLATE_H__
#define TRANSLATE_H__

// Translate function prototype!
typedef void (*rfbTranslateFnType)(char *table, PPIXEL_FORMAT in,
	PPIXEL_FORMAT out,
	char *iptr, char *optr,
	int bytesBetweenInputLines,
	int width, int height);

// Init function prototype!
typedef void (*rfbInitTableFnType)(char **table, PPIXEL_FORMAT in,PPIXEL_FORMAT out);

// External translation stuff
extern void rfbTranslateNone(char *table, PPIXEL_FORMAT in,
	PPIXEL_FORMAT out,
	char *iptr, char *optr,
	int bytesBetweenInputLines,
	int width, int height);

// Macro to compare pixel formats.
#define PF_EQ(x,y)												\
	((x.BitsPerPixel == y.BitsPerPixel) &&						\
	 (x.Depth == y.Depth) &&									\
	 (x.TrueColourFlag == y.TrueColourFlag) &&							\
	 ((x.BigEndianFlag == y.BigEndianFlag) || (x.BitsPerPixel == 8)) &&	\
	 (!x.TrueColourFlag || ((x.RedMax == y.RedMax) &&				\
	 (x.GreenMax == y.GreenMax) &&					\
	 (x.BlueMax == y.BlueMax) &&						\
	 (x.RedShift == y.RedShift) &&					\
	 (x.GreenShift == y.GreenShift) &&				\
	 (x.BlueShift == y.BlueShift))))

// Translation functions themselves
extern rfbInitTableFnType rfbInitTrueColourSingleTableFns[];
extern rfbInitTableFnType rfbInitColourMapSingleTableFns[];
extern rfbInitTableFnType rfbInitTrueColourRGBTablesFns[];
extern rfbTranslateFnType rfbTranslateWithSingleTableFns[3][3];
extern rfbTranslateFnType rfbTranslateWithRGBTablesFns[3][3];

#endif