#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

from scapy.all import *

frame = Ether(dst="15:16:89:fa:dd:09")/IP(dst="9.16.5.4")/TCP()/"This is my payload"

print(frame)
sendp(frame)
