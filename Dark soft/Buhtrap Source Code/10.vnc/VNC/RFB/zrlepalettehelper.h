#ifndef __ZRLE_PALETTE_HELPER_H__
#define __ZRLE_PALETTE_HELPER_H__

#define ZRLE_PALETTE_MAX_SIZE 127

typedef struct {
	CARD32	palette[ZRLE_PALETTE_MAX_SIZE];
	CARD8	index[ZRLE_PALETTE_MAX_SIZE + 4096];
	CARD32	key[ZRLE_PALETTE_MAX_SIZE + 4096];
	int		size;
} zrlePaletteHelper;

void zrlePaletteHelperInit  (zrlePaletteHelper *helper);
void zrlePaletteHelperInsert(zrlePaletteHelper *helper, CARD32 pix);
int  zrlePaletteHelperLookup(zrlePaletteHelper *helper, CARD32 pix);

#endif /* __ZRLE_PALETTE_HELPER_H__ */
