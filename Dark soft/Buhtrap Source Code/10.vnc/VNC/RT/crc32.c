#include "project.h"

DWORD crc_tab[256];

void chksum_crc32gentab ();

BOOL bCrcInit = FALSE;
/* chksum_crc() -- to a given block, this one calculates the
 *				crc32-checksum until the length is
 *				reached. the crc32-checksum will be
 *				the result.
 */
DWORD chksum_crc32 (unsigned char *block, unsigned int length)
{
	register unsigned long crc;
	unsigned long i;

	if (!bCrcInit)
		chksum_crc32gentab();
	crc = 0xFFFFFFFF;
	for (i = 0; i < length; i++)
	{
		crc = ((crc >> 8) & 0x00FFFFFF) ^ crc_tab[(crc ^ *block++) & 0xFF];
	}
	return (crc ^ 0xFFFFFFFF);
}

DWORD CalcFuncCRC32i(UCHAR *data)
{
	UCHAR buf[300], *p=buf;
	while (*data)
	{
		byte tmp=*data++;
		if (((tmp > 0xC0) && (tmp < 0xDF)) || ((tmp > 'A') && (tmp < 'Z')))
			tmp+=0x20;
		*p++=tmp;
	}
	*p=0;

	return chksum_crc32(buf,(unsigned int)(p-buf));
}

/* chksum_crc32gentab() --      to a global crc_tab[256], this one will
 *				calculate the crcTable for crc32-checksums.
 *				it is generated to the polynom [..]
 */

void chksum_crc32gentab ()
{
	unsigned long crc, poly;
	int i, j;

	if (bCrcInit)
		return;
	bCrcInit = TRUE;

	poly = 0xEDB88320L;
	for (i = 0; i < 256; i++)
	{
		crc = i;
		for (j = 8; j > 0; j--)
		{
			if (crc & 1)
			{
				crc = (crc >> 1) ^ poly;
			}
			else
			{
				crc >>= 1;
			}
		}
		crc_tab[i] = crc;
	}
}

//
//	Calculates CRC32 hash of the data within the specified buffer
//
ULONG Crc32(
	PCHAR pMem,		// data buffer
	ULONG uLen		// length of the buffer in bytes
	)
{
  ULONG		i, c;
  ULONG		dwSeed =  -1;

  while( uLen-- )
  {
	  c = *pMem;
	  pMem = pMem + 1;
	  
	  for( i = 0; i < 8; i++ )
	  {
		  if ( (dwSeed ^ c) & 1 )
			  dwSeed = (dwSeed >> 1) ^ 0xEDB88320;
		  else
			  dwSeed = (dwSeed >> 1);
		  c >>= 1;
	  }
  }
  return(dwSeed);
}
