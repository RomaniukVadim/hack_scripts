#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

from HTMLParser import HTMLParser
import urllib2

class myParser(HTMLParser):
    def handle_starttag(self, tag, attrs):
        if (tag == "a"):
            for a in attrs:
                if (a[0] == 'href'):
                    link = a[1]
                    if (link.find('http') >= 0):
                        print(link)
                        newParse = myParser()
                        newParse.feed(link)


url = "http://www.infiniteskills.com/"
request = urllib2.Request(url)
handle = urllib2.urlopen(request)
parser = myParser()
parser.feed(handle.read())

