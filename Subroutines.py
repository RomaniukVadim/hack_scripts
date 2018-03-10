#!/usr/bin/python3

__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

def sub(x,y):
    #  out of scope here
    z = x - y
    # just print, no return
    print(z)

# using parameters
def add(x,y):
    # return value
    return x+y

print(add(15,4))
#  not passing parameters initially
sub(15,4)

