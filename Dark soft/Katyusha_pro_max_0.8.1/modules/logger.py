#!/usr/bin/env python
# -*- coding: utf-8 -*-

import datetime

now = datetime.datetime.now()

class logger_class():

    def save_good(self, save_url, engine_name):

        save = open('good_' + engine_name + '.txt', 'a+')
        save.write(save_url + "\n")
        save.close()

    def save_bad(self, save_url):

        save = open('bad.txt', 'a+')
        save.write(save_url + "\n")
        save.close()

    def save_log(self, text):

        save = open('log.txt', 'a+')
        save.write(text + "\n")
        save.close()

    # CONSOLE COLORS

    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'

    # 🚹 INFO MESSAGE
    # ⛔ WARNING MESSAGE
    # 💡 ALERT MESSAGE

    def alert(self, module, text):
        print "💡 " + "[" + self.HEADER + now.strftime("%Y-%m-%d %H:%M") + self.ENDC + "]" + "[" + self.HEADER + module + self.ENDC +  "]" + " -> " + self.FAIL + text + self.ENDC
        self.save_log("💡" + "[" + now.strftime("%Y-%m-%d %H:%M") + "]" +  "[" +  module +  "]" + " -> " +  text )
        pass

    def info(self, module, text):
        print "🚹 " + "[" + self.HEADER + now.strftime("%Y-%m-%d %H:%M") + self.ENDC + "]" + "[" + self.OKBLUE + module + self.ENDC + "]" + " -> " + self.OKGREEN + text + self.ENDC
        self.save_log("🚹" + "[" + now.strftime("%Y-%m-%d %H:%M") + "]" + " -> " + text)
        pass

    def warning(self, module, text):
        print "⛔ " + "[" + self.HEADER + now.strftime("%Y-%m-%d %H:%M") + self.ENDC + "]" + "[" + self.WARNING + module + self.ENDC + "]" + " -> " + self.FAIL + text + self.ENDC
        self.save_log("⛔" + "[" + now.strftime("%Y-%m-%d %H:%M") + "]" + "[" + module + "]" + " -> " + text)
        pass