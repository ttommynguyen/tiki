<?php
//original Code written by Louis Philippe Huberdeau
//Code edited under Google Summer of Code 2008 by
//GSoC 2008 Student - Shishir Mittal under the guidance of 
//Project Mentor - David Tenser
//Project Co-Mentor - Nelson Koth

function wikiplugin_annotation_help() {
        return tra("Creates annotation blocks for an image. Use User Interface for Editing/Adding Annotations").":<br />~np~{ANNOTATION(src=[,width=,height=])}{ANNOTATION}~/np~";
}

// This function checks if the current page has a staging page that is editable by the user provided
// It takes as input parameters the current page name and the user name
// It returns the staging pagename if yes, and false of no.
//This very function has been written by Nelson Koth
function has_editable_staging ( $page, $user = '' ) {
	global $tikilib, $prefs;

	// only run this code if feature wiki approval is on
	if ($prefs['feature_wikiapproval'] == 'y') {
		// find out staging page name
		$stagingPageName = $prefs['wikiapproval_prefix'] . $page;
		// check if page has staging
		if (!$tikilib->page_exists($stagingPageName))
			return false;
		// check if page actually needs staging (i.e. in approved pages category)
		
		global $categlib;
		if (!is_object($categlib)) 
			include_once('lib/categories/categlib.php');
		$cats = $categlib->get_object_categories('wiki page', $page);
		//print_r ($cats);
		if (!$prefs['wikiapproval_approved_category'] || !in_array($prefs['wikiapproval_approved_category'], $cats))
			return false;
		// Now check if the user can edit the staging page
		if (!$tikilib->user_has_perm_on_object($user,$stagingPageName,'wiki page','tiki_p_edit','tiki_p_edit_categories')) {
			return false;
		}
		
		// All conditions passed
		//return $stagingPageName;
		return true;
	} else {
		return false;
	}
}

/*
The function listed here encodes a link string (e.g. http://www.domain.com/long_path/to\file.php?query=param#fragm) to a valid <a href=""> parameter string, preserving the original URI structure and the path given.
*/
function linkencode ($p_url) {
    $ta = parse_url($p_url);
    if (!empty($ta[scheme])) { $ta[scheme].='://'; }
    if (!empty($ta[pass]) and !empty($ta[user])) {
            $ta[user].=':';
            $ta[pass]=rawurlencode($ta[pass]).'@';
    } elseif (!empty($ta[user])) {
        $ta[user].='@';
    }
    if (!empty($ta[port]) and !empty($ta[host])) {
        $ta[host]=''.$ta[host].':';
    } elseif    (!empty($ta[host])) {
        $ta[host]=$ta[host];
    }
    if (!empty($ta[path])) {
        $tu='';
        $tok=strtok($ta[path], "\\/");
        while (strlen($tok)) {
            $tu.=rawurlencode($tok).'/';
            $tok=strtok("\\/");
        }
        $ta[path]='/'.trim($tu, '/');
    }
    if (!empty($ta[query])) { $ta[query]='?'.$ta[query]; }
    if (!empty($ta[fragment])) { $ta[fragment]='#'.$ta[fragment]; }
    return implode('', array($ta[scheme], $ta[user], $ta[pass], $ta[host], $ta[port], $ta[path], $ta[query], $ta[fragment]));
}

function wikiplugin_annotation($data, $params) {
	static $first = true;
	global $page, $tiki_p_edit,$tikilib, $prefs,$user;

	/*$params = array_merge( array( 'align' => 'left', 'desc' => '' ), $params );*/
	if( !function_exists( 'json_encode' ) )
		return "^Annotations not supported on this host.^\n{img src={$params['src']}}";
	$imagewidth = $params["width"];
	$imageheight = $params["height"];
	if(!$imagewidth || !$imageheight){
		if(preg_match('#http[s]?://#i',$params['src'])) {
		//$params['src']='http://'.$params['src'];
			$imagedata=$tikilib->httprequest("{$params['src']}");
		//print ($imagedata);
		//$size_x = 0;
		//$size_y = 0;
			if (function_exists("ImageCreateFromString")){
				if($img = imagecreatefromstring($imagedata)) {
					$imagewidth = imagesx($img);
					$imageheight = imagesy($img);
				}
				else
					return "^Connection Error. Can't calculate width and height of the Image. Please specify them as Annotation Plugin Parameters. For eg {ANNOTATION(src=,width=,height=)}^\n{img src={$params['src']}}";
			}else{
		//Giving default width and height
				$imagewidth = 200;
				$imageheight = 100;
			}
		}else{
			if(list($imagewidth, $imageheight)=@getimagesize("{$params['src']}")) {
			}else{
				return "^No image exists at the given Image Source.^";
			}
		}
	}	
	$params["width"] = $imagewidth;
	$params["height"] = $imageheight;
	//print $size_x;
	//print $size_y;
	//print_r ($prefs);
	$annotations = array();
	$annotationsregexp = "/\n\(\s*(\d+)\s*,\s*(\d+)\s*\)\s*,\s*\(\s*(\d+)\s*,\s*(\d+)\s*\)\s*\[([^\]]*)\]([^\r\n]*)(\n\-.*=.*)?(\n\-.*=.*)?/";
	preg_match_all($annotationsregexp , $data, $initialannotations,PREG_SET_ORDER);
			
	//$hovertextarray = preg_split('/ /', $data, -1, PREG_SPLIT_NO_EMPTY);
	
	//print($data);	
	$hovertextarray=preg_split("/\n\(\s*(\d+)\s*,\s*(\d+)\s*\)\s*,\s*\(\s*(\d+)\s*,\s*(\d+)\s*\)\s*\[([^\]]*)\](.*)(\n\-.*=.*)?(\n\-.*=.*)?/", $data);	
	//$result = count($annotations[0]);
	//print $result;
	//print (count($initialannotations));
	static $imagectr = -1;
	$imagectr++;
	$iaid = 'image-annotation-'.$imagectr;
	for($k=0;$k<count($initialannotations);$k++){
		$annotationskeys = array_keys($initialannotations[$k]);
		$annotationsvalues = array_values($initialannotations[$k]);
		$annotationskeys[0] = "full";
		$annotationskeys[1] = "topleftX";
		$annotationskeys[2] = "topleftY";
		$annotationskeys[3] = "width";
		$annotationskeys[4] = "height";
		$annotationskeys[5] = "zonelink";
		$annotationskeys[6] = "statictext";	
		$annotations[$k] = array_combine($annotationskeys,$annotationsvalues);
		//$annotations[$k]["text1"] = $tikilib->parse_data($annotations[$k]["text1"]);
		$parametercount = count($annotations[$k]) - 7;
		$annotations[$k] = (array_merge($annotations[$k], array("hovertext" =>($hovertextarray[$k+1]),"parametercount"=>$parametercount)));
		//print ($annotations[$k]["hovertext"]);
		//print ("shi");
		//To remove '\n' from hovertext
		$annotations[$k]["hovertext"] = substr($annotations[$k]["hovertext"],1);
		//Limiting different numerical values to the image dimensions.
		$annotations[$k]["topleftX"] = min($annotations[$k]["topleftX"], $params["width"]);
		$annotations[$k]["topleftY"] = min($annotations[$k]["topleftY"],  $params["height"]);
		$annotations[$k]["width"] = min($annotations[$k]["width"], -2 -$annotations[$k]["topleftX"] + $params["width"]);
		$annotations[$k]["height"] = min($annotations[$k]["height"], -2 -$annotations[$k]["topleftY"] + $params["height"]);
		$annotations[$k]["zonelink"] = trim($annotations[$k]["zonelink"]);
		if(strlen($annotations[$k]["zonelink"])) {
			if(!preg_match('#http[s]?://#i',$annotations[$k]["zonelink"])) 
				$annotations[$k]["zonelink"]='http://'.$annotations[$k]["zonelink"];
			//$annotations[$k]["zonelink"] = linkencode($annotations[$k]["zonelink"]);
			$zonelinkdiv[$k] = <<<ZONELINK
		<a id="$iaid-areaanchor-$k" class="annotation-area" href="{$annotations[$k]['zonelink']}" target="_blank" style="width: {$annotations[$k]['width']}px; height: {$annotations[$k]['height']}px; visibility:inherit" ></a>
		<div id="$iaid-areadiv-$k" class="annotation-area" style="width: {$annotations[$k]['width']}px; height: {$annotations[$k]['height']}px; visibility:hidden" ></div>
ZONELINK;
		}else{
		$zonelinkdiv[$k] = <<<ZONELINK
		<div id="$iaid-areaanchor-$k" class="annotation-area" style="width: {$annotations[$k]['width']}px; height: {$annotations[$k]['height']}px; visibility:inherit; cursor:crosshair;" ></div>
		<div id="$iaid-areadiv-$k" class="annotation-area" style="width: {$annotations[$k]['width']}px; height: {$annotations[$k]['height']}px; visibility:hidden;" ></div>
ZONELINK;
		}
		
	}
	//print_r ($annotations);
	//Remove '\n' from last annotation produced as {ANNOTATION} is in a new line
	$annotationcount = count($initialannotations);
	if($annotationcount){
		$annotations[$annotationcount - 1]["hovertext"] = substr($annotations[$annotationcount - 1]["hovertext"],0,strlen($annotations[count($initialannotations) - 1]["hovertext"])-1);
	}
	
	//$statictextprint ='';
	$annframes = '';
	for($k=0; $k<count($annotations) ;$k++){
		$statictext = $tikilib->parse_data($annotations[$k]["statictext"]);
		$statictext = substr($statictext,0,strlen($statictext)-1);//1 to remove '\n' from it.
		$hovertext = $tikilib->parse_data($annotations[$k]["hovertext"]);
		//Remove unnecessary '\n' produced by parse_data.
		$hovertext = str_replace("\n","",$hovertext);
		$notetop = 4 + $annotations[$k]["height"];
		//$notetop2 = $notetop + $notetop;
		$borderwidth = 2 + $annotations[$k]["width"]; // 2 since the inside border has width of 1 px
		$borderheight = 2 + $annotations[$k]["height"];
		$annframes = $annframes . <<<ANFRAMES
<div id="$iaid-annotation-$k" class="annotation-complete" style="position:absolute;top: {$annotations[$k]['topleftY']}px; left: {$annotations[$k]['topleftX']}px;">
	<div id="$iaid-areaborder-$k" class="annotation-areaborder" style="width: {$borderwidth}px; height: {$borderheight}px; visibility:visible;">
		$zonelinkdiv[$k]
	</div>
	<PRE id="$iaid-statictext-$k" class="annotation-note" style="position:absolute;top: {$notetop}px; left: 0px; visibility:inherit; border:none;z-index:1">{$statictext}</PRE>
	<PRE id="$iaid-hovertext-$k" class="annotation-note" style="position:absolute;top: {$notetop}px; left: 0px; visibility:hidden;z-index:3;">{$hovertext}</PRE>
</div>	
ANFRAMES;
}
	//print ($statictextprint);
	$annotations = json_encode( $annotations );
	//print_r ($annotations);
	//print $editBarHeight;
	
	//print $canvasHeight;
	if( $first ) // {{{
	{
		$first = false;
		$script = <<<SCRIPT
<link rel="stylesheet" href="styles/imageannotation/default.css" type="text/css">
<script type="text/javascript" src="lib/imageannotation/annotation-js.js"></script>

SCRIPT;
		
	} // }}}
	else
		$script = '';
		
	
	if(has_editable_staging ($page, $user)){
		$editBarHeight = 24;
		$editBarWidth = 150;
		$stagingPageName = $prefs['wikiapproval_prefix'] . urlencode($page);
		if (isset($_REQUEST['locale'])) {
		        $locale = $_REQUEST['locale'];
		        $url = "tiki-index.php?page={$stagingPageName}&locale=$locale&bl=n";     
		} else {
		
		        $url = "tiki-index.php?page={$stagingPageName}&bl=n";
		}
		//$url = urlencode($url);
		$editBar = <<<EDITBAR
<div id="$iaid-edit-bar" style="position:absolute; top: -{$editBarHeight}px ; left:0px;">
	<a href=$url>Edit Annotations</a>
</div>
EDITBAR;

		$datainsertion = '';
	}
	
	//if the page is a Staging Page
	else if(substr($page, 0, strlen($prefs['wikiapproval_prefix'])) == $prefs['wikiapproval_prefix'])
	{
		$editBarHeight = 24;
		$editBarWidth = 300;
		$editBar = <<<EDITBAR
<div id="$iaid-edit-bar" style="position:absolute; top: -{$editBarHeight}px ; left:0px;">
	<label id="$iaid-change-mode" class="annotation-edit-bar"  onclick="imageNoteChangeMode($imagectr);">Switch to Edit Mode</label>&nbsp;
	<label id="$iaid-AddOrDelete-note" class="annotation-edit-bar" style="visibility:hidden" onclick="performAction($imagectr);">Add Note</label>&nbsp;

</div>
<div id="$iaid-save-changes" style="position:absolute; top:-{$editBarHeight}px; right:0%; visibility : hidden;">
	<form id="$iaid-submit-form" name="$iaid-submit-form" method="post" action="tiki-annotation_edit.php">
		<input id="$iaid-submit-form-save-changes" type="submit" value="Save Changes" onclick="return checkImageNoteData($imagectr);"/>
		<label style="visibility:hidden">
		<input type="hidden" name="page" value="$page"/>
		<input type="hidden" name="imageNoteCounter" value="$imagectr"/>
		<input type="hidden" name="imageNoteData" value=""/>
		</label>
	</form>
</div>
EDITBAR;
		$datainsertion = <<<DATAINSERTION
document.forms["$iaid-submit-form"].elements["imageNoteData"].value = imageNoteData($imagectr);
orgImageNoteData[$imagectr] = imageNoteData($imagectr);
DATAINSERTION;
	}
	else
	{
		$editBarHeight = 0;
		$editBarWidth = 0;
		$editBar = '';
	
		$datainsertion = '';	
	}
	$canvasHeight = $params['height'] + $editBarHeight;
	$canvasWidth = max($params['width'],$editBarWidth);
	return <<<IMAGENOTE
~np~
$script
<div id="$iaid-IEfix" style="width: {$canvasWidth}px;">
<div id="$iaid-canvas" class="annotation-canvas annotation-container-active" style="width: {$canvasWidth}px; height: {$canvasHeight}px; position: relative;">
<img id="$iaid-image" src="{$params['src']}" width="{$params['width']}" height="{$params['height']}" style="position: absolute; top:{$editBarHeight}px; left:0px; z-index:0;"/>
<div id="$iaid-container" class="annotation-container annotation-container-active" style="width: {$canvasWidth}px; height: {$params['height']}px; top:{$editBarHeight}px; left:0px; position: absolute;visibility:hidden">
$editBar
	<div id="$iaid-newArea" class="annotation-editframe" style="left:5px;top:5px;width:50px;height:50px;"></div>
	<div id="$iaid-topBg" style="position:absolute; top:0px; left : 0px; width:{$params['width']}px; opacity:0.4;filter : alpha(opacity=40);background-color:#000;visibility:hidden;"></div>
	<div id="$iaid-leftBg" style="position:absolute;left : 0px; opacity:0.4;filter : alpha(opacity=40);background-color:#000;visibility:hidden;"></div>
	<div id="$iaid-bottomBg" style="position:absolute;left : 0px; width:{$params['width']}px; opacity:0.4;filter : alpha(opacity=40);background-color:#000;visibility:hidden;"></div>
	<div id="$iaid-rightBg" style="position:absolute;opacity:0.4;filter : alpha(opacity=40);background-color:#000;visibility:hidden;"></div>
	$annframes
</div>
</div>
</div>

<script type="text/javascript">
var draggablemaxleft = -2 + {$params['width']};
// -2 to account for annotation border width  
var draggablemaxtop = -2 + {$params['height']};
var dragresize$imagectr = new DragResize('dragresize',
 { minWidth: 5, minHeight: 5, minLeft: 0, minTop: 0, maxLeft: draggablemaxleft, maxTop: draggablemaxtop });

// Optional settings/properties of the DragResize object are:
//  enabled: Toggle whether the object is active.
//  handles[]: An array of drag handles to use (see the .JS file).
//  minWidth, minHeight: Minimum size to which elements are resized (in pixels).
//  minLeft, maxLeft, minTop, maxTop: Bounding box (in pixels).

// Next, you must define two functions, isElement and isHandle. These are passed
// a given DOM element, and must "return true" if the element in question is a
// draggable element or draggable handle. Here, I'm checking for the CSS classname
// of the elements, but you have have any combination of conditions you like:

dragresize$imagectr.isElement = function(elm)
{
 if (elm.id && (elm.id == '$iaid-newArea')) return true;
  
};
dragresize$imagectr.isHandle = function(elm)
{
 if (elm.id && (elm.id == '$iaid-newArea'))  return true;
  
};

// You can define optional functions that are called as elements are dragged/resized.
// Some are passed true if the source event was a resize, or false if it's a drag.
// The focus/blur events are called as handles are added/removed from an object,
// and the others are called as users drag, move and release the object's handles.
// You might use these to examine the properties of the DragResize object to sync
// other page elements, etc.

dragresize$imagectr.ondragfocus = function() { };
dragresize$imagectr.ondragstart = function(isResize) { };
dragresize$imagectr.ondragmove = function(isResize) { };
dragresize$imagectr.ondragend = function(isResize) { };
dragresize$imagectr.ondragblur = function() { };

// Finally, you must apply() your DragResize object to a DOM node; all children of this
// node will then be made draggable. Here, I'm applying to the entire document.
dragresize$imagectr.apply(document);

//imageNotes = new Array($imagectr + 1);
imageNotes[$imagectr] = $annotations;

$datainsertion
process[$imagectr] = '';
editmode[$imagectr] = 0;
editNoteNum[$imagectr] = -1;
newform[$imagectr] = 0;
//alert(process[$imagectr]);
</script>
~/np~
IMAGENOTE;
}

?>
