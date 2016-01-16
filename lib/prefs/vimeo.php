<?php
// (c) Copyright 2002-2015 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_vimeo_list()
{
	return array(
		'vimeo_upload' => array(
			'name' => tr('Vimeo upload'),
			'description' => tr('Enables video upload to the Vimeo service. API keys are required and approval must be granted by Vimeo for the upload API.'),
			'help' => 'Vimeo',
			'dependencies' => array('feature_file_galleries', 'fgal_upload_from_source'),
			'type' => 'flag',
			'default' => 'n',
		),
		'vimeo_delete' => array(
			'name' => tr('Vimeo delete'),
			'description' => tr('Causes videos uploaded to the Vimeo service via Tiki file galleries to be deleted when the Tiki file is deleted.'),
			'help' => 'Vimeo',
			'dependencies' => array('vimeo_upload'),
			'type' => 'flag',
			'default' => 'n',
			'warning' => tra('Permanently removes videos from the registered vimeo.com account.'),
		),
		'vimeo_consumer_key' => array(
			'name' => tr('Vimeo consumer key'),
			'description' => tr('API consumer key'),
			'type' => 'text',
			'size' => 40,
			'filter' => 'word',
			'default' => '',
		),
		'vimeo_consumer_secret' => array(
			'name' => tr('Vimeo consumer secret'),
			'description' => tr('API consumer secret'),
			'type' => 'text',
			'size' => 40,
			'filter' => 'word',
			'default' => '',
		),
		'vimeo_access_token' => array(
			'name' => tr('Vimeo access token'),
			'description' => tr('Access token automatically generated by Vimeo when API keys are requested.'),
			'help' => 'Vimeo',
			'type' => 'text',
			'size' => 40,
			'filter' => 'word',
			'default' => '',
		),
		'vimeo_access_token_secret' => array(
			'name' => tr('Vimeo access token secret'),
			'description' => tr('Access token secret automatically generated by Vimeo when API keys are requested.'),
			'help' => 'Vimeo',
			'type' => 'text',
			'size' => 40,
			'filter' => 'word',
			'default' => '',
		),
		'vimeo_default_gallery' => array(
			'name' => tr('Vimeo Default Gallery'),
			'description' => tr('ID of the default file gallery in which to store the Vimeo reference.'),
			'help' => 'Vimeo',
			'type' => 'text',
			'filter' => 'int',
			'default' => 1,
			'size' => 5,
			'profile_reference' => 'file_gallery',
		),
	);
}

