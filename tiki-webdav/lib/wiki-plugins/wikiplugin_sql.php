<?php
// (c) Copyright 2002-2010 by authors of the Tiki Wiki/CMS/Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_sql_help() {
	return tra("Run a sql query").":<br />~np~{SQL(db=>dsnname)}".tra("sql query")."{SQL}~/np~";
}

function wikiplugin_sql_info() {
	return array(
		'name' => tra('SQL'),
		'documentation' => 'PluginSQL',
		'description' => tra('Run a sql query'),
		'prefs' => array( 'wikiplugin_sql' ),
		'body' => tra('sql query'),
		'validate' => 'all',
		'params' => array(
			'db' => array(
				'required' => true,
				'name' => tra('DNS Name'),
				'description' => tra('ADODB DNS'),
			),
		),
	);
}

function wikiplugin_sql($data, $params) {
	global $tikilib;

	extract ($params,EXTR_SKIP);

	if (!isset($db)) {
		return tra('Missing db param');
	}

	$perms = Perms::get( array( 'type' => 'dsn', 'object' => $db ) );
	if ( ! $perms->dsn_query ) {
		return tra('You do not have permission to use this feature');
	}

	$bindvars = array();
	$data = html_entity_decode($data);
	if ($nb = preg_match_all("/\?/", $data, $out)) {
		foreach($params as $key => $value) {
			if (ereg("^[0-9]*$", $key)) {
				if (strpos($value, "$") === 0) {
					$value = substr($value, 1);
					global $$value;
					$bindvars[$key] = $$value;
				}
				else {
					$bindvars[$key] = $value;
				}
			}
		}
		if (count($bindvars) != $nb) {
			return tra('Missing db param');
		}
	}		

	$ret = '';
	$sql_oke = true;
 	$dbmsg = '';

	if ($db = $tikilib->get_db_by_name( $db ) ) {
		$result = $db->query( $data, $bindvars );
	} else {
		return '~np~' . tra('Could not obtain valid DSN connection.') . '~/np~';
	}

	$first = true;
	$class = 'even';

	while ($result && $res = $result->fetchRow()) {
		if ($first) {
			$ret .= "<div align='center'><table class='sortable'><tr>";

			$first = false;

			foreach (array_keys($res)as $col) {
				$ret .= "<td class='heading'>$col</td>";
			}

			$ret .= "</tr>";
		}

		$ret .= "<tr>";

		if ($class == 'even') {
			$class = 'odd';
		} else {
			$class = 'even';
		}
	
		foreach ($res as $name => $val) {
			$ret .= "<td class='$class'>$val</td>";
		}
		$ret .= "</tr>";
	}

	if ($ret) {
		$ret .= "</table></div>";
	}
	if ($dbmsg) {
		$ret .= $dbmsg;
	}

	return '~np~' . $ret . '~/np~';
} 
