<?php
// CVS: $Id: trackerlib.php,v 1.231.2.43 2008-03-21 21:50:38 sylvieg Exp $
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"],basename(__FILE__)) !== false) {
  header("location: index.php");
  exit;
}


					
class TrackerLib extends TikiLib {

	var $trackerinfo_cache;

	// allowed types for image fields
	var $imgMimeTypes;
	var $imgMaxSize;

	function TrackerLib($db) {
		parent::TikiLib($db);

		$this->imgMimeTypes = array('image/jpeg', 'image/gif', 'image/png', 'image/pjpeg');
		$this->imgMaxSize = (1048576 * 4); // 4Mo
	}

	// check that the image type is good
	function check_image_type($mimeType) {
		return in_array( $mimeType, $this->imgMimeTypes );
	}

	function get_image_filename($imageFileName, $itemId, $fieldId) {
		return $file_name = 'img/trackers/'.md5( "$imageFileName.$itemId.$fieldId" );
	}

	function remove_field_images($fieldId) {
		$query = 'select `value` from `tiki_tracker_item_fields` where `fieldId`=?';
		$result = $this->query( $query, array((int)$fieldId) );
		while( $r = $result->fetchRow() ) {
			if( file_exists($r['value']) ) {
				unlink( $r['value'] );
			}
		}
	}

	function add_item_attachment_hit($id) {
		global $prefs, $user;
		if ($user != 'admin' || $prefs['count_admin_pvs'] == 'y' ) {
			$query = "update `tiki_tracker_item_attachments` set `hits`=`hits`+1 where `attId`=?";
			$result = $this->query($query,array((int) $id));
		}
		return true;
	}

	function get_item_attachment_owner($attId) {
		return $this->getOne("select `user` from `tiki_tracker_item_attachments` where `attId`=?",array((int) $attId));
	}

	function list_item_attachments($itemId, $offset, $maxRecords, $sort_mode, $find) {
		if ($find) {
			$findesc = '%' . $find . '%';
			$mid = " where `itemId`=? and (`filename` like ?)";
			$bindvars=array((int) $itemId,$findesc);
		} else {
			$mid = " where `itemId`=? ";
			$bindvars=array((int) $itemId);
		}
		$query = "select `user`,`attId`,`itemId`,`filename`,`filesize`,`filetype`,`hits`,`created`,`comment`,`longdesc`,`version` ";
		$query.= " from `tiki_tracker_item_attachments` $mid order by ".$this->convert_sortmode($sort_mode);
		$query_cant = "select count(*) from `tiki_tracker_item_attachments` $mid";
		$result = $this->query($query,$bindvars,$maxRecords,$offset);
		$cant = $this->getOne($query_cant,$bindvars);
		$ret = array();

		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}

		$retval = array();
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}

	function get_item_nb_attachments($itemId) {
		$query = "select sum(hits) as hits, count(*) as attachments from `tiki_tracker_item_attachments` where `itemId`=?";
		$result = $this->query($query, array($itemId));
		if ($res = $result->fetchRow())
			return $res;
		return array();
	}

	function get_item_nb_comments($itemId) {
		return $this->getOne('select count(*) from `tiki_tracker_item_comments` where `itemId`=?', array((int)$itemId));
	}

	function list_all_attachements($offset=0, $maxRecords=-1, $sort_mode='created_desc', $find='') {
		if ($find) {
			$findesc = '%' . $find . '%';
			$mid = " where `filename` like ?";
			$bindvars=array($findesc);
		} else {
			$mid = "";
			$bindvars=array();
		}
		$query = "select `user`,`attId`,`itemId`,`filename`,`filesize`,`filetype`,`hits`,`created`,`comment`,`path` ";
		$query.= " from `tiki_tracker_item_attachments` $mid order by ".$this->convert_sortmode($sort_mode);
		$query_cant = "select count(*) from `tiki_tracker_item_attachments` $mid";
		$result = $this->query($query,$bindvars,$maxRecords,$offset);
		$cant = $this->getOne($query_cant,$bindvars);
		$ret = array();
		while ($res = $result->fetchRow()) {
			$ret[] = $res;
		}
		$retval = array();
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}

	function file_to_db($path,$attId) {
		if (is_file($path)) {
			$fp = fopen($path,'rb');
			$data = '';
			while (!feof($fp)) {
				$data .= fread($fp, 8192 * 16);
			}
			fclose ($fp);
			$query = "update `tiki_tracker_item_attachments` set `data`=?,`path`=? where `attId`=?";
			if ($this->query($query,array($data,'',(int)$attId))) {
				unlink($path);
			}
		}
	}

	function db_to_file($path,$attId) {
		$fw = fopen($path,'wb');
		$data = $this->getOne("select `data` from `tiki_tracker_item_attachments` where `attId`=?",array((int)$attId));
		if ($data) {
			fwrite($fw, $data);
		}
		fclose ($fw);
		if (is_file($path)) {
			$query = "update `tiki_tracker_item_attachments` set `data`=?,`path`=? where `attId`=?";
			$this->query($query,array('',basename($path),(int)$attId));
		}
	}

	function get_item_attachment($attId) {
		$query = "select * from `tiki_tracker_item_attachments` where `attId`=?";
		$result = $this->query($query,array((int) $attId));
		if (!$result->numRows()) return false;
		$res = $result->fetchRow();
		return $res;
	}

	function remove_item_attachment($attId) {
		global $prefs;
		$path = $this->getOne("select `path` from `tiki_tracker_item_attachments` where `attId`=?",array((int) $attId));
		if ($path) @unlink ($prefs['t_use_dir'] . $path);
		$query = "delete from `tiki_tracker_item_attachments` where `attId`=?";
		$result = $this->query($query,array((int) $attId));
		$query = 'update `tiki_tracker_item_fields` ttif left join `tiki_tracker_fields` ttf using (`fieldId`) set `value`="" where ttif.`value`=? and ttf.`type`=?';
		$this->query($query, array((int)$attId, 'A'));
	}

	function replace_item_attachment($attId, $filename, $type, $size, $data, $comment, $user, $fhash, $version, $longdesc, $trackerId=0, $itemId=0,$options='', $notif=true) {
		global $prefs;
		$comment = strip_tags($comment);
		$now = $this->now;
		if (empty($attId)) {
			$query = "insert into `tiki_tracker_item_attachments`(`itemId`,`filename`,`filesize`,`filetype`,`data`,`created`,`hits`,`user`,";
			$query.= "`comment`,`path`,`version`,`longdesc`) values(?,?,?,?,?,?,?,?,?,?,?,?)";
			$result = $this->query($query,array((int) $itemId,$filename,$size,$type,$data,(int) $now,0,$user,$comment,$fhash,$version,$longdesc));
			$query = 'select `attId` from `tiki_tracker_item_attachments` where `itemId`=? and `user`=? and `created`=?';
			$attId = $this->getOne($query, array($itemId, $user, $now));
		} elseif (empty($filename)) {
			$query = "update `tiki_tracker_item_attachments` set `comment`=?,`user`=?,`version`=?,`longdesc`=? where `attId`=?";
			$result = $this->query($query,array($comment, $user, $version, $longdesc, $attId));
		} else {
			$path = $this->getOne("select `path` from `tiki_tracker_item_attachments` where `attId`=?",array((int) $attId));
			if ($path) @unlink ($prefs['t_use_dir'] . $path);
			$query = "update `tiki_tracker_item_attachments` set `filename`=?,`filesize`=?,`filetype`=?, `data`=?,`comment`=?,`user`=?,`path`=?, `version`=?,`longdesc`=? where `attId`=?";
			$result = $this->query($query,array($filename, $size, $type, $data, $comment, $user, $fhash, $version, $longdesc, (int)$attId));
		}
		if (!$notif)
			return $attId;
		$watchers = $this->get_notification_emails($trackerId, $itemId, $options);
		if (count($watchers > 0)) {
			global $smarty;
			$trackerName = $this->getOne("select `name` from `tiki_trackers` where `trackerId`=?",array((int) $trackerId));
			$smarty->assign('mail_date', $this->now);
			$smarty->assign('mail_user', $user);
			$smarty->assign('mail_action', 'New File Atttached to Item:' . $itemId . ' at tracker ' . $trackerName);
			$smarty->assign('mail_itemId', $itemId);
			$smarty->assign('mail_trackerId', $trackerId);
			$smarty->assign('mail_trackerName', $trackerName);
			$smarty->assign('mail_attId', $attId);
			$smarty->assign('mail_data', $filename."\n".$comment."\n".$version."\n".$longdesc);
			$foo = parse_url($_SERVER["REQUEST_URI"]);
			$machine = $this->httpPrefix(). $foo["path"];
			$smarty->assign('mail_machine', $machine);
			$parts = explode('/', $foo['path']);
			if (count($parts) > 1)
				unset ($parts[count($parts) - 1]);
			$smarty->assign('mail_machine_raw', $this->httpPrefix(). implode('/', $parts));
			if (!isset($_SERVER["SERVER_NAME"])) {
				$_SERVER["SERVER_NAME"] = $_SERVER["HTTP_HOST"];
			}
			include_once ('lib/webmail/tikimaillib.php');
			$smarty->assign('server_name', $_SERVER['SERVER_NAME']);
			foreach ($watchers as $w) {
				$mail = new TikiMail($w['user']);
				$mail->setHeader("From", $prefs['sender_email']);
				$mail->setSubject($smarty->fetchLang($w['lang'], 'mail/tracker_changed_notification_subject.tpl'));
				$mail->setText($smarty->fetchLang($w['lang'], 'mail/tracker_changed_notification.tpl'));
				$mail->send(array($w['email']));
			}
		}
		return $attId;
	}

	function replace_item_comment($commentId, $itemId, $title, $data, $user, $options) {
		global $smarty, $notificationlib, $prefs;
		include_once ('lib/notifications/notificationlib.php');
		$title = strip_tags($title);
		$data = strip_tags($data, "<a>");

		if ($commentId) {
			$query = "update `tiki_tracker_item_comments` set `title`=?, `data`=? , `user`=? where `commentId`=?";

			$result = $this->query($query,array($title,$data,$user,(int) $commentId));
		} else {

			$query = "insert into `tiki_tracker_item_comments`(`itemId`,`title`,`data`,`user`,`posted`) values (?,?,?,?,?)";
			$result = $this->query($query,array((int) $itemId,$title,$data,$user,(int) $this->now));
			$commentId
				= $this->getOne("select max(`commentId`) from `tiki_tracker_item_comments` where `posted`=? and `title`=? and `itemId`=?",array((int) $this->now,$title,(int)$itemId));
		}

		$trackerId = $this->getOne("select `trackerId` from `tiki_tracker_items` where `itemId`=?",array((int) $itemId));

		$watchers = $this->get_notification_emails($trackerId, $itemId, $options);

		if (count($watchers > 0)) {
			$trackerName = $this->getOne("select `name` from `tiki_trackers` where `trackerId`=?",array((int) $trackerId));
			$smarty->assign('mail_date', $this->now);
			$smarty->assign('mail_user', $user);
			$smarty->assign('mail_action', 'New comment added for item:' . $itemId . ' at tracker ' . $trackerName);
			$smarty->assign('mail_data', $title . "\n\n" . $data);
			$smarty->assign('mail_itemId', $itemId);
			$smarty->assign('mail_trackerId', $trackerId);
			$smarty->assign('mail_trackerName', $trackerName);
			$foo = parse_url($_SERVER["REQUEST_URI"]);
			$machine = $this->httpPrefix(). $foo["path"];
			$smarty->assign('mail_machine', $machine);
			$parts = explode('/', $foo['path']);
			if (count($parts) > 1)
				unset ($parts[count($parts) - 1]);
			$smarty->assign('mail_machine_raw', $this->httpPrefix(). implode('/', $parts));
			if (!isset($_SERVER["SERVER_NAME"])) {
				$_SERVER["SERVER_NAME"] = $_SERVER["HTTP_HOST"];
			}
			include_once ('lib/webmail/tikimaillib.php');
			$smarty->assign('server_name', $_SERVER['SERVER_NAME']);
			foreach ($watchers as $w) {
				$mail = new TikiMail($w['user']);
				$mail->setHeader("From", $prefs['sender_email']);
				$mail->setSubject($smarty->fetchLang($w['lang'], 'mail/tracker_changed_notification_subject.tpl'));
				$mail->setText($smarty->fetchLang($w['lang'], 'mail/tracker_changed_notification.tpl'));
				$mail->send(array($w['email']));
			}
		}

		return $commentId;
	}

	function remove_item_comment($commentId) {
		$query = "delete from `tiki_tracker_item_comments` where `commentId`=?";
		$result = $this->query($query,array((int) $commentId));
	}

	function list_item_comments($itemId, $offset, $maxRecords, $sort_mode, $find) {
		if ($find) {
			$findesc = '%' . $find . '%';
			$mid = " and (`title` like ? or `data` like ?)";
			$bindvars = array((int) $itemId,$findesc,$findesc);
		} else {
			$mid = "";
			$bindvars = array((int) $itemId);
		}

		$query = "select * from `tiki_tracker_item_comments` where `itemId`=? $mid order by ".$this->convert_sortmode($sort_mode);
		$query_cant = "select count(*) from `tiki_tracker_item_comments` where `itemId`=? $mid";
		$result = $this->query($query,$bindvars,$maxRecords,$offset);
		$cant = $this->getOne($query_cant,$bindvars);
		$ret = array();

		while ($res = $result->fetchRow()) {
			$res["parsed"] = nl2br($res["data"]);

			$ret[] = $res;
		}

		$retval = array();
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}

	function list_last_comments($trackerId = 0, $itemId = 0, $offset, $maxRecords) {
	    $mid = "1=1";
	    $bindvars = array();

	    if ($itemId != 0) {
		$mid .= " and `itemId`=?";
		$bindvars[] = (int) $itemId;
	    }

	    if ($trackerId != 0) {
		$query = "select t.* from `tiki_tracker_item_comments` t left join `tiki_tracker_items` a on t.`itemId`=a.`itemId` where $mid and a.`trackerId`=? order by t.`posted` desc";
		$bindvars[] = $trackerId;
		$query_cant = "select count(*) from `tiki_tracker_item_comments` t left join `tiki_tracker_items` a on t.`itemId`=a.`itemId` where $mid and a.`trackerId`=? order by t.`posted` desc";
	    }
	    else {
		$query = "select * from `tiki_tracker_item_comments` where $mid order by `posted` desc";
		$query_cant = "select count(*) from `tiki_tracker_item_comments` where $mid";
	    }
	    $result = $this->query($query,$bindvars,$maxRecords,$offset);
	    $cant = $this->getOne($query_cant,$bindvars);
	    $ret = array();

	    while ($res = $result->fetchRow()) {
		$res["parsed"] = nl2br($res["data"]);
		$ret[] = $res;
	    }

	    $retval = array();
	    $retval["data"] = $ret;
	    $retval["cant"] = $cant;

	    return $retval;
	}


	function get_item_comment($commentId) {
		$query = "select * from `tiki_tracker_item_comments` where `commentId`=?";
		$result = $this->query($query,array((int) $commentId));
		if (!$result->numRows()) return false;
		$res = $result->fetchRow();
		return $res;
	}

	function get_last_position($id) {
		return $this->getOne("select max(`position`) from `tiki_tracker_fields` where `trackerId` = ?",array((int)$id));
	}

	function get_tracker_item($itemid) {
		$query = "select * from `tiki_tracker_items` where `itemId`=?";

		$result = $this->query($query,array((int) $itemid));

		if (!$result->numrows())
			return false;

		$res = $result->fetchrow();
		$query = "select * from `tiki_tracker_item_fields` ttif, `tiki_tracker_fields` ttf where ttif.`fieldId`=ttf.`fieldId` and `itemId`=?";
		$result = $this->query($query,array((int) $itemid));
		$fields = array();

		while ($res2 = $result->fetchrow()) {
			$id = $res2["fieldId"];
			$res["$id".$res2["lang"].""] = $res2["value"];
		}

		return $res;
	}

	function get_item_id($trackerId,$fieldId,$value) {
		$query = "select distinct ttif.`itemId` from `tiki_tracker_items` tti, `tiki_tracker_fields` ttf, `tiki_tracker_item_fields` ttif ";
		$query.= " where tti.`trackerId`=ttf.`trackerId` and ttif.`fieldId`=ttf.`fieldId` and ttf.`trackerId`=? and ttf.`fieldId`=? and ttif.`value`=?";
		$ret = $this->getOne($query,array((int) $trackerId,(int)$fieldId,$value));
		return $ret;
	}

	function get_item($trackerId,$fieldId,$value) {
		$itemId = $this->get_item_id($trackerId,$fieldId,$value);
		return $this->get_tracker_item($itemId);
	}

	/* experimental shared */
	function get_item_value($trackerId,$itemId,$fieldId) {
		global $prefs;
		$query = "select ttif.`value`, ttf.`type`, ttif.`lang` from `tiki_tracker_items` tti, `tiki_tracker_fields` ttf, `tiki_tracker_item_fields` ttif where tti.`trackerId`=ttf.`trackerId` and ttif.`fieldId`=ttf.`fieldId` and ttf.`trackerId`=? and ttf.`fieldId`=? and ttif.`itemId`=? ";
		$result = $this->query($query, array((int)$trackerId,(int)$fieldId,(int)$itemId));
		if (!$result->numRows()) {
			return false;
		}
		if ($this->is_multilingual($fieldId) == 'y') {
			while ($res = $result->fetchRow()) {
				if ($res['lang'] == $prefs['language']) {
					return $res['value'];
				}
				$ret = $res['value'];
			}
		} else {
			$res = $result->fetchRow();
			$ret =  $res['value'];
		}
		return $ret;
	}

	/* experimental shared */
	function get_items_list($trackerId, $fieldId, $value, $status='o') {
		$query = "select distinct ttif.`itemId` from `tiki_tracker_items` tti, `tiki_tracker_fields` ttf, `tiki_tracker_item_fields` ttif ";
		$query.= " where tti.`trackerId`=ttf.`trackerId` and ttif.`fieldId`=ttf.`fieldId` and ttf.`trackerId`=? and ttf.`fieldId`=? and ttif.`value`=?";
		$bindVars = array((int)$trackerId, (int)$fieldId, $value);
		if (!empty($status)) {
			$query .= 'and tti.`status`=?';
			$bindVars[] = $status;
		}
		$result = $this->query($query, $bindVars);
		$ret = array();
		while ($res = $result->fetchRow()) {
			$ret[] = $res['itemId'];
		}
		return $ret;
	}

        function concat_item_from_fieldslist($trackerId,$itemId,$fieldsId,$status='o',$separator=' '){
                $res='';
                $sts = preg_split('/\|/', $fieldsId, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($sts as $field){
                    $myfield=$this->get_tracker_field($field);
                    $is_date=($myfield['type']=='f');
                    $is_trackerlink=($myfield['type']=='r');
                    
                    $tmp=$this->get_item_value($trackerId,$itemId,$field);
                    if ($is_trackerlink){
                      $options = split(',', $myfield["options"]);
                      $tmp=$this->concat_item_from_fieldslist($options[0],$this->get_item_id($options[0],$options[1],$tmp),$options[3]);
                     }
                    if ($is_date) $tmp=$this->date_format("%e/%m/%y",$tmp);
                    $res.=$separator.$tmp;
                }
                return $res;
        }

        function concat_all_items_from_fieldslist($trackerId,$fieldsId,$status='o',$separator=' ') {
           $sts = preg_split('/\|/', $fieldsId, -1, PREG_SPLIT_NO_EMPTY);
		   $res = array();
           foreach ($sts as $field){
                $myfield=$this->get_tracker_field($field);
                $is_date=($myfield['type']=='f');
                $is_trackerlink=($myfield['type']=='r');
                $tmp="";
                $tmp=$this->get_all_items($trackerId,$field,$status);
                $options = split(',', $myfield["options"]);
                foreach ($tmp as $key=>$value){
                    if ($is_date) $value=$this->date_format("%e/%m/%y",$value);
                    if ($is_trackerlink){
                      $value=$this->concat_item_from_fieldslist($options[0],$this->get_item_id($options[0],$options[1],$value),$options[3]);
                    }
					if (!empty($res[$key])) {
						$res[$key].=$separator.$value;
					} else {
						$res[$key] = $value;
                    }
                }
			}
            return $res;
        }
        
        
	function valid_status($status) {
		if ($status == 'o' || $status == 'c' || $status == 'p' || $status == 'op' || $status == 'oc'
			|| $status == 'pc' || $status == 'opc') {
			return true;
		} else {
			return false;
		}
	}
	function get_all_items($trackerId,$fieldId,$status='o') {
		global $cachelib, $prefs;

		$sort_mode = "value_asc";
		$cache = md5('trackerfield'.$fieldId.$status);
		if ($this->is_multilingual($fieldId) == 'y') { 
			$multi_languages=$prefs['available_languages'];
			$cache = md5('trackerfield'.$fieldId.$status.$prefs['language']);
		} else
			unset($multi_languages);
                
		
		if (!$cachelib->isCached($cache) || !$this->valid_status($status)) {
			$sts = preg_split('//', $status, -1, PREG_SPLIT_NO_EMPTY);
			$mid = "  (".implode('=? or ',array_fill(0,count($sts),'tti.`status`'))."=?) ";
			$fieldIdArray = preg_split('/\|/', $fieldId, -1, PREG_SPLIT_NO_EMPTY);
			$mid.= " and (".implode('=? or ',array_fill(0,count($fieldIdArray),'ttif.`fieldId`'))."=?) ";
			if ($this->is_multilingual($fieldId) == 'y'){
				$mid.=" and ttif.`lang`=?";
				$bindvars = array_merge($sts,$fieldIdArray,array((string)$prefs['language']));
			}else {
				$bindvars = array_merge($sts,$fieldIdArray);
			}
			
			$query = "select ttif.`itemId` , ttif.`value` FROM `tiki_tracker_items` tti,`tiki_tracker_item_fields` ttif ";
			$query.= " WHERE  $mid and  tti.`itemId` = ttif.`itemId` order by ".$this->convert_sortmode($sort_mode);
			$result = $this->query($query,$bindvars);
			$ret = array();
			while ($res = $result->fetchRow()) {
				$k = $res['itemId'];
				$ret[$k] = $res['value'];
			}
			$cachelib->cacheItem($cache,serialize($ret));
			return $ret;
		} else {
			return unserialize($cachelib->getCached($cache));
		}
	}
	
	function get_all_tracker_items($trackerId){
	     $query="select distinct(`itemId`) from `tiki_tracker_items` where`trackerId`=?";
	     $result=$result = $this->query($query,array((int)$trackerId));
	     while ($res = $result->fetchRow()) {
				$ret=$res['itemId'];
	     }
	     return $ret;
	}

	function getSqlStatus($status, &$mid, &$bindvars, $trackerId) {
		global $tiki_p_view_trackers_pending,$tiki_p_view_trackers_closed, $user;
		if (is_array($status)) {
			$status = implode('', $status);
		}

		// Check perms
		if ( $status && ! $this->user_has_perm_on_object($user, $trackerId, 'tracker', 'tiki_p_view_trackers_pending') ) {
			$status = str_replace('p', '', $status);
		}
		if ( $status && ! $this->user_has_perm_on_object($user, $trackerId, 'tracker', 'tiki_p_view_trackers_closed') ) {
			$status = str_replace('c', '', $status);
		}

		if (!$status) {
			return false;
		} elseif (strlen($status) > 1) {
			$sts = preg_split('//', $status, -1, PREG_SPLIT_NO_EMPTY);
			if (count($sts)) {
				$mid.= " and (".implode('=? or ',array_fill(0,count($sts),'`status`'))."=?) ";
				$bindvars = array_merge($bindvars,$sts);
			}
		} else {
			$mid.= " and tti.`status`=? ";
			$bindvars[] = $status;
		}
		return true;
	}
	
	/* to filter filterfield is an array of fieldIds
	 * and the value of each field is either filtervalue or exactvalue
	 * ex: filterfield=array('1','2', '3'), filtervalue=array(array('this', '*that'), ''), exactvalue('', array('there', 'those'), 'these')
	 * will filter items with fielId 1 with a value %this% or %that, and fieldId with the value there or those, and fieldId 3 with a value these
	 * listfields = array(fieldId=>array('type'=>, 'name'=>...), ...)
	 */
	function list_items($trackerId, $offset=0, $maxRecords=-1, $sort_mode ='' , $listfields='', $filterfield = '', $filtervalue = '', $status = '', $initial = '', $exactvalue = '', $filter='') {
		global $tiki_p_view_trackers_pending, $tiki_p_view_trackers_closed, $tiki_p_admin_trackers, $prefs;

		$cat_table = '';
		$sort_tables = '';
		$sort_join_clauses = '';
		$csort_mode = '';
		$corder = '';
		$trackerId = (int)$trackerId;
		$numsort = false;

		$mid = ' WHERE tti.`trackerId` = ? ';
		$bindvars = array($trackerId);

		if (!empty($filter)) {
			$this->parse_filter($filter, $mid, $bindvars);
		}

		if ( $status && ! $this->getSqlStatus($status, $mid, $bindvars, $trackerId) ) {
			return array('cant' => 0, 'data' => '');
		}
		if ( substr($sort_mode, 0, 2) == 'f_' ) {
			list($a, $asort_mode, $corder) = split('_', $sort_mode);
		}
		if ( $initial ) {
			$mid .= ' AND ttif.`value` LIKE ?';
			$bindvars[] = $initial.'%';
			if (isset($asort_mode)) {
				$mid .= ' AND ttif.`fieldId` = ?';
				$bindvars[] = $asort_mode;
			}
		}
		if ( ! $sort_mode ) $sort_mode = 'lastModif_desc';

		if ( substr($sort_mode, 0, 2) == 'f_' or $filtervalue or $exactvalue ) {
			$cat_table = '';
			if ( substr($sort_mode, 0, 2) == 'f_' ) {
				$csort_mode = 'sttif.`value` ';
				if (isset($listfields[$asort_mode]['type']) && $listfields[$asort_mode]['type'] == 'l') {// item list
					$optsl = split(',', $listfields[$asort_mode]['options']);
					$optsl[1] = split(':', $optsl[1]);
					$sort_tables = $this->get_left_join_sql(array_merge(array($optsl[2]), $optsl[1], array($optsl[3])));
				} else {
					$sort_tables = ' LEFT JOIN (`tiki_tracker_item_fields` sttif)'
						.' ON (tti.`itemId` = sttif.`itemId`'
						." AND sttif.`fieldId` = $asort_mode"
						.')';
				}
				// Do we need a numerical sort on the field ?
				$field = $this->get_tracker_field($asort_mode);
				switch ($field['type']) {
				  case 'q':
					case 'n': $numsort = true; 
				}
			} else {
				list($csort_mode, $corder) = split('_', $sort_mode);
				$csort_mode = 'tti.`'.$csort_mode.'` ';
			}

			if (empty($filterfield)) {
				$nb_filtered_fields = 0;
			} elseif ( ! is_array($filterfield) ) {
				$fv = $filtervalue;
				$ev = $exactvalue;
				$ff = $filterfield;
				$nb_filtered_fields = 1;
			} else {
				$nb_filtered_fields = count($filterfield);
			}

			for ( $i = 0 ; $i < $nb_filtered_fields ; $i++ ) {
				if ( is_array($filterfield) ) { //multiple filter on an exact value or a like value - each value can be simple or an array
					$ff = $filterfield[$i];
					$ev = $exactvalue[$i];
					$fv = $filtervalue[$i];
				}

				$filter = $this->get_tracker_field($ff);

				$j = ( $i > 0 ) ? '0' : '';
				$cat_table .= " INNER JOIN `tiki_tracker_item_fields` ttif$i ON (ttif$i.`itemId` = ttif$j.`itemId`)";
				
				if ( $ff ) {
					$mid .= " AND ttif$i.`fieldId`=? ";
					$bindvars[] = $ff;
				}
					
				if ( $filter['type'] == 'e' && $prefs['feature_categories'] == 'y' ) { //category
		
					$cat_table .= " INNER JOIN `tiki_objects` tob$ff ON (tob$ff.`itemId` = tti.`itemId`)"
						." INNER JOIN `tiki_category_objects` tco$ff ON (tob$ff.`objectId` = tco$ff.`catObjectId`)";
					$mid .= " AND tob$ff.`type` = 'tracker $trackerId' AND tco$ff.`categId` IN ( 0 ";
					$value = empty($fv) ? $ev : $fv;
					if ( ! is_array($value) && $value != '' )
						$value = array($value);
					foreach ( $value as $catId ) {
						$bindvars[] = $catId;
						$mid .= ',?';
					}
					$mid .= " ) ";
		
				} elseif ($ev) {
					if (is_array($ev)) {
						$mid .= " AND ttif$i.`value` in (".implode(',', array_fill(0,count($ev),'?')).")";
						$bindvars = array_merge($bindvars, $ev);
					} else {
						$mid.= " AND ttif$i.`value`=? ";
						$bindvars[] = $ev;
					}
		
				} elseif ( $fv ) {
					if (!is_array($fv)) {
						$value = array($fv);
					}
					$mid .= ' AND(';
					$cpt = 0;
					foreach ($value as $v) {
						if ($cpt++)
							$mid .= ' OR ';
						$mid .= " ttif$i.`value` like ? ";
						if ( substr($v, 0, 1) == '*' || substr($v, 0, 1) == '%') {
							$bindvars[] = '%'.substr($v, 1);
						} elseif ( substr($v, -1, 1) == '*' || substr($v, -1, 1) == '%') {
							$bindvars[] = substr($v, 0, strlen($v)-1).'%';
						} else {
							$bindvars[] = '%'.$v.'%';
						}
					}
					$mid .= ')';
				}
			}
		} else {
			list($csort_mode, $corder) = split('_', $sort_mode);
			if ($csort_mode == 'itemId')
				$csort_mode = 'tti.`itemId`';
			$sort_tables = '';
			$cat_tables = '';
		}

		$base_tables = '('
			.' `tiki_tracker_items` tti'
			.' INNER JOIN `tiki_tracker_item_fields` ttif ON tti.`itemId` = ttif.`itemId`'
			.' INNER JOIN `tiki_tracker_fields` ttf ON ttf.`fieldId` = ttif.`fieldId`'
			.')';

		$query = 'SELECT tti.*, ttif.`value`, ttf.`type`'
				.', '.( ($numsort) ? "right(lpad($csort_mode,40,'0'),40)" : $csort_mode).' as `sortvalue`'
			.' FROM '.$base_tables.$sort_tables.$cat_table
			.$mid
			.' GROUP BY tti.`itemId`'
			.' ORDER BY '.$this->convert_sortmode('sortvalue_'.$corder);
		$query_cant = 'SELECT count(DISTINCT ttif.`itemId`) FROM '.$base_tables.$sort_tables.$cat_table.$mid;

		$result = $this->query($query, $bindvars, $maxRecords, $offset);
		$cant = $this->getOne($query_cant, $bindvars);
		$type = '';
		$ret = array();

		while ( $res = $result->fetchRow() ) {
			$res['itemUser'] = '';
			$res['field_values'] = $this->get_item_fields($trackerId, $res['itemId'], $listfields, $res['itemUser']);
			if (!empty($asort_mode)) {
				foreach ($res['field_values'] as $i=>$field)
					if ($field['fieldId'] == $asort_mode ) {
						$kx = $field['value'].'.'.$res['itemId'];
				}
			}
			if (empty($kx)) // ex: if the sort field is non visible, $kx is null
				$ret[] = $res;
			else
				$ret[$kx] = $res;
		}

		$retval = array();
		$retval['data'] = array_values($ret);
		$retval['cant'] = $cant;
		return $retval;
	}
	function get_item_fields($trackerId, $itemId, $listfields, &$itemUser) {
		global $prefs, $user;
		$fields = array();
		$fil = array();
		$kx = '';

		$bindvars = array((string)$prefs['language'], (int)$itemId);

		$query2 = 'SELECT ttf.`fieldId`, `value`, `isPublic`'
			.' FROM `tiki_tracker_item_fields` ttif INNER JOIN `tiki_tracker_fields` ttf ON ttif.`fieldId` = ttf.`fieldId`'
			." WHERE (`lang` = ? or `lang` is null or `lang` = '') AND `itemId` = ?"
			.' ORDER BY `position` ASC, `lang` DESC';
		$result2 = $this->query($query2, $bindvars);

		while ( $res1 = $result2->fetchRow() ) {
			$fil[$res1['fieldId']] = $res1['value'];
		}

		foreach ( $listfields as $fieldId =>$fopt ) { // be possible to need the userItem before this field
			if ($fopt['type'] == 'u' && $fopt['options_array'][0] == 1) {
				$itemUser = isset($fil[$fieldId]) ? $fil[$fieldId] : '';
			}
		}

		foreach ( $listfields as $fieldId =>$fopt ) {
			if (empty($fopt['fieldId'])) // to accept listfield as a simple table
				$fopt['fieldId'] = $fieldId;
			$fieldId = $fopt['fieldId'];
			$fopt['value'] = ( isset($fil[$fieldId]) ) ? $fil[$fieldId] : '';
			$fopt['linkId'] = '';
			if (!empty($fopt['options'])) {
				$fopt['options_array'] = split(',', $fopt['options']);
			}
			if ($fopt['isHidden'] == 'c' && empty($itemUser)) { // need itemUser
				$itemUser = $this->get_item_creator($trackerId, $itemId);
			}
			switch ( $fopt['type'] ) {
			case 'r':
				$fopt['links'] = array();
				$opts = split(',', $fopt['options']);
				$fopt['linkId'] = $this->get_item_id($opts[0], $opts[1], $fopt['value']);
				$fopt['trackerId'] = $opts[0];
				break;
			case 'a':
				$fopt['pvalue'] = $this->parse_data(trim($fopt['value']));
				break;
			case 'C':
				$calc = preg_replace('/#([0-9]+)/', '$fil[\1]', $fopt['options']);
				eval('$computed = '.$calc.';');
				$fopt['value'] = $computed;
				break;
			case 's':
				$key = 'tracker.'.$trackerId.'.'.$itemId;
				$fopt['numvotes'] = $this->getOne('select count(*) from `tiki_user_votings` where `id` = ?', array($key));
				$fopt['voteavg'] = ( $fopt['numvotes'] > 0 ) ? ($fopt['value'] / $fopt['numvotes']) : '0';
				break;
			case 'e':
				global $categlib;
				include_once('lib/categories/categlib.php');
				$mycats = $categlib->get_child_categories($fopt['options']);
				if (empty($zcatItemId) || $zcatItemId != $itemId) {
					$zcatItemId = $itemId;
					$zcats = $categlib->get_object_categories('tracker '.$trackerId, $itemId);
				}
				$cats = array();
				foreach ( $mycats as $m ) {
					if ( in_array($m['categId'], $zcats) ) {
						$cats[] = $m;
					}
				}
				$fopt['categs'] = $cats;
				break;
			case 'l':
				if ( isset($fopt['options_array'][2]) && isset($fil[$fopt['options_array'][2]]) && ($lst = $fil[$fopt['options_array'][2]]) && isset($fopt['options_array'][3])) {
					$opts[1] = split(':', $fopt['options_array'][1]);
					$finalFields = explode('|', $fopt['options_array'][3]);
					$fopt['links'] = $this->get_join_values($itemId, array_merge(array($fopt['options_array'][2]), array($fopt['options_array'][1]), array($finalFields[0])), $fopt['options_array'][0], $finalFields);
					$fopt['trackerId'] = $fopt['options_array'][0];
				}
				if (isset($fopt['links']) && count($fopt['links']) == 1) { //if a computed field use it
					foreach ($fopt['links'] as $linkItemId=>$linkValue) {
						if (is_numeric($linkValue)) {
							$fil[$fieldId] = $linkValue;
						}
					}
				}
				break;
			case 'u':
				if ($fopt['options_array'][0] == 1) {
					$itemUser = $fopt['value'];
				}
				break;
			case 'p':
				if (empty($itemUser)) {
					$itemUser = $this->get_item_creator($trackerId, $itemId);
				}
				if ($fopt['options_array'][0] == 'password') {
				} elseif ($fopt['options_array'][0] == 'email' && !empty($itemUser)) {
					global $userlib;
					$fopt['value'] = $userlib->get_user_email($itemUser);
				} elseif (!empty($itemUser)) {
					global $userlib;
					$fopt['value'] = $userlib->get_user_preference($itemUser, $fopt['options_array'][0]);
				} 
				break;
			case 'A':
				if (!empty($fopt['options_array'][0])) {
					$fopt['info'] = $this->get_item_attachment($fopt['value']);
				}
				break;
			default:
				break;
			}
			
			if ( isset($fopt['options']) ) {
				if ( $fopt['type'] == 'i' ) {
					global $imagegallib;
					include_once('lib/imagegals/imagegallib.php');
					if ( $imagegallib->readimagefromfile($fopt['value']) ) {
						$imagegallib->getimageinfo();
						if ( ! isset($fopt['options_array'][1]) ) $fopt['options_array'][1] = 0;
						$t = $imagegallib->ratio($imagegallib->xsize, $imagegallib->ysize, $fopt['options_array'][0], $fopt['options_array'][1] );
						$fopt['options_array'][0] = round($t * $imagegallib->xsize);
						$fopt['options_array'][1] = round($t * $imagegallib->ysize);
						if ( isset($fopt['options_array'][2]) ) {
							if ( ! isset($fopt['options_array'][3]) ) $fopt['options_array'][3] = 0;
							$t = $imagegallib->ratio($imagegallib->xsize, $imagegallib->ysize, $fopt['options_array'][2], $fopt['options_array'][3] );
							$fopt['options_array'][2] = round($t * $imagegallib->xsize);
							$fopt['options_array'][3] = round($t * $imagegallib->ysize);
						}
					}
				} elseif ( $fopt['type'] == 'r' && isset($fopt['options_array'][3]) ) {
					$fopt['displayedvalue'] = $this->concat_item_from_fieldslist(
						$fopt['options_array'][0],
						$this->get_item_id($fopt['options_array'][0], $fopt['options_array'][1], $fopt['value']),
						$fopt['options_array'][3]
					);
					$fopt = $this->set_default_dropdown_option($fopt);
				} elseif ( $fopt['type'] == 'd' || $fopt['type'] == 'D' ) {
					if ( $prefs['feature_multilingual'] == 'y' ) {
						foreach ( $fopt['options_array'] as $key => $l ) {
							$fopt['options_array'][$key] = tra($l);
						}
					}
					$fopt = $this->set_default_dropdown_option($fopt);
				}
			}
			$fields[] = $fopt;
		}
		return($fields);
	}

	function replace_item($trackerId, $itemId, $ins_fields, $status = '', $ins_categs = array(), $bulk_import = false) {
		global $user, $smarty, $notificationlib, $prefs, $cachelib, $categlib, $tiki_p_admin_trackers, $userlib, $tikilib;
		include_once('lib/categories/categlib.php');
		include_once('lib/notifications/notificationlib.php');

		if (!empty($itemId)) {
			$oldStatus = $this->getOne("select `status` from `tiki_tracker_items` where `itemId`=?", array($itemId));
			if ($status) {
				$query = "update `tiki_tracker_items` set `status`=?,`lastModif`=? where `itemId`=?";
				$result = $this->query($query,array($status,(int) $this->now,(int) $itemId));
			} else {
				$query = "update `tiki_tracker_items` set `lastModif`=? where `itemId`=?";
				$result = $this->query($query,array((int) $this->now,(int) $itemId));
				$status = $oldStatus;
			}
		} else {
			if (!$status) {
				$status = $this->getOne("select `value` from `tiki_tracker_options` where `trackerId`=? and `name`=?",array((int) $trackerId,'newItemStatus'));
			}
			if (empty($status)) { $status = 'o'; }
			$query = "insert into `tiki_tracker_items`(`trackerId`,`created`,`lastModif`,`status`) values(?,?,?,?)";
			$result = $this->query($query,array((int) $trackerId,(int) $this->now,(int) $this->now,$status));
			$new_itemId = $this->getOne("select max(`itemId`) from `tiki_tracker_items` where `created`=? and `trackerId`=?",array((int) $this->now,(int) $trackerId));
		}

		if ($prefs['feature_categories'] == 'y') {
			$old_categs = $categlib->get_object_categories("tracker $trackerId", $itemId ? $itemId : $new_itemId);

			$new_categs = array_diff($ins_categs, $old_categs);
			$del_categs = array_diff($old_categs, $ins_categs);
			$remain_categs = array_diff($old_categs, $new_categs, $del_categs);
		}
		if (!empty($oldStatus) || !empty($status)) {
			$the_data = tra('Status:').' ';
			$statusTypes = $this->status_types();
			if (isset($oldStatus) && $oldStatus != $status) {
				$the_data .= $statusTypes[$oldStatus]['label'] . ' -> ';
			}
			if (!empty($status)) {
				$the_data .= $statusTypes[$status]['label'] . "\n\n";
			}
		} else {
			$the_data = '';
		}

		foreach($ins_fields["data"] as $i=>$array) {
			if (!isset($ins_fields["data"][$i]["type"]) or $ins_fields["data"][$i]["type"] == 's') {
				// system type, do nothing
			} else if ($ins_fields["data"][$i]["type"] != 'u' && $ins_fields["data"][$i]["type"] != 'g' && $ins_fields["data"][$i]["type"] != 'I' && isset($ins_fields['data'][$i]['isHidden']) && ($ins_fields["data"][$i]["isHidden"] == 'p' or $ins_fields["data"][$i]["isHidden"] == 'y')and $tiki_p_admin_trackers != 'y') {
					// hidden field type require tracker amdin perm
			} elseif (empty($ins_fields["data"][$i]["fieldId"])) {
					// can have been unset for a user field
			} else {
				// -----------------------------
				// save image on disk
				if ( $ins_fields["data"][$i]["type"] == 'i' && isset($ins_fields["data"][$i]['value'])) {
					$itId = $itemId ? $itemId : $new_itemId;
					$old_file = $this->get_item_value($trackerId, $itemId, $ins_fields["data"][$i]['fieldId']);

					if($ins_fields["data"][$i]["value"] == 'blank') {
						if(file_exists($old_file)) {
							unlink($old_file);
						}
						$ins_fields["data"][$i]["value"] = '';
					} else if( $ins_fields["data"][$i]['value'] != '' && $this->check_image_type( $ins_fields["data"][$i]['file_type'] ) ) {
						$opts = split(',', $ins_fields['data'][$i]["options"]);
						if (!empty($opts[4])) {
							global $imagegallib;include_once('lib/imagegals/imagegallib.php');
							$imagegallib->image = $ins_fields["data"][$i]['value'];
							$imagegallib->readimagefromstring();
							$imagegallib->getimageinfo();
							if ($imagegallib->xsize > $opts[4] || $imagegallib->xsize > $opts[4]) {
								$imagegallib->rescaleImage($opts[4], $opts[4]);
								$ins_fields["data"][$i]['value'] = $imagegallib->image;
							}
						}
						if ($ins_fields["data"][$i]['file_size'] <= $this->imgMaxSize) {

							$file_name = $this->get_image_filename(	$ins_fields["data"][$i]['file_name'],
																	$itemId,
																	$ins_fields["data"][$i]['fieldId']);

							$fw = fopen( $file_name, "wb");
							fwrite($fw, $ins_fields["data"][$i]['value']);
							fflush($fw);
							fclose($fw);
							chmod($file_name, 0644); // seems necessary on some system (see move_uploaded_file doc on php.net

							$ins_fields['data'][$i]['value'] = $file_name;

							if(file_exists($old_file) && $old_file != $file_name) {
								unlink($old_file);
							}
						}
					}
					else {
						continue;
					}
				} elseif ($ins_fields['data'][$i]['type'] == 'A') { //attachment
					global $tiki_p_attach_trackers;
					if ($tiki_p_attach_trackers == 'y' && !empty($ins_fields['data'][$i]['file_name'])) {
						if ($prefs['t_use_db'] == 'n') {
							$fhash = md5($ins_fields['data'][$i]['file_name'].$this->now);
							if (!$fw = fopen($prefs['t_use_dir'] . $fhash, 'wb')) {
								$smarty->assign('msg', tra('Cannot write to this file:'). $fhash);
								$smarty->display("error.tpl");
								die;
							}
							fwrite($fw, $ins_fields['data'][$i]['value']);
							fclose($fw);
						} else {
							$fhash = 0;
						}
						$ins_fields['data'][$i]['value'] = $this->replace_item_attachment($ins_fields['data'][$i]['old_value'], $ins_fields['data'][$i]['file_name'], $ins_fields['data'][$i]['file_type'], $ins_fields['data'][$i]['file_size'], $ins_fields['data'][$i]['value'], '', $user, $fhash, '', '', $trackerId, $itemId ? $itemId : $new_itemId, '', false);
					} else {
						continue;
					}
				}

			// Handle truncated fields. Only for textareas which have option 3 set
			if ( $ins_fields["data"][$i]["type"] == 'a' && isset($ins_fields["data"][$i]["options_array"][3]) && ($ins_fields["data"][$i]['options_array'][3]) ) {
				if (function_exists('mb_substr')) { // then mb_strlen() also exists
					if ( mb_strlen($ins_fields["data"][$i]['value']) > $ins_fields["data"][$i]['options_array'][3] ) {
						$ins_fields['data'][$i]['value'] = mb_substr($ins_fields["data"][$i]['value'],0,$ins_fields["data"][$i]['options_array'][3])." (...)";
					}
				} else {
					if ( strlen($ins_fields["data"][$i]['value']) > $ins_fields["data"][$i]['options_array'][3] ) {
						$ins_fields['data'][$i]['value'] = substr($ins_fields["data"][$i]['value'],0,$ins_fields["data"][$i]['options_array'][3])." (...)";
					}
				}
			}

			// Normalize on/y on a checkbox
			if ($ins_fields["data"][$i]["type"] == 'c' && $ins_fields['data'][$i]['value'] == 'on') {
				$ins_fields['data'][$i]['value'] = 'y';
			}

			if ( $ins_fields["data"][$i]["type"] == 'M' && $ins_fields["data"][$i]["options_array"][0] >= '3' && isset($ins_fields["data"][$i]['value'])) {
					$itId = $itemId ? $itemId : $new_itemId;
					$old_file = $this->get_item_value($trackerId, $itemId, $ins_fields["data"][$i]['fieldId']);
					if($ins_fields["data"][$i]["value"] == 'blank') {
						if(file_exists($old_file)) {
							unlink($old_file);
						}
						$ins_fields["data"][$i]["value"] = '';
					} else if( $ins_fields["data"][$i]['value'] != '' ) {
						$opts = split(',', $ins_fields['data'][$i]["options"]);
 						global $filegallib; 
						if(  $ins_fields["data"][$i]["options_array"][0] == '3' ||  $ins_fields["data"][$i]["options_array"][0] == '5' ) {// flv
						$Mytype="video/x-flv";
						}
						else {	//MP3
						$Mytype="audio/x-mp3";
						}	
						$fileGalId=$filegallib->insert_file($prefs['MultimediaGalerie'],$ins_fields["data"][$i]["file_name"] ,$ins_fields["data"][$i]["file_name"] , $ins_fields["data"][$i]["file_name"] ,$ins_fields["data"][$i]["value"] ,$ins_fields["data"][$i]["file_size"] ,$Mytype , $user,"" , '', "system", time(), $lockedby=NULL) ; 
						$ins_fields["data"][$i]['value']=$fileGalId;

 						if (!$fileGalId) {
							$errors[] = tra('Upload was not successful. Duplicate file content ?'). ': ' . $name;
// 						if (($prefs['fgal_use_db'] == 'n') || ($podCastGallery)) {
// 							@unlink($savedir . $fhash);
// 						}
// 						if ( $prefs['URLAppend'] == "" ) { }
						$ins_fields["data"][$i]["value"]="$fileGalId";
					
					}
					     }
					
					}
				
				// ---------------------------
                if (isset($ins_fields["data"][$i]["fieldId"]))
				   $fieldId = $ins_fields["data"][$i]["fieldId"];
				if (isset($ins_fields["data"][$i]["name"])) {
					$name = $ins_fields["data"][$i]["name"];
				} else {
					$name = $this->getOne("select `name` from `tiki_tracker_fields` where `fieldId`=?",array((int)$fieldId));
				}
				$value = @ $ins_fields["data"][$i]["value"];

				if (isset($ins_fields["data"][$i]["type"]) and $ins_fields["data"][$i]["type"] == 'q') {
					if (isset($ins_fields["data"][$i]['options_array'][3]) && $ins_fields["data"][$i]['options_array'][3] == 'itemId') {
						$value = $itemId?$itemId: $new_itemId;
					} elseif ($itemId == false) {
						$value = $this->getOne("select max(cast(value as UNSIGNED)) from `tiki_tracker_item_fields` where `fieldId`=?",array((int)$fieldId));
						if ($value == NULL) {
							$value = isset($ins_fields["data"][$i]['options_array'][0]) ? $ins_fields["data"][$i]['options_array'][0] : 1;
						} else {
							$value += 1;
						}
					}
				}

				if ($ins_fields["data"][$i]["type"] == 'e' && $prefs['feature_categories'] == 'y') {
				// category type

					$my_categs = $categlib->get_child_categories($ins_fields['data'][$i]["options"]);
					$aux = array();
					foreach ($my_categs as $cat) {
						$aux[] = $cat['categId'];
					}
					$my_categs = $aux;

					$my_new_categs = array_intersect($new_categs, $my_categs);
					$my_del_categs = array_intersect($del_categs, $my_categs);
					$my_remain_categs = array_intersect($remain_categs, $my_categs);

					if (sizeof($my_new_categs) + sizeof($my_del_categs) == 0) {
							$the_data .= "$name ".tra('(unchanged)') . ":\n";
					} else {
							$the_data .= "$name :\n";
					}

					if (sizeof($my_new_categs) > 0) {
							$the_data .= "  " . tra("Added:") . "\n";
							$the_data .= $this->_describe_category_list($my_new_categs);
					}
					if (sizeof($my_del_categs) > 0) {
							$the_data .= "  " . tra("Removed:") . "\n";
							$the_data .= $this->_describe_category_list($my_del_categs);
					}
					if (sizeof($my_remain_categs) > 0) {
							$the_data .= "  " . tra("Remaining:") . "\n";
							$the_data .= $this->_describe_category_list($my_remain_categs);
					}
					$the_data .= "\n";

					if ($itemId) {
						$query = "select `itemId` from `tiki_tracker_item_fields` where `itemId`=? and `fieldId`=?";
						if ($this->getOne($query,array((int) $itemId, (int) $fieldId))) {
							$query = "update `tiki_tracker_item_fields` set `value`=? where `itemId`=? and `fieldId`=?";
							$this->query($query,array('',(int) $itemId,(int) $fieldId));
						} else {
							$query = "insert into `tiki_tracker_item_fields`(`itemId`,`fieldId`,`value`) values(?,?,?)";
							$this->query($query,array((int) $itemId,(int) $fieldId,''));
						}
					} else {
						$query = "insert into `tiki_tracker_item_fields`(`itemId`,`fieldId`,`value`) values(?,?,?)";
						$this->query($query,array((int) $new_itemId,(int) $fieldId,''));
					}
				} elseif ((isset($ins_fields['data'][$i]['isMultilingual']) && $ins_fields['data'][$i]['isMultilingual'] == 'y') && ($ins_fields['data'][$i]['type'] =='a' || $ins_fields['data'][$i]['type']=='t')){
				 
                                if (!isset($multi_languages))
									$multi_languages=$prefs['available_languages'];
                                if (empty($ins_fields["data"][$i]['lingualvalue'])) {
									$ins_fields["data"][$i]['lingualvalue'][] = array('lang'=>$prefs['language'], 'value'=>$ins_fields["data"][$i]['value']);
                                }
                              
                
				  foreach ($ins_fields["data"][$i]['lingualvalue'] as $linvalue) 
	                                if ($itemId) {
	                                        $result = $this->query('select `value` from `tiki_tracker_item_fields` where `itemId`=? and `fieldId`=? and `lang`=?',array((int) $itemId,(int) $fieldId,(string)$linvalue['lang']));
						if ($row = $result->fetchRow()){
                                                        $query = "update `tiki_tracker_item_fields` set `value`=? where `itemId`=? and `fieldId`=? and `lang`=?";
                                                        $result=$this->query($query,array($linvalue['value'],(int) $itemId,(int) $fieldId,(string)$linvalue['lang']));
                                                        }else{
                                                            $query = "insert into `tiki_tracker_item_fields`(`itemId`,`fieldId`,`value`,`lang`) values(?,?,?,?)";
                                                            $result=$this->query($query,array((int) $itemId,(int) $fieldId,(string)$linvalue['value'],(string)$linvalue['lang']));
                                                        }
                                                        } else {
                                                        //echo "error in this insert";
                                                                $query = "insert into `tiki_tracker_item_fields`(`itemId`,`fieldId`,`value`,`lang`) values(?,?,?,?)";
                                                                $this->query($query,array((int) $new_itemId,(int) $fieldId,(string)$linvalue['value'],(string)$linvalue['lang']));
                                                        }
				} elseif ($ins_fields['data'][$i]['type']=='p') {
					if ($ins_fields['data'][$i]['options_array'][0] == 'password') {
						if (!empty($ins_fields['data'][$i]['value']) && $prefs['change_password'] == 'y' && ($e = $userlib->check_password_policy($ins_fields['data'][$i]['value'])) == '') {
							$userlib->change_user_password($user, $ins_fields['data'][$i]['value']);
						}
					} elseif ($ins_fields['data'][$i]['options_array'][0] == 'email') {
						if (!empty($ins_fields['data'][$i]['value']) && validate_email($ins_fields['data'][$i]['value'])) {
							$userlib->change_user_email($user, $ins_fields['data'][$i]['value']);
						}
						
					} else {
						$tikilib->set_user_preference($user, $ins_fields['data'][$i]['options_array'][0], $ins_fields['data'][$i]['value']);
					}
				} else {

					$is_date = (isset($ins_fields["data"][$i]["type"]) and ($ins_fields["data"][$i]["type"] == 'f' or $ins_fields["data"][$i]["type"] == 'j'));

					$is_visible = !isset($ins_fields["data"][$i]["isHidden"]) || $ins_fields["data"][$i]["isHidden"] == 'n';

					if ($itemId) {
						$result = $this->query('select `value` from `tiki_tracker_item_fields` where `itemId`=? and `fieldId`=?',array((int) $itemId,(int) $fieldId));
						if ($row = $result->fetchRow()) {
							if ($is_visible) {
								$old_value = $row['value'];
								if ($is_date) {
									$dformat = $prefs['short_date_format'].' '.$prefs['short_time_format'];
									$old_value = $this->date_format($dformat, (int)$old_value);
									$new_value = $this->date_format($dformat, (int)$value);
								} else {
									$new_value = $value;
								}
								if ($old_value != $new_value) {
									$the_data .= "$name" . ":\n ".tra("Old:")." $old_value\n ".tra("New:")." $new_value\n\n";
								} else {
									$the_data .= "$name ".tra('(unchanged)') . ":\n $new_value\n\n";
								}
							}

							$query = "update `tiki_tracker_item_fields` set `value`=? where `itemId`=? and `fieldId`=?";
							$this->query($query,array($value,(int) $itemId,(int) $fieldId));
						} else {
							if ($is_visible) {
								if ($is_date) {
									$dformat = $prefs['short_date_format'].' '.$prefs['short_time_format'];
									$new_value = $this->date_format($dformat, (int)$value);
								} else {
									$new_value = $value;
								}
								$the_data .= "$name".":\n   $new_value\n\n";
							}
							$query = "insert into `tiki_tracker_item_fields`(`itemId`,`fieldId`,`value`) values(?,?,?)";
							$this->query($query,array((int) $itemId,(int) $fieldId,(string)$value));
						}
					} else {
						if ($is_visible) {
							if ($is_date) {
								$new_value = $this->date_format("%a, %e %b %Y %H:%M:%S %O",(int)$value);
							} else {
								$new_value = $value;
							}
							$the_data .= "$name".":\n   $new_value\n\n";
						}

						$query = "insert into `tiki_tracker_item_fields`(`itemId`,`fieldId`,`value`) values(?,?,?)";
						$this->query($query,array((int) $new_itemId,(int) $fieldId,(string)$value));
					}
					$cachelib->invalidate(md5('trackerfield'.$fieldId.'o'));
					$cachelib->invalidate(md5('trackerfield'.$fieldId.'c'));
					$cachelib->invalidate(md5('trackerfield'.$fieldId.'p'));
					$cachelib->invalidate(md5('trackerfield'.$fieldId.'op'));
					$cachelib->invalidate(md5('trackerfield'.$fieldId.'oc'));
					$cachelib->invalidate(md5('trackerfield'.$fieldId.'pc'));
					$cachelib->invalidate(md5('trackerfield'.$fieldId.'opc'));
				}
			}
		}

		// Don't send a notification if this operation is part of a bulk import
		if(!$bulk_import) {
			$options = $this->get_tracker_options( $trackerId );
			$watchers = $this->get_notification_emails($trackerId, $itemId, $options, $new_itemId, $status, isset($oldStatus)?$oldStatus: '');

			if (count($watchers) > 0) {
				if( array_key_exists( "simpleEmail", $options ) ) {
					$simpleEmail = $options["simpleEmail"];
				} else {
					$simpleEmail = "n";
				}
				$trackerName = $this->getOne("select `name` from `tiki_trackers` where `trackerId`=?",array((int) $trackerId));
				if (!isset($_SERVER["SERVER_NAME"])) {
					$_SERVER["SERVER_NAME"] = $_SERVER["HTTP_HOST"];
				}
				include_once('lib/webmail/tikimaillib.php');
				if( $simpleEmail == "n" ) {
					$smarty->assign('mail_date', $this->now);
					$smarty->assign('mail_user', $user);
					if ($itemId) {
						$smarty->assign('mail_itemId', $itemId);
					} else {
						$smarty->assign('mail_itemId', $new_itemId);
					}
					$smarty->assign('mail_trackerId', $trackerId);
					$smarty->assign('mail_trackerName', $trackerName);
					$smarty->assign('server_name', $_SERVER['SERVER_NAME']);
					$foo = parse_url($_SERVER["REQUEST_URI"]);
					$machine = $this->httpPrefix(). $foo["path"];
					$smarty->assign('mail_machine', $machine);
					$parts = explode('/', $foo['path']);
					if (count($parts) > 1)
						unset ($parts[count($parts) - 1]);
					$smarty->assign('mail_machine_raw', $this->httpPrefix(). implode('/', $parts));
					$smarty->assign_by_ref('status', $status);
					foreach ($watchers as $watcher) {
						if ($itemId) {
							$mail_action = "\r\n".tra('Item Modification', $watcher['language'])."\r\n\r\n";
							$mail_action.= tra('Tracker', $watcher['language']).":\n   ".$trackerName."\r\n";
							$mail_action.= tra('Item', $watcher['language']).":\n   ".$itemId;
						} else {
							$mail_action = "\r\n".tra('Item creation', $watcher['language'])."\r\n\r\n";
							$mail_action.= tra('Tracker', $watcher['language']).': '.$trackerName;
						}
						$smarty->assign('mail_action', $mail_action);
						$smarty->assign('mail_data', $the_data);
						if (isset($watcher['action']))
							$smarty->assign('mail_action', $watcher['action']);


						$mail_data = $smarty->fetchLang($watcher['language'], 'mail/tracker_changed_notification.tpl');

						$mail = new TikiMail($watcher['user']);
						$mail->setSubject($smarty->fetchLang($watcher['language'], 'mail/tracker_changed_notification_subject.tpl'));
						$mail->setText($mail_data);
						$mail->setHeader("From", $prefs['sender_email']);
						$mail->send(array($watcher['email']));
					}
				} else {
			    		// Use simple email

			    		global $userlib;

						if (!empty($user)) {
							$my_sender = $userlib->get_user_email($user);
						} else { // look if a email field exists
							$fieldId = $this->get_field_id_from_type($trackerId, 'm');
							if (!empty($fieldId))
								$my_sender = $this->get_item_value($trackerId, (!empty($itemId)? $itemId:$new_itemId), $fieldId);
						}


			    		// Try to find a Subject in $the_data
			    		$subject_test = preg_match( '/^Subject:\n   .*$/m', $the_data, $matches );

			    		if( $subject_test == 1 ) {
							$subject = preg_replace( '/^Subject:\n   /m', '', $matches[0] );
							// Remove the subject from $the_data
							$the_data = preg_replace( '/^Subject:\n   .*$/m', '', $the_data );
			    		}

			    		$the_data = preg_replace( '/^.+:\n   /m', '', $the_data );

						foreach ($watchers as $watcher) {
							$mail = new TikiMail($watcher['user']);
							$mail->setSubject('['.$trackerName.'] '.tra('Tracker was modified at ', $watcher['language']). $_SERVER["SERVER_NAME"]);
							$mail->setText($the_data);

							if( ! empty( $my_sender ) ) {
								$mail->setHeader("From", $my_sender);
							}
							$mail->send(array($watcher['email']));
						}
				}
			}
		}

		$cant_items = $this->getOne("select count(*) from `tiki_tracker_items` where `trackerId`=?",array((int) $trackerId));
		$query = "update `tiki_trackers` set `items`=?,`lastModif`=?  where `trackerId`=?";
		$result = $this->query($query,array((int)$cant_items,(int) $this->now,(int) $trackerId));

		if (!$itemId) $itemId = $new_itemId;

		global $cachelib;
		require_once('lib/cache/cachelib.php');
		$cachelib->invalidate('trackerItemLabel'.$itemId);
		
		$options = $this->get_tracker_options($trackerId);
		
		if ( isset($options) && isset($options['autoCreateCategories']) && $options['autoCreateCategories'] == 'y' && $prefs['feature_categories'] == 'y' ) {
			$trackerName = $this->getOne("select `name` from `tiki_trackers` where `trackerId`=?", array((int) $trackerId));
			$trackerDescription = $this->getOne("select `description` from `tiki_trackers` where `trackerId`=?", array((int) $trackerId));
			$tracker_item_desc = $this->get_isMain_value($trackerId, $itemId);

			// Verify that parentCat exists Or Create It
			$parentcategId = $categlib->get_category_id("Tracker $trackerId");
			if ( ! isset($parentcategId) ) {
				$parentcategId = $categlib->add_category(0,"Tracker $trackerId",$trackerDescription);
			}
			// Verify that the sub Categ doesn't already exists
			$currentCategId = $categlib->get_category_id("Tracker Item $itemId");
			if ( ! isset($currentCategId) || $currentCategId == 0 ) {
				$currentCategId = $categlib->add_category($parentcategId,"Tracker Item $itemId",$tracker_item_desc);
			} else {
				$categlib->update_category($currentCategId, "Tracker Item $itemId", $tracker_item_desc, $parentcategId);
			}
			$cat_type = "tracker $trackerId";
			$cat_objid = $itemId;
			$cat_desc = '';
			$cat_name = "Tracker Item $itemId";
			$cat_href = "tiki-view_tracker_item.php?trackerId=$trackerId&itemId=$itemId";
			// ?? HAS to do it ?? $categlib->uncategorize_object($cat_type, $cat_objid);
			$catObjectId = $categlib->is_categorized($cat_type, $cat_objid);
			if ( ! $catObjectId ) {
				$catObjectId = $categlib->add_categorized_object($cat_type, $cat_objid, $cat_desc, $cat_name, $cat_href);
			}
			$categlib->categorize($catObjectId, $currentCategId);
		}

		if ( $prefs['feature_search'] == 'y' && $prefs['feature_search_fulltext'] != 'y' && $prefs['search_refresh_index_mode'] == 'normal' ) {
			require_once('lib/search/refresh-functions.php');
			refresh_index('tracker_items', $itemId);
		}

		return $itemId;
	}

	function _format_data($field, $data) {
		$data = trim($data);
		if($field['type'] == 'a') {
			if(isset($field["options_array"][3]) and $field["options_array"][3] > 0 and strlen($data) > $field["options_array"][3]) {
				$data = substr($data,0,$field["options_array"][3])." (...)";
			}
		} elseif ($field['type'] == 'c') {
			if($data != 'y') $data = 'n';
		}
		return $data;
	}

	/* Experimental feature.
	 * PHP's execution time limit of 30 seconds may have to be extended when
	 * importing large files ( > 1000 items).
	 */
	function import_items($trackerId, $indexField, $csvHandle, $csvDelimiter = "," , $replace = true) {

		// Read the first line.  It contains the names of the fields to import
		if (($data = fgetcsv($csvHandle, 4096, $csvDelimiter)) === FALSE) return -1;
		$nColumns = count($data);
		for ($i = 0; $i < $nColumns; $i++) {
			$data[$i] = trim($data[$i]);
		}
		$fields = $this->list_tracker_fields($trackerId, 0, -1, 'position_asc', '');
		$temp_max = count($fields["data"]);
		$indexId = -1;
		for ($i = 0; $i < $temp_max; $i++) {
			$column[$i] = -1;
			for ($j = 0; $j < $nColumns; $j++) {
				if($fields["data"][$i]['name'] == $data[$j]) {
					$column[$i] = $j;
				}
				if($indexField == $data[$j]) {
					$indexId = $j;
				}
			}
		}

		// If a primary key was specified, check that it was found among the columns of the file
		if($indexField && $indexId == -1) return -1;

		$total = 0;
		while (($data = fgetcsv($csvHandle, 4096, $csvDelimiter)) !== FALSE) {
			$status = array_shift($data);
			$itemId = array_shift($data);
			for ($i = 0; $i < $temp_max-2; $i++) {
				if (isset($data[$i])) {
					$fields["data"][$i]['value'] = $data[$i];
				} else {
					$fields["data"][$i]['value'] = "";
				}
			}
			$this->replace_item($trackerId, $itemId, $fields, $status, array(), true);
			$total++;
		}

		// TODO: Send a notification indicating that an import has been done on this tracker

		return $total;
	}

	function import_csv($trackerId, $csvHandle, $replace = true, $dateFormat='', $encoding='UTF8', $csvDelimiter=',') {
		global $tikilib;
		$tracker_info = $this->get_tracker_options($trackerId);
		if (($header = fgetcsv($csvHandle,100000,  $csvDelimiter)) === FALSE) {
			return 'Illegal first line';
		}
		$max = count($header);
		for ($i = 0; $i < $max; $i++) {
			if ($encoding == 'ISO-8859-1') {
				$header[$i] = utf8_encode($header[$i]);
			}
			$header[$i] = preg_replace('/ -- [0-9]*$/', '', $header[$i]);
		}
		if (count($header) != count(array_unique($header))) {
			return 'Duplicate header names';
		}
		$total = 0;
		$need_reindex = array();
		$fields = $this->list_tracker_fields($trackerId, 0, -1, 'position_asc', '');
		while (($data = fgetcsv($csvHandle,100000,  $csvDelimiter)) !== FALSE) {
			$status = $tracker_info['defaultStatus'];
			$itemId = 0;
			$created = $tikilib->now;
			$lastModif = $created;
			$cats = '';
			for ($i = 0; $i < $max; $i++) {
				if ($encoding == 'ISO-8859-1') {
					$data[$i] = utf8_encode($data[$i]);
				}
				if ($header[$i] == 'status') {
					if ($data[$i] == 'o' || $data[$i] =='p' || $data[$i] == 'c')
						$status = $data[$i];
				} elseif ($header[$i] == 'itemId') {
					$itemId = $data[$i];
				} elseif ($header[$i] == 'created' && is_numeric($data[$i])) {
					$created = $data[$i];
				} elseif ($header[$i] == 'lastModif' && is_numeric($data[$i])) {
					$lastModif = $data[$i];
				} elseif ($header[$i] == 'categs') { // for old compatibility
					$cats = split(',',trim($data[$i]));
				}
			}
			if ($itemId && ($t = $this->get_tracker_for_item($itemId)) && $t == $trackerId) {
				$query = "update `tiki_tracker_items` set `created`=?, `lastModif`=?, `status`=? where `itemId`=?";
				$this->query($query, array((int)$created, (int)$lastModif, $status, (int)$itemId));
				$replace = true;
			} elseif ($itemId && !$t) {
				$query = "insert into `tiki_tracker_items`(`trackerId`,`created`,`lastModif`,`status`, `itemId`) values(?,?,?,?,?)";
				$this->query($query,array((int)$trackerId, (int)$created,(int)$lastModif, $status, (int)$itemId));
				$replace = false;
			} else {
				$query = "insert into `tiki_tracker_items`(`trackerId`,`created`,`lastModif`,`status`) values(?,?,?,?)";
				$this->query($query,array((int)$trackerId, (int)$created,(int)$lastModif, $status));
				$query = "select max(`itemId`) from `tiki_tracker_items` where `trackerId`=? and `created`=? and `lastModif`=? and `status`=?";
				$itemId = $this->getOne($query, array((int)$trackerId, (int)$created,(int)$lastModif, $status));
				$replace = false;
			}
			$need_reindex[] = $itemId;
			if (!empty($cats)) {
				foreach ($cats as $c) {
					$this->categorized_item($trackerId, $itemId, "item $itemId", $cats);
				}
			}
			$query = "insert into `tiki_tracker_item_fields`(`itemId`,`fieldId`,`value`) values(?,?,?)";
			$query2 = "update `tiki_tracker_item_fields` set `value`=? where `itemId`=? and `fieldId`=?";
			for ($i = 0; $i < $max; ++$i) {
				foreach ($fields['data'] as $field) {
					if ($field['name'] == $header[$i]) {
						if ($field['type'] == 'e' or $field['type'] == 's') {
							$data[$i] = '';
						} elseif ($field['type'] == 'a') {
							$data[$i] = preg_replace('/\%\%\%/',"\r\n",$data[$i]);
						} elseif ($field['type'] == 'c') {
							if (strtolower($data[$i]) == 'yes' || strtolower($data[$i]) == 'on')
								$data[$i] = 'y';
							elseif (strtolower($data[$i]) == 'no')
								$data[$i] = 'n';
						} elseif ($field['type'] == 'f' || $field['type'] == 'j') {
							if ($dateFormat == 'mm/dd/yyyy') {
								list($m, $d, $y) = split('/', $data[$i]);
								$data[$i] = $tikilib->make_time(0, 0, 0, $m, $d, $y);
							} elseif ($dateFormat == 'dd/mm/yyyy') {
								list($d, $m, $y) = split('/', $data[$i]);
								$data[$i] = $tikilib->make_time(0, 0, 0, $m, $d, $y);
							}
						}
						if ($itemId && $replace && $this->get_item_value($trackerId, $itemId, $field['fieldId']) !== false) {
							$this->query($query2, array($data[$i], (int)$itemId,(int)$field['fieldId']));
						} else {
							$this->query($query, array((int)$itemId,(int)$field['fieldId'], $data[$i]));
						}							
						break;
					}
				}
			}
			$total++;
		}

		if ( $prefs['feature_search'] == 'y' && $prefs['feature_search_fulltext'] != 'y' && $prefs['search_refresh_index_mode'] == 'normal' && is_array($need_reindex) ) {
			require_once('lib/search/refresh-functions.php');
			foreach ( $need_reindex as $id ) refresh_index('tracker_items', $id);
			unset($need_reindex);
		}

		return $total;
	}
	
	function _describe_category_list($categs) {
	    global $categlib;
	    $res = '';
	    foreach ($categs as $cid) {
		$info = $categlib->get_category($cid);
		$res .= '    ' . $info['name'] . "\n";
	    }
	    return $res;
	}

	// check the validity of each field values of a tracker item
	// and the presence of mandatory fields
	function check_field_values($ins_fields, $categorized_fields='') {
		global $prefs;
		$mandatory_fields = array();
		$erroneous_values = array();
        if (isset($ins_fields)&&isset($ins_fields['data']))        
		foreach($ins_fields['data'] as $f) {

			if ($f['type'] != 'q' and isset($f['isMandatory']) && $f['isMandatory'] == 'y') {
			
				if (isset($f['type']) &&  $f['type'] == 'e') {
					if (!in_array($f['fieldId'], $categorized_fields))
						$mandatory_fields[] = $f;
				} elseif (isset($f['type']) &&  ($f['type'] == 'a' || $f['type'] == 't') && ($this->is_multilingual($f['fieldId']) == 'y')) {
                                  if (!isset($multi_languages))
                                  $multi_languages=$prefs['available_languages'];
				    //Check recipient
				    if (isset($f['lingualvalue']) ) {
				        foreach ($f['lingualvalue'] as $val)
				        foreach ($multi_languages as $num=>$tmplang)
				            //Check if trad is empty
				            if (!isset($val['lang']) ||!isset($val['value']) ||(($val['lang']==$tmplang) && strlen($val['value'])==0))
				            $mandatory_fields[] = $f;
				      
				    }else 
				    {
				       $mandatory_fields[] = $f;
				    }
				} elseif (isset($f['type']) &&  ($f['type'] == 'u' || $f['type'] == 'g') && $f['options_array'][0] == 1) {
					;
				} elseif (!isset($f['value']) or strlen($f['value']) == 0) {
					$mandatory_fields[] = $f;
				}
			}
			if (!empty($f['value']) && isset($f['type'])) {

				switch ($f['type']) {
				// numeric
				case 'n':
					if(!is_numeric($f['value'])) {
						$f['error'] = tra('Field is not numeric');
						$erroneous_values[] = $f;
					}
					break;

				// email
				case 'm':
					if(!validate_email($f['value'],$prefs['validateEmail'])) {
						$erroneous_values[] = $f;
					}
					break;
				//multimedia
				case 'M':
				if ( empty($f['options_array'][0]) 
					||$f['options_array'][0] == '0' ) {
					//MP3 link file in gallery expected 
					  $file=$prefs['URLAppend'].$f['value'];
					  list($rest1,$idfilegal)=split('=',$file);
					  global $filegallib ; include_once ('lib/filegals/filegallib.php');
					  $info = $filegallib->get_file_info($idfilegal);
					  $filetype = $info['filetype'];
					  if ( $filetype != "audio/x-mp3" && $filetype != "audio/mpeg" ) {
					$f['error'] = tra('Field is not a link to mp3 in the gallery');
					$erroneous_values[] = $f; 				
					  }
					}
				elseif ($f['options_array'][0] == '1' ) {
					// FLV link in gallery expected 
					  $file=$prefs['URLAppend'].$f['value'] ;
					  list($rest1,$idfilegal)=split('=',$file);
					  global $filegallib ;include_once ('lib/filegals/filegallib.php');
					  $info = $filegallib->get_file_info($idfilegal);
					  $filetype = $info['filetype'];
					  if ( $filetype != "video/x-flv" ) {
				   	   $f['error'] = tra('Field is not a link to FLV in the gallery');
					   $erroneous_values[] = $f; 				
					  }
					}
				elseif ($f['options_array'][0] == '2' ) {
					// FLV or MP3 link in gallery expected 
					  $file=$prefs['URLAppend'].$f['value'] ;
					  list($rest1,$idfilegal)=split('=',$file);
					  global $filegallib ;include_once ('lib/filegals/filegallib.php');
					  $info = $filegallib->get_file_info($idfilegal);
					  $filetype = $info['filetype'];
					  if ( $filetype != "video/x-flv" && $filetype != "audio/x-mp3" && $filetype != "audio/mpeg" ) {
				   	   $f['error'] = tra('Field is not a link to FLV or MP3 in the gallery');
					   $erroneous_values[] = $f; 				
					  }
					}
				break;
				case 'p':
				if ($f['options_array'][0] == 'password') {
					global $userlib;
					if (($e = $userlib->check_password_policy($f['value'])) != '') {
						 $erroneous_values[] = $f;
					}
				} elseif ($f['options_array'][0] == 'email') {
					if (!validate_email($f['value'])) {
						$erroneous_values[] = $f;
					}
				}
				break;
				case 'a':
					if (isset($f['options_array'][5]) &&  $f['options_array'][5] > 0) {
						if (count(preg_split('/\s+/', $f['value'])) > $f['options_array'][5]) {
							$erroneous_values[] = $f;
						}
					}
				}
			}
		}

		$res = array();
		$res['err_mandatory'] = $mandatory_fields;
		$res['err_value'] = $erroneous_values;
		return $res;
	}

	function remove_tracker_item($itemId) {
		global $user;
		$query = "select * from `tiki_tracker_items` where `itemId`=?";
		$result = $this->query($query, array((int) $itemId));
		$res = $result->fetchRow();
		$trackerId = $res['trackerId'];
		$status = $res['status'];

		// ---- save image list before sql query ---------------------------------
		$fieldList = $this->list_tracker_fields($trackerId, 0, -1, 'name_asc', '');
		$imgList = array();
		foreach($fieldList['data'] as $f) {
			if( $f['type'] == 'i' ) {
				$imgList[] = $this->get_item_value($trackerId, $itemId, $f['fieldId']);
			}
		}
		$watchers = $this->get_notification_emails($trackerId, $itemId, $this->get_tracker_options( $trackerId));
		if (count($watchers > 0)) {
			global $smarty;
			$trackerName = $this->getOne("select `name` from `tiki_trackers` where `trackerId`=?",array((int) $trackerId));
			$smarty->assign('mail_date', $this->now);
			$smarty->assign('mail_user', $user);
			$smarty->assign('mail_action', 'deleted');
			$smarty->assign('mail_itemId', $itemId);
			$smarty->assign('mail_trackerId', $trackerId);
			$smarty->assign('mail_trackerName', $trackerName);
			$foo = parse_url($_SERVER["REQUEST_URI"]);
			$machine = $this->httpPrefix(). $foo["path"];
			$smarty->assign('mail_machine', $machine);
			$parts = explode('/', $foo['path']);
			if (count($parts) > 1)
				unset ($parts[count($parts) - 1]);
			$smarty->assign('mail_machine_raw', $this->httpPrefix(). implode('/', $parts));
			if (!isset($_SERVER["SERVER_NAME"])) {
				$_SERVER["SERVER_NAME"] = $_SERVER["HTTP_HOST"];
			}
			include_once ('lib/webmail/tikimaillib.php');
			$smarty->assign('server_name', $_SERVER['SERVER_NAME']);
			foreach ($watchers as $w) {
				$mail = new TikiMail($w['user']);
				$mail->setHeader("From", $prefs['sender_email']);
				$mail->setSubject($smarty->fetchLang($w['lang'], 'mail/tracker_changed_notification_subject.tpl'));
				$mail->setText($smarty->fetchLang($w['lang'], 'mail/tracker_changed_notification.tpl'));
				$mail->send(array($w['email']));
			}
		}

		$query = "update `tiki_trackers` set `lastModif`=? where `trackerId`=?";
		$result = $this->query($query,array((int) $this->now,(int) $trackerId));
		$query = "update `tiki_trackers` set `items`=`items`-1 where `trackerId`=?";
		$result = $this->query($query,array((int) $trackerId));
		$query = "delete from `tiki_tracker_item_fields` where `itemId`=?";
		$result = $this->query($query,array((int) $itemId));
		$query = "delete from `tiki_tracker_items` where `itemId`=?";
		$result = $this->query($query,array((int) $itemId));
		$query = "delete from `tiki_tracker_item_comments` where `itemId`=?";
		$result = $this->query($query,array((int) $itemId));
		$query = "delete from `tiki_tracker_item_attachments` where `itemId`=?";
		$result = $this->query($query,array((int) $itemId));

		// ---- delete image from disk -------------------------------------
		foreach($imgList as $img) {
			if( file_exists($img) ) {
				unlink( $img );
			}
		}

		global $cachelib;
		require_once('lib/cache/cachelib.php');
		$cachelib->invalidate('trackerItemLabel'.$itemId);
		foreach($fieldList['data'] as $f) {
			$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].$status));
			$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].'opc'));
			$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].'opc'));
			if ($status == 'o') {
				$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].'op'));
				$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].'oc'));
			} elseif ($status == 'c') {
				$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].'oc'));
				$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].'pc'));
			} elseif ($status == 'p') {
				$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].'op'));
				$cachelib->invalidate(md5('trackerfield'.$f['fieldId'].'pc'));
			}
		}
		
		$options=$this->get_tracker_options($trackerId);
		if (isset ($option) && isset($option['autoCreateCategories']) && $option['autoCreateCategories']=='y') {
		
		$currentCategId=$categlib->get_category_id("Tracker Item $itemId");
		$categlib->remove_category($currentCategId);
		}
		return true;
	}

	// filter examples: array('fieldId'=>array(1,2,3)) to look for a list of fields
	// array('or'=>array('isSearchable'=>'y', 'isTplVisible'=>'y')) for fields that are visible ou searchable
	// array('not'=>array('isHidden'=>'y')) for fields that are not hidden
	function parse_filter($filter, &$mids, &$bindvars) {
		foreach ($filter as $type=>$val) {
			if ($type == 'or') {
				$midors = array();
				$this->parse_filter($val, $midors, $bindvars);
				$mids[] = '('.implode(' or ', $midors).')';
			} elseif ($type == 'not') {
				$midors = array();
				$this->parse_filter($val, $midors, $bindvars);
				$mids[] = '!('.implode(' and ', $midors).')';
			} elseif (is_array($val)) {
				if (count($val) > 0) {
					if (!strstr($type, '`')) $type = "`$type`";
					$mids[] = "$type in (".implode(",",array_fill(0,count($val),'?')).')';
					$bindvars = array_merge($bindvars, $val);
				}
			} else {
				if (!strstr($type, '`')) $type = "`$type`";
				$mids[] = "$type=?";
				$bindvars[] = $val;
			}
		}
	}

	// Lists all the fields for an existing tracker
	function list_tracker_fields($trackerId, $offset=0, $maxRecords=-1, $sort_mode='position_asc', $find='', $tra_name=true, $filter='') {
		global $prefs;
		if ($find) {
			$findesc = '%' . $find . '%';
			$mid = " where `trackerId`=? and (`name` like ?)";
			$bindvars=array((int) $trackerId,$findesc);
		} else {
			$mid = " where `trackerId`=? ";
			$bindvars=array((int) $trackerId);
		}

		if (!empty($filter)) {
			$mids = array();
			$this->parse_filter($filter, $mids, $bindvars);
			$mid .= 'and '.implode(' and ', $mids);
		}

		$query = "select * from `tiki_tracker_fields` $mid order by ".$this->convert_sortmode($sort_mode);
		$query_cant = "select count(*) from `tiki_tracker_fields` $mid";
		$result = $this->query($query,$bindvars,$maxRecords,$offset);
		$cant = $this->getOne($query_cant,$bindvars);
		$ret = array();

		while ($res = $result->fetchRow()) {
			$res['options_array'] = preg_split('/\s*,\s*/', trim($res['options']));
			$res['itemChoices'] = ( $res['itemChoices'] != '' ) ? unserialize($res['itemChoices']) : array();
			$res['visibleBy'] = ($res['visibleBy'] != '') ? unserialize($res['visibleBy']) : array();
			$res['editableBy'] = ($res['editableBy'] != '') ? unserialize($res['editableBy']) : array();
			if ($tra_name && $prefs['feature_multilingual'] == 'y' && $prefs['language'] != 'en')
				$res['name'] = tra($res['name']);
			if ($res['type'] == 'd' || $res['type'] == 'D' || $res['type'] == 'R') { // drop down
				if ($prefs['feature_multilingual'] == 'y') {
					foreach ($res['options_array'] as $key=>$l) {
						$res['options_array'][$key] = $l;
					}
				}
				$res = $this->set_default_dropdown_option($res);						
			}
			if ($res['type'] == 'l' || $res['type'] == 'r') { // get the last field type
				if (!empty($res['options_array'][3])) {
					if (is_numeric($res['options_array'][3]))
						$fieldId = $res['options_array'][3];
					else
						$fieldId = 0;
				} elseif (is_numeric($res['options_array'][1])) {
					$fieldId = $res['options_array'][1];
				} elseif ($fields = split(':', $res['options_array'][1])) {
					$fieldId = $fields[count($fields) - 1];
				}
				if (!empty($fieldId)) {
					$res['otherField'] = $this->get_tracker_field($fieldId);
				}
			}
			$ret[] = $res;
		}
		$retval = array();
		$retval["data"] = $ret;
		$retval["cant"] = $cant;
		return $retval;
	}

	// Inserts or updates a tracker
	function replace_tracker($trackerId, $name, $description, $options, $descriptionIsParsed) {
		if ($trackerId) {
			$old = $this->getOne('select count(*) from `tiki_trackers` where `trackerId`=?',array((int)$trackerId)); 
			if ($old) {
				$query = "update `tiki_trackers` set `name`=?,`description`=?,`descriptionIsParsed`=?,`lastModif`=? where `trackerId`=?";
				$this->query($query,array($name,$description,$descriptionIsParsed,(int)$this->now,(int) $trackerId));
			} else {
				$query = "insert into `tiki_trackers` (`name`,`description`,`descriptionIsParsed`,`lastModif`,`trackerId`) values (?,?,?,?,?)";
				$this->query($query,array($name,$description,$descriptionIsParsed,(int)$this->now,(int) $trackerId));
			}
		} else {
			$this->getOne("delete from `tiki_trackers` where `name`=?",array($name),false);
			$query = "insert into `tiki_trackers`(`name`,`description`,`descriptionIsParsed`,`created`,`lastModif`) values(?,?,?,?,?)";
			$this->query($query,array($name,$description,$descriptionIsParsed,(int) $this->now,(int) $this->now));
			$trackerId = $this->getOne("select max(`trackerId`) from `tiki_trackers` where `name`=? and `created`=?",array($name,(int) $this->now));
		}
		$this->query("delete from `tiki_tracker_options` where `trackerId`=?",array((int)$trackerId));
		$rating = false;
		foreach ($options as $kopt=>$opt) {
			$this->query("insert into `tiki_tracker_options`(`trackerId`,`name`,`value`) values(?,?,?)",array((int)$trackerId,$kopt,$opt));
			if ($kopt == 'useRatings' and $opt == 'y') {
				$rating = true;
			} elseif ($kopt == 'ratingOptions') {
				$ratingoptions = $opt;
			} elseif ($kopt == 'showRatings') {
				$showratings = $opt;
			}
		}
		$ratingId = $this->get_field_id($trackerId,'Rating');
		if ($rating) {
			if (!$ratingId) $ratingId = 0;
			if (!isset($ratingoptions)) $ratingoptions = '';
			if (!isset($showratings)) $showratings = 'n';
			$this->replace_tracker_field($trackerId,$ratingId,'Rating','s','-','-',$showratings,'y','n','-',0,$ratingoptions);
		} else {
			$this->query('delete from `tiki_tracker_fields` where `fieldId`=?',array((int)$ratingId));
		}
		$this->clear_tracker_cache($trackerId);

		global $prefs;
		if ( $prefs['feature_search'] == 'y' && $prefs['feature_search_fulltext'] != 'y' && $prefs['search_refresh_index_mode'] == 'normal' ) {
			require_once('lib/search/refresh-functions.php');
			refresh_index('trackers', $trackerId);
		}

		return $trackerId;
	}

	function clear_tracker_cache($trackerId) {
		$query = "select `itemId` from `tiki_tracker_items` where `trackerId`=?";
		$result = $this->query($query,array((int)$trackerId));

		global $cachelib;
		require_once('lib/cache/cachelib.php');

		while ($res = $result->fetchRow()) {
		    $cachelib->invalidate('trackerItemLabel'.$res['itemId']);
		}
	}


	function replace_tracker_field($trackerId, $fieldId, $name, $type, $isMain, $isSearchable, $isTblVisible, $isPublic, $isHidden, $isMandatory, $position, $options, $description='',$isMultilingual='', $itemChoices=null, $errorMsg='', $visibleBy=null, $editableBy=null) {
		// Serialize choosed items array (items of the tracker field to be displayed in the list proposed to the user)
		if ( is_array($itemChoices) && count($itemChoices) > 0 && !empty($itemChoices[0]) ) {
			$itemChoices = serialize($itemChoices);
		} else {
			$itemChoices = '';
		}
		if (is_array($visibleBy) && count($visibleBy) > 0 && !empty($visibleBy[0])) {
			$visibleBy = serialize($visibleBy);
		}
		if (is_array($editableBy) && count($editableBy) > 0 && !empty($editableBy[0])) {
			$editableBy = serialize($editableBy);
		}
		  

		if ($fieldId) {
			// -------------------------------------
			// remove images when needed
			$old_field = $this->get_tracker_field($fieldId);
			if ($old_field) {
				if( $old_field['type'] == 'i' && $type != 'i' ) {
					$this->remove_field_images( $fieldId );
				}
				$query = "update `tiki_tracker_fields` set `name`=? ,`type`=?,`isMain`=?,`isSearchable`=?,
					`isTblVisible`=?,`isPublic`=?,`isHidden`=?,`isMandatory`=?,`position`=?,`options`=?,`isMultilingual`=?, `description`=?, `itemChoices`=?, `errorMsg`=?, visibleBy=?, editableBy=? where `fieldId`=?";
				$bindvars=array($name,$type,$isMain,$isSearchable,$isTblVisible,$isPublic,$isHidden,$isMandatory,(int)$position,$options,$isMultilingual,$description, $itemChoices, $errorMsg, $visibleBy, $editableBy, (int) $fieldId);
			} else {
				$query = "insert into `tiki_tracker_fields` (`trackerId`,`name`,`type`,`isMain`,`isSearchable`,
					`isTblVisible`, `isPublic`, `isHidden`, `isMandatory`, `position`, `options`, `fieldId`, `isMultilingual`, `description`, `itemChoices`, `errorMsg`, `visibleBy`, `editableBy`) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
				$bindvars=array((int) $trackerId,$name,$type,$isMain,$isSearchable,$isTblVisible,$isPublic,$isHidden,$isMandatory,(int)$position,$options,(int) $fieldId,$isMultilingual, $description, $itemChoices, $errorMsg, $visibleBy, $editableBy);
			}
			$result = $this->query($query, $bindvars);
		} else {
			$this->getOne("delete from `tiki_tracker_fields` where `trackerId`=? and `name`=?",
				array((int) $trackerId,$name),false);
			$query = "insert into `tiki_tracker_fields`(`trackerId`,`name`,`type`,`isMain`,`isSearchable`,`isTblVisible`,`isPublic`,`isHidden`,`isMandatory`,`position`,`options`,`description`,`isMultilingual`, `itemChoices`, `errorMsg`, `visibleBy`, `editableBy`)
                values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

			$result = $this->query($query,array((int) $trackerId,$name,$type,$isMain,$isSearchable,$isTblVisible,$isPublic,$isHidden,$isMandatory,$position,$options,$description,$isMultilingual, $itemChoices, $errorMsg, $visibleBy, $editableBy));
			$fieldId = $this->getOne("select max(`fieldId`) from `tiki_tracker_fields` where `trackerId`=? and `name`=?",array((int) $trackerId,$name));
			// Now add the field to all the existing items
			$query = "select `itemId` from `tiki_tracker_items` where `trackerId`=?";
			$result = $this->query($query,array((int) $trackerId));

			while ($res = $result->fetchRow()) {
				$itemId = $res['itemId'];
				$this->getOne("delete from `tiki_tracker_item_fields` where `itemId`=? and `fieldId`=?",
					array((int) $itemId,(int) $fieldId),false);

				$query2 = "insert into `tiki_tracker_item_fields`(`itemId`,`fieldId`,`value`) values(?,?,?)";
				$this->query($query2,array((int) $itemId,(int) $fieldId,''));
			}
		}
		$this->clear_tracker_cache($trackerId);
		return $fieldId;
	}

	function replace_rating($trackerId,$itemId,$fieldId,$user,$new_rate) {
		$val = $this->getOne("select `value` from `tiki_tracker_item_fields` where `itemId`=? and `fieldId`=?", array((int)$itemId,(int)$fieldId));
		if ($val === NULL) {
			$query = "insert into `tiki_tracker_item_fields`(`value`,`itemId`,`fieldId`) values (?,?,?)";
			$newval = $new_rate;
			//echo "$newval";die;
		} else {
			$query = "update `tiki_tracker_item_fields` set `value`=? where `itemId`=? and `fieldId`=?";
			$olrate = $this->get_user_vote("tracker.$trackerId.$itemId",$user);
			if ($olrate === NULL) $olrate = 0;
			if ($new_rate === NULL) {
				$newval = $val - $olrate;
			} else {
				$newval = $val - $olrate + $new_rate;
			}
			//echo "$val - $olrate + $new_rate = $newval";die;
		}
		$this->query($query,array((int)$newval,(int)$itemId,(int)$fieldId));
		$this->register_user_vote($user, "tracker.$trackerId.$itemId", $new_rate);
		return $newval;
	}

	function remove_tracker($trackerId) {

		// ---- delete image from disk -------------------------------------
		$fieldList = $this->list_tracker_fields($trackerId, 0, -1, 'name_asc', '');
		foreach($fieldList['data'] as $f) {
			if( $f['type'] == 'i' ) {
				$this->remove_field_images($f['fieldId']);
			}
		}

		$bindvars=array((int) $trackerId);
		$query = "delete from `tiki_trackers` where `trackerId`=?";

		$result = $this->query($query,$bindvars);
		// Remove the fields
		$query = "delete from `tiki_tracker_fields` where `trackerId`=?";
		$result = $this->query($query,$bindvars);
		// Remove the items (Remove fields for each item for this tracker)
		$query = "select `itemId` from `tiki_tracker_items` where `trackerId`=?";
		$result = $this->query($query,$bindvars);

		while ($res = $result->fetchRow()) {
			$query2 = "delete from `tiki_tracker_item_fields` where `itemId`=?";
			$result2 = $this->query($query2,array((int) $res["itemId"]));
			$query2 = "delete from `tiki_tracker_item_comments` where `itemId`=?";
			$result2 = $this->query($query2,array((int) $res["itemId"]));
			$query2 = "delete from `tiki_tracker_item_attachments` where `itemId`=?";
			$result2 = $this->query($query2,array((int) $res["itemId"]));
		}

		$query = "delete from `tiki_tracker_items` where `trackerId`=?";
		$result = $this->query($query,$bindvars);

		$query = "delete from `tiki_tracker_options` where `trackerId`=?";
		$result = $this->query($query,$bindvars);

		$this->remove_object('tracker', $trackerId);

		$this->clear_tracker_cache($trackerId);
                
                $options=$this->get_tracker_options($trackerId);
		if (isset ($option) && isset($option['autoCreateCategories']) && $option['autoCreateCategories']=='y') {
		
		$currentCategId=$categlib->get_category_id("Tracker $trackerId");
		$categlib->remove_category($currentCategId);
		}
		return true;
	}

	function remove_tracker_field($fieldId,$trackerId) {
		global $cachelib;

		// -------------------------------------
		// remove images when needed
		$field = $this->get_tracker_field($fieldId);
		if( $field['type'] == 'i' ) {
			$this->remove_field_images($fieldId);
		}

		$query = "delete from `tiki_tracker_fields` where `fieldId`=?";
		$bindvars=array((int) $fieldId);
		$result = $this->query($query,$bindvars);
		$query = "delete from `tiki_tracker_item_fields` where `fieldId`=?";
		$result = $this->query($query,$bindvars);
		$cachelib->invalidate(md5('trackerfield'.$fieldId.'o'));
		$cachelib->invalidate(md5('trackerfield'.$fieldId.'p'));
		$cachelib->invalidate(md5('trackerfield'.$fieldId.'c'));
		$cachelib->invalidate(md5('trackerfield'.$fieldId.'op'));
		$cachelib->invalidate(md5('trackerfield'.$fieldId.'oc'));
		$cachelib->invalidate(md5('trackerfield'.$fieldId.'pc'));
		$cachelib->invalidate(md5('trackerfield'.$fieldId.'opc'));
		$cachelib->invalidate(md5('trackerfield'.$fieldId.'poc'));

		$this->clear_tracker_cache($trackerId);

		return true;
	}
	
	/**
	 * Returns the trackerId of the tracker possessing the item ($itemId)
	 */
	function get_tracker_for_item($itemId) {
		$query = "select t1.`trackerId` from `tiki_trackers` t1, `tiki_tracker_items` t2 where t1.`trackerId`=t2.`trackerId` and `itemId`=?";		
		return $this->getOne($query,array((int) $itemId));			
	}

	function get_tracker_options($trackerId) {
		$query = "select * from `tiki_tracker_options` where `trackerId`=?";
		$result = $this->query($query,array((int) $trackerId));
		if (!$result->numRows()) return array();
		$res = array();
		while ($opt = $result->fetchRow()) {
			$res["{$opt['name']}"] = $opt['value'];
		}
		return $res;
	}

	function get_tracker_field($fieldId) {
		$query = "select * from `tiki_tracker_fields` where `fieldId`=?";
		$result = $this->query($query,array((int) $fieldId));
		if (!$result->numRows())
			return false;
		$res = $result->fetchRow();
		$res['options_array'] = split(',', $res['options']);
		$res['itemChoices'] = ( $res['itemChoices'] != '' ) ? unserialize($res['itemChoices']) : array();
		$res['visibleBy'] = ($res['visibleBy'] != '') ? unserialize($res['visibleBy']) : array();
		$res['editableBy'] = ($res['editableBy'] != '') ? unserialize($res['editableBy']) : array();
		return $res;
	}

	function get_field_id($trackerId,$name) {
		return $this->getOne("select `fieldId` from `tiki_tracker_fields` where `trackerId`=? and `name`=?",array((int)$trackerId,$name));
	}

	function get_field_id_from_type($trackerId, $type, $option=NULL) {
		$mid = ' `trackerId`=? and `type`=? ';
		$bindvars = array((int)$trackerId, $type);
		if (!empty($option)) {
			if (strstr($option, '%') === false) {
				$mid .= ' and `options`=? ';
			} else {
				$mid .= ' and `options` like ? ';
			}
			$bindvars[] = $option;
		}
		return $this->getOne("select `fieldId` from `tiki_tracker_fields` where $mid",$bindvars);
	}

/*
** function only used for the popup for more infos on attachements
*  returns an array with field=>value
*/
	function get_moreinfo($attId) {
		$query = "select o.`value`, o.`trackerId` from `tiki_tracker_options` o";
		$query.= " left join `tiki_tracker_items` i on o.`trackerId`=i.`trackerId` ";
		$query.= " left join `tiki_tracker_item_attachments` a on i.`itemId`=a.`itemId` ";
		$query.= " where a.`attId`=? and o.`name`=?";
		$result = $this->query($query,array((int)$attId, 'orderAttachments'));
		$resu = $result->fetchRow();
		if ($resu) {
			$resu['orderAttachments'] = $resu['value'];
		} else {
			$query = "select `orderAttachments`, t.`trackerId` from `tiki_trackers` t ";
			$query.= " left join `tiki_tracker_items` i on t.`trackerId`=i.`trackerId` ";
			$query.= " left join `tiki_tracker_item_attachments` a on i.`itemId`=a.`itemId` ";
			$query.= " where a.`attId`=? ";
			$result = $this->query($query,array((int)$attId));
			$resu = $result->fetchRow();
		}
		if (strstr($resu['orderAttachments'],'|')) {
			$fields = split(',',substr($resu['orderAttachments'],strpos($resu['orderAttachments'],'|')+1));
			$query = "select `".implode("`,`",$fields)."` from `tiki_tracker_item_attachments` where `attId`=?";
			$result = $this->query($query,array((int)$attId));
			$res = $result->fetchRow();
			$res["trackerId"] = $resu['trackerId'];
			$res["longdesc"] = isset($res['longdesc'])?$this->parse_data($res['longdesc']):'';
		} else {
			$res = array(tra("Message") => tra("No extra information for that attached file. "));
			$res['trackerId'] = 0;
		}
		return $res;
	}

	function field_types() {

		global $userlib;
		$tmp = $userlib->list_all_users();
		foreach ( $tmp as $u ) $all_users[$u] = $u;
		$tmp = $userlib->list_all_groups();
		foreach ( $tmp as $u ) $all_groups[$u] = $u;
		unset($tmp);

		// 'label' => represents what shows up in the field type drop-down selector
		// 'opt' => true|false - not sure what this does
		// 'options' => not quite sure what this does either
		// 'help' => help text that appears in the left side of the field type selector
		$type['t'] = array(
			'label'=>tra('text field'),
			'opt'=>true,
			'options'=>array(
				'half'=>array('type'=>'bool','label'=>tra('half column')),
				'size'=>array('type'=>'int','label'=>tra('size')),
				'prepend'=>array('type'=>'str','label'=>tra('prepend')),
				'append'=>array('type'=>'str','label'=>tra('append')),
				'max'=>array('type'=>'int','label'=>tra('max')),
			),
			'help'=>tra('<dl>
				<dt>Function: Allows alhpanumeric text input in a one-line field of arbitrary size.
				<dt>Usage: <strong>[samerow][,size][,prepend][,append][,max]</strong>
				<dt>Description:
				<dd><strong>[samerow]</strong> will display the next field or checkbox in the same row if a 1 is specified; 
				<dd><strong>[size]</strong> is the visible length of the field in characters; 
				<dd><strong>[prepend]</strong> is text that will be displayed before the field; 
				<dd><strong>[append]</strong> is text that will be displayed just after the field; 
				<dd><strong>[max]</strong> is the maximum number of characters that can be saved; 
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['a'] = array(
			'label'=>tra('textarea'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Allows alhpanumeric text input in a multi-line field of arbitrary size.
				<dt>Usage: <strong>[quicktags][,width][,height][,max][,listmax][,wordmax]</strong>
				<dt>Description: 
				<dd><strong>[quicktags]</strong> enables quicktags and may be either a 0 or 1;
				<dd><strong>[width]</strong> is the width of the box, in chars;
				<dd><strong>[height]</strong> is the number of visible lines in the box;
				<dd><strong>[max]</strong> is the maximum number of characters that can be saved;
				<dd><strong>[listmax]</strong> is the maximum number of characters that are displayed in list mode;
				<dd><strong>[wordmax]</strong> will alert if word count exceeded with a positive number (1+) or display a word count with a negative number (-1);
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['c'] = array(
			'label'=>tra('checkbox'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provides a checkbox field for yes/no, on/off input.
				<dt>Usage: <strong>[samerow]</strong>
				<dt>Description: 
				<dd><strong>[samerow]</strong> will display the next field on the same row if a 1 is specified.
				</dl>'));
		$type['n'] = array(
			'label'=>tra('numeric field'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provides a one-line field for numeric input only.  Prepend or append values may be alphanumeric.
				<dt>Usage: <strong>[samerow][,size][,prepend][,append]</strong>
				<dt>Description: 
				<dd><strong>[samerow]</strong> will display the next field or checkbox in the same row if a 1 is specified;
				<dd><strong>[size]</strong> is the visible size of the field in characters;
				<dd><strong>[prepend]</strong> is text that will be displayed before the field;
				<dd><strong>[append]</strong> is text that will be displayed just after the field;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['d'] = array(
			'label'=>tra('drop down'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Allows users to select only from a specified set of options in a drop-down bar.
				<dt>Usage: <strong>[list of items]</strong>
				<dt>Description: 
				<dd><strong>[list of items]</strong> is the list of all values you want in the drop-down, separated by commas;
				<dd>if you wish to specify a default value other than the first item, enter the value twice, consecutively, and it will appear once in the list as the default selection.
				</dl>'));
		$type['D'] = array(
			'label'=>tra('drop down with other textfield'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Allows users to select from a specified set of options in a drop-down bar, or provide an alternate selection in a one-line text field.
				<dt>Usage: <strong>[list of items]</strong>
				<dt>Description: 
				<dd><strong>[list of items]</strong> is the list of all values you want in the drop-down, separated by commas;
				<dd>if you wish to specify a default value other than the first item, enter the value twice, consecutively, and it will appear once in the list as the default selection.
				</dl>'));
		$type['R'] = array(
			'label'=>tra('radio buttons'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provides a multiple-choice-style set of options from which a user may only choose one.
				<dt>Usage: <strong>[list of items]</strong>
				<dt>Description: 
				<dd><strong>[list of items]</strong> is the list of all values you want in the set, separated by commas;
				<dd>if you wish to specify a default value other than the first item, enter the value twice, consecutively, and it will appear as the one selected.
				</dl>'));
		$type['u'] = array(
			'label'=>tra('user selector'),
			'opt'=>true,
			'itemChoicesList' => $all_users,
			'help'=>tra('<dl>
				<dt>Function: Allows a selection from a specified list of usernames that have active accounts.
				<dt>Usage: <strong>[auto-assign][,email_notify]</strong>
				<dt>Description: 
				<dd><strong>[auto-assign]</strong> will auto-assign the creator of the item if set to 1 even if that user is not in the list, or will set the selection to the user who last modified the item if set to 2;
				<dd><strong>[email_notify]</strong> will send an email to the assigned user when the item is saved;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['g'] = array(
			'label'=>tra('group selector'),
			'opt'=>true,
			'itemChoicesList' => $all_groups,
			'help'=>tra('<dl>
				<dt>Function: Allows a selection from a specified list of usergroups.
				<dt>Usage: <strong>[auto-assign]</strong>
				<dt>Description: 
				<dd><strong>[auto-assign]</strong> will auto-assign the field to the usergroup of the creator if set to 1 even if that group is not in the list, or will set the selection to the group of the user who last modified the item if set to 2;
				<dd>if the user does not have a default group set, the first group the user belongs to will be chosen, otherwise Registered group will be used.
				</dl>'));
		$type['I'] = array(
			'label'=>tra('ip selector'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provides a field for entering an IP address.
				<dt>Usage: <strong>[auto-assign][,linkType]</strong>
				<dt>Description: 
				<dd><strong>[auto-assign]</strong> will auto-populate the field with the IP address of the user who created the item if set to 1, or will set the field to the IP of the user who last modified the item if set to 2;
				<dd><strong>[linkType]</strong> will make the IP into a URI and linkable with the specified prefix where 0 is unlinked (default), 1 is http://, 2 is https://, 3 is telnet://, and 4 is ssh://;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['y'] = array(
			'label'=>tra('country selector'),
			'opt'=>true,
			'itemChoicesList' => $this->get_flags(true, true, true),
			'help'=>tra('<dl>
				<dt>Function: Allows a selection from a specified list of countries.
				<dt>Usage: <strong>[name~flag][,sort]</strong>
				<dt>Description: 
				<dd><strong>[name~flag]</strong> default is 0 and will display both the country name and its flag, 1 will display only the country name, while 2 will show only the country flag;
				<dd><strong>[sortorder]</strong> specifies the order the country list should be displayed in, where 0 is the default and sorts according to the translated name, and 1 sorts according to the english name;
				<dd>if the country names are translated and option 1 is selected for the sort order, the countries will still appear translated, but will merely be in english order.
				</dl>'));
		$type['f'] = array(
			'label'=>tra('date and time'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provides drop-down options to accurately select a date and/or time.
				<dt>Usage: <strong>[datetime][,startyear][,endyear][,blankdate]</strong>
				<dt>Description:
				<dd><strong>[datetime]</strong> will only allow a date to be selected if set to "d", and allows a full date and time selection if set to "dt", defaulting to "dt";
				<dd><strong>[startyear]</strong> allows you to specify a custom first year in the date range (eg. 1987), default is current year;
				<dd><strong>[endyear]</strong> allows you to specify a custom end year in the date range (eg. 2020), default is 4 years from now;
				<dd><strong>[blankdate]</strong> when set to "blank" will default the initial date field to an empty date, and allow selection of empty dates;
				<dd>blankdate is overridden if the field isMandatory;
				<dd>multiple options must appear in the order specified, separated by commas.
				<dt>Example: "d,2000,2009,blank"
				<dd>sets a date only field from 2000 through 2009, allowing blank dates.
				</dl>'));
		$type['j'] = array(
			'label'=>tra('jscalendar'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provides a javascript graphical date selector to select a date and/or time.
				<dt>Usage: <strong>[datetime]</strong>
				<dt>Description:
				<dd><strong>[datetime]</strong> will only allow a date to be selected if set to "d", and allows a full date and time selection if set to "dt", defaulting to "dt".
				</dl>'));
		$type['i'] = array(
			'label'=>tra('image'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Allows user to upload an image into the tracker item.
				<dt>Usage: <strong>[xListSize][,yListSize][,xDetailsSize][,yDetailsSize][,uploadLimitScale]</strong>
				<dt>Description:
				<dd><strong>[xListSize]</strong> sets the pixel width of the image in the list view;
				<dd><strong>[yListSize]</strong> sets the pixel height of the image in the list view;
				<dd><strong>[xDetailSize]</strong> sets the pixel width of the image in the item view;
				<dd><strong>[yDetailSize]</strong> sets the pixel height of the image in the item view;
				<dd><strong>[uploadLimitScale]</strong> sets the maximum total size of the image, in pixels (width * height);
				<dd>images are stored in img/trackers;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['x'] = array(
			'label'=>tra('action'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: ?
				<dt>Usage: <strong>[label][,post][,tiki-index.php][,page:fieldname][,highlight=test]</strong>
				<dt>Description:
				<dd><strong>[label]</strong> needs explanation;
				<dd><strong>[post]</strong> needs explanation;
				<dd><strong>[tiki-index.php]</strong> needs explanation;
				<dd><strong>[page:fieldname]</strong> needs explanation;
				<dd><strong>[highlight=test]</strong> needs explanation;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['h'] = array(
			'label'=>tra('header'),
			'opt'=>false);
		$type['S'] = array(
			'label'=>tra('static text'),
			'opt'=>true, 
			'help'=>tra('<dl>
				<dt>Function: Allows insertion of a static block of text into a tracker to augment input fields. (non-editable)
				<dt>Usage: <strong>[wikiparse][,max]</strong>
				<dt>Description:
				<dd><strong>[wikiparse]</strong> will allow wiki syntax to be parsed if set to 1, otherwise default is 0 to only support line-breaks;
				<dd><strong>[max]</strong> specifies the maximum size of the text block in characters;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['e'] = array(
			'label'=>tra('category'), 
			'opt'=>true, 
			'help'=>tra('<dl>
				<dt>Function: Allows one or more Categories under a main category to be assigned to the tracker item.
				<dt>Usage: <strong>[parentId][,inputtype][,selectall]</strong>
				<dt>Description:
				<dd><strong>[parentId]</strong> is the ID of the main category, categories in the list will be children of this;
				<dd><strong>[inputtype]</strong> is one of [d|m|radio|checkbox], where d is a drop-down list, m is a multiple-selection drop-down list, radio and checkbox are self-explanatory;
				<dd><strong>[selectall]</strong> will provide a checkbox to automatically select all categories in the list if set to 1, default is 0;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['r'] = array(
			'label'=>tra('item link'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provides a relational link to another item in a specified tracker.
				<dt>Usage: <strong>[trackerId][,fieldId][,linkToItem][,displayedFieldsList]</strong>
				<dt>Description:
				<dd><strong>[trackerId]</strong> is the tracker ID of the data you want to link to;
				<dd><strong>[fieldId]</strong> is the field from which you can select a value to link to;
				<dd><strong>[linkToItem]</strong> if set to 0 will simply display the value, but if set to 1 will provide a link directly to that values item in the other tracker;
				<dd><strong>[displayedFieldsList]</strong> is a list of fields to display instead of the target item, multiple fields can be separated with a |;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['l'] = array(
			'label'=>tra('items list'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provide a list of items as a result of a relational link between tracker items.
				<dt>Usage: <strong>[trackerId][,fieldIdThere][,fieldIdHere][,displayFieldIdThere][,linkToItems]</strong>
				<dt>Description:
				<dd><strong>[trackerId]</strong> is the tracker ID you want to link with;
				<dd><strong>[fieldIdThere]</strong> is the field (multiple fields can be separated with a ":") you want to link with;
				<dd><strong>[fieldIdHere]</strong> is the field (multiple fields can be separated with a ":") in this tracker you want to link with;
				<dd><strong>[displayFieldIdThere]</strong> the field containing the data that shares the common link with fieldIdThere and fieldIdHere, can be separated by "|";
				<dd><strong>[linkToItems]</strong> if set to 0 will simply display the value, but if set to 1 will provide a link directly to that values item in the other tracker;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['w'] = array(
			'label'=>tra('dynamic items list'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Dynamically updates a selection list based on linked data from another tracker.
				<dt>Usage: <strong>[trackerId][,filterFieldIdThere][,filterFieldIdHere][,listFieldIdThere][,statusThere]</strong>
				<dt>Description:
				<dd><strong>[trackerId]</strong> is the ID of the tracker to link with;
				<dd><strong>[filterFieldIdThere]</strong> is the field you want to link with in that tracker;
				<dd><strong>[filterFieldIdHere]</strong> is the field you want to link with in the current tracker;
				<dd><strong>[listFieldIdThere]</strong> is the field ID you wish to pull the selection list from, based on the value selected in fiterFieldIdHere matching field(s) in filterFieldIdThere;
				<dd><strong>[statusThere]</strong> restricts values appearing in the list to those coming from records in the other tracker that meet specified statuses of [o|p|c] or in combination (op, opc);
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['m'] = array(
			'label'=>tra('email'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Allows users to enter an email address with option of making it active.
				<dt>Usage: <strong>[link][,watchopen][,watchpending][,watchclosed]</strong>
				<dt>Description:
				<dd><strong>[link]</strong> may be one of [0|1|2] and specifies how to display the email address, defaulting to 0 as plain text, 1 as an encoded hex mailto link, or 2 as a standard mailto link;
				<dd><strong>[watchopen]</strong> if set to "o" will email the address every time the status of the item changes to open;
				<dd><strong>[watchpending]</strong> if set to "p" will email the address every time the status of the item changes to pending;
				<dd><strong>[watchclosed]</strong> if set to "c" will email the address every time the status of the item changes to closed;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['M'] = array(
			'label'=>tra('multimedia'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Will play MP3 or FLV files, as specified, in a player.
				<dt>Usage: <strong>[source][,xSize][,ySize]</strong>
				<dt>Description:
				<dd><strong>[source]</strong> is one of [0|1|2|3|4|5] where 0 is default and will support a URL in the file gallery for MP3, 1 for URL in file gallery for FLV, 2 for URL in file gallery of either MP3 or FLV, 3 to upload an MP3 file to be played, 4 to upload an FLV file to be played, 5 to upload either an MP3 or FLV file;
				<dd><strong>[xSize]</strong> is the width in pixels of the player applet (default 200);
				<dd><strong>[ySize]</strong> is the height in pixels of the player applet (default 100);
				<dd>multiple options must appear in the order specified, separated by commas.
				<dd>this is best-used as a tracker item that is not generally editable and you want a specific file played;
					edit the tracker item and provide the URL for the file you wish to play.
				</dl>'));
		$type['q'] = array(
			'label'=>tra('auto-increment'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Allows an incrementing value field, or itemId field. (non-editable)
				<dt>Usage: <strong>[start][,prepend][,append][,itemid]</strong>
				<dt>Description:
				<dd><strong>[start]</strong> is the starting value for the field, defaults to 1;
				<dd><strong>[prepend]</strong> is text that will be displayed before the field;
				<dd><strong>[append]</strong> is text that will be displayed after the field;
				<dd><strong>[itemid]</strong> if set to "itemid" will set this field to match the value of the actual database itemId field value;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['U'] = array(
			'label'=>tra('user subscription'),
			'opt'=>false,
			'help'=>tra('<dl>
				<dt>Function: Allow registered users to subscribe themselves to a tracker item (think evite).
				<dt>Description:
				<dd>Use this field as you would to have people sign up for an event. It is best if the tracker is only editable by its creator or the admin.  To set the max number of subscribers, edit the tracker item and put the number at the beginning of the field.
				<dt>Example: 
				<dd>Old field may have "#" or "#2[0]" in it.  Making it "20#2[0]" will set the max number to 20.
				</dl>'));
		$type['G'] = array(
			'label'=>tra('Google Maps'),
			'opt'=>false,
			'help'=>tra('<dl>
				<dt>Function: Use Google Maps.
				<dt>Usage: <strong>Unknown</strong>
				<dt>Description:
				<dd><strong></strong> needs explanation;
				</dl>'));
		$type['s'] = array(
			'label'=>tra('system'),
			'opt'=>false,
			'help'=>tra('<dl>
				<dt>Function: System only.
				<dt>Usage: None
				<dt>Description:
				<dd>Needs a description.
				</dl>'));
		$type['C'] = array(
			'label'=>tra('computed field'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Provides a computed value based on numeric field values.
				<dt>Usage: <strong>[formula]</strong>
				<dt>Description:
				<dd><strong>[formula]</strong> is the formula you wish to compute, using numeric values, operators "+ - * / ( )", and tracker fields identified with a leading #;
				<dt>Example: "#3*(#4+5)"
				<dd>adds the numeric value in item 4 by 5, and multiplies it by the numeric value in item 3.
				</dl>'));
		$type['p'] = array(
			'label'=>tra('user preference'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Allows user preference changes from a tracker.
				<dt>Usage: <strong>[password][,email]</strong>
				<dt>Description:
				<dd><strong>[password]</strong> needs explanation;
				<dd><strong>[email]</strong> needs explanation;
				<dd>multiple options must appear in the order specified, separated by commas.
				</dl>'));
		$type['A'] = array(
			'label'=>tra('attachment'),
			'opt'=>true,
			'help'=>tra('<dl>
				<dt>Function: Allows a file to be attached to the tracker item.
				<dt>Usage: <strong>[listview]</strong>
				<dt>Description:
				<dd><strong>[listview]</strong> may be one of [n|t|s|u] on their own or in any combination (n, t, ns, nts), allowing you to see the attachment in the item list view as its name (n), its type (t), its name (n), or display the username of the uploader (u);
				note that this option will cost an extra query to the database for each attachment and can severely impact performance with several attachments.
				<dd>
				</dl>'));
		return $type;
	}

	function status_types() {
		$status['o'] = array('label'=>tra('open'),'perm'=>'tiki_p_view_trackers','image'=>'img/icons2/status_open.gif');
		$status['p'] = array('label'=>tra('pending'),'perm'=>'tiki_p_view_trackers_pending','image'=>'img/icons2/status_pending.gif');
		$status['c'] = array('label'=>tra('closed'),'perm'=>'tiki_p_view_trackers_closed','image'=>'img/icons2/status_closed.gif');
		return $status;
	}

	function get_isMain_value($trackerId, $itemId) {
	    global $prefs;

	    $query = "select tif.`value` from `tiki_tracker_item_fields` tif, `tiki_tracker_items` i, `tiki_tracker_fields` tf where i.`itemId`=? and i.`itemId`=tif.`itemId` and tf.`fieldId`=tif.`fieldId` and tf.`isMain`=? and tif.`lang`=? ";
		$result = $this->getOne($query, array( (int)$itemId, "y", $prefs['language']));
		if(isset($result) && $result!='')
		  return $result;
	
		$query = "select tif.`value` from `tiki_tracker_item_fields` tif, `tiki_tracker_items` i, `tiki_tracker_fields` tf where i.`itemId`=? and i.`itemId`=tif.`itemId` and tf.`fieldId`=tif.`fieldId` and tf.`isMain`=?  ";
		$result = $this->getOne($query, array((int)$itemId, "y"));
		return $result;
	}
	function categorized_item($trackerId, $itemId, $mainfield, $ins_categs) {
		global $categlib; include_once('lib/categories/categlib.php');
		$cat_type = "tracker $trackerId";
		$cat_objid = $itemId;
		$cat_desc = '';
		if (empty($mainfield))
				$cat_name = $itemId;
		else
				$cat_name = $mainfield;
		$cat_href = "tiki-view_tracker_item.php?trackerId=$trackerId&itemId=$itemId";
		$categlib->update_object_categories($ins_categs, $cat_objid, $cat_type, $cat_desc, $cat_name, $cat_href);
	}
	function move_up_last_fields($trackerId, $position, $delta=1) {
		$query = 'update `tiki_tracker_fields`set `position`= `position`+ ? where `trackerId` = ? and `position` >= ?';
		$result = $this->query( $query, array((int)$delta, (int)$trackerId, (int)$position) );		
	}
	/* list all the values of a field
	 */
	function list_tracker_field_values($trackerId, $fieldId, $status='o') {
		$mid = '';
		$bindvars[] = (int)$fieldId;
		if (!$this->getSqlStatus($status, $mid, $bindvars, $trackerId))
			return null;
		$sort_mode = "value_asc";
		$query = "select distinct(ttif.`value`) from `tiki_tracker_item_fields` ttif, `tiki_tracker_items` tti where tti.`itemId`= ttif.`itemId`and ttif.`fieldId`=? $mid order by ".$this->convert_sortmode($sort_mode);
		$result = $this->query( $query, $bindvars);
		$ret = array();
		while ($res = $result->fetchRow()) {
			$ret[] = $res['value'];
		}
		return $ret;
	}

	function is_multilingual($fieldId){
	         if ($fieldId<1)
	           return 'n';
	         global $prefs;
	         if ( $prefs['feature_multilingual'] !='y')
	           return 'n';
	         $query = "select `isMultilingual` from `tiki_tracker_fields` where `fieldId`=?";
	         $res=$this->getOne($query,(int)$fieldId);
	         if ($res === NULL || $res=='n' || $res=='')
	           return 'n';
	         else
		   return "y";
	}
	
	/* return the values of $fieldIdOut of an item that has a value $value for $fieldId */
	function get_filtered_item_values($fieldId, $value, $fieldIdOut) {
		$query = "select ttifOut.`value` from `tiki_tracker_item_fields` ttifOut, `tiki_tracker_item_fields` ttif
			where ttifOut.`itemId`= ttif.`itemId`and ttif.`fieldId`=? and ttif.`value`=? and ttifOut.`fieldId`=?";
		$result = $this->query($query, array($fieldId, $value, $fieldIdOut));
		$ret = array();
		while ($res = $result->fetchRow()) {
			$ret[] = $res['value'];
		}
		return $ret;
	}
	/* look if a tracker has only one item per user and if an item has already being created for the user  or the IP*/
	function get_user_item($trackerId, $trackerOptions,$userparam=null) {
		global $user, $IP;
		if (empty($trackerOptions['oneUserItem']) || $trackerOptions['oneUserItem'] != 'y') {
			return 0;
		}

		$userreal=$userparam!=null?$userparam:$user;
		if (!empty($userreal)) {
			if ($fieldId = $this->get_field_id_from_type($trackerId, 'u', '1%')) { // user creator field
				$value = $userreal;
				$items = $this->get_items_list($trackerId, $fieldId, $value, '');
				if ($items)
					return $items[0];
			}
		}
		if ($fieldId = $this->get_field_id_from_type($trackerId, 'I', '1')) { // IP creator field
			$items = $this->get_items_list($trackerId, $fieldId, $IP);
			if ($items)
				return $items[0];
			else
				return 0; 
		}
	}
	function get_item_creator($trackerId, $itemId) {
		if ($fieldId = $this->get_field_id_from_type($trackerId, 'u', '1%')) { // user creator field
			return $this->get_item_value($trackerId, $itemId, $fieldId);
		} else {
			return null;
		}
	}
	/* find the best fieldwhere you can do a filter on the initial
	 * 1) if sort_mode and sort_mode is a text and the field is visible
	 * 2) the first main taht is text
	 */
	function get_initial_field($list_fields, $sort_mode) {
		if (preg_match('/^f_([^_]*)_?.*/', $sort_mode, $matches)) {
			if (isset($list_fields[$matches[1]])) {
				$type = $list_fields[$matches[1]]['type'];
				if ($type == 't' || $type == 'a' || $type == 'm')
					return $matches[1];
			}
		}
		foreach($list_fields as $fieldId=>$field) {
			if ($field['isMain'] == 'y' && ($field['type'] == 't' || $field['type'] == 'a' || $field['type'] == 'm'))
				return $fieldId;
		}
	}
	function get_nb_items($trackerId) {
		return $this->getOne("select count(*) from `tiki_tracker_items` where `trackerId`=?",array((int) $trackerId));
	}
	function duplicate_tracker($trackerId, $name, $description='', $descriptionIsParsed) {
		$tracker_info = $this->get_tracker($trackerId);
		if ($t = $this->get_tracker_options($trackerId))
			$tracker_info = array_merge($tracker_info,$t);
		$newTrackerId = $this->replace_tracker(0, $name, $description, array(), $descriptionIsParsed);
		$query = "select * from `tiki_tracker_options` where `trackerId`=?";
		$result = $this->query($query, array($trackerId));
		while ($res = $result->fetchRow()) {
			$options[$res['name']] = $res['value'];
		}
		$fields = $this->list_tracker_fields($trackerId, 0, -1, 'position_asc', '');
		foreach($fields['data'] as $field) {
			$newFieldId = $this->replace_tracker_field($newTrackerId, 0, $field['name'], $field['type'], $field['isMain'], $field['isSearchable'], $field['isTblVisible'], $field['isPublic'], $field['isHidden'], $field['isMandatory'], $field['position'], $field['options'], $field['description'], $field['isMultilingual'], $field['itemChoices']);
			if ($options['defaultOrderKey'] == $field['fieldId']) {
				$options['defaultOrderKey'] = $newFieldId;
			}
		}
		$query = "insert into `tiki_tracker_options`(`trackerId`,`name`,`value`) values(?,?,?)";
		foreach ($options as $name=>$val) {
			$this->query($query, array($newTrackerId, $name, $val));
		}
		return $newTrackerId;
	}
	// look for default value: a default value is 2 consecutive same value
	function set_default_dropdown_option($field) {
		for ($io = 0; $io < sizeof($field['options_array']); ++$io) {
			if ($io > 0 && $field['options_array'][$io] == $field['options_array'][$io - 1]) {
				$field['defaultvalue'] = $field['options_array'][$io];
				for (; $io < sizeof($field['options_array']) - 1; ++$io) {
					$field['options_array'][$io] = $field['options_array'][$io + 1];
				}
				unset($field['options_array'][$io]);
				break;
			}
		}
		return $field;
	}
	function get_notification_emails($trackerId, $itemId, $options, $newItemId=0, $status='', $oldStatus='') {
		$watchers_global = $this->get_event_watches('tracker_modified',$trackerId);
		$watchers_local = $this->get_local_notifications($itemId, $newItemId, $status, $oldStatus);
		$watchers_item = $itemId? $this->get_event_watches('tracker_item_modified',$itemId, array('trackerId'=>$trackerId)): array();
		$watchers_outbound = array();
		if( array_key_exists( "outboundEmail", $options ) && $options["outboundEmail"] ) {
			$emails3 = split(',', $options['outboundEmail']);
			foreach ($emails3 as $w) {
				global $userlib, $user_preferences;
				$u = $userlib->get_user_by_email($w);
				$this->get_user_preferences($u, array('user', 'language', 'mailCharset'));
				$watchers_outbound[] = array('email'=>$w, 'user'=>$u, 'language'=>$user_preferences[$u]['language'], 'mailCharset'=>$user_preferences[$u]['mailCharset']);
			}
		}
		//echo "<pre>GLOBAL ";print_r($watchers_global);echo 'LOCAL ';print_r($watchers_local); echo 'ITEM ';print_r($watchers_item); echo 'OUTBOUND ';print_r($watchers_outbound);
		$emails = array();
		$watchers = array();
		foreach (array('watchers_global', 'watchers_local', 'watchers_item', 'watchers_outbound') as $ws) {
			if (!empty($$ws)) {
				foreach($$ws as $w) {
					if (!in_array($w['email'], $emails)) {
						$emails[] = $w['email'];
						$watchers[] = $w;
					}
				}
			}
		}
		return $watchers;
	}
	/* sort allFileds function of a list of fields */
	function sort_fields($allFields, $listFields) {
		$tmp = array();
		foreach ($listFields as $fieldId) {
			if (substr($fieldId, 0, 1) == '-') {
				$fieldId = substr($fieldId, 1);
			}
			foreach ($allFields['data'] as $i=>$field) {
				if ($field['fieldId'] == $fieldId && $field['fieldId']) {
					$tmp[] = $field;
					$allFields['data'][$i]['fieldId'] = 0;
					break;
				}
			}
		}
		// do not forget the admin fields like user selector
		foreach ($allFields['data'] as $field) {
			if ($field['fieldId']) {
				$tmp[] = $field;
			}
		}
		$allFields['data'] = $tmp;
		$allFields['cant'] = sizeof($tmp);
		return $allFields;
	}
	/* return all the values+field options  of an item for a type field (ex: return all the user selector value for an item) */
	function get_item_values_by_type($itemId, $typeField) {
		$query = "select ttif.`value`, ttf.`options` from `tiki_tracker_fields` ttf, `tiki_tracker_item_fields` ttif";
		$query .= " where ttif.`itemId`=? and ttf.`type`=? and ttf.`fieldId`=ttif.`fieldId`";
		$result = $this->query($query, array($itemId, $typeField));
		$ret = array();
		while ($res = $result->fetchRow()) {
			$res['options_array'] = split(',', $res['options']);
			$ret[] = $res;
		}
		return $ret;
	}
	/* return all the emails that are locally watching an item */
	function get_local_notifications($itemId, $newItemId=0, $status='', $oldStatus='') {
		global $tikilib, $userlib, $user_preferences, $prefs;
		$emails = array();
		// user field watching item
		$res = $this->get_item_values_by_type($itemId?$itemId:$newItemId, 'u');
		if (is_array($res)) {
			foreach ($res as $f) {
				if (isset($f['options_array'][0]) && ($f['options_array'][0] == 1 || $f['options_array'][0] == 2) && empty($itemId))
					continue;//do not send email on a new item for a creator/modif field
				if (isset($f['options_array'][1]) && $f['options_array'][1] == 1) {
					$tikilib->get_user_preferences($f['value'], array('email', 'user', 'language', 'mailCharset'));
					$emails[] = array('email'=>$userlib->get_user_email($f['value']), 'user'=>$f['value'], 'language'=>$user_preferences[$f['value']]['language'], 'mailCharset'=>$user_preferences[$f['value']]['mailCharset']);
				}
			}
		}
		// email field watching status change
		if ($status != $oldStatus) {
			$res = $this->get_item_values_by_type($itemId?$itemId:$newItemId, 'm');
			if (is_array($res)) {
				foreach ($res as $f) {
					if ((isset($f['options_array'][1]) && $f['options_array'][1] == 'o' && $status == 'o')
						|| (isset($f['options_array'][2]) && $f['options_array'][2] == 'p' && $status == 'p')
						|| (isset($f['options_array'][3]) && $f['options_array'][3] == 'c' && $status == 'c')) {
						$emails[] = array('email'=> $f['value'], 'user'=>'', 'language'=>$prefs['language'], 'mailCharset'=>$prefs['users_prefs_mailCharset'], 'action'=>'status');	
					}
				}
			}
		}
		return $emails;
	}
	function get_join_values($itemId, $fieldIds, $finalTrackerId='', $finalFields='', $separator=' ') {
		global $smarty;
		$select[] = "`tiki_tracker_item_fields` t0";
		$where[] = " t0.`itemId`=?";
		$bindVars[] = $itemId;
		for ($i = 0; $i < count($fieldIds)-1; $i = $i+2) {
			$j = $i + 1;
			$k = $j + 1;
			$select[] = "`tiki_tracker_item_fields` t$j";
			$select[] = "`tiki_tracker_item_fields` t$k";
			$where[] = "t$i.`value`=t$j.`value` and t$i.`fieldId`=? and t$j.`fieldId`=?";
			$bindVars[] = $fieldIds[$i];
			$bindVars[] = $fieldIds[$j];
			$where[] = "t$j.`itemId`=t$k.`itemId` and t$k.`fieldId`=?";
			$bindVars[] = $fieldIds[$k];
		}
		$query = "select t$k.* from ".implode(',',$select).' where '.implode(' and ',$where);
		$result = $this->query($query, $bindVars);
		$ret = array();
		while ($res = $result->fetchRow()) {
			$field_value = $this->get_tracker_field($res['fieldId']);
			$field_value['value'] = $res['value'];
			$smarty->assign('field_value', $field_value);
			$ret[$res['itemId']] = $smarty->fetch('tracker_item_field_value.tpl');
			if (is_array($finalFields) && count($finalFields)) {
				$i = 0;
				foreach ($finalFields as $f) {
					if (!$i++)
						continue;
					$field_value = $this->get_tracker_field($f);
					$ff = $this->get_item_value($finalTrackerId, $res['itemId'], $f);;
					$field_value['value'] = $ff;
					$smarty->assign('field_value', $field_value);
					$ret[$res['itemId']] .= $separator.$smarty->fetch('tracker_item_field_value.tpl');
				}
			}
		}
		return $ret;
	}
	function get_left_join_sql($fieldIds) {
		$sql = '';
		for ($i = 0; $i < count($fieldIds); $i = $i+3) {
			$j = $i + 1;
			$k = $j + 1;
			$tti = $i?"t$i": 'tti';
			$sttif = $k<count($fieldIds)-1?"t$k": 'sttif';
			$sql .= " LEFT JOIN (`tiki_tracker_item_fields` t$i) ON ($tti.`itemId`= t$i.`itemId` and t$i.`fieldId`=".$fieldIds[$i].")";
			$sql .= " LEFT JOIN (`tiki_tracker_item_fields` t$j) ON (t$i.`value`= t$j.`value` and t$j.`fieldId`=".$fieldIds[$j].")";
			$sql .= " LEFT JOIN (`tiki_tracker_item_fields` $sttif) ON (t$j.`itemId`= $sttif.`itemId` and $sttif.`fieldId`=".$fieldIds[$k].")";
		}
		return $sql;
	}
	function get_item_info($itemId) {
		$query = 'select * from `tiki_tracker_items` where `itemId`=?';
		$result = $this->query($query, array((int) $itemId));
		if ($res = $result->fetchRow()) {
			return $res;
		} else {
			return NULL;
		}
	}
}

global $dbTiki, $tikilib, $prefs;

if ( $prefs['trk_with_mirror_tables'] == 'y' ) {
	include_once ("trkWithMirrorTablesLib.php");
	$trklib = new TrkWithMirrorTablesLib($dbTiki);
}
else {
	$trklib = new TrackerLib($dbTiki);
}

?>
