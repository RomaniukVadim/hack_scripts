#include "main.h"
#include "vnc.h"
#include "rfb.h"
#include "zrlepalettehelper.h"

#define ZRLE_HASH(pix) (((pix) ^ ((pix) >> 17)) & 4095)

void zrlePaletteHelperInit(zrlePaletteHelper *helper)
{
	memset(helper->palette, 0, sizeof(helper->palette));
	memset(helper->index, 255, sizeof(helper->index));
	memset(helper->key, 0, sizeof(helper->key));
	helper->size = 0;
}

void zrlePaletteHelperInsert(zrlePaletteHelper *helper, CARD32 pix)
{
	if (helper->size < ZRLE_PALETTE_MAX_SIZE) {
		int i = ZRLE_HASH(pix);

		while (helper->index[i] != 255 && helper->key[i] != pix)
			i++;
		if (helper->index[i] != 255) return;

		helper->index[i] = helper->size;
		helper->key[i] = pix;
		helper->palette[helper->size] = pix;
	}
	helper->size++;
}

int zrlePaletteHelperLookup(zrlePaletteHelper *helper, CARD32 pix)
{
	int i = ZRLE_HASH(pix);

	ASSERT(helper->size <= ZRLE_PALETTE_MAX_SIZE);

	while (helper->index[i] != 255 && helper->key[i] != pix)
		i++;
	if (helper->index[i] != 255) return helper->index[i];

	return -1;
}
