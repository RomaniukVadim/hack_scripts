#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import MySQLdb

try:
    db = MySQLdb.connect(host="localhost", user="ric", passwd="P4ssw0rd!", db="myDB")

    curs = db.cursor()

    curs.execute("select * from tblGrades")

    for row in curs.fetchall():
        print("Name: %s, Grade: %s" % (row[1], row[2]))

except Exception as e:
    print(e)

