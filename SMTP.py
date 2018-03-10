#!/usr/bin/python3
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import smtplib
from email.mime.text import MIMEText

#  ehlo foo.com
#  mail from:  foo@foo.com
#  rcpt to:  me@me.com
#  data

s = smtplib.SMTP("172.30.42.127", 25)
#s.login("user", "password")

try:
#  could use the following for a MIME message
#    f = open("myfile", "r")
#    m = MIMEText(f.read())
#    f.close()
#    m['To'] = "kilroy@cloudroy.com"
#    m['From'] = "ricmessier@gmail.com"
#    m['Subject'] = "This is a message to you"

    m = "\nThis is a message from the last session"
    s.sendmail("ricmessier@gmail.com", "ric@cloudroy.com", m)
    #  send the MIME message
    # s.send_message(m)
    print("Finished sending message")
except Exception as e:
    print("Unable to send message: ", e)

s.quit()
