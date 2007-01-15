<?php 
/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: config.php
 * 	Configuration file for the File Manager Connector for PHP.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

$tikiroot = dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(getcwd()))))))));
$tikidomain = '';
if (is_file('db/virtuals.inc')) {
	if (isset($_SERVER['TIKI_VIRTUAL']) and is_file($tikiroot.'/db/'.$_SERVER['TIKI_VIRTUAL'].'/local.php')) {
		$tikidomain = $_SERVER['TIKI_VIRTUAL'];
	} elseif (isset($_SERVER['SERVER_NAME']) and is_file($tikiroot.'/db/'.$_SERVER['SERVER_NAME'].'/local.php')) {
		$tikidomain = $_SERVER['SERVER_NAME'];
	} elseif (isset($_SERVER['HTTP_HOST']) and is_file($tikiroot.'/db/'.$_SERVER['HTTP_HOST'].'/local.php')) {
		$tikidomain = $_SERVER['HTTP_HOST'];
	}
}
if ($tikidomain) $tikidomain.= '/';
if ($tikiroot != $_SERVER['DOCUMENT_ROOT']) {
	$tikipath = strrchr($tikiroot,$_SERVER['DOCUMENT_ROOT']).'/';
} else {
	$tikipath = '/';
}

global $Config ;

// SECURITY: You must explicitelly enable this "connector". (Set it to "true").
$Config['Enabled'] = true;

// Path to user files relative to the document root.
$Config['UserFilesPath'] = $tikipath.'img/wiki_up/'.$tikidomain ;

// Fill the following value it you prefer to specify the absolute path for the
// user files directory. Usefull if you are using a virtual directory, symbolic
// link or alias. Examples: 'C:\\MySite\\UserFiles\\' or '/root/mysite/UserFiles/'.
// Attention: The above 'UserFilesPath' must point to the same directory.
$Config['UserFilesAbsolutePath'] = '' ;

// Due to security issues with Apache modules, it is reccomended to leave the
// following setting enabled.
$Config['ForceSingleExtension'] = true ;

$Config['AllowedExtensions']['File']	= array() ;
$Config['DeniedExtensions']['File']		= array('php','php2','php3','php4','php5','phtml','pwml','inc','asp','aspx','ascx','jsp','cfm','cfc','pl','bat','exe','com','dll','vbs','js','reg','cgi','htaccess') ;

$Config['AllowedExtensions']['Image']	= array('jpg','gif','jpeg','png') ;
$Config['DeniedExtensions']['Image']	= array() ;

$Config['AllowedExtensions']['Flash']	= array('swf','fla') ;
$Config['DeniedExtensions']['Flash']	= array() ;

$Config['AllowedExtensions']['Media']	= array('swf','fla','jpg','gif','jpeg','png','avi','mpg','mpeg') ;
$Config['DeniedExtensions']['Media']	= array() ;

?>
