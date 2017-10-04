<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
	die('This script may only be included.');
}

if ($svn = $cachelib->getSerialized('svninfo')) {
	$smarty->assign('svnrev', $svn['svnrev']);
	$smarty->assign('lastup', $svn['lastup']);
}

if (is_readable('.svn')) {
	$svn = array();
	if (is_readable('.svn/entries')) {
		$fp = fopen('.svn/entries', 'r');
		for ($i = 0; 10 > $i && $line = fgets($fp, 80); ++$i) {
			$svn[] = $line;
		}
		fclose($fp);
	}

	if (count($svn) > 2) {
		// Standard SVN client
		$smarty->assign('svnrev', $svn[3]);
		$smarty->assign('lastup', strtotime($svn[9]));
	} else {
		// Check for Tortoise 1.7+ SVN client, if sqlite3 is present
		if (extension_loaded('sqlite3')) {
			$location = '.svn/wc.db';
			if (is_file($location)) {
				$handle = new SQLite3($location);

				// Assign svnrev
				$query = "select max(changed_revision) as svnrev from nodes";
				$result = $handle->query($query);
				$svnrev = $lastupTime = $strDT = '';
				if ($result) {
					$resx = $result->fetchArray(SQLITE3_ASSOC);
					$svnrev = $resx['svnrev'];
					$smarty->assign('svnrev', $svnrev);
				}

				// Assign lastup
				$query = "select max(changed_date)/1000000 as lastup from nodes";
				$result = $handle->query($query);
				if ($result) {
					$resx = $result->fetchArray(SQLITE3_ASSOC);
					$lastupTime = intval($resx['lastup']);
					$dt = new DateTime();
					$dt->setTimestamp($lastupTime);
					$strDT = $dt->format(DateTime::ISO8601);
					$smarty->assign('lastup', $strDT);
				}

				// Release/Unlock the database afterwards
				$handle->close();

				$cachelib->cacheItem('svninfo', serialize([
					'svnrev' => $svnrev,
					'lastup' => $strDT
				]));
			}
		}
	}
}


