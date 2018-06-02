#ifndef __MURMUR_H_
#define __MURMUR_H_

unsigned int MurmurHash2A ( const void * key, int len, unsigned int seed );

unsigned long MurmurHash3_x86_32 ( const void * key, int len, unsigned long seed);
void MurmurHash3_x86_128 ( const void * key, int len, unsigned long seed, void * out );
void MurmurHash3_x64_128 ( const void * key, int len, unsigned long seed, void * out );

#define MurmurHash3 MurmurHash3_x86_32
#define MurmurHash  MurmurHash3

#endif //__MURMUR_H_