#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

class me:
    def __init__(self, foo):
        self.myvar = foo

    def getval(self):
        return self.myvar

my = me("this")
x = my.getval()
print(x)
