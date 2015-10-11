<?php
// (c) Copyright 2002-2015 by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// This script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
	header('location: index.php');
	exit;
}

function iconset_glyphicons()
{
	return array(
		'name' => tr('Glyphicons'),
		'description' => tr('Glyphicon focused iconset, see http://getbootstrap.com/components/'),
		'tag' => 'span',
		'prepend' => 'glyphicon glyphicon-',
		'append' => '',
		'icons' => array(
			'actions' => array(
				'id' => 'play-circle',
			),
			'add' => array(
				'id' => 'plus-sign',
			),
			//align-center in defaults
			//arrow-up in defaults
			'article' => array(
				'id' => 'text-background',
			),
			'attach' => array(
				'id' => 'paperclip',
			),
			'audio' => array(
				'id' => 'volume-up',
			),
			'back' => array(
				'id' => 'arrow-left',
			),
			'background-color' => array(
				'id' => 'text-background',
			),
			'backlink' => array(
				'id' => 'new-window',
			),
			//backward in defaults
			'backward_step' => array(
				'id' => 'step-backward',
			),
			'ban' => array(
				'id' => 'ban-circle',
			),
			//book in defaults
			//bookmark in defaults
			'box' => array(
				'id' => 'text-background',
			),
			'bug' => array(
				'id' => 'alert',
			),
			//bullhorn in defaults
			//calendar in defaults
			'caret-left' => array(
				'id' => 'triangle-left',
			),
			'caret-right' => array(
				'id' => 'triangle-right',
			),
			'cart' => array(
				'id' => 'shopping-cart',
			),
			'chart' => array(
				'id' => 'object-align-bottom',
			),
			'check' => array(
				'id' => 'ok-circle',
			),
			'code' => array(
				'id' => 'console',
			),
			'code_file' => array(
				'id' => 'file',
			),
			'collapsed' => array(
				'id' => 'expand',
			),
			'columns' => array(
				'id' => 'th-large',
			),
			//comment in defaults
			'comments' => array(
				'id' => 'comment',
			),
			'compose' => array(
				'id' => 'pencil',
			),
			'computer' => array(
				'id' => 'modal-window',
			),
			'contacts' => array(
				'id' => 'user',
			),
			'copy' => array(
				'id' => 'duplicate',
			),
			'copyright' => array(
				'id' => 'copyright-mark',
			),
			'create' => array(
				'id' => 'plus',
			),
			'delete' => array(
				'id' => 'remove',
			),
			//dashboard in defaults
			'difference' => array(
				'id' => 'text-color',
			),
			'disable' => array(
				'id' => 'minus-sign',
			),
			'documentation' => array(
				'id' => 'book',
			),
			'down' => array(
				'id' => 'arrow-down',
			),
			//edit in defaults
			//education in defaults
			//envelope in defaults
			'error' => array(
				'id' => 'exclamation-sign',
			),
			'excel' => array(
				'id' => 'file',
			),
			'expanded' => array(
				'id' => 'collapse-down',
			),
			//export in defaults
			'facebook' => array(
				'id' => 'thumbs-up',
			),
			//file in defaults
			'file-archive' => array(
				'id' => 'folder-close',
			),
			'file-archive-open' => array(
				'id' => 'folder-open',
			),
			//filter in defaults
			//flag in defaults
			'floppy' => array(
				'id' => 'floppy-save',
			),
			//font in defaults
			//forward in defaults
			'forward_step' => array(
				'id' => 'step-forward',
			),
			//fullscreen in defaults
			//used for google doc plugin
			'google' => array(
				'id' => 'file',
			),
			'group' => array(
				'id' => 'user',
			),
			'h1' => array(
				'id' => 'header',
			),
			'h2' => array(
				'id' => 'header',
			),
			'h3' => array(
				'id' => 'header',
			),
			'help' => array(
				'id' => 'question-sign',
			),
			'history' => array(
				'id' => 'time',
			),
			'horizontal-rule' => array(
				'id' => 'minus',
			),
			'html' => array(
				'id' => 'console',
			),
			'image' => array(
				'id' => 'picture',
			),
			//import in defaults
			'index' => array(
				'id' => 'refresh',
			),
			'information' => array(
				'id' => 'info-sign',
			),
			'language' => array(
				'id' => 'globe',
			),
			//link in defaults
			'link-external' => array(
				'id' => 'share',
			),
			'link-external-alt' => array(
				'id' => 'share',
			),
			//list in defaults
			'list-numbered' => array(
				'id' => 'list-alt',
			),
			//lock in defaults
			'log' => array(
				'id' => 'list-alt',
			),
			'login' => array(
				'id' => 'log-in',
			),
			'logout' => array(
				'id' => 'log-out',
			),
			'mailbox' => array(
				'id' => 'inbox',
			),
			'map' => array(
				'id' => 'map-marker',
			),
			'menu' => array(
				'id' => 'menu-hamburger',
			),
			'menuitem' => array(
				'id' => 'chevron-right',
			),
			'merge' => array(
				'id' => 'random',
			),
			//minus in defaults
			'module' => array(
				'id' => 'cog',
			),
			'money' => array(
				'id' => 'piggy-bank',
			),
			'more' => array(
				'id' => 'option-horizontal',
			),
			//move in defaults
			//music in defaults
			'next' => array(
				'id' => 'arrow-right',
			),
			'notepad' => array(
				'id' => 'file',
			),
			'notification' => array(
				'id' => 'bell',
			),
			//off in defaults
			'ok' => array(
				'id' => 'ok-sign',
			),
			'page-break' => array(
				'id' => 'scissors',
			),
			//pause in defaults
			'paypal' => array(
				'id' => 'credit-card',
			),
			'pdf' => array(
				'id' => 'file',
			),
			//pencil in defaults
			'permission' => array(
				'id' => 'lock',
			),
			//play in defaults
			'plugin' => array(
				'id' => 'briefcase',
			),
			'popup' => array(
				'id' => 'list-alt',
			),
			'post' => array(
				'id' => 'pencil',
			),
			'powerpoint' => array(
				'id' => 'blackboard',
			),
			'previous' => array(
				'id' => 'arrow-left',
			),
			//print in defaults
			'quotes' => array(
				'id' => 'comment',
			),
			'ranking' => array(
				'id' => 'sort-by-order',
			),
			//refresh in defaults
			//remove in defaults
			//repeat in defaults
			'rss' => array(
				'id' => 'bullhorn',
			),
			'screencapture' => array(
				'id' => 'camera',
			),
			//search in defaults
			//send in defaults
			'settings' => array(
				'id' => 'wrench',
			),
			//share in defaults
			'sharethis' => array(
				'id' => 'share-alt',
			),
			'skype' => array(
				'id' => 'share',
			),
			'smile' => array(
				'id' => 'sunglasses',
			),
			//sort in defaults
			'sort-down' => array(
				'id' => 'sort-by-alphabet-alt',
			),
			'sort-up' => array(
				'id' => 'sort-by-alphabet',
			),
			//star in defaults
			//star-empty in defaults
			'star-empty-selected' => array(
				'id' => 'star-empty',
				'class' => 'text-success'
			),
			'star-half-rating' => array(
				'id' => 'star-empty',
			),
			'star-half-selected' => array(
				'id' => 'star-empty',
				'class' => 'text-success'
			),
			'star-selected' => array(
				'id' => 'star',
				'class' => 'text-success'
			),
			//stop in defaults
			'stop-watching' => array(
				'id' => 'eye-close',
			),
			'structure' => array(
				'id' => 'tree-conifer',
			),
			'success' => array(
				'id' => 'ok',
			),
			//subscript in defaults
			//superscript in defaults
			'table' => array(
				'id' => 'list-alt',
			),
			//tag in defaults
			//tags in defaults
			'textfile' => array(
				'id' => 'file',
			),
			//th-large in defaults
			//th-list in defaults
			'themegenerator' => array(
				'id' => 'picture',
			),
			'three-d' => array(
				'id' => 'road',
			),
			//thumbs-up in defaults
			'title' => array(
				'id' => 'text-color',
			),
			'toggle-off' => array(
				'id' => 'stop',
			),
			'toggle-on' => array(
				'id' => 'play',
			),
			'trackers' => array(
				'id' => 'tasks',
			),
			'translate' => array(
				'id' => 'globe',
			),
			//trash in defaults
			'tv' => array(
				'id' => 'film',
			),
			'twitter' => array(
				'id' => 'retweet',
			),
			'undo' => array(
				'id' => 'arrow-left',
			),
			//use a better unlock icon when available
			'unlock' => array(
				'id' => 'folder-open',
			),
			'up' => array(
				'id' => 'arrow-up',
			),
			'video' => array(
				'id' => 'facetime-video',
			),
			'video_file' => array(
				'id' => 'facetime-video',
			),
			'view' => array(
				'id' => 'zoom-in',
			),
			'vimeo' => array(
				'id' => 'facetime-video',
			),
			'warning' => array(
				'id' => 'warning-sign',
			),
			'watch' => array(
				'id' => 'eye-open',
			),
			'watch-group' => array(
				'id' => 'eye-open',
			),
			'wizard' => array(
				'id' => 'flash',
			),
			'word' => array(
				'id' => 'file',
			),
			'wysiwyg' => array(
				'id' => 'text-background',
			),
			'youtube' => array(
				'id' => 'play',
			),
			'zip' => array(
				'id' => 'compressed',
			),
		),
		'defaults' => array(
			'adjust',
			'align-center',
			'align-justify',
			'align-left',
			'align-right',
			'arrow-down',
			'arrow-left',
			'arrow-right',
			'arrow-up',
			'asterisk',
			'backward',
			'ban-circle',
			'barcode',
			'bell',
			'bold',
			'book',
			'bookmark',
			'briefcase',
			'bullhorn',
			'calendar',
			'camera',
			'certificate',
			'check',
			'chevron-down',
			'chevron-left',
			'chevron-right',
			'chevron-up',
			'circle-arrow-down',
			'circle-arrow-left',
			'circle-arrow-right',
			'circle-arrow-up',
			'cloud-download',
			'cloud-upload',
			'cloud',
			'cog',
			'collapse-down',
			'collapse-up',
			'comment',
			'compressed',
			'copyright-mark',
			'credit-card',
			'cutlery',
			'dashboard',
			'download-alt',
			'download',
			'earphone',
			'edit',
			'eject',
			'envelope',
			'euro',
			'exclamation-sign',
			'expand',
			'export',
			'eye-close',
			'eye-open',
			'facetime-video',
			'fast-backward',
			'fast-forward',
			'file',
			'film',
			'filter',
			'fire',
			'flag',
			'flash',
			'floppy-disk',
			'floppy-open',
			'floppy-remove',
			'floppy-save',
			'floppy-saved',
			'folder-close',
			'folder-open',
			'font',
			'forward',
			'fullscreen',
			'gbp',
			'gift',
			'glass',
			'globe',
			'hand-down',
			'hand-left',
			'hand-right',
			'hand-up',
			'hd-video',
			'hdd',
			'header',
			'headphones',
			'heart-empty',
			'heart',
			'home',
			'import',
			'inbox',
			'indent-left',
			'indent-right',
			'info-sign',
			'italic',
			'leaf',
			'link',
			'list-alt',
			'list',
			'lock',
			'log-in',
			'log-out',
			'magnet',
			'map-marker',
			'minus-sign',
			'minus',
			'move',
			'music',
			'new-window',
			'off',
			'ok-circle',
			'ok-sign',
			'ok',
			'open',
			'paperclip',
			'pause',
			'pencil',
			'phone-alt',
			'phone',
			'picture',
			'plane',
			'play-circle',
			'play',
			'plus-sign',
			'plus',
			'print',
			'pushpin',
			'qrcode',
			'question-sign',
			'random',
			'record',
			'refresh',
			'registration-mark',
			'remove-circle',
			'remove-sign',
			'remove',
			'repeat',
			'resize-full',
			'resize-horizontal',
			'resize-small',
			'resize-vertical',
			'retweet',
			'road',
			'save',
			'saved',
			'scissors',
			'screenshot',
			'sd-video',
			'search',
			'send',
			'share-alt',
			'share',
			'shopping-cart',
			'signal',
			'sort-by-alphabet-alt',
			'sort-by-alphabet',
			'sort-by-attributes-alt',
			'sort-by-attributes',
			'sort-by-order-alt',
			'sort-by-order',
			'sort',
			'sound-5-1',
			'sound-6-1',
			'sound-7-1',
			'sound-dolby',
			'sound-stereo',
			'star-empty',
			'star',
			'stats',
			'step-backward',
			'step-forward',
			'stop',
			'subscript',
			'subtitles',
			'superscript',
			'tag',
			'tags',
			'tasks',
			'text-height',
			'text-width',
			'th-large',
			'th-list',
			'th',
			'thumbs-down',
			'thumbs-up',
			'time',
			'tint',
			'tower',
			'transfer',
			'trash',
			'tree-conifer',
			'tree-deciduous',
			'unchecked',
			'upload',
			'usd',
			'user',
			'volume-down',
			'volume-off',
			'volume-up',
			'warning-sign',
			'wrench',
			'zoom-in',
			'zoom-out',
		),
	);
}
