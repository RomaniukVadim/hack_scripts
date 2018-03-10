#!/usr/bin/python
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

# need pycrypto package
from Crypto.Cipher import AES

# key has to be 16, 24 or 32 bytes long
cryptObj = AES.new("This is my key42", AES.MODE_CBC, "16 character vec")
#  notice the spaces -- that's to pad it out to a multiple of 16 bytes
plaintext = "This is some text we need to encrypt because it's very secret   "
ciphertext = cryptObj.encrypt(plaintext)
print("Cipher text: ", ciphertext)

# this won't work if the key isn't identical
newcryptObj = AES.new("This is my key42", AES.MODE_CBC, "16 character vec")
result = newcryptObj.decrypt(ciphertext)

print(result)