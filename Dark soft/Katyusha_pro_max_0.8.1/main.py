#@plz: где кодировка?
# coding: utf-8 #@plz: это я добавил уже, для ру-коментов
#@plz: каждый мой комент относится к строке кода под ним
#@plz: пустые строки и места сохранены по оригиналу
#@plz: =====================================================================
#@plz: как потом окажется, это целый модуль, который тут не нужен полностью.
#@plz: кроме одного класса там нихуя нет, как и везде. 
from modules import telegram_api 

#@plz: тупо но не страшно
from modules import logger
log = logger.logger_class()

#@plz: тупо и тупо. Импортировал модуль, и присвоил его глобальной переменной. Наркоман штоле?
from modules import shell_uploader
uploader = shell_uploader

#@plz: тут он понял что спорол хуйню в строке выше, и решил присвоить переменной не целый модуль, а только класс. Успехи, они такие.
from modules import filemanager
file_manager = filemanager.filemanager_class

#@plz: from settings import settings_var и дальше юзать где угодно по коду ниже не судьба. И опять это ебанутое присвоение.
import settings
setup = settings.settings_var

#@plz: бесполезный модуль imp. Ниже будет понятно почему
import imp
import sys
import os
import time

#@plz: опять 25
from modules import arachni
arachni_launch = arachni.arachni_scanner_class

#@plz: это уже не смешно
from modules import sqlmap
sqlmap_class = sqlmap.sqlmap_class

#@plz: чтобы не забыть, а то таким трудом было составить эту строку. Комент ниже оставил автор, не знаю зачем

# @reboot cd /home/parallels/katusha_pro_max && sudo python main.py
# (sudo crontab -l ; echo "0 * * * * your_command") | sort - | uniq - | sudo crontab -

# CHECK MODULES
#@plz: начинаются пиздатые пустые строки. Они везде.
#@plz: видимо, когда я написал автору, что его кода в софте не больше 1000 строк, он всерьез обиделся и решил растягивать
def check_modules():
    #@plz: данная функция предпологает проверку модулей
    #@plz: но она бесполезна

    try:

        #@plz: одного модуля, как оказывается (а их всего 8 в текущей версии)
        modules = ['telegram']

        for module in modules:

            log.info("__main__|check_modules", "Check Katusha modules started.")

            try:
                #@plz: вот бесполезный imp. Он ищет модули по проекту с telegram в названии. 
                #@plz: автор настолько упоролося, что забыл где у него модули хранятся, и как называются
                #@plz: но почему он бесполезный? Смотрите первый импорт в этом файле.
                #@plz: если модуль не найден, скрипт рухнет ещё в момент импорта... Да похуй, нужно больше кода
                imp.find_module(module)
            except ImportError:
                log.warning("__main__|check_modules", "Module not found: " + module)
                sys.exit()

        #@plz: сообщает что ВСЕ один модуль установлены... куда блять они установлены. мозг себе установи
        log.info("__main__|check_modules", "All modules installed.")

    except Exception as error_code:
        log.warning("__main__|check_modules", str(error_code))
        pass

# CHECK LOADED AS ROOT USER
def check_privileges():
    #@plz: чекает права рута

    try:
        if os.getenv("USER") != "root":

            log.warning("__main__|check_privileges", "Please run script with root privileges.")
            sys.exit()
        #@plz: если прав нет, скрипт завершится, но...
    except Exception as error_code:
        #@plz: ...но, если произойдет ошибка, и окажется что прав рута нет
        log.warning("__main__|check_privileges", str(error_code))
        #@plz: то ему будет похуй, он напечает текст ошибки и просто выйдет из функции.
        #@plz: нахуя тут pass? (это фраза будет очень часто по коду)
        pass

# INSTALL CRONTAB JOB
#@plz: корн таб. Корн, сука, таб. Опечатка..?
def install_corn_tab():
    try:

        if not os.path.exists("installed"):

            #@plz: ...не, не думаю.
            log.warning("__main__|install_corn_tab", "Installing corn tab job.")

            #@plz: шикарный метод проверки, стоит ли скрипт в таске крона. 
            #@plz: он создает файлик, блять. Инновационная идея автора
            file("installed", 'w').close()
            #@plz: та самая строка из одинокого комментария в начале. 
            #@plz: назначение в автозапуске скрипта после ребута системы
            #@plz: первый os.system(), дальше по всем файлам их намного больше
            os.system('(sudo crontab -l ; echo "@reboot cd ' + os.path.dirname(os.path.abspath(__file__)) +
                                        ' && sudo python main.py ") | sort - | uniq - | sudo crontab -')

    except Exception as error_code:
        log.warning("__main__|install_corn_tab", str(error_code))
        #@plz: нахуя тут pass?
        pass

# START LAMPP SERVER
def start_http_server():
    #@plz: стартуем LAMP-сервант. Окей

    try:

        log.warning("__main__|start_http_server", "Start lampp server.")
        #@plz: что блять? Нахуя тут сон на 50 секунд?
        time.sleep(50)
        #@plz: а, теперь понятно. Не стану утверждать, но по-моему он перепутал строки местами
        #@plz: и почему именно 50? Наверное он делал замеры за сколько поднимается LAMP. Будем верить.
        #@plz: систем!
        os.system('/opt/lampp/lampp start')

    except Exception as error_code:
        log.warning("__main__|install_corn_tab", str(error_code))
        #@plz: нахуя тут pass?
        pass


# START MAIN SCRIPT
if __name__ == "__main__":
    #@plz: тут никто не сомневался, pass'ом клянусь
    """
    IM SUPER CODING HERO!
             by Blackman
    
    """

    try:

        log.info("__main__|__init__", setup.VERSION + " Started.")

        #@plz: тут, собсно, пошли вызовы всей этой вакханалии выше.
        #@plz: Помните мы говорили про импорты классов?
        #@plz: так вот, класс как сущность, сам по себе, представляет фабрику объектов.
        #@plz: т.е., вызывая класс, мы создаем instance класса, объект с методами
        #@plz: но наш супер-кодинг-херо не принимает инстансы. 
        #@plz: он вызывает классы как функцию, которая ничего не возвзращает... Кромешный долбоеб.
        check_privileges()
        check_modules()
        install_corn_tab()
        start_http_server()

        #@plz: ВАУ! первый инстанс, охуеть. Больше таких не будет
        telegram = telegram_api.telegram_api_class()
        telegram.telegram_utils()

        #@plz: всё, пиздец
        arachni_launch()
        file_manager()

        sqlmap_class()

        #@plz: далее посмотрим, что делает этот список вызовов выше в порядке их следования
        #@plz: первые четыре мы уже посмотрели, это функции из данного файла
        #@plz: остальные импортированы из modules, они в одноименной папке
        #@plz: но для начала, давайте посмотрим на файлы в корневой директории Хуйнюши
        #@plz: начнем с serv.py. Го туда.
    except Exception as error_code:
        log.warning("__main__|__init__", str(error_code))
        #@plz: ты издеваешься?
        pass
