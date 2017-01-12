<?php
// (c) Copyright 2002-2017 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_meta_info()
{
	return array(
		'name' => tra('Meta'),
		'documentation' => 'PluginMeta',
		'description' => tra('Add custom (meta) tags to HTML head on page where the plugin is used'),
		'prefs' => array( 'wikiplugin_meta' ),
		'body' => tra('Tags for the HTML head'),
		'validate' => 'all',
		'filter' => 'rawhtml_unsafe',
		'iconname' => 'code',
		'introduced' => 17,
		'tags' => array( 'basic' ),
		'params' => array(
			'name' => array(
				'required' => false,
				'name' => tra('Name'),
				'description' => tra('Name attribute of the meta tag'),
				'since' => '17.0',
				'filter' => 'url',
				'default' => '',
			),
			'content' => array(
				'required' => false,
				'name' => tra('Content'),
				'description' => tra('Content attribute of the meta tag'),
				'since' => '17.0',
				'filter' => 'url',
				'default' => '',
			),
/* @todo?			'property' => array(
				'required' => false,
				'name' => tra('Property'),
				'description' => tra('Proprietary property attribute of the meta tag (Facebook OpenGraph specific - used instead of the meta name attribute)'),
				'since' => '17.0',
				'filter' => 'url',
				'default' => '',
			),*/
		),
	);
}

function wikiplugin_meta($data, $params)
{
	$headerlib = TikiLib::lib('header');
	extract($params, EXTR_SKIP);

	if (isset($name)) {
		if (!isset($content)) {
			$content = '';
		}
		$headerlib->add_meta($name,$content);
	} else if ($data) {
		$headerlib->add_rawhtml($data);
	}
	return '';
}
