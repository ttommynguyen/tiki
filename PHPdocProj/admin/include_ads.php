<?php
/**
 * This script may only be included.
 *
 * At this time, the script is effectively a placeholder.
 * It has no functions of it's own.
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 *
 * @package Tikiwiki\admin
 * @copyright (c) Copyright 2002-2012 by authors of the Tiki Wiki CMS Groupware Project
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}
