#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import urllib

print("starting download")

urllib.urlretrieve("http://trendingmp3.com/music/user_folder/Rae%20Sremmurd%20-%20No%20Flex%20Zone.mp3", "thing.mp3")

print("completed")