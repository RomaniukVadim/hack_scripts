#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import socket
import threading
import urllib2

class clientConnect(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self)

    def run(self):
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        addr = ("www.google.com", 443)
        sock.connect(addr)
        print("Connected")


sockClients = []
for i in range(1,100):
    s = clientConnect()
    s.start()
    print("started ", i)
    sockClients.append(s)
