#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

x=1
print(x)
x='foo'
print(x)
#  strings and integers don't match
x=x+"1"
print(x)
#  difference between python 2 and python 3 (raw_input)
y=input("hi, tell me something ")
print(y)