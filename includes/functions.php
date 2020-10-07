<?php
/*
Pixelpost version 1.5

CVS file version: $Id: functions.php,v 1.56 2006/07/16 19:43:28 gajcy Exp $

Pixelpost www: http://www.pixelpost.org/

Version 1.5:
Development Team:
Ramin Mehran, Connie Mueller-Goedecke, Will Duncan, Joseph Spurling, GeoS
Version 1.1 to Version 1.3: Linus <http://www.shapestyle.se>

Contact: thecrew@pixelpost.org
Copyright 2006 Pixelpost.org <http://www.pixelpost.org>


License: http://www.gnu.org/copyleft/gpl.html

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

// Returns the Pixelpost version by looking at the database.  Returns 0 if not installed.
// This is used when performing the initial install/upgrade
function Get_Pixelpost_Version( $prefix )
{
	// First, check to see if we are 1.4 or better
	$querystr = "SELECT version FROM {$prefix}version ORDER BY version DESC LIMIT 1";
	$query = mysql_query($querystr);
	if( $query ) {
		if( $row = mysql_fetch_array( $query ) ) {
			if( $row[0] > 1.3 ) return $row[0];
		}
	}

	// Are we even installed?
	$query = mysql_query("SELECT COUNT(admin) FROM {$prefix}config");
	if( $query ) {
		if( $row = mysql_fetch_array( $query ) ) {
			if( $row[0] > 0 ) return 1.3;	// This could also be 1.2, but that is okay
		}
	}
	return 0;	// Everything failed, must not be installed
}


function print_comments($imageid) {
	global $pixelpost_db_prefix;
	global $lang_no_comments_yet;
	global $lang_visit_homepage;
	global $cfgrow;

	$comment_count = 0;
	$image_comments = "<ul>"; // comments stored in this string
	$cquery = mysql_query("select datetime, message, name, url, email  from ".$pixelpost_db_prefix."comments where parent_id='".$imageid."' and publish='yes' order by id asc");
	while(list($comment_datetime, $comment_message, $comment_name, $comment_url, $comment_email) = mysql_fetch_row($cquery)) {
		$comment_message = pullout($comment_message);
		$comment_name = pullout($comment_name);
		$comment_email = pullout($comment_email);

		if($comment_url != "") {
	  		if( preg_match( '/^(http|https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}'.'((:[0-9]{1,5})?\/.*)?$/i' ,$comment_url))
			{
  			$comment_name = "<a href=\"$comment_url\" title=\"$lang_visit_homepage\" target=\"new\" rel=\"nofollow\">$comment_name</a>";
			} else {
			unset($comment_url);
			$comment_name = "$comment_name";
			}
			}
		$comment_datetime = strtotime($comment_datetime);
		$comment_datetime = date($cfgrow['dateformat'],$comment_datetime);
		$image_comments .= "<li>".nl2br($comment_message)."<br />$comment_name @ $comment_datetime</li>";
		$comment_count++;
		}
	if($comment_count == 0) { $image_comments .= "<li>$lang_no_comments_yet</li>"; }
	$image_comments .= "</ul>";
	return $image_comments;
	}

function check_upload($string) {
	$error_explained = array(
		"0" => "$admin_lang_pp_up_error_0",
		"1" => "$admin_lang_pp_up_error_1",
		"2" => "$admin_lang_pp_up_error_2",
		"3" => "$admin_lang_pp_up_error_3",
		"4" => "$admin_lang_pp_up_error_4"
		);

 	$result = $error_explained[$string];
	return($result);
	}

function createthumbnail($file) {
    global $pixelpost_db_prefix;
    $cfgquery = mysql_query("select * from ".$pixelpost_db_prefix."config");
    $cfgrow = mysql_fetch_array($cfgquery);
    // credit to codewalkers.com - there is 90% a tutorial there
    $max_width = $cfgrow['thumbwidth'];
    $max_height = $cfgrow['thumbheight'];
    define(IMAGE_BASE, "../images");
    $image_path = IMAGE_BASE . "/$file";
    $img = null;
    $image_path_exp = explode('.', $image_path);
    $image_path_end = end($image_path_exp);
    $ext = strtolower($image_path_end);
    if ($ext == 'jpg' || $ext == 'jpeg') {
      $img = @imagecreatefromjpeg($image_path);
        } else if ($ext == 'png') {
        $img = @imagecreatefrompng($image_path);
        } else if ($ext == 'gif') {
        $img = @imagecreatefromgif($image_path);
        }
    if($img) {
        $width = imagesx($img);
        $height = imagesy($img);
        $scale = max($max_width/$width, $max_height/$height);
        if($scale < 1) {
            $new_width = floor($scale*$width);
            $new_height = floor($scale*$height);
            $tmp_img = imagecreatetruecolor($new_width,$new_height);
      // gd 2.0.1 or later: imagecopyresampled
      // gd less than 2.0: imagecopyresized
            if(function_exists(imagecopyresampled)) {
                imagecopyresampled($tmp_img, $img, 0,0,0,0,$new_width,$new_height,$width,$height);
                } else {
                imagecopyresized($tmp_img, $img, 0,0,0,0,$new_width,$new_height,$width,$height);
                }
            imagedestroy($img);
            $img = $tmp_img;
            }
        if($cfgrow['crop'] == "yes" | $cfgrow['crop'] == "12c") {
            // crop
            $tmp_img = imagecreatetruecolor($max_width,$max_height);
            if(function_exists(imagecopyresampled)) {
                imagecopyresampled($tmp_img, $img, 0,0,0,0,$max_width,$max_height,$max_width,$max_height);
                } else {
                imagecopyresized($tmp_img, $img, 0,0,0,0,$max_width,$max_height,$max_width,$max_height);
                }
            imagedestroy($img);
            $img = $tmp_img;
            } // end crop yes
        }
    touch("../thumbnails/thumb_$file");
    imagejpeg($img,"../thumbnails/thumb_$file",$cfgrow['compression']);
    $thumbimage = "../thumbnails/thumb_$file";
    chmod($thumbimage,0644);
    }

function sql_query($str)
{
	$query = "$str";
	$result = mysql_query($query) || die(mysql_error());
}

function clean($str)
{
		//$str = UTF8_encode($str);
	$str = addslashes($str);
	return $str;
}

function pullout($str)
{
	$str = stripslashes($str);
	//$str = UTF8_decode($str);
	return $str;
}

function clean_url($str)
{
	$url = EscapeShellCmd($str);
	return $str;
}

function book_visitor($str)
{
	global $cfgrow;
	// book a visitor
	$datetime = gmdate("Y-m-d H:i:s",gmdate("U")+(3600 * $cfgrow['timezone']));
	$host = $_SERVER['HTTP_HOST'];
	$referer = addslashes($_SERVER['HTTP_REFERER']);

	// don't book a referer from self
	$refererhost = parse_url($referer);
	$refererhost = $refererhost['host'];
	if($refererhost == $host)
	{
   	$referer = "";
	}
	$ua = addslashes($_SERVER['HTTP_USER_AGENT']);
	$ip = $_SERVER['REMOTE_ADDR'];
	$ruri = addslashes($_SERVER['REQUEST_URI']);
	// ### if cookie lastvisit not set, count the people!
	if(!isset($_COOKIE['lastvisit']))
	{
		$query = "insert into $str(id,datetime,host,referer,ua,ip,ruri)
		VALUES(NULL,'$datetime','$host','$referer','$ua','$ip','$ruri')";
    	$result = mysql_query($query);
	}
}

function sql_array($str)
{
	$query = mysql_query($str);
	$row = mysql_fetch_array($query);
	return $row;
}

function start_mysql()
{
	global $pixelpost_db_host, $pixelpost_db_user, $pixelpost_db_pass, $pixelpost_db_pixelpost;
	$dir = 'templates';
	if (!file_exists($dir ."/splash_page.html"))
		$dir = '../templates';

	if(!mysql_connect($pixelpost_db_host, $pixelpost_db_user, $pixelpost_db_pass) )
		show_splash("Connect DB Error: ". mysql_error()." Cause #2",$dir);

	if(!mysql_select_db($pixelpost_db_pixelpost) )
		show_splash("Select DB Error: ". mysql_error()." Cause #2",$dir);
}

// function show_splash
function show_splash($extra_message,$splash_dir)
{
if (file_exists($splash_dir."/splash_page.html")){
		$splash = file_get_contents($splash_dir."/splash_page.html");
		$splash = ereg_replace("<ERROR_MESSAGE>",$extra_message,$splash);
		die($splash) ;
	} else {
	die($extra_message);
	}
}
/*
function &reduceExif($exifvalue)
{
  $vals = split("/",$exifvalue);
  if(count($vals) == 2)  {
		$exposure = round($vals[0]/$vals[1],2);
		if($exposure < 1) $exposure = '1/'.round($vals[1]/$vals[0],0);
  }  else {
		$exposure = round($vals[0]/$vals[1], 2);
  }
 return $exposure;
}
*/
function &reduceExif($exifvalue)
{

$vals = split("/",$exifvalue);
if(count($vals) == 2) {
	// MJS 29092005 - Code to deal with exposure times of > 1 sec
	if ( $vals[1] == 0 ) {
		$exposure = round($vals[0].$vals[1],2);
	} else {
		$exposure = round($vals[0]/$vals[1],2);
		if ( $exposure < 1 ) $exposure = '1/'.round($vals[1]/$vals[0],0);
	}
} else {
	$exposure = round($vals[0]/$vals[1], 2);
}
return $exposure;

}


// categories as table
function category_list_as_table()
{
	global $pixelpost_db_prefix;
  // get the id and name of the first entered category, default category.
  $query = mysql_query("select * from ".$pixelpost_db_prefix."categories order by id asc LIMIT 0,1");
  list($firstid,$firstname) = mysql_fetch_row($query);
   $getid = $_GET['id'];
 // begin of category-list as a table
	 $query = mysql_query("select t1.id, name, image_id from ".$pixelpost_db_prefix."categories as t1 left join ".$pixelpost_db_prefix."catassoc t2 on t2.cat_id = t1.id and t2.image_id='$getid' order by t1.name");
   while(list($id,$name) = mysql_fetch_row($query)) {
		 	echo "<table id='cattable'><tr>";
   		$query = mysql_query("select t1.id, name, image_id from ".$pixelpost_db_prefix."categories as t1 left join ".$pixelpost_db_prefix."catassoc t2 on t2.cat_id = t1.id and t2.image_id='$getid' order by t1.name");
 			while(list($id,$name,$image_id) = mysql_fetch_row($query)) {
	   		$name = pullout($name);
        $id = pullout($id);
				$catcounter++;
				$inarow = 4;
   			if ($image_id != "" AND $_GET['view']=='images' ) {
					echo "<td><input type='checkbox' CHECKED name='category[]' value='".$id."' />&nbsp;".$name."</td>";
				} else {
					if ($firstid==$id && $_GET['view']!='images') // if it is the first defualt category in the new_image page
						echo "<td><input type='checkbox' name='category[]' value='".$id."' />&nbsp;".$name."</td>";
				 	else // if it is other categories in the new image page
    	  		echo "<td><input type='checkbox' name='category[]' value='".$id."' />&nbsp;".$name."</td>";
						  }
						if ($catcounter % $inarow == 0){
							echo "\n</tr><tr>\n";
						} else {
							echo "\n";
						}
			 }
		 }
	 if ($catcounter % $inarow > 0) {echo "</tr>";}
	 echo "</table>\n\n";
}

// function refresh addon table
function refresh_addons_table($dir)
{
	global $pixelpost_db_prefix;
	add_new_addons_2table($dir);
	delete_obsolute_addon($dir,$pixelpost_db_prefix);
}

// add new addon 2 addons table
function add_new_addons_2table($dir)
{
	global $pixelpost_db_prefix;
	global $pixelpost_db_pixelpost;
	//start_mysql();
	// Check to see if the ban table exists, if not, create it
	//$query = "show tables from ".$pixelpost_db_pixelpost." like '".$pixelpost_db_prefix."addons'";
	$query = "SHOW TABLES FROM `".$pixelpost_db_pixelpost."` LIKE '".$pixelpost_db_prefix."addons'";
	$query = mysql_query( $query );
	$query = mysql_fetch_array($query);
	if ($query !='')
	{// addons table does exist
		$str = '';
		
		if($handle = opendir($dir))
		{
			while (false !== ($file = readdir($handle)))
			{
				if($file != "." && $file != "..")
				{
					$farry = explode('.', $file);
					reset($farry);			
					$filename = current($farry);
					$filename_exp = explode('_', $filename);
					if (is_array($filename_exp))
						$filename_crnt = current($filename_exp);
					$addontype = strtolower($filename_crnt);
					$farry_end = end($farry);
					$ftype = strtolower($farry_end);
					if($ftype == "php" AND !check_addon_exists($filename,$pixelpost_db_prefix))	{
						if ( strtolower($addontype) != 'admin')
							$query = "INSERT INTO {$pixelpost_db_prefix}addons VALUES ( NULL, '$filename', 'on', 'normal' )";
						else
							$query = "INSERT INTO {$pixelpost_db_prefix}addons VALUES ( NULL, '$filename',  'on','admin' )";
						mysql_query( $query );
						if (mysql_error())
							echo 'Failed to insert addon: ' .$filename .'.php';
					}//end if
				}//end if
			}// end while

			closedir($handle);
		}// end if
	} // end if addon table exist
}

// delete the row of the deleted addon
function delete_obsolute_addon($dir,$db_prefix)
{
	global $pixelpost_db_prefix;
	global $pixelpost_db_pixelpost;
	//start_mysql();
	// Check to see if the ban table exists, if not, create it
	//$query = "show tables from `".$pixelpost_db_pixelpost."` like '".$pixelpost_db_prefix."addons'";
	$query = "SHOW TABLES FROM `".$pixelpost_db_pixelpost."` LIKE '".$pixelpost_db_prefix."addons'";
	$query = mysql_query( $query );
	$query = mysql_fetch_array($query);
	if ($query !='') {     // addons table does exist
		$query = "select id,addon_name from {$db_prefix}addons ";				
		$query = mysql_query($query);		
		while (list($id,$addon_name)= mysql_fetch_row($query)){
				if (!file_exists($dir.$addon_name.".php")){				
						$querydel = "delete from {$db_prefix}addons where id='$id' ";												
						$resquerydel = mysql_query($querydel);
						if (mysql_error())
							echo 'Failed to delete the addon_name: ' .$addon_name;
				} // end if file not exists
			}	/// end while		
	}// end if addon exists
}

// check existence of an addon in the addons table
function check_addon_exists($name,$db_prefix)
{
	$returnvalue = FALSE;
	$query = "select id from {$db_prefix}addons where addon_name='$name'";
	$query = mysql_query($query);
	while (list($id)= mysql_fetch_row($query)){
		if (is_numeric($id))
			$returnvalue = TRUE;
	}
	return $returnvalue;
}
// check if a table exists inside PP DB
function is_table_created($table_name)
{
global $pixelpost_db_prefix;

   // Check to see if the ban table exists, if not, create it
   $query = "SELECT id FROM {$pixelpost_db_prefix}".$table_name." LIMIT 1";
   if( mysql_query($query)) 
   	return true;
   else return false;
}   
   
// check if a field exist inside a table
function is_field_exists ($fieldname,$table)
{
	global $pixelpost_db_prefix;
	global $pixelpost_db_pixelpost;
	// FIELD_EXISTS
	// Checks whether specified field exists in current or specified table.
	//$fieldname = "maxpthumb";
	$table = $pixelpost_db_prefix .$table;
	$fieldexists = FALSE;
	$t = 0;
	$attention_call = "";
	if ($table != "") {
		if ($table_name != "") $current_table = $table;
		$result_id = mysql_list_fields( $pixelpost_db_pixelpost, $table );
		for ($t = 0; $t < mysql_num_fields($result_id); $t++) {
			$msql_fname = mysql_field_name($result_id, $t);
			if (strtolower( $fieldname) == strtolower($msql_fname)) {
				$fieldexists = TRUE;
				break;
			}
		}
	}
	return $fieldexists;
}

//----------- for addons in admin panel

//
function add_admin_functions($function_name, $function_workspace,$function_menu ='' ,$function_submenu ='')
{
	global $addon_admin_functions;
	$wrkspc_fcn = array('function_name' => $function_name,
											'workspace' => $function_workspace,
											'menu_name' => $function_menu,
											'submenu_name' => $function_submenu);
	$c = count($addon_admin_functions);
	$end = array($c => $wrkspc_fcn);
	$addon_admin_functions = array_merge($addon_admin_functions, $end);
}

// evaluates the admin functions
function eval_addon_admin_workspace_menu ($workspace,$menu_name ='')
{
	global $addon_admin_functions;
	for ($i = 0 ; $i < count($addon_admin_functions) ; $i++)
		{
			$funcs = $addon_admin_functions[$i];
//	foreach ($addon_admin_functions as $key => $funcs)
//	{
	$view_menu = $menu_name ."view";

	// if action is needed
	if ($funcs['workspace']== strtolower($workspace) )
	{
		// if main menu
		if ($funcs['workspace']=='admin_main_menu'){
			echo "<a href='".$_SERVER['PHP_SELF']."?view=".strtolower($funcs['menu_name'])."'>".$funcs['menu_name']."</a>"; 
			continue;
		}
		
		// no menu
		if ($menu_name=='')
		{
			if ($funcs['workspace']=='admin_main_menu_contents' & $_GET['view']!=strtolower($funcs['menu_name']) ){
				continue;
			}
			call_user_func ($funcs['function_name'] );
		}	// end if menu is empty
		else {
		// if menu is needed
			if ($_GET['view']==strtolower($menu_name) &
					$_GET[$view_menu] == strtolower($funcs['submenu_name']))
				{
				// add style (show that the button is pushed!)
				// BANNED! AND REPLACED WITH SOMETHING ELSE!
				//$style_of_selected = "<style> #".$_GET['view'].$_GET[$view_menu] ."{background:#FFA500;}	</style>";
				//echo $style_of_selected ;
				call_user_func ($funcs['function_name'] );
				}// end if menu is need
			} // end else
		} // end if workspace
	}// end foreach
} // end function

// create array
function create_admin_addon_array()
{
	global $addon_admin_functions;
	global $pixelpost_db_prefix;
	$acounter = 0;
	$dir = "../addons/";
	if( $_GET['view'] != "addons" ) {
			//-----------
		$query_ad_s = "select * from {$pixelpost_db_prefix}addons where status='on' and type='admin'";
		$query_ad_s = mysql_query($query_ad_s);
			//-----------
	while (list($id,$filename,$status,$addon_type)= mysql_fetch_row($query_ad_s))	{
			//$addontype = strtolower(current(explode('_', $filename)));
			$dir = "../addons/";
			 include($dir.$filename.".php");
	} // end while
} // end if not addons


//return $addon_admin_functions;
}


// echos the submenu title
function echo_addon_admin_menus ($addon_admin_menus,$menu_name,$additional = '')
{
	for ($i = 0 ; $i < count($addon_admin_menus) ; $i++)
	{
	$submenus = $addon_admin_menus[$i];
//	foreach ($addon_admin_menus as $key => $submenus)
//	{

		if ($submenus['menu_name'] == $menu_name)
		{
			$submenu_name = $submenus['submenu_name'];
			// echo  html content of that sub menu
			$menuitem = strtolower($menu_name).'view';
			$submenuitem = strtolower($submenu_name);
			$selecteclass = '';
			if (isset($_GET[$menuitem]) && ($_GET[$menuitem] == $submenuitem) )
				$selecteclass='selectedsubmenu';
				$toecho ="<a class='".$selecteclass."' href='?view=".strtolower($menu_name) ."&amp;".$menuitem ."=".$submenuitem.$additional."' id='".$menu_name.$submenu_name."'>" .strtoupper($submenu_name) ."</a>";
			echo $toecho;
		}
	}
}

//============================= CONTROL SPAM SECTION BEGINS ========================

function banlist_exist()
{
	global $pixelpost_db_prefix;
	// Check to see if the banlist table exists, if not, create it
	$ret = TRUE;
	$query = "SELECT id FROM {$pixelpost_db_prefix}banlist LIMIT 1";
	if( !mysql_query( $query ) )
	 $ret = FALSE;
	return $ret;
}

// function create banlist table
function create_banlist()
{
	global $pixelpost_db_prefix;
	$result = '';
	if (!banlist_exist()){
		$query = "CREATE TABLE {$pixelpost_db_prefix}banlist (
		id INT(11) NOT NULL auto_increment,
		moderation_list MEDIUMTEXT NOT NULL default '',
		blacklist MEDIUMTEXT NOT NULL default '',
		ref_ban_list MEDIUMTEXT NOT NULL default '',
		acceptable_num_links int(3) NOT NULL default '2',
		PRIMARY KEY  (id)
		)";
	  mysql_query( $query );
	  $query = "INSERT INTO {$pixelpost_db_prefix}banlist VALUES ( NULL,'','','tramadol\n-online\nadipex\nadvicer\nambien\nbllogspot\ncarisoprodol\ncasino\ncasinos\nbaccarrat\ncialis\ncwas\ncyclen\ncyclobenzaprine\nday-trading\ndiscreetordering\ndutyfree\nduty-free\nfioricet\nfreenet-shopping\nincest\nlevitra\nmacinstruct\nmeridia\nonline-gambling\npaxil\nphentermine\nplatinum-celebs\npoker-chip\npoze\nprescription\nsoma\nslot-machine\ntaboo\nteen\ntramadol\ntrim-spa\nultram\nviagra\nxanax\nbooker\nzolus\nchatroom\npoker\ncasino\ntexas\nholdem','2' )";
	  mysql_query( $query );
	  if (mysql_error())
	  	$result = "$admin_lang_spam_err_1".mysql_error();
	  else
	  	$result = "$admin_lang_spam_tableadd";
	}// end if
	return $result;
}

// Update the ban list if the form is called
function update_banlist()
{
global $pixelpost_db_prefix;

	if( isset( $_POST['banlistupdate'] ) ){

		// moderation list
		if (isset( $_POST['moderation_list'] ) ) {
			$banlist = str_replace( "\r\n", "\n", $_POST['moderation_list'] );
			$banlist = str_replace( "\r", "\n", $banlist );
			if (version_compare(phpversion(),"4.3.0")=="-1") {
				 $banlist = mysql_escape_string($banlist);
			 } else {
				 $banlist = mysql_real_escape_string($banlist);
			 }// end if

			$query = "UPDATE {$pixelpost_db_prefix}banlist SET moderation_list='$banlist' LIMIT 1";
			mysql_query($query) ;

			if ( mysql_error() )
				$result .= "$admin_lang_spam_err_2".mysql_error()."<br/>";
		}// end if

		// black list
		if (isset( $_POST['blacklist'] ) ) {
			$banlist = str_replace( "\r\n", "\n", $_POST['blacklist'] );
			$banlist = str_replace( "\r", "\n", $banlist );
			if (version_compare(phpversion(),"4.3.0")=="-1") {
				 $banlist = mysql_escape_string($banlist);
			 } else {
				 $banlist = mysql_real_escape_string($banlist);
			 } // end if
			$query = "UPDATE {$pixelpost_db_prefix}banlist SET blacklist='$banlist' LIMIT 1";
			mysql_query($query) ;
			if ( mysql_error() )
				$result .= "$admin_lang_spam_err_3".mysql_error()."<br/>";
		}// end if

		// referer ban list
		if (isset( $_POST['ref_ban_list'] ) ) {
			$banlist = str_replace( "\r\n", "\n", $_POST['ref_ban_list'] );
			$banlist = str_replace( "\r", "\n", $banlist );
			if(version_compare(phpversion(),"4.3.0")=="-1") {
				 $banlist = mysql_escape_string($banlist);
			 } else {
				 $banlist = mysql_real_escape_string($banlist);
			 }// end if
			$query = "UPDATE {$pixelpost_db_prefix}banlist SET ref_ban_list='$banlist' LIMIT 1";
			mysql_query($query) ;
			if ( mysql_error() )
				$result .= "$admin_lang_spam_err_4 ".mysql_error()."<br/>";
		}// end if

		// acceptable_num_links
		if (isset( $_POST['acceptable_num_links'] ) ) {
			$acceptable_num_links= $_POST['acceptable_num_links'];
			$query = "UPDATE {$pixelpost_db_prefix}banlist SET acceptable_num_links='$acceptable_num_links' LIMIT 1";
			mysql_query($query) ;
			if ( mysql_error() )
				$result .= "$admin_lang_spam_err_5 ".mysql_error()."<br/>";
		}

		if (!isset($result))
			$result = "$admin_lang_spam_upd";
		$result = $result."<br/>";
	} // end if isset( $_POST['banlistupdate'] )

	return $result;
}

// Get the moderation_list
function get_moderation_banlist()
{
global $pixelpost_db_prefix;
	$query = "SELECT moderation_list FROM {$pixelpost_db_prefix}banlist LIMIT 1";
	$result = mysql_query($query) or die( mysql_error() );
	if( $row = mysql_fetch_row($result) )
		$banlist = $row[0];

 return $banlist;
}

// Get the blacklist
function get_blacklist()
{
global $pixelpost_db_prefix;
	$query = "SELECT blacklist FROM {$pixelpost_db_prefix}banlist LIMIT 1";
	$result = mysql_query($query) or die( mysql_error() );
	if( $row = mysql_fetch_row($result) )
		$banlist = $row[0];

	return $banlist;

}

// Get the ref_ban_list
function get_ref_ban_list()
{
global $pixelpost_db_prefix;
	$query = "SELECT ref_ban_list FROM {$pixelpost_db_prefix}banlist LIMIT 1";
	$result = mysql_query($query) or die( mysql_error() );
	if( $row = mysql_fetch_row($result) )
		$banlist = $row[0];

	return $banlist;

}




// prevent bad comments!
function check_moderation_blacklist($cmnt_message,$cmnt_ip,$cmnt_name,$field){
	global $pixelpost_db_prefix;
 
// help from wordpress codes
  $query = "select ".$field." from {$pixelpost_db_prefix}banlist LIMIT 1";
  $result = mysql_query($query);
  $bad_keys = mysql_fetch_array($result);

	$words = explode("\n", $bad_keys[$field] );

	foreach ($words as $word) {
		$word = trim($word);

		// Skip empty lines
		if ( empty($word) ) { continue; }
	
		// Do some escaping magic so that '#' chars in the 
		// spam words don't break things:
		$word = preg_quote($word, '#');

		$pattern = "#$word#i"; 
		if ( preg_match($pattern, $cmnt_message    ) ) return true;
		if ( preg_match($pattern, $cmnt_ip     ) ) return true;
		if ( preg_match($pattern, $cmnt_name   ) ) return true;
		/*
		if ( preg_match($pattern, $comment   ) ) return true;
		if ( preg_match($pattern, $user_ip   ) ) return true;
		if ( preg_match($pattern, $user_agent) ) return true;
		*/
	}

	return false;
}
// is it in blacklist
function is_comment_in_blacklist($cmnt_message,$cmnt_ip,$cmnt_name)
{
	$field = 'blacklist';
	return check_moderation_blacklist($cmnt_message,$cmnt_ip,$cmnt_name,$field);

}

// is it in blacklist
function is_comment_in_moderation_list($cmnt_message,$cmnt_ip,$cmnt_name)
{
	$field = 'moderation_list';
	return check_moderation_blacklist($cmnt_message,$cmnt_ip,$cmnt_name,$field);

}

// clean the reflist entry. no http
function clean_reflist ($entry)
{
	$entry = explode('http://',$entry);
	$entry = end($entry);
	$entry = end(explode('https://',$entry));
	return $entry;
}

// is ref list entry an IP?
function is_entry_ip ($entry)
{
	$entry = explode('.',$entry);
	$entry = current($entry);
	$entry = trim($entry);
	return is_numeric($entry);
}

// create the .htaccess for copy paste
function create_htaccess_banlist()
{
	$badreflist = "SetEnvIfNoCase Referer \".*(";
	$ref_banlist = get_ref_ban_list();
	$ref_banlist = explode("\n",$ref_banlist);
	if (is_array($ref_banlist) ){
		foreach ($ref_banlist as $entry){
			if ($entry=='')
				continue;
			$entry = trim($entry);
			$entry = clean_reflist($entry);
			if (is_entry_ip($entry))
				$denylist .= "deny from " .$entry."\n";
			else
				$badreflist .= $entry."|";
		}// end for each
	} else {
		$entry = trim($ref_banlist);
		$entry = clean_reflist($entry);
		if (is_entry_ip($entry))
			$denylist .= "deny from " .$entry."\n";
		else
			$badreflist .= $entry."|";
	}
	$badreflist .="baccarat.host-c.com).*\" BadReferrer\norder deny,allow\n";
	$badreflist .="deny from env=BadReferrer";

	$to_htaccess = $denylist.$badreflist;

	return $to_htaccess;
}

// compare the moderation list with comments
function moderate_past_with_list()
{
global $pixelpost_db_prefix;
//	if( isset( $_POST['banlistupdate'] ) ){
		// moderation list
	//	if (isset( $_POST['moderation_list'] ) ) {

			$where ='';
			//if moderation of past comments pressed
			if ($_GET['antispamaction']=='moderation'){
				$banlist= get_moderation_banlist();
				$banlist = str_replace( "\r\n", "\n",$banlist );
				$banlist = str_replace( "\r", "\n", $banlist );
				$banlist = explode("\n",$banlist );

				if (is_array($banlist) ){
					foreach ($banlist as $entry){
						if ($entry=='')
							continue;
						$entry = trim($entry);
						$where .= " message LIKE '%{$entry}%' OR name LIKE '%{$entry}%' OR ip LIKE '%{$entry}%' OR ";
					}// end for each
				} else
				{
					$entry = trim($ref_banlist);
					$where .= " message LIKE '%{$entry}%' OR name LIKE '%{$entry}%' OR ip LIKE '%{$entry}%' OR ";
				}
				$where .= ' 0 ';

				$query = "UPDATE {$pixelpost_db_prefix}comments SET publish='no' WHERE $where ";
				mysql_query($query);
				if (mysql_error())
					$additional_msg = "$admin_lang_spam_err_6 ".mysql_error()."<br/>";
				else
					$additional_msg = "$admin_lang_spam_com_upd"."<br/>";
			}// end if moderation

	//	}// end if (isset( $_POST['moderation_list'] ) ) {
//	}// end if isset( $_POST['banlistupdate'] ) ){
	$additional_msg = $additional_msg;
	return $additional_msg;
}


// delete comments which contains words from the blacklist
function delete_past_with_list()
{
global $pixelpost_db_prefix;
//	if( isset( $_POST['banlistupdate'] ) ){
		// moderation list
	//	if (isset( $_POST['moderation_list'] ) ) {

			$where ='';
			//if moderation of past comments pressed
			if ($_GET['antispamaction']=='deletecmnt'){
				$banlist= get_blacklist();
				$banlist = str_replace( "\r\n", "\n",$banlist );
				$banlist = str_replace( "\r", "\n", $banlist );
				$banlist = explode("\n",$banlist );

				if (is_array($banlist) ){
					foreach ($banlist as $entry){
						if ($entry=='')
							continue;
						$entry = trim($entry);
						$where .= " message LIKE '%{$entry}%' OR name LIKE '%{$entry}%' OR ip LIKE '%{$entry}%' OR ";
					}// end for each
				} else
				{
					$entry = trim($ref_banlist);
					$where .= " message LIKE '%{$entry}%' OR name LIKE '%{$entry}%' OR ip LIKE '%{$entry}%' OR ";
				}
				$where .= ' 0 ';

				$query = "delete from {$pixelpost_db_prefix}comments WHERE $where ";

				mysql_query($query);
				if (mysql_error())
					$additional_msg = "$admin_lang_spam_err_7 ".mysql_error()."<br/>";
				else
					$additional_msg = "$admin_lang_spam_com_del"."<br/>";
			}// end if moderation

	//	}// end if (isset( $_POST['moderation_list'] ) ) {
//	}// end if isset( $_POST['banlistupdate'] ) ){
	$additional_msg = $additional_msg;
	return $additional_msg;
}

// delete refs that are listed in the ref ban list
function delete_from_badreferer_list()
{
global $pixelpost_db_prefix;
//	if( isset( $_POST['banlistupdate'] ) ){
		// moderation list
	//	if (isset( $_POST['moderation_list'] ) ) {

			$where ='';
			//if moderation of past comments pressed
			if ($_GET['antispamaction']=='deleterefs'){
				$banlist= get_ref_ban_list();
				$banlist = str_replace( "\r\n", "\n",$banlist );
				$banlist = str_replace( "\r", "\n", $banlist );
				$banlist = explode("\n",$banlist );

				if (is_array($banlist) ){
					foreach ($banlist as $entry){
						if ($entry=='')
							continue;
						$entry = trim($entry);
						$where .= " referer LIKE '%{$entry}%' OR ";
					}// end for each
				} else {
					$entry = trim($ref_banlist);
					$where .= " referer LIKE '%{$entry}%' OR ";
				}
				$where .= ' 0 ';

				$query = "delete from {$pixelpost_db_prefix}visitors WHERE $where ";
				mysql_query($query);
				if (mysql_error())
					$additional_msg = "$admin_lang_spam_err_8".mysql_error()."<br/>";
				else
					$additional_msg = "$admin_lang_spam_visit_del"."<br/>";
			}// end if moderation

	//	}// end if (isset( $_POST['moderation_list'] ) ) {
//	}// end if isset( $_POST['banlistupdate'] ) ){
	$additional_msg = $additional_msg;
	return $additional_msg;
}
//
//============================= ANTI SPAM SECTION ENDS   ========================

function clean_comment($string)
{
	$string = strip_tags($string);
	$string = htmlspecialchars($string,ENT_QUOTES);
	$string = addslashes($string);
	return $string;
}

?>