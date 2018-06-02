#!/usr/bin/env python
# -*- coding: utf-8 -*-

from telegram.ext import Updater, CommandHandler, MessageHandler, Filters
from telegram import ParseMode, chataction

from messages import message_viewer
messages = message_viewer()

from logger import logger_class
log = logger_class()

import settings
setup = settings.settings_var()

import random, string, os

# IDS
USER_ID = ""

# MESSAGES ID
HEAD_MESSAGE_ID = ""
BODY_MESSAGE_ID = ""
FOOTER_MESSAGE_ID = ""

class telegram_api_class():

    try:

        updater = Updater(setup.TELEGRAM_API_KEY)
        dispatcher = updater.dispatcher
        Action = chataction

    except Exception, error_code:
        log.warning("telegram_api_class|", str(error_code))
        pass

    # LOAD TELEGRAM SETTINGS
    def telegram_load_and_save_settings(self):

        global USER_ID
        global HEAD_MESSAGE_ID
        global BODY_MESSAGE_ID
        global FOOTER_MESSAGE_ID

        try:

            if not os.path.exists("USER_DATA.kt"):

                if USER_ID:

                    log.info("telegram_api|telegram_load_and_save_settings", "Telegram API | Save user data...")
                    save_user_data = open("USER_DATA.kt", 'w+')

                    save_user_data.write("USER_ID=" + str(USER_ID) + "\n")
                    save_user_data.write("HEAD_MESSAGE_ID=" + str(HEAD_MESSAGE_ID) + "\n")
                    save_user_data.write("BODY_MESSAGE_ID=" + str(BODY_MESSAGE_ID) + "\n")
                    save_user_data.write("FOOTER_MESSAGE_ID=" + str(FOOTER_MESSAGE_ID) + "\n")

                    save_user_data.close()

            else:

                log.info("telegram_api|telegram_load_and_save_settings", "Telegram API | Load user data...")
                load_user_data = open("USER_DATA.kt", "a+")

                for data in load_user_data.readlines():

                    if "USER_ID" in data.replace("\n", ""):
                        USER_ID = int(data.replace("\n", "").split("=")[1])
                    if "HEAD_MESSAGE_ID" in data.replace("\n", ""):
                        HEAD_MESSAGE_ID = int(data.replace("\n", "").split("=")[1])
                    if "BODY_MESSAGE_ID" in data.replace("\n", ""):
                        BODY_MESSAGE_ID = int(data.replace("\n", "").split("=")[1])
                    if "FOOTER_MESSAGE_ID" in data.replace("\n", ""):
                        FOOTER_MESSAGE_ID = int(data.replace("\n", "").split("=")[1])

        except Exception, error_code:
            log.warning("telegram_api|telegram_load_settings", str(error_code))
            pass


    def check_user_connected_status(self):

        try:

            if not USER_ID:
                return False
            else:
                return True

        except Exception, error_code:
            log.warning("telegram_api|check_user_connected_status", str(error_code))
            pass

    def telegram_utils_typing(self):

        try:

            if self.check_user_connected_status:
                self.dispatcher.bot.sendChatAction(chat_id=USER_ID, action=self.Action.ChatAction.TYPING)
            else:
                pass

        except Exception, error_code:
            log.warning("telegram_api_class|telegram_utils_typing", str(error_code))
            pass

    # START MESSAGE
    def start_handler(self, bot, update):

        try:

            global USER_ID
            global HEAD_MESSAGE_ID
            global BODY_MESSAGE_ID
            global FOOTER_MESSAGE_ID

            if USER_ID:
                if USER_ID != update.message.chat_id:
                    log.warning("telegram_api_class|start_handler",
                                "Unknown user connected | ID: " + str(update.message.chat_id))
            else:

                USER_ID = update.message.chat_id
                log.info("telegram_api|start_handler", "Telegram API | New user connected | ID: " + str(update.message.chat_id))

                HEAD_MESSAGE_ID = bot.send_message(chat_id=update.message.chat_id, text=messages.HEAD_MESSAGE(
                    update.message.chat_id, 0, 0), parse_mode=ParseMode.HTML)['message_id']

                BODY_MESSAGE_ID = \
                    bot.send_message(chat_id=update.message.chat_id, text=messages.BODY_MESSAGE(),
                                     parse_mode=ParseMode.HTML)['message_id']

                FOOTER_MESSAGE_ID = \
                    bot.send_message(chat_id=update.message.chat_id,
                                     text=messages.FOOTER_MESSAGE(setup.VERSION + " loaded."),
                                     parse_mode=ParseMode.HTML)['message_id']

                self.telegram_load_and_save_settings()

        except Exception, error_code:
            log.warning("telegram_api|start_handler", str(error_code))
            pass

    # UPDATE HEAD MESSAGE
    def update_head_message(self, sites, sites_left):

        try:

            if self.check_user_connected_status():
                self.dispatcher.bot.editMessageText(text=messages.HEAD_MESSAGE(
                    USER_ID, sites, sites_left), message_id=HEAD_MESSAGE_ID, chat_id=USER_ID, parse_mode=ParseMode.HTML)
            else:
                pass


        except Exception, error_code:
            log.warning("telegram_api|update_head_message", str(error_code))
            pass

    # UPDATE FOOTER MESSAGE
    def update_footer_message(self, message):

        try:

            if self.check_user_connected_status():
                self.dispatcher.bot.editMessageText(text=messages.FOOTER_MESSAGE(message),
                                                    message_id=FOOTER_MESSAGE_ID, chat_id=USER_ID,
                                                    parse_mode=ParseMode.HTML)

            else:
                pass



        except Exception, error_code:
            log.warning("telegram_api|update_footer_message", str(error_code))
            pass

    # SCAN FILES DISPATCHER
    def scan_files_dispatcher(self, bot, update):

        try:

            if USER_ID:

                if USER_ID != update.message.chat_id:
                    log.warning("telegram_api_class|scan_files_dispatcher",
                                "Unknown user send scan file | ID: " + str(update.message.chat_id))
                else:

                    file_name = ''.join(random.choice(string.ascii_uppercase + string.digits) for _ in range(5)) + ".sq"

                    log.info("telegram_api|scan_dispatcher",
                             "Telegram API | Updated new scan file, saved as " + file_name)
                    file = bot.getFile(update.message.document.file_id)
                    file.download('scan_files/' + file_name)

            else:

                log.warning("telegram_api_class|scan_files_dispatcher",
                            "Unknown user send scan file | ID: " + str(update.message.chat_id))



        except Exception, error_code:
            log.warning("telegram_api|scan_dispatcher", str(error_code))
            pass

    def telegram_utils(self):

        try:

            # ADD DISPATCHER UTILS
            self.dispatcher.add_handler(CommandHandler('start', self.start_handler))
            self.dispatcher.add_handler(MessageHandler(Filters.document, self.scan_files_dispatcher))

            log.info("telegram_api|telegram_utils", "Telegram API | Waiting for user...")
            self.updater.start_polling()

            self.telegram_load_and_save_settings()

        except Exception, error_code:
            log.warning("telegram_api|telegram_utils", str(error_code))
            pass
