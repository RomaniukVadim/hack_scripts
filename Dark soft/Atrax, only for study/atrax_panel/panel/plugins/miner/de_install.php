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
                                `GUID` BINARY(16) NOT NULL PRIMARY KEY ,
								`creationDate` datetime NOT NULL,
                                `hashspeed` VARCHAR( 300 ) NOT NULL
                                ) ENGINE = MYISAM CHARACTER SET ucs2;
							');
    }

    public function deinstall()
    {
        $this->MySQL->doQuery('DROP TABLE `plugin_' . $this->PluginName . '`');
    }
}