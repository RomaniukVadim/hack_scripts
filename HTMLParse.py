#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

from HTMLParser import HTMLParser
import urllib2

class myParser(HTMLParser):
    def handle_starttag(self, tag, attrs):
        if (tag == "input"):
            print("Found an input field ", tag)
            print(attrs)

url = "http://172.30.42.127/test.php"
request = urllib2.Request(url)
handle = urllib2.urlopen(request)
parser = myParser()
parser.feed(handle.read())