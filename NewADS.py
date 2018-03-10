#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

#  this requires NTFS on the file system
fh = open("file.txt:myfile", "w")
fh.write("this is a test")
fh.close()

fh = open("file.txt:myfile", "r")
data = fh.read(100)
print(data)


