#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import sqlite3

try:
    conn = sqlite3.connect("Databases.db")
except Exception as e:
    print(e)

# need a cursor to keep track of where we are
cur = conn.cursor()
for row in cur.execute("SELECT * FROM Origins"):
    print(row)
