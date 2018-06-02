//-----------------------------------------------------------------------------
// MurmurHash2A, by Austin Appleby

// This is a variant of MurmurHash2 modified to use the Merkle-Damgard
// construction. Bulk speed should be identical to Murmur2, small-key speed
// will be 10%-20% slower due to the added overhead at the end of the hash.

// This variant fixes a minor issue where null keys were more likely to
// collide with each other than expected, and also makes the algorithm
// more amenable to incremental implementations. All other caveats from
// MurmurHash2 still apply.

#define mmix(h,k) { k *= m; k ^= k >> r; k *= m; h *= m; h ^= k; }

unsigned int MurmurHash2A ( const void * key, int len, unsigned int seed )
{
	const unsigned int m = 0x5bd1e995;
	const int r = 24;
	unsigned int l = len;

	const unsigned char * data = (const unsigned char *)key;

	unsigned int h = seed;
	unsigned int t = 0;

	while(len >= 4)
	{
		unsigned int k = *(unsigned int*)data;

		mmix(h,k);

		data += 4;
		len -= 4;
	}

	switch(len)
	{
	case 3: t ^= data[2] << 16;
	case 2: t ^= data[1] << 8;
	case 1: t ^= data[0];
	};

	mmix(h,t);
	mmix(h,l);

	h ^= h >> 13;
	h *= m;
	h ^= h >> 15;

	return h;
}

//////////////////////////////////////////////////////////////////////////
// murmur3

//-----------------------------------------------------------------------------
// Platform-specific functions and macros

// Microsoft Visual Studio

#if defined(_MSC_VER)

#define FORCE_INLINE	__forceinline

#include <stdlib.h>

#define ROTL32(x,y)	_rotl(x,y)
#define ROTL64(x,y)	_rotl64(x,y)

#define BIG_CONSTANT(x) (x)

// Other compilers

#else	// defined(_MSC_VER)

#define	FORCE_INLINE __attribute__((always_inline))

inline unsigned long rotl32 ( unsigned long x, unsigned char r )
{
	return (x << r) | (x >> (32 - r));
}

inline unsigned __int64 rotl64 ( unsigned __int64 x, unsigned char r )
{
	return (x << r) | (x >> (64 - r));
}

#define	ROTL32(x,y)	rotl32(x,y)
#define ROTL64(x,y)	rotl64(x,y)

#define BIG_CONSTANT(x) (x##LLU)

#endif // !defined(_MSC_VER)

//-----------------------------------------------------------------------------
// Block read - if your platform needs to do endian-swapping or can only
// handle aligned reads, do the conversion here

#define getblock(_p,_i) _p[_i]

FORCE_INLINE unsigned __int64 getblock64 ( const unsigned __int64 * p, int i )
{
	return p[i];
}

//-----------------------------------------------------------------------------
// Finalization mix - force all bits of a hash block to avalanche

FORCE_INLINE unsigned long fmix ( unsigned long h )
{
	h ^= h >> 16;
	h *= 0x85ebca6b;
	h ^= h >> 13;
	h *= 0xc2b2ae35;
	h ^= h >> 16;

	return h;
}

//----------

FORCE_INLINE unsigned __int64 fmix64 ( unsigned __int64 k )
{
	k ^= k >> 33;
	k *= BIG_CONSTANT(0xff51afd7ed558ccd);
	k ^= k >> 33;
	k *= BIG_CONSTANT(0xc4ceb9fe1a85ec53);
	k ^= k >> 33;

	return k;
}

//-----------------------------------------------------------------------------
unsigned long MurmurHash3_x86_32 ( const void * key, int len, unsigned long seed)
{
	const unsigned char * data = (const unsigned char*)key;
	const int nblocks = len / 4;

	unsigned long h1 = seed;

	const unsigned long c1 = 0xcc9e2d51;
	const unsigned long c2 = 0x1b873593;
	int i;

	//----------
	// body

	const unsigned long * blocks = (const unsigned long *)(data + nblocks*4);

	for( i = -nblocks; i; i++)
	{
		unsigned long k1 = getblock(blocks,i);

		k1 *= c1;
		k1 = ROTL32(k1,15);
		k1 *= c2;

		h1 ^= k1;
		h1 = ROTL32(h1,13); 
		h1 = h1*5+0xe6546b64;
	}

	//----------
	// tail

	{
		const unsigned char * tail = (const unsigned char*)(data + nblocks*4);

		unsigned long k1 = 0;

		switch(len & 3)
		{
		case 3: k1 ^= tail[2] << 16;
		case 2: k1 ^= tail[1] << 8;
		case 1: k1 ^= tail[0];
			k1 *= c1; k1 = ROTL32(k1,15); k1 *= c2; h1 ^= k1;
		};
	}
	//----------
	// finalization

	h1 ^= len;

	h1 = fmix(h1);

	return h1;
} 

//-----------------------------------------------------------------------------

void MurmurHash3_x86_128 ( const void * key, const int len, unsigned long seed, void * out )
{
	const unsigned char * data = (const unsigned char*)key;
	const int nblocks = len / 16;

	unsigned long h1 = seed;
	unsigned long h2 = seed;
	unsigned long h3 = seed;
	unsigned long h4 = seed;

	const unsigned long c1 = 0x239b961b; 
	const unsigned long c2 = 0xab0e9789;
	const unsigned long c3 = 0x38b34ae5; 
	const unsigned long c4 = 0xa1e38b93;

	//----------
	// body

	const unsigned long * blocks = (const unsigned long *)(data + nblocks*16);
	int i;

	for( i = -nblocks; i; i++)
	{
		unsigned long k1 = getblock(blocks,i*4+0);
		unsigned long k2 = getblock(blocks,i*4+1);
		unsigned long k3 = getblock(blocks,i*4+2);
		unsigned long k4 = getblock(blocks,i*4+3);

		k1 *= c1; k1  = ROTL32(k1,15); k1 *= c2; h1 ^= k1;

		h1 = ROTL32(h1,19); h1 += h2; h1 = h1*5+0x561ccd1b;

		k2 *= c2; k2  = ROTL32(k2,16); k2 *= c3; h2 ^= k2;

		h2 = ROTL32(h2,17); h2 += h3; h2 = h2*5+0x0bcaa747;

		k3 *= c3; k3  = ROTL32(k3,17); k3 *= c4; h3 ^= k3;

		h3 = ROTL32(h3,15); h3 += h4; h3 = h3*5+0x96cd1c35;

		k4 *= c4; k4  = ROTL32(k4,18); k4 *= c1; h4 ^= k4;

		h4 = ROTL32(h4,13); h4 += h1; h4 = h4*5+0x32ac3b17;
	}

	//----------
	// tail

	{
		const unsigned char * tail = (const unsigned char*)(data + nblocks*16);

		unsigned long k1 = 0;
		unsigned long k2 = 0;
		unsigned long k3 = 0;
		unsigned long k4 = 0;

		switch(len & 15)
		{
		case 15: k4 ^= tail[14] << 16;
		case 14: k4 ^= tail[13] << 8;
		case 13: k4 ^= tail[12] << 0;
			k4 *= c4; k4  = ROTL32(k4,18); k4 *= c1; h4 ^= k4;

		case 12: k3 ^= tail[11] << 24;
		case 11: k3 ^= tail[10] << 16;
		case 10: k3 ^= tail[ 9] << 8;
		case  9: k3 ^= tail[ 8] << 0;
			k3 *= c3; k3  = ROTL32(k3,17); k3 *= c4; h3 ^= k3;

		case  8: k2 ^= tail[ 7] << 24;
		case  7: k2 ^= tail[ 6] << 16;
		case  6: k2 ^= tail[ 5] << 8;
		case  5: k2 ^= tail[ 4] << 0;
			k2 *= c2; k2  = ROTL32(k2,16); k2 *= c3; h2 ^= k2;

		case  4: k1 ^= tail[ 3] << 24;
		case  3: k1 ^= tail[ 2] << 16;
		case  2: k1 ^= tail[ 1] << 8;
		case  1: k1 ^= tail[ 0] << 0;
			k1 *= c1; k1  = ROTL32(k1,15); k1 *= c2; h1 ^= k1;
		};
	}

	//----------
	// finalization

	h1 ^= len; h2 ^= len; h3 ^= len; h4 ^= len;

	h1 += h2; h1 += h3; h1 += h4;
	h2 += h1; h3 += h1; h4 += h1;

	h1 = fmix(h1);
	h2 = fmix(h2);
	h3 = fmix(h3);
	h4 = fmix(h4);

	h1 += h2; h1 += h3; h1 += h4;
	h2 += h1; h3 += h1; h4 += h1;

	((unsigned long*)out)[0] = h1;
	((unsigned long*)out)[1] = h2;
	((unsigned long*)out)[2] = h3;
	((unsigned long*)out)[3] = h4;
}

//-----------------------------------------------------------------------------

void MurmurHash3_x64_128 ( const void * key, const int len, const unsigned long seed, void * out )
{
	const unsigned char * data = (const unsigned char*)key;
	const int nblocks = len / 16;

	unsigned __int64 h1 = seed;
	unsigned __int64 h2 = seed;

	const unsigned __int64 c1 = BIG_CONSTANT(0x87c37b91114253d5);
	const unsigned __int64 c2 = BIG_CONSTANT(0x4cf5ad432745937f);

	//----------
	// body

	const unsigned __int64 * blocks = (const unsigned __int64 *)(data);
	int i;

	for( i = 0; i < nblocks; i++)
	{
		unsigned __int64 k1 = getblock64(blocks,i*2+0);
		unsigned __int64 k2 = getblock64(blocks,i*2+1);

		k1 *= c1; k1  = ROTL64(k1,31); k1 *= c2; h1 ^= k1;

		h1 = ROTL64(h1,27); h1 += h2; h1 = h1*5+0x52dce729;

		k2 *= c2; k2  = ROTL64(k2,33); k2 *= c1; h2 ^= k2;

		h2 = ROTL64(h2,31); h2 += h1; h2 = h2*5+0x38495ab5;
	}

	//----------
	// tail
	{
		const unsigned char * tail = (const unsigned char*)(data + nblocks*16);

		unsigned __int64 k1 = 0;
		unsigned __int64 k2 = 0;

		switch(len & 15)
		{
		case 15: k2 ^= (unsigned __int64)(tail[14]) << 48;
		case 14: k2 ^= (unsigned __int64)(tail[13]) << 40;
		case 13: k2 ^= (unsigned __int64)(tail[12]) << 32;
		case 12: k2 ^= (unsigned __int64)(tail[11]) << 24;
		case 11: k2 ^= (unsigned __int64)(tail[10]) << 16;
		case 10: k2 ^= (unsigned __int64)(tail[ 9]) << 8;
		case  9: k2 ^= (unsigned __int64)(tail[ 8]) << 0;
			k2 *= c2; k2  = ROTL64(k2,33); k2 *= c1; h2 ^= k2;

		case  8: k1 ^= (unsigned __int64)(tail[ 7]) << 56;
		case  7: k1 ^= (unsigned __int64)(tail[ 6]) << 48;
		case  6: k1 ^= (unsigned __int64)(tail[ 5]) << 40;
		case  5: k1 ^= (unsigned __int64)(tail[ 4]) << 32;
		case  4: k1 ^= (unsigned __int64)(tail[ 3]) << 24;
		case  3: k1 ^= (unsigned __int64)(tail[ 2]) << 16;
		case  2: k1 ^= (unsigned __int64)(tail[ 1]) << 8;
		case  1: k1 ^= (unsigned __int64)(tail[ 0]) << 0;
			k1 *= c1; k1  = ROTL64(k1,31); k1 *= c2; h1 ^= k1;
		};
	}

	//----------
	// finalization

	h1 ^= len; h2 ^= len;

	h1 += h2;
	h2 += h1;

	h1 = fmix64(h1);
	h2 = fmix64(h2);

	h1 += h2;
	h2 += h1;

	((unsigned __int64*)out)[0] = h1;
	((unsigned __int64*)out)[1] = h2;
}

//-----------------------------------------------------------------------------
