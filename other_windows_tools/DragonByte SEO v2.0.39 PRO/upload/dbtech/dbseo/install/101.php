<?php

self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "dbtech_dbseo_resolvedurl` ADD INDEX (`seourl`(255))");
self::report('Altered Table', 'dbtech_dbseo_resolvedurl');