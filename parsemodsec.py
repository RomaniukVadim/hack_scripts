#!/usr/bin/python3
__author__ = 'kilroy'
#  (c) 2014, WasHere Consulting, Inc.
#  Written for Infinite Skills

# this requires Python 3 to function properly
import os, sys, re, argparse

#   This is a class designed to store the results from the parsed file until we're
#   ready to print them out
class modsecRec:
    #  this is the initializer
    def __init__(self):
    	#  this is the list where all of the individual items are stored
        self.storageList = []

    #  append items to the list
    def append(self, newItem):
        self.storageList.append(newItem)

    #  extract information from the message line and append it
    def extractMessage(self, msgLine):
        self.storageList.append(msgLine)

	#  print the parsed data out to a file from the list
    def printListToFile(self, outputFilename):
        with open(outputFileName, 'a') as outHandle:
        	#  create a blank string
            completeLine = ''
            for singleEntry in self.storageList:
                #  strip newlines out but append a comma for CSV format
                completeLine = completeLine + singleEntry.rstrip() + ","
            #  now we can write the line out, but strip the trailing comma
            outHandle.write(completeLine.rstrip(","))

    #  print out the entries to screen since we don't have an output file
    def printList(self):
        for singleEntry in self.storageList:
            print(singleEntry.rstrip(), ",")

	#  start over on the list since we've dumped one out
    def clear(self):
        self.storageList = []


#  parse the command line arguments
argParser = argparse.ArgumentParser()
argParser.add_argument('-i', type=str, help='the input file with the ModSecurity audit log', required=True)
argParser.add_argument('-o', type=str, help='the output file this should generate')
# argParser.add_argument('-f', type=str, help='the format of the output')

passedArgs = vars(argParser.parse_args())

inputFileName = passedArgs['i']
outputFileName = passedArgs['o']

if not os.path.exists(inputFileName):
    print("You must specify an input file that exists")
    exit()

if outputFileName and os.path.exists(outputFileName):
    os.remove(outputFileName)

eachRecord = modsecRec()

with open(inputFileName, 'r') as fileHandle:
    for dataLine in fileHandle:
        if '--' in dataLine:
            if '-A--' in dataLine:
                dateInfo = fileHandle.readline()
                logDate = dateInfo[dateInfo.find("[")+1:dateInfo.find(":")]
                logTime = dateInfo[dateInfo.find(":")+1:dateInfo.find(" ")]
                eachRecord.append(logDate)
                eachRecord.append(logTime)
            if '-B--' in dataLine:
                httpReq = fileHandle.readline()
                eachRecord.append(httpReq)
            if '-H--' in dataLine:
                # loop until we get to the end
                for messageLine in fileHandle:
                    if 'Message' in messageLine:
                        eachRecord.extractMessage(messageLine)
                    else:
                        break
            if '-Z--' in dataLine:
                # do something with all the data we have acquired
                if outputFileName:
                	eachRecord.printListToFile(outputFileName)
                else:
                	eachRecord.printList()
                eachRecord.clear()


fileHandle.close()
