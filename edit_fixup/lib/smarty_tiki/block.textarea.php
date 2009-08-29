<?php

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"],basename(__FILE__)) !== false) {
  header("location: index.php");
  exit;
}

/*
 * smarty_block_textarea : add a textarea to a template.
 *
 * special params:
 *    _toolbars: if set to 'y', display toolbars above the textarea
 *    _enlarge: if set to 'y', display the enlarge buttons above the textarea
 *
 * usage: {textarea id='my_area' name='my_area'}{tr}My Text{/tr}{/textarea}
 *
 */

function smarty_block_textarea($params, $content, &$smarty, $repeat) {
	global $prefs, $headerlib, $smarty;
	if ( $repeat ) return;

	// some defaults
	$params['_toolbars'] = isset($params['_toolbars']) ? $params['_toolbars'] : 'y';
	if ( $prefs['javascript_enabled'] != 'y') $params['_toolbars'] = 'n';
/*
	if ( ! isset($params['_wikiparsed']) ) {
		// Toolbars implies wiki parsing
		$params['_wikiparsed'] = $params['toolbars'];
	}
*/
	$params['_wysiwyg'] = isset($params['_wysiwyg']) ? $params['_wysiwyg'] : 'n';
	$params['rows'] = isset($params['rows']) ? $params['rows'] : 20;
	$params['cols'] = isset($params['cols']) ? $params['cols'] : 80;
	$params['name'] = isset($params['name']) ? $params['name'] : 'edit';
	$params['id'] = isset($params['id']) ? $params['id'] : 'editwiki';
	
	if ( isset($params['_zoom']) && $params['_zoom'] == 'n' ) {
		$feature_template_zoom_orig = $prefs['feature_template_zoom'];
		$prefs['feature_template_zoom'] = 'n';
	}
	if ( ! isset($params['_section']) ) {
		global $section;
		$params['_section'] = $section ? $section: 'wiki';
	}
	if ( ! isset($params['style']) ) $params['style'] = 'width:99%';
	$html = '';

	if ( $params['_wysiwyg'] == 'y' ) {
// TODO
//		{editform Meat=$pagedata InstanceName='edit' ToolbarSet="Tiki"}
//		<input type="hidden" name="wysiwyg" value="y" />
	} else {
		
		// setup for wiki editor
		
		$textarea_attributes = '';
		foreach ( $params as $k => $v ) {
			if ( $k == 'id' || $k == 'name' || $k == 'class' ) {
				$smarty->assign('textarea_'.$k, $v);
			} elseif ( $k[0] != '_' ) {
				$textarea_attributes .= ' '.$k.'="'.$v.'"';
			}
		}

		if ( $textarea_attributes != '' ) {
			$smarty->assign('textarea_attributes', $textarea_attributes);
		}
		$smarty->assign_by_ref('pagedata', $content);

		$html .= $smarty->fetch('wiki_edit.tpl');

		$html .= "\n".'<input type="hidden" name="rows" value="'.$params['rows'].'"/>'
			."\n".'<input type="hidden" name="cols" value="'.$params['cols'].'"/>'
			."\n".'<input type="hidden" name="wysiwyg" value="'.$params['_wysiwyg'].'" />';

/* Commented because wiki may have to be supported everywhere and is not a user choice. IMO, It complexify the user interface for no good reason.
		if ( $params['_wikiparsed'] == 'optional_on' || $params['_wikiparsed'] == 'optional_off' ) {
			$html .= '<input type="checkbox" name="'.$params['name'].'IsParsed"'
				. ( $params['_wikiparsed'] == 'optional_on' ? ' checked="checked"' : '' )
				. ' onclick="toggleBlock(\'qt'.$params['name'].'\');" /> '
				. tra('Allow wiki syntax')
				. '<br />';
		}
*/

		if ( isset($params['_zoom']) && $params['_zoom'] == 'n' ) {
			$prefs['feature_template_zoom'] = $feature_template_zoom_orig;
		}
		
		if ($prefs['feature_ajax'] == 'y' && $prefs['feature_ajax_autosave'] == 'y') {
			$headerlib->add_js("register_id('$textarea_id');auto_save();");
		}
//		if ($prefs['feature_template_zoom'] == 'y' && isset($_REQUSET['zoom']) && $_REQUSET['zoom'].'.php' == $_SERVER['SCRIPT_NAME']) {	// was $smarty.template
//			$smarty->assign('zoom_mode', 'y');
//		} else {
//			$smarty->assign('zoom_mode', 'n');
//		}
		
	}

	return $html;
}
