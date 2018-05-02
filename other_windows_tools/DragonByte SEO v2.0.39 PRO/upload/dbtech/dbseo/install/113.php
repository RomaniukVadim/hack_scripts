<?php
self::$db->query_write("DELETE FROM `" . TABLE_PREFIX  . "dbtech_dbseo_urlhistory` WHERE regexpformat LIKE '%([a-z\._\-'");
self::report('Truncated Table', 'dbtech_dbseo_urlhistory');