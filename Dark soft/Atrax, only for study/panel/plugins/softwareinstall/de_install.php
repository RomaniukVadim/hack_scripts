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
								`creationDate` datetime NOT NULL,
                                `software` text NOT NULL
                                ) ENGINE = MYISAM CHARACTER SET ucs2;
							');
    }

    public function deinstall()
    {
        $this->MySQL->doQuery('DROP TABLE `plugin_' . $this->PluginName . '`');
    }
}