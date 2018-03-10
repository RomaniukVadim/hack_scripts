#!/usr/bin/python3
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

import poplib, imaplib, getpass

p = poplib.POP3("172.30.42.127", 110)
# p = poplib.POP3_SSL("172.30.42.126", 995)

print(p.getwelcome())
p.user("ric")
p.pass_("P4ssw0rd!")

print(p.list())
p.quit()

i = imaplib.IMAP4("172.30.42.127", 143)
# i.login(getpass.getuser(), getpass.getpass_())
i.login("ric", "P4ssw0rd!")
i.select()
t, l = i.list()
print("Response code: ", t)
print(l)

t, ids = i.search(None, "ALL")
print("Response code: ", t)
print(ids)
t, msg = i.fetch('5', "(UID BODY[TEXT])")
#  store messages on the server
#  i.store()
print(msg)
i.close()
i.logout()
