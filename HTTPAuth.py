#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import httplib
import base64
import string

h = "172.30.42.127"
u = "ric"
p = "P4ssw0rd"

authToken = base64.encodestring('%s:%s' % (u, p)).replace('\n', '')
print(authToken)

req = httplib.HTTP(h)
req.putrequest("GET", "/protected/index.html")
req.putheader("Host", h)
req.putheader("Authorization", "Basic %s" % authToken)
req.endheaders()
req.send("")

statusCode, statusMsg, headers = req.getreply()
print("Response: ", statusMsg)
