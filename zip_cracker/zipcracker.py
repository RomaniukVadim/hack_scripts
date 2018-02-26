#!/usr/env python3

##___glicOne dictionary zipfile brute___##

import argparse
import zipfile
from threading import Thread

def extract_zip(zFile, pass_from_file):
    try:
        zFile.extractall(pwd=pass_from_file.encode('cp850','replace'))
        print("[+] Password found: " + pass_from_file + "\n" )
    except:
        print("Trying password: " + pass_from_file)
        pass

def main():
    parser = argparse.ArgumentParser(description='Process some zipfiles.')
    parser.add_argument("-f", dest='zname', type=argparse.FileType('r'), help='specify zipfile')
    parser.add_argument("-d", dest='dname', type=argparse.FileType('r'), help='specify dictionary file')
    args = parser.parse_args()
    if (args.zname == None) | (args.dname == None):
        print(parser.print_help())
        exit(0)
    else:
        zname=args.zname.name
        dname=args.dname.name
    zFile = zipfile.ZipFile(zname)
    passFile = open(dname)

    for line in passFile.readlines():
        pass_from_file = line.strip("\n")
        t = Thread(target=extract_zip, args=(zFile,pass_from_file))
        t.start()
    


if __name__ == '__main__' :
    main()
