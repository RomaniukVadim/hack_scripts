#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import os

rootdir = "/"

for curr, dirs, files in os.walk(rootdir):

    for f in files:
        print ('%s/%s' % (curr, f))

