#!/usr/bin/python3
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

try:
    fhandle = open("myfile", "w")
    fhandle.write("This is some data to dump into the file")
    print("Wrote some data to the file")
except IOError as e:
    print("Exception caught: Unable to write to myfile ", e)
except Exception as e:
    print("Another error occurred ", e)
else:
    print("File written to successfully")
    fhandle.close()
