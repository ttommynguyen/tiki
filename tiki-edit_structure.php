<?php

// $Header: /cvsroot/tikiwiki/tiki/tiki-edit_structure.php,v 1.8 2003-11-04 10:03:04 caustin_ats Exp $

// Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
require_once ('tiki-setup.php');

include_once ('lib/structures/structlib.php');

if ($tiki_p_edit_structures != 'y') {
	$smarty->assign('msg', tra("You dont have permission to use this feature"));

	$smarty->display("styles/$style_base/error.tpl");
	die;
}

if (!isset($_REQUEST["structID"])) {
	$smarty->assign('msg', tra("No structure indicated"));

	$smarty->display("styles/$style_base/error.tpl");
	die;
}

if (!isset($_REQUEST["page"])) {
	$_REQUEST["page"] = $structlib->get_first_page($_REQUEST["structID"]);
}
//
$smarty->assign('page', $_REQUEST["page"]);

$smarty->assign('structID', $_REQUEST["structID"]);
$pages = $structlib->get_structure_pages($_REQUEST["structID"],'');
$smarty->assign('pages', $pages);

if (isset($_REQUEST["create"])) {
	if (!isset($_REQUEST["after"]))
		$_REQUEST["after"] = '';

	if (isset($_REQUEST["pageAlias"]))	{
		$structlib->set_page_alias($_REQUEST["structID"], $_REQUEST["page"], $_REQUEST["pageAlias"]);
	}

	if (!(empty($_REQUEST['name']))) {
		$structlib->s_create_page($_REQUEST["page"], $_REQUEST["after"], $_REQUEST["name"], $_REQUEST["structID"], $_REQUEST["pageAlias"]);
		$userlib->copy_object_permissions($_REQUEST["page"],$_REQUEST["name"],'wiki page');

	} 
	elseif(!empty($_REQUEST['name2'])) {
		$after = $_REQUEST['after'];

		foreach ($_REQUEST['name2'] as $name) {
			$structlib->s_create_page($_REQUEST["page"], $after, $name, $_REQUEST["structID"], $_REQUEST["pageAlias"]);

			$after = $name;
		}
	}
}

$smarty->assign('remove', 'n');

if (isset($_REQUEST["remove"])) {
	$smarty->assign('remove', 'y');

	$smarty->assign('removepage', $_REQUEST["remove"]);
}

if (isset($_REQUEST["rremove"])) {
	$structlib->s_remove_page($_REQUEST["rremove"], false);
}

if (isset($_REQUEST["sremove"])) {
	$structlib->s_remove_page($_REQUEST["sremove"], true);
}

$pageAlias = $structlib->get_page_alias($_REQUEST["structID"], $_REQUEST["page"]);
$smarty->assign('pageAlias', $pageAlias);

$subpages = $structlib->get_pages($_REQUEST["structID"], $_REQUEST["page"]);
$max = $structlib->get_max_children($_REQUEST["structID"], $_REQUEST["page"]);
$smarty->assign('subpages', $subpages);
$smarty->assign('max', $max);

if (isset($_REQUEST["find_objects"])) {
	$find_objects = $_REQUEST["find_objects"];
} else {
	$find_objects = '';
}

$smarty->assign('find_objects', $find_objects);

// Get all wiki pages for the dropdown menu
$listpages = $tikilib->list_pages(0, -1, 'pageName_asc', $find_objects);
$smarty->assign_by_ref('listpages', $listpages["data"]);

$html = '';
$subtree = $structlib->get_subtree($_REQUEST["structID"],'', $html);
$smarty->assign('subtree', $subtree);
//print('<pre>'.htmlspecialchars($html).'</pre>');
$smarty->assign('html', $html);

// Display the template
$smarty->assign('mid', 'tiki-edit_structure.tpl');
$smarty->display("styles/$style_base/tiki.tpl");

?>
