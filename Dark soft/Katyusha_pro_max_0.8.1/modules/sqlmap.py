from modules import logger
log = logger.logger_class()

import settings
setup = settings.settings_var

from os import walk
import subprocess, \
       threading, os, \
       time

class sqlmap_class():

    SQLMAP_DONE = []

    # SQLMAP LOAD DONE FILE
    def sqlmap_load_info(self):

        try:

            log.info("sqlmap_class|sqlmap_load_info", "Load done url from list.")

            for done_url in open("sqlmap_done.txt", "r+").readlines():
                self.SQLMAP_DONE.append(done_url.replace("\n", ""))

        except Exception, error_code:
            log.warning("sqlmap_class|sqlmap_load_info", str(error_code))
            pass

    # SQLMAP SAVE DONE FILE
    def sqlmap_save_info(self, filename):
        try:

            log.info("sqlmap_class|sqlmap_load_info", "Save done to list: " + filename)
            open("sqlmap_done.txt", "a+").write(filename + "\n")
            self.SQLMAP_DONE.append(filename)

        except Exception, error_code:
            log.warning("sqlmap_class|sqlmap_save_info", str(error_code))
            pass

    # CHECK SQLMAP THREADS
    def sqlmap_get_threads(self):

        try:

            sqlmap_processes = 0

            result = subprocess.Popen(['screen', '-ls'], stdout=subprocess.PIPE)

            for processes in result.stdout.readlines():

                if "sqlmap" in processes:
                    sqlmap_processes += 1

            return sqlmap_processes

        except Exception, error_code:
            log.warning("sqlmap_class|sqlmap_get_threads", str(error_code))
            pass

    # START SQLMAP THREAD
    def sqlmap_thread(self, filename):

        try:

            log.info("sqlmap_class|sqlmap_thread", "Start new SQLMAP thread: " + filename)

            if setup.DUMPMODE_COL:

                reports_path = os.path.dirname(os.path.abspath(__file__)).replace("modules", "reports_requests/")
                dumps_path = os.path.dirname(os.path.abspath(__file__)).replace("modules", "sqlmap_dumps/")

                os.system(
                    "cd sqlmap && screen -dm -S " + "sqlmap." + filename + " ./sqlmap.py -r " + reports_path + filename + " --search -C "
                    + setup.DUMP_COLUMNS
                    + " --tamper=" + setup.TAMPER_LIST + " --technique=EU --threads 3" + " --output-dir=" + dumps_path
                    + " --batch")

            else:

                reports_path = os.path.dirname(os.path.abspath(__file__)).replace("modules", "reports_requests/")
                dumps_path = os.path.dirname(os.path.abspath(__file__)).replace("modules", "sqlmap_dumps/")

                os.system(
                    "cd sqlmap && screen -dm -S " + "sqlmap." + filename + " ./sqlmap.py -r " + reports_path + filename + " --dump-all"
                    " --tamper=" + setup.TAMPER_LIST + " --technique=EU --threads 3" + " --output-dir=" + dumps_path + " --batch")

        except Exception, error_code:
            log.warning("sqlmap_class|sqlmap_thread", str(error_code))
            pass

    # SQLMAP TASKS WATCHER
    def sqlmap_task_watcher(self):

        """
        SQLMAP Task watch system
        """

        try:

            log.info("sqlmap_class|sqlmap_task_watcher", "Watch tasks for sqlmap.")

            while True:

                log.info("sqlmap_class|sqlmap_task_watcher", "check current threads && new reports.")

                try:

                    for (dirpath, dirnames, filenames) in walk("reports_requests"):
                        for file in filenames:
                            if ".request" in file:
                                if file not in self.SQLMAP_DONE:

                                    if self.sqlmap_get_threads() < setup.SQLMAP_THREADS:
                                        self.sqlmap_thread(file)
                                        self.sqlmap_save_info(file)

                    time.sleep(15)

                except Exception, error_code:
                    log.warning("sqlmap_class|sqlmap_task_watcher", str(error_code))
                    time.sleep(4)
                    pass

        except Exception, error_code:
            log.warning("sqlmap_class|sqlmap_task_watcher", str(error_code))
            pass

    def del_task(self, task):

        try:

            log.info("sqlmap_class|del_task", "Delete not done task: " + task)



        except Exception, error_code:
            log.warning("sqlmap_class|del_task", str(error_code))
            pass

    def __init__(self):

        try:

            log.info("sqlmap_class|__init__", "Started.")

            self.sqlmap_load_info()

            sqlmap_start_watcher = threading.Thread(target=self.sqlmap_task_watcher)
            sqlmap_start_watcher.start()

        except Exception, error_code:
            log.warning("sqlmap_class|__init__", str(error_code))
            pass

