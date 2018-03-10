#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import urllib2

proxy = urllib2.ProxyHandler({'http': '127.0.0.1:8080'})
opener = urllib2.build_opener(proxy)
urllib2.install_opener(opener)
handle = urllib2.urlopen('http://www.microsoft.com')

print(handle.read())

