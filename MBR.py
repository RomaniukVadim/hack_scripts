#!/usr/bin/python3
#  (c) 2014, WasHere Consulting, Inc
import struct

f = open("mbr.dd", "rb")

mbr = bytearray()
try:
    mbr = f.read(512)
finally:
    f.close()

sig = struct.unpack("<I", mbr[0x1B8:0x1BC])
print("Disk signature: ", sig[0])
active = mbr[0x1BE]
if active == 0x80:
	print("Active flag: Active")
else:
	print("Active flag: Not active")

lbastart = struct.unpack("<I", mbr[0x1C6:0x1CA])
print("Partition Start (LBA): ", lbastart[0])
lbaend = struct.unpack("<I", mbr[0x1C9:0x1CD])
print("Partition End (LBA): ", lbaend[0])

