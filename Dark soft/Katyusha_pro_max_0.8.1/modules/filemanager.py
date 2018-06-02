from modules import logger
log = logger.logger_class()

from os import walk
import time, json, threading
from urllib2 import unquote

class filemanager_class():

    REPORTS_DONE =   []

    # SAVE REPORTS
    def save_settings_data(self):

        try:

            log.info("filemanager_class|save_settings_data", "Save settings.")

            load_settings = open("modules/filemanager.settings", "r+").readlines()
            save_settings = open("modules/filemanager.settings", "w+")

            for settings in load_settings:

                if '%REPORTS_DONE' in settings.replace("\n", ""):

                    if self.REPORTS_DONE:
                        line = ""
                        for columns in self.REPORTS_DONE:
                            line += columns + ","
                        save_settings.write("%REPORTS_DONE = " + line[:-1] + "\n")
                    else:
                        save_settings.write("%REPORTS_DONE = False" + "\n")

        except Exception, error_code:
            log.warning("filemanager_class|save_settings_data", str(error_code))
            pass

    # LOAD LOG DATA
    def load_settings_data(self):

        try:

            log.info("filemanager_class|load_settings_data", "Loading settings.")
            load_settings = open("modules/filemanager.settings", "r+").readlines()

            for settings in load_settings:

                if '%REPORTS_DONE' in settings.replace("\n", ""):

                    if settings.replace("\n", "").split("%REPORTS_DONE = ")[1] != "False":
                        for columns in settings.replace("\n", "").split("%REPORTS_DONE = ")[1].split(","):
                            self.REPORTS_DONE.append(columns)

        except Exception, error_code:
            log.warning("filemanager_class|load_settings_data", str(error_code))
            pass

    # REPORTS CONVERTER
    def reports_converter_system(self, file_name):

        try:
            log.info("filemanager_class|reports_converter_system", "Start convert: " + "reports/" + file_name)

            report = json.loads(open('reports/' + file_name, 'r+').read())

            if report[0]['request']['method'] == "get":
                open('reports_requests/' + file_name.replace('.report', '') + '.request', 'w+').write(unquote(report[0]['request']['headers_string'])
                                                                               .replace(report[0]['vector']['seed'], "*"))


            elif report[0]['request']['method'] == "post":
                open('reports_requests/' + file_name.replace('.report', '') + '.request', 'w+').write(unquote(report[0]['request']['headers_string']
                + report[0]['request']['effective_body']).replace(report[0]['vector']['seed'], "*"))


            else:
                log.warning("filemanager_class|reports_converter_system", "unknown format.")

        except Exception, error_code:
            log.warning("filemanager_class|reports_converter_system", str(error_code))
            pass

    # REPORTS WATCHER
    def reports_watcher(self):

        try:
            log.info("filemanager_class|reports_watcher", "Thread started.")

            while True:

                log.info("filemanager_class|reports_watcher", "Watch reports folder.")

                try:

                    for (dirpath, dirnames, filenames) in walk("reports"):

                        for file in filenames:

                            if file not in self.REPORTS_DONE:

                                log.info("filemanager_class|reports_watcher", "New report " + file + " found, send to reports converter.")

                                self.REPORTS_DONE.append(file)
                                self.reports_converter_system(file)
                                self.save_settings_data()

                    time.sleep(3)

                except Exception, error_code:

                    log.warning("filemanager_class|reports_watcher", str(error_code))
                    pass

        except Exception, error_code:
            log.warning("filemanager_class|reports_watcher", str(error_code))
            pass

    def __init__(self):

        try:

            # LOAD SETTINGS
            self.load_settings_data()

            # START REPORT WATCHER THREAD
            report_watcher_thread = threading.Thread(target=self.reports_watcher)
            report_watcher_thread.start()

        except Exception, error_code:
            log.warning("filemanager_class|__init__", str(error_code))
            pass
