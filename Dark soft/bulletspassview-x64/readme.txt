


BulletsPassView v1.32
Copyright (c) 2010 - 2015 Nir Sofer
Web site: http://www.nirsoft.net



Description
===========

BulletsPassView is a password recovery tool that reveals the passwords
stored behind the bullets in the standard password text-box of Windows
operating system and Internet Explorer Web browser. After revealing the
passwords, you can easily copy them to the clipboard or save them into
text/html/csv/xml file.


BulletsPassView is the successor of the old 'Asterisk Logger' utility,
with some advantages over the older tool:
* BulletsPassView doesn't reveal the password inside the password
  text-box itself. The password is only displayed in the main window of
  BulletsPassView, while the password text-box continues to display
  bullets.
* BulletsPassView also supports Windows 7/Vista/2008, while Asterisk
  Logger failed to work in these new operating systems.
* BulletsPassView also reveals the passwords stored in the password
  text-box of Internet Explorer.
* BulletsPassView supports command-line options to save the current
  opened password boxes into text/html/csv/xml file.
* BulletsPassView is a unicode application, which insures that
  passwords with non-English characters will be extracted properly.




Versions History
================


* Version 1.32:
  o Added 'Run As Administrator' option (Ctrl+F11), which allows you
    to easily run BulletsPassView as administrator on Windows
    Vista/7/8/2008. You should use this option is the software that has a
    password text-box is executed as administrator.
  o Fixed bug: BulletsPassView failed to remember the last
    size/position of the main window if it was not located in the primary
    monitor.

* Version 1.31:
  o Added 'Always On Top' option.

* Version 1.30:
  o Added option to choose another font (name and size) to display in
    the main window.
  o Added 'Auto Size Columns+Headers' option.

* Version 1.25:
  o Added 'Beep On New Password' option.

* Version 1.20:
  o Added 'Put Icon On Tray' option.
  o Added 'Start As Hidden' option. When this option and 'Put Icon On
    Tray' option are turned on, the main window of BulletsPassView will
    be invisible on start.

* Version 1.10:
  o Added 'Detected On' column.
  o Added 'Mark Odd/Even Rows' option, under the View menu. When it's
    turned on, the odd and even rows are displayed in different color, to
    make it easier to read a single line.

* Version 1.05:
  o Added 'Unmask Password Text Box' option. When this option is
    turned on, BulletsPassView also shows the password inside the
    password text box, instead of the bullets. This feature doesn't work
    with Internet Explorer windows.

* Version 1.00 - First release.



System Requirements
===================

This utility works on any version of Windows, starting from Windows 2000
and up to Windows 7/2008. If you want to extract passwords from x64
application, you have to use the x64 version of BulletsPassView.



Know Limitations
================

This utility works fine with most password text-boxes, but there are some
applications that don't store the password behind the bullets, in order
to increase their security. In such cases, BulletsPassView will not be
able to reveal the password.
Here's some examples for applications that BulletsPassView cannot reveal
their passwords:
* Chrome, Firefox, and Opera Web browsers.
* Dialup and network passwords of Windows.



Start Using BulletsPassView
===========================

BulletsPassView doesn't require any installation process or additional
dll file. In order to start using it, simply run the executable file -
BulletsPassView.exe

When you run BulletsPassView, it makes a first scan to locate passwords
text-boxes that are currently on the screen. If it finds any password,
it'll be displayed on the main window. In order to make another scan,
simply choose 'Refresh' under the view menu or press F5. There is also
'Auto Refresh' option under the Options menu. If you turn it on,
BulletsPassView will automatically scan for new password text-boxes every
few seconds. Be aware that the 'Auto Refresh' feature might be slow on
some computers, especially if there are many opened windows.



Command-Line Options
====================



/stext <Filename>
Save the list of bullet passwords that are currenly on the screen into a
simple text file.

/stab <Filename>
Save the list of bullet passwords that are currenly on the screen into a
tab-delimited text file.

/scomma <Filename>
Save the list of bullet passwords that are currenly on the screen into a
comma-delimited text file (csv).

/stabular <Filename>
Save the list of bullet passwords that are currenly on the screen into a
tabular text file.

/shtml <Filename>
Save the list of bullet passwords that are currenly on the screen into
HTML file (Horizontal).

/sverhtml <Filename>
Save the list of bullet passwords that are currenly on the screen into
HTML file (Vertical).

/sxml <Filename>
Save the list of bullet passwords that are currenly on the screen into
XML file.



Translating BulletsPassView to other languages
==============================================

In order to translate BulletsPassView to other language, follow the
instructions below:
1. Run BulletsPassView with /savelangfile parameter:
   BulletsPassView.exe /savelangfile
   A file named BulletsPassView_lng.ini will be created in the folder of
   BulletsPassView utility.
2. Open the created language file in Notepad or in any other text
   editor.
3. Translate all string entries to the desired language. Optionally,
   you can also add your name and/or a link to your Web site.
   (TranslatorName and TranslatorURL values) If you add this information,
   it'll be used in the 'About' window.
4. After you finish the translation, Run BulletsPassView, and all
   translated strings will be loaded from the language file.
   If you want to run BulletsPassView without the translation, simply
   rename the language file, or move it to another folder.



License
=======

This utility is released as freeware. You are allowed to freely use it at
your home or in your company. However, you are not allowed to make profit
from this software or to charge your customers for recovering their
passwords with this software, unless you got a permission from the
software author.
You are also allowed to freely distribute this utility via floppy disk,
CD-ROM, Internet, or in any other way, as long as you don't charge
anything for this. If you distribute this utility, you must include all
files in the distribution package, without any modification !



Disclaimer
==========

The software is provided "AS IS" without any warranty, either expressed
or implied, including, but not limited to, the implied warranties of
merchantability and fitness for a particular purpose. The author will not
be liable for any special, incidental, consequential or indirect damages
due to loss of data or any other reason.



Feedback
========

If you have any problem, suggestion, comment, or you found a bug in my
utility, you can send a message to nirsofer@yahoo.com
