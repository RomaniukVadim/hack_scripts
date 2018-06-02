# coding: utf-8 #@plz
#@plz: очевидно, тут нужно применить ООП. Крайне необходимо блять!
#@plz: это был сарказм, разумеется
class settings_var():
    #@plz: версия бета, а была продана как pro max. Ему похууууй на тебя
    VERSION = "Katusha 0.8.1 (beta)"

    TELEGRAM_API_KEY = ""
    TELEGRAM_USER_ID = ""

    ARACHNI_THREADS = 10
    ARACHNI_MAX_WORK_TIME = "02:00:00"
    ARACHNI_SERVER_IP =   "127.0.0.1"
    ARACHNI_SERVER_PORT =      "7331"

    SQLMAP_THREADS   = 5

    DUMPMODE_COL = True
    #@plz: хваленый функционал с автоопределением столбцов mail:pass
    #@plz: охуеть от разнообразия, правда?
    DUMP_COLUMNS = "user,mail,pass,pwd,usr"
    TAMPER_LIST =  ""
    #@plz: далее переходим в modules/arachni.py
