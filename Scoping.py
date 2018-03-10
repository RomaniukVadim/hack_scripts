#!/usr/bin/python3
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

# adding x here makes it in scope
x = 0

for i in range(1,25):
    #  x is in scope
    x = i + x

# x is now out of scope
print(x)

