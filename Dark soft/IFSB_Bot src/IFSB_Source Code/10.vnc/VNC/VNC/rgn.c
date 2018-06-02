#include "project.h"
#include "rgn.h"


HRGN RegionCreate(int x1, int y1, int x2, int y2)
{
	return CreateRectRgn(x1, y1, x2, y2);
}

HRGN RegionCreateFromRect(LPRECT Rect)
{
	return CreateRectRgnIndirect(Rect);
}

HRGN RegionCreateFromRegion(HRGN r) {
	HRGN rgn = CreateRectRgn(0,0,0,0);
	if ( rgn ){
		CombineRgn(rgn, r, r, RGN_COPY);
	}
	return rgn;
}

void RegionDelete(HRGN rgn) {
	DeleteObject(rgn);
}

BOOL IsPtInRegion(HRGN rgn,int x, int y)
{
	return PtInRegion(rgn,x,y);
}

void ReagionClear(HRGN rgn) {
	SetRectRgn(rgn, 0, 0, 0, 0);
}

void RegionReset(HRGN rgn, LPRECT r) {
	SetRectRgn(rgn, r->left, r->top, r->right, r->bottom);
}

void RegionTranslate(HRGN rgn, LPPOINT delta) {
	OffsetRgn(rgn, delta->x, delta->y);
}


void RegionAssignIntersect(HRGN rgn,HRGN r ) {
	CombineRgn(rgn, rgn, r, RGN_AND);
}

void RegionAssignUnion(HRGN rgn,HRGN r) {
	CombineRgn(rgn, rgn, r, RGN_OR);
}

void RegionAssignSubtract(HRGN rgn,HRGN r) 
{
	CombineRgn(rgn, rgn, r, RGN_DIFF);
}

BOOL RegionEquals(HRGN rgn,HRGN r)
{
	return EqualRgn(rgn, r);
}

BOOL RegionIsEmpty(HRGN rgn)
{
	RECT rc;
	return (GetRgnBox(rgn, &rc) == NULLREGION);
}