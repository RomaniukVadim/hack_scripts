import logger
log = logger.logger_class()

import threading, os, time, subprocess

class system_health_watcher():

    # SAVE SETTINGS
    def save_sqlmap_settings(self):

        try:

            log.warning("system_health_watcher|save_sqlmap_settings", "Save sqlmap dump state.")

            result = subprocess.Popen(['screen', '-ls'], stdout=subprocess.PIPE)

            for processes in result.stdout.readlines():

                if "sqlmap" in processes:
                    sqlmap_dump = processes.split(".")[2] + "." + processes.split(".")[3] + "." + \
                                  processes.split(".")[4].split("	")[0]

                    subprocess.call("sed -i.bak '/" + sqlmap_dump + "/d' ./sqlmap_done.txt", shell=True)

        except Exception, error_code:
            log.warning("system_health_watcher|save_sqlmap_settings", str(error_code))
            pass



    # CHECK MEMORY USAGE
    def check_memory_usage(self):

        try:

            while True:

                try:

                    tot_m, used_m, free_m = map(int, os.popen('free -t -m').readlines()[-1].split()[1:])

                    log.info("system_health_watcher|check_memory_usage",
                             "Total memory: " + str(tot_m) + " | Used memory: "
                             + str(used_m) + " | Free memory: " + str(free_m))

                    if free_m < 200:
                        log.warning("system_health_watcher|check_memory_usage", "Not enough memory in the system, "
                                                                                "force reboot.")
                        self.save_sqlmap_settings()

                        os.system("reboot -f")

                    time.sleep(5)

                except Exception, error_code:
                    log.warning("system_health_watcher|check_memory_usage", str(error_code))
                    pass

        except Exception, error_code:
            log.warning("system_health_watcher|check_memory_usage", str(error_code))
            pass

    # UPDATE STATS FILE
    def update_stats(self, indb, done):

        try:

            stats = open("stats.txt", "w+")

            stats.write("SITES_IN_DB = " + indb + "\n")
            stats.write("SITES_LEFT = " + done + "\n")
            stats.write("REPORTS_COUNT = " + str(len(os.listdir("reports"))) + "\n")
            stats.write("REPORTS_REQUESTS = " + str(len(os.listdir("reports_requests"))) + "\n")

        except Exception, error_code:
            log.warning("system_health_watcher|update_stats", str(error_code))
            pass


    def __init__(self):

        try:

            log.info("system_health_watcher|__init__", "Started.")

            check_memory_usage_thread = threading.Thread(target=self.check_memory_usage)
            check_memory_usage_thread.start()

        except Exception, error_code:
            log.warning("arachni_scanner_class|__init__", str(error_code))
            pass