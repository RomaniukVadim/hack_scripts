import logger
log = logger.logger_class()
from telegram_api import telegram_api_class
from modules import system_watcher
watcher = system_watcher.system_health_watcher()
import settings
setup = settings.settings_var

from os import walk, remove
import os

import time
import json
import urllib2
import threading
import subprocess

import sys

reload(sys)
sys.setdefaultencoding('utf8')

class arachni_scanner_class():

    SCAN_JOB_FILES = []
    SCANNED_SITES_LIST = []

    job_execute_index = 0
    current_arachni_threads = 0
    arachni_healath = 50

    telegram = telegram_api_class()

    # SAVE REPORT
    def save_arachni_report(self, url, data):

        try:

            log.info("arachni_scanner_class|save_arachni_report", "Save report: " + url.split('/')[2] + ".report")

            # SEND TELEGRAM MESSAGE
            self.telegram.update_footer_message("SQL Injection report saved as: " + url.split('/')[2] + ".report")

            with open('reports/' + url.split('/')[2] + '.report', 'w') as outfile:
                json.dump(data, outfile, sort_keys=True, indent=4,
                          ensure_ascii=True)

        except Exception, error_code:
            log.warning("arachni_scanner_class|save_arachni_report", str(error_code))
            pass

    # GET SCAN SEEDS
    def get_seeds(self):

        try:

            get_seeds = urllib2.urlopen('http://' + setup.ARACHNI_SERVER_IP + ":"
                                        + str(setup.ARACHNI_SERVER_PORT) + '/scans', timeout=10)

            response = get_seeds.read()

            log.info("arachni_scanner_class|get_seeds", "Current seeds: " + response)

            return response

        except Exception, error_code:
            log.warning("arachni_scanner_class|get_seeds", str(error_code))
            self.arachni_healath -= 1
            pass

    # START SCAN
    def start_arachni_scan_thread(self, url):

        try:

            request = urllib2.Request("http://" + setup.ARACHNI_SERVER_IP + ":" + str(setup.ARACHNI_SERVER_PORT) + "/scans")
            request.add_header('Content-Type', 'application/json')

            data = {'url': url, 'checks': ['sql_injection'],
                    "http": {"user_agent": 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36'},
                    "audit": {"elements": ["links", "forms", "cookies", "headers", "jsons", "xmls", "ui_inputs","ui_forms"]}}

            response = urllib2.urlopen(request, json.dumps(data), timeout=15)

            return response

        except Exception, error_code:
            log.warning("arachni_scanner_class|start_arachni_scan_thread", str(error_code))
            self.arachni_healath -= 1
            pass

    # CHECK SCAN STATUS
    def get_scan_details(self, seed):

        try:

            log.info("arachni_scanner_class|get_scan_details", "Get arachni scanner details: " + seed)

            request = urllib2.Request(
                "http://" + setup.ARACHNI_SERVER_IP + ":" + str(
                    setup.ARACHNI_SERVER_PORT) + "/scans/" + seed + "/report")

            request.add_header('Content-Type', 'application/json')

            response = urllib2.urlopen(request, timeout=25)
            response_to_json = json.loads(response.read())

            start_datetime = response_to_json['start_datetime']
            delta_time = response_to_json['delta_time']
            issues_count = len(response_to_json['issues'])
            issues = response_to_json['issues']
            options = response_to_json['options']
            sitemap_size = len(response_to_json['sitemap'])
            url = response_to_json['options']['url']

            log.info("arachni_scanner_class|get_scan_details", "Transfer target details: " + url + " | Work time: "
                     + delta_time + "/" + setup.ARACHNI_MAX_WORK_TIME + " | Sitemap size: " + str(sitemap_size))

            # CHECK STATUS

            request = urllib2.Request("http://" + setup.ARACHNI_SERVER_IP + ":" + str(setup.ARACHNI_SERVER_PORT) + "/scans/" + seed)
            request.add_header('Content-Type', 'application/json')

            response = urllib2.urlopen(request, timeout=15)
            response_to_json = json.loads(response.read())

            status = response_to_json['status']

            return [start_datetime, delta_time, issues_count, issues, options, sitemap_size, url, status]

        except Exception, error_code:
            log.warning("arachni_scanner_class|get_scan_details", str(error_code))
            self.arachni_healath -= 1
            pass

    def delete_arachni_scan_job(self, seed):

        try:

            log.info("arachni_scanner_class|delete_arachni_scan_job", "Delete arachni scanner job: " + seed)

            request = urllib2.Request('http://' + setup.ARACHNI_SERVER_IP + ":" + str(setup.ARACHNI_SERVER_PORT) + "/scans/" + seed)
            request.add_header('Content-Type', 'application/json')
            request.get_method = lambda: 'DELETE'

            urllib2.urlopen(request, timeout=30)

            return False

        except Exception, error_code:
            log.warning("arachni_scanner_class|delete_arachni_scan_job", str(error_code))
            self.arachni_healath -= 1
            pass

    # HEALTH WATCHER
    def health_watcher(self):

        while True:

            try:

                log.info("arachni_scanner_class|health_watcher", "Check arachni health, current health: "
                         + str(self.arachni_healath))

                if self.arachni_healath <= 0:
                    log.warning("arachni_scanner_class|health_watcher", "Restart Arachni-Scanner bad health")
                    self.arachni_healath = 20
                    self.job_execute_index -= self.current_arachni_threads
                    self.restart_arachni_scanner()
                    time.sleep(120)

                time.sleep(40)

            except Exception, error_code:
                log.warning("arachni_scanner_class|health_watcher", str(error_code))
                pass



    # SCAN WATCHER
    def scan_watcher(self):

        try:

            log.info("arachni_scanner_class|scan_watcher", "Scan watcher service started.")

            while True:

                time.sleep(20)

                if self.check_arachni_loaded():

                    try:

                        if self.get_seeds() == "{}":
                            pass

                        elif self.get_seeds() == None:
                            pass

                        else:

                            log.info("arachni_scanner_class|scan_watcher", "Check scan status.")

                            for seed in json.loads(self.get_seeds()):
                                get_details = self.get_scan_details(seed)

                                if get_details[2] >= 1:
                                    log.alert("arachni_scanner_class|scan_watcher",
                                              "SQL Injection found: " + get_details[6])

                                    # SEND TELEGRAM MESSAGE
                                    self.telegram.update_footer_message(
                                        "SQL Injection found: " + get_details[6])

                                    # SAVE REPORT
                                    self.save_arachni_report(get_details[6],
                                                             get_details[3])

                                    # DELETE SEED
                                    self.delete_arachni_scan_job(seed)

                                if get_details[7] == "done":

                                    if get_details[2] >= 1:

                                        log.alert("arachni_scanner_class|scan_watcher",
                                                  "SQL Injection found: " + get_details[6])

                                        # SEND TELEGRAM MESSAGE
                                        self.telegram.update_footer_message("SQL Injection found: " +
                                                                            get_details[6])

                                        # SAVE REPORT
                                        self.save_arachni_report(get_details[6],
                                                                 get_details[3])

                                        # DELETE SEED
                                        self.delete_arachni_scan_job(seed)

                                    else:

                                        # DELETE SEED
                                        self.delete_arachni_scan_job(seed)

                                if str(get_details[1]) > setup.ARACHNI_MAX_WORK_TIME:

                                    log.alert("arachni_scanner_class|scan_watcher",
                                              "Max work time reached: " + get_details[6])

                                    # DELETE SEED
                                    self.delete_arachni_scan_job(seed)

                    except Exception, error_code:
                        log.warning("arachni_scanner_class|scan_watcher", str(error_code))
                        pass

        except Exception, error_code:
            log.warning("arachni_scanner_class|scan_watcher", str(error_code))
            pass

    # CHECK ARACHNI LOADED
    def check_arachni_loaded(self):

        try:

            log.info("arachni_scanner_class|check_arachni_loaded", "Check Arachni-Scanner live.")

            check_arachni_screen = subprocess.Popen(["screen", "-ls"],
                                      stdout=subprocess.PIPE).communicate()[0]

            if "arachni_system" not in check_arachni_screen:
                return False
            else:
                return True

        except Exception, error_code:
            log.warning("arachni_scanner_class|check_arachni_loaded", str(error_code))
            pass

    # CHECK ARACHNI SCANNER
    def launch_arachni_scanner(self):

        try:

            log.info("arachni_scanner_class|launch_arachni_scanner", "Launch Arachni-Scanner.")

            subprocess.call("screen -dm -S arachni_system arachni_scanner/bin/arachni_rest_server --address " +
                      setup.ARACHNI_SERVER_IP + " " + "--port " + str(setup.ARACHNI_SERVER_PORT), shell=True)

        except Exception, error_code:
            log.warning("arachni_scanner_class|launch_arachni_scanner", str(error_code))
            pass

    # RESTART ARACHNI SCANNER
    def restart_arachni_scanner(self):

        try:

            os.system("screen -X -S arachni_system quit")

            if not self.check_arachni_loaded():
                self.launch_arachni_scanner()

        except Exception, error_code:
            log.warning("arachni_scanner_class|restart_arachni_scanner", str(error_code))
            pass


    # DELETE SCAN JOB FILES
    def delete_scan_job_file(self, filename):

        try:
            log.info("arachni_scanner_class|delete_scan_job_file", "Delete job file: " + filename)

            remove("scan_files/" + filename)
            self.SCAN_JOB_FILES.remove(filename)

        except Exception, error_code:
            log.warning("arachni_scanner_class|delete_scan_job_file", str(error_code))
            pass

    # UPDATE SCANNED TARGET LIST
    def update_scanned_target_list(self, target):

        try:

            self.SCANNED_SITES_LIST.append(target)

            save_scan = open('scanned.txt', 'a')
            save_scan.write(target + '\n')
            save_scan.close()

        except Exception, error_code:
            log.warning("arachni_scanner_class|update_scanned_file", str(error_code))
            pass

    # LOAD SCANNED TARGET LIST
    def load_scanned_target_list(self):

        try:

            scanned_list = open('scanned.txt', 'r')

            for scanned_targets in scanned_list.readlines():
                self.SCANNED_SITES_LIST.append(scanned_targets.replace("\n", ""))

            scanned_list.close()

        except Exception, error_code:
            log.warning("arachni_scanner_class|load_scanned_target_list", str(error_code))
            pass

    # JOB EXECUTE
    def job_execute(self):

        try:

            while True:

                if not self.SCAN_JOB_FILES:
                    pass
                else:

                    log.info("arachni_scanner_class|job_execute", "Check current job state.")

                    for file in self.SCAN_JOB_FILES:

                        open_job_file = open("scan_files/" + file, "r+")
                        target_list = open_job_file.readlines()

                        while self.job_execute_index <= len(target_list):

                            try:

                                if self.check_arachni_loaded():

                                    if int(len(json.loads(self.get_seeds()))) < int(setup.ARACHNI_THREADS):

                                        if target_list[self.job_execute_index].replace("\n",
                                                        "") not in self.SCANNED_SITES_LIST:

                                            log.info("arachni_scanner_class|job_execute",
                                                     "Update target job. | Target: "
                                                     + target_list[self.job_execute_index].replace("\n", ""))

                                            # UPDATE HEAD MESSAGE TELEGRAM
                                            self.telegram.update_head_message(str(len(target_list)),
                                                                              str(self.job_execute_index))

                                            watcher.update_stats(str(len(target_list)), str(self.job_execute_index))

                                            # SEND TARGET TO ARACHNI
                                            self.start_arachni_scan_thread(
                                                "http://" + target_list[self.job_execute_index].replace("\n", "").replace("\r", ""))

                                            # WAIT 5 SEC
                                            time.sleep(5)

                                            # UPDATE SCANNED
                                            self.update_scanned_target_list(
                                                target_list[self.job_execute_index].replace("\n", ""))

                                            self.current_arachni_threads += 1
                                            self.job_execute_index += 1

                                        else:

                                            log.info("arachni_scanner_class|job_execute",
                                                     "Update target job exist in scanned."
                                                     " | Target: "
                                                     + target_list[self.job_execute_index].replace("\n", ""))

                                            self.job_execute_index += 1

                                    else:
                                        log.info("arachni_scanner_class|job_execute", "Max jobs loaded, please wait.")
                                        break

                            except IndexError:

                                self.job_execute_index = 0
                                self.delete_scan_job_file(file)
                                break

                            except Exception:
                                break

                time.sleep(15)

        except Exception, error_code:
            log.warning("arachni_scanner_class|job_execute", str(error_code))
            pass

    # SCAN FILES LOOP
    def scan_files_loop(self):

        log.info("arachni_scanner_class|scan_files_loop", "Update scan job files thread started.")

        while True:

            try:
                for (dirpath, dirnames, filenames) in walk("scan_files"):
                    for file in filenames:

                        if file not in self.SCAN_JOB_FILES:

                            log.info("arachni_scanner_class|scan_files_loop",
                                     "Added new scan job file: " + file)

                            self.telegram.update_footer_message("New scan file loaded: " + file)

                            self.SCAN_JOB_FILES.append(file)

                time.sleep(3)

                if not self.SCAN_JOB_FILES:

                    log.info("arachni_scanner_class|scan_files_loop",
                             "Waiting for job files.")


            except Exception, error_code:
                log.warning("arachni_scanner_class|scan_files_loop", str(error_code))
                pass

    def __init__(self):

        try:
            log.info("arachni_scanner_class|__init__", "Started.")

            # CHECK ARACHNI SCANNER WORKING
            if not self.check_arachni_loaded():
                self.launch_arachni_scanner()

            # LOAD TARGETS LIST
            self.load_scanned_target_list()

            # START THREADS
            scan_files_loop_thread = threading.Thread(target=self.scan_files_loop)
            scan_files_loop_thread.start()

            job_execute_thread = threading.Thread(target=self.job_execute)
            job_execute_thread.start()

            scan_watcher_thread = threading.Thread(target=self.scan_watcher)
            scan_watcher_thread.start()

            check_arachni_health = threading.Thread(target=self.health_watcher)
            check_arachni_health.start()

        except Exception, error_code:
            log.warning("arachni_scanner_class|__init__", str(error_code))
            pass
