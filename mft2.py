#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import struct
f = open("win-ntfs.dd", "rb")

bpb = bytearray()
try:
    bpb = f.read(512)
finally:
    f.close()

#  figure out the size of a cluster
bytespersector = struct.unpack("<h", bpb[0x00B:0x00D])
sectorspercluster = bpb[0x00D]
clustersize = bytespersector[0] * int(sectorspercluster)

#  quad word for the first cluster
mftloc = struct.unpack("<Q", bpb[0x030:0x038])
mftmirrloc = struct.unpack("<Q", bpb[0x038:0x040])

#  calculate the first MFT sector location in bytes
firstmftsector = mftmirrloc[0] * clustersize
print(firstmftsector)

f = open("win-ntfs.dd", "rb")
f.seek(firstmftsector)
# $mft
#  read 1024, which is the size of an attribute in NTFS
mft = f.read(1024)
#  get the first 4 bytes, which should be FILE
value = mft[0x00:0x04]
print(value)
#  sequence number of the record
seqno = struct.unpack("<h", mft[0x010:0x012])
print(seqno)
#  actual size of the record
recsize = struct.unpack("<I", mft[0x018:0x01C])
print(recsize)
