#!/usr/bin/env python
# -*- coding: utf-8 -*-

from modules import logger
log = logger.logger_class()

FOOTER_MSG = ""

class message_viewer():

    def HEAD_MESSAGE(self, ID, SITES_IN_DB, SITES_LEFT):

        message = "🔰<b> SYSTEM INFO:</b>\n" \
                  "\n" \
                  "▪️ <b>USER_ID:</b> <code>" + str(ID) + "</code> | ⚛ <b>SITES:</b> " \
                  "<code>" + str(SITES_IN_DB) +  "</code> <b>/</b> ⭐ <b>LEFT:</b> <code>" + str(SITES_LEFT) + "</code>" \
                  "\n" \

        return message

    def BODY_MESSAGE(self):

        message = "▫\n" \
                  "▫️<b>          ⚔ Welcome to Arachni Scanner System 0.8</b> \n" \
                  "▫\n" \
                  "▫️  Katusha🕵️‍♀️ is hight Perfomance SQL vulnerability️ scanner,\n" \
                  "▫️  it is based on Arachni Scanner and can help you to find \n" \
                  "▫️  SQL injection on your target list! It is very   \n" \
                  "▫️  FAST and include ERROR-BASED, TIME BASED & \n" \
                  "▫  BLIND BASED sql injection tests. \n"

        return message

    def FOOTER_MESSAGE(self, message):

        global FOOTER_MSG

        FOOTER_MSG += "▫ " + message + "\n"
        message = "♻️<b> OUTPUT:</b>\n" \
                  "\n" \
                  + FOOTER_MSG

        return message