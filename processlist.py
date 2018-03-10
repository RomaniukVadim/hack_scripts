#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import psutil

l = psutil.get_process_list()

for proc in l:
    print(proc)
    print(proc.name())
    if (proc.name() == "Python"):
        print(proc.get_memory_maps())
