#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

from scapy.all import *
from scapy.utils import rdpcap

src_mac = "12:45:ff:aa:bb:3d"
dst_mac = "05:34:10:df:ef:ab"
src_ip = "1.5.4.2"
dst_ip = "4.5.4.2"

frames=rdpcap("file.pcap") #  could also read in only a small number of packets
for frame in frames:
    try:
        frame[Ether].src = src_mac
        frame[Ether].dst = dst_mac
        if IP in frame:
            frame[IP].src = src_ip
            frame[IP].dst = dst_ip
        sendp(frame)
    except Exception as e:
        print(e)
