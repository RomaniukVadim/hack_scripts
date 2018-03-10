#!/usr/bin/python3
__author__ = 'kilroy'

name=input("What is your name? ")

#  for loop ... range creates a list
for i in range(1,10):
    print (name, i)

# while loop using break
x=0
while True:
    print (x)
    x=x+1
    if (x==15):
        break

#  for loop using a list
mylist=['lions', 'tigers', 'bears', 'ohmy']
for i in mylist:
    print(i)

