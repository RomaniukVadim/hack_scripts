#!/usr/bin/python3
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import socket

mgrp = "224.1.1.1"
mport = 5775

sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM, socket.IPPROTO_UDP)
sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
for i in range(1,10):
    sock.sendto(b"Hi, this is me", (mgrp, mport))

print("Message sent out to the multicast group")
