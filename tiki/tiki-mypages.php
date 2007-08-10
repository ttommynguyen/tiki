<?php

// $Header: /cvsroot/tikiwiki/tiki/tiki-mypages.php,v 1.4 2007-08-10 16:14:37 niclone Exp $

// Copyright (c) 2002-2007, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once ('tiki-setup.php');
require_once ('lib/mypage/mypagelib.php');
require_once ('lib/ajax/ajaxlib.php');

if (strlen($user) <= 0) {
	$id_users=0;
} else {
	$id_users=$userlib->get_user_id($user);
}


function mypageedit_populate() {
	global $smarty, $id_users;

	$lpp=25;
	$showpage=isset($_REQUEST['showpage']) ? (int)$_REQUEST['showpage'] : 0;

	$tcount=MyPage::countPages($id_users);
	$pcount=(int)(($tcount-1) / $lpp) + 1;
	$offset=$showpage * $lpp;
	$pages=MyPage::listPages($id_users, $offset, $lpp);

	$pagesnum=array(); for($i=0; $i < $pcount; $i++) $pagesnum[$i]=$i+1;
	$smarty->assign("pagesnum", $pagesnum);
	$smarty->assign("pcount", $pcount);
	$smarty->assign("showpage", $showpage);
	$smarty->assign("mypages", $pages);
}

function mypage_ajax_init() {
	global $ajaxlib;

	$smarty->assign("mootools",'y');
	$smarty->assign("mootab",'y');
	$smarty->assign("mootools_windoo",'y');

	//$ajaxlib->debugOn();
	$ajaxlib->setRequestURI("tiki-mypage_ajax.php");
	$ajaxlib->registerTemplate("tiki-mypages.tpl");
	$ajaxlib->registerFunction("mypage_update");
	$ajaxlib->registerFunction("mypage_create");
	$ajaxlib->registerFunction("mypage_delete");
	$ajaxlib->registerFunction("mypage_fillinfos");
	$ajaxlib->processRequests();
}

function mypageedit_init() {
	global $smarty, $headerlib;

	mypageedit_populate();
	mypage_ajax_init();

	$headerlib->add_cssfile("lib/mootools/extensions/windoo/themes/windoo.css");
	$headerlib->add_cssfile("lib/mootools/extensions/windoo/themes/windoo.aero.css");
	$headerlib->add_cssfile("lib/mootools/extensions/windoo/themes/windoo.alphacube.css");
	$headerlib->add_cssfile("lib/mootools/extensions/windoo/themes/windoo.aqua.css");
	$headerlib->add_cssfile("lib/mootools/extensions/windoo/themes/windoo.nada.css");

	$smarty->assign("mid", "tiki-mypages.tpl");
	$smarty->display("tiki.tpl");
}

mypageedit_init();

/* For the emacs weenies in the crowd.
Local Variables:
   tab-width: 4
   c-basic-offset: 4
End:
*/

?>
