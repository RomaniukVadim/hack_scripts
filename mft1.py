#!/usr/bin/python3
#  (c) 2014, WasHere Consulting, Inc
import struct

f = open("win-ntfs.dd", "rb")

bpb = bytearray()
try:
    bpb = f.read(512)
finally:
    f.close()

bytespersector = struct.unpack("<h", bpb[0x00B:0x00D])
print(bytespersector)

sectorspercluster = bpb[0x00D]
print(sectorspercluster)

clustersize = bytespersector[0] * int(sectorspercluster)
print(clustersize)

# sectors in volume
secs = struct.unpack("<Q", bpb[0x028:0x030])
print(secs)

#  quad word for the first cluster
mftloc = struct.unpack("<Q", bpb[0x030:0x038])
mftmirrloc = struct.unpack("<Q", bpb[0x038:0x040])
print(mftloc)
print(mftmirrloc)

#  calculate the first MFT sector location in bytes
firstmftsector = mftmirrloc[0] * clustersize
print(firstmftsector)
