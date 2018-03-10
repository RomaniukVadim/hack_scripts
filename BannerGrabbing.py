#!/usr/bin/python3
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import socket
import re

sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
sock.connect(("www.microsoft.com", 80))

http_get = b"GET / HTTP/1.1\nHost: www.microsoft.com\n\n"
data = ''
try:
    sock.sendall(http_get)
    data = sock.recvfrom(1024)
except socket.error:
    print ("Socket error", socket.errno)
finally:
    print("closing connection")
    sock.close()

strdata = data[0].decode("utf-8")
#  looks like one long line so split it at newline into multiple strings
headers = strdata.splitlines()
#  use regular expression library to look for the one line we like
for s in headers:
    if re.search('Server:', s):
        s = s.replace("Server: ", "")
        print(s)
