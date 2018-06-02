# coding: utf-8 #@plz: сново прописываю для unicode
#@plz: посмотрите на эти прекрасные импорты
import os
import random 
from flask import Flask, request, redirect, send_file, render_template, url_for, flash
from werkzeug.utils import secure_filename
import threading
import time
import requests
import socks
import smtplib
from BeautifulSoup import BeautifulSoup as Soup
import urllib2
import urllib
import re
import mimetypes
from email.mime.base import MIMEBase
from email.utils import formatdate
from smtplib import SMTP_SSL
from email.mime.multipart import MIMEMultipart
from email import encoders
from email.mime.text import MIMEText
from email.header import Header
from validate_email import validate_email
from email.mime.application import MIMEApplication
from email import charset
import imaplib
import email
from email.encoders import encode_base64
from email.encoders import encode_quopri
import urllib3
import base64
import dns.resolver
from email.utils import formatdate
import socket
import datetime
import subprocess
#@plz: я, разумеется, понимаю, что человек его уровня развития, мог сделать эти импорты на будущее
#@plz: но на кой хуй сдавать в аренду недоделку, либо dev-версию? Очевидно, ему просто похуй...


#@plz: этот код являет собой унылые потуги определить доменное имя хоста
urllib3.disable_warnings()
#@plz: импортированы urllib, urllib2, urllib3, requests для отправки http-запросов
#@plz: но нет, наш автор создает саб-процесс с wget и парсит айпи оттуда. Диагноз: ебанат
inst_ip = os.popen('wget -O - -q icanhazip.com').read().strip()

try:
    #@plz: тут он определяет домен хоста 
    #@plz: но больше этот адрес нигде не используется. Видимо по тем же причинам
    host_name = socket.gethostbyaddr(inst_ip)[0]
    print host_name
except:
    host_name = inst_ip
#@plz: пустые строки...




UPLOAD_FOLDER = './mailer_old'
ALLOWED_EXTENSIONS = set(['txt', 'pdf', 'png', 'jpg', 'jpeg', 'gif'])

app = Flask(__name__)
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER


def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS
#@plz: пустые строки... не pass, так пустые строки





@app.route('/reboot')
def reboot():
    os.system('reboot')
    return '200'
#@plz: дальше всё однотипное и сырое, смысла комментироть нет. Переходим к settings.py



@app.route('/clear_scanned')
def clear_scanned():
    os.system('rm -f scanned.txt')
    return '200'

@app.route('/clear_list')
def clear_list():
    os.system('rm -f scan_files/*')
    return '200'

@app.route('/clear_tmp')
def clear_tmp():
    os.system('screen rm -rf /tmp && mkdir /tmp && chmod -R 777 /tmp')
    return '200'

@app.route('/download')
def download():
    today = datetime.datetime.today()
    a = today.strftime("%Y%m%d%H%M%S")
    os.system('zip dump_' + a + '.zip -r reports_requests/* reports/* sqlmap_dumps/* sqlmap_done.txt scanned.txt')
    file_name = 'dump_' + a + '.zip'
    return send_file(file_name, attachment_filename=file_name)


@app.route('/download_smtp')
def download_smtp():
    file_name = 'mailer_old/smtp.txt'
    return send_file(file_name, attachment_filename=file_name)

@app.route('/download_imap')
def download_imap():
    file_name = 'mailer_old/imap.txt'
    return send_file(file_name, attachment_filename=file_name)



port = os.getenv('VCAP_APP_PORT', '5000')
if __name__ == "__main__":
        app.run(host='0.0.0.0', port=int(port))
