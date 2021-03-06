<?php
/*

Pixelpost version 1.5

CVS file version: $Id: create_tables.php,v 1.22 2006/07/26 21:52:33 gajcy Exp $

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

// include functions
require('functions.php');

echo "<ul>";

function Create13Tables( $prefix )
{
	// Config table
	mysql_query("
	CREATE TABLE IF NOT EXISTS {$prefix}config (
		admin varchar(20) NOT NULL default '',
		password varchar(90) NOT NULL default '',
		email varchar(90) NOT NULL default '',
		commentemail varchar(3) NOT NULL default '',
		template varchar(150) NOT NULL default '',
		imagepath varchar(150) NOT NULL default '',
		siteurl varchar(100) NOT NULL default '',
		sitetitle varchar(100) NOT NULL default '',
		langfile varchar(100) NOT NULL default '',
		calendar varchar(30) NOT NULL default '',
		crop varchar(3) NOT NULL default '',
		thumbwidth int(11) NOT NULL,
		thumbheight int(11) NOT NULL,
		thumbnumber int(11) NOT NULL,
		compression int(11) NOT NULL,
		dateformat varchar(30) NOT NULL default ''
	)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table {$prefix}config created ...</li>";

	// Categories Table
	mysql_query("
	CREATE TABLE IF NOT EXISTS {$prefix}categories (
		id int(11) NOT NULL auto_increment,
		name varchar(100) NOT NULL default '',
		KEY id (id)
	)
	") or die("Error: ". mysql_error());

	mysql_query("
	INSERT INTO {$prefix}categories VALUES (0, 'default')
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table {$prefix}categories created ...</li>";

	// Pixelpost table
	mysql_query("
	CREATE TABLE IF NOT EXISTS {$prefix}pixelpost (
		id int(11) NOT NULL auto_increment,
		datetime datetime NOT NULL default '0000-00-00 00:00:00',
		headline varchar(150) NOT NULL default '',
		body text NOT NULL,
		image text NOT NULL,
		category varchar(150) NOT NULL default '',
		KEY id (id)
	)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table {$prefix}pixelpost created ...</li>";

	// Comments table
	mysql_query("
	CREATE TABLE IF NOT EXISTS {$prefix}comments (
		id int(11) NOT NULL auto_increment,
		parent_id int(11) NOT NULL default '0',
		datetime datetime NOT NULL default '0000-00-00 00:00:00',
		ip varchar(20) NOT NULL default '',
		message text NOT NULL,
		name varchar(20) NOT NULL default '',
		url varchar(40) NOT NULL default '',
		KEY id (id)
	)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table {$prefix}comments created ...</li>";

	// Visitors table
	mysql_query("
	CREATE TABLE IF NOT EXISTS {$prefix}visitors (
		id int(11) NOT NULL auto_increment,
		datetime datetime NOT NULL default '0000-00-00 00:00:00',
		host varchar(100) NOT NULL default '',
		referer varchar(255) NOT NULL default '',
		ua varchar(255) NOT NULL default '',
		ip varchar(255) NOT NULL default '',
		ruri varchar(150) NOT NULL default '',
		PRIMARY KEY  (id)
	)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table {$prefix}visitors created ...</li>";
}

// This is 1.3 version of the config except the password is now MD5
function Set_Configuration($prefix)
{
	// guess environment
	$site_url = $_SERVER['HTTP_HOST'];
	$site_url .= $_SERVER['SCRIPT_NAME'];
	$site_url = pathinfo($site_url);
	$site_url = $site_url['dirname'];
	$site_url = str_replace("admin","",$site_url);
	$site_url = "http://$site_url";
	$images_path = str_replace("admin","images/",$images_path);
	
	$images_path  = "../images/";

	// get post data
	$admin_user = addslashes($_POST['admin_user']);
	$admin_password = $_POST['admin_password'];

	$query = mysql_query("
	INSERT INTO {$prefix}config
	(`admin`, `password`, `email`, `commentemail`, `template`, `imagepath`, `siteurl`, `sitetitle`, `langfile`, `calendar`, `crop`, `thumbwidth`, `thumbheight`, `thumbnumber`, `compression`, `dateformat`)
	VALUES ( '$admin_user', MD5('$admin_password'),'','no', 'simple', '$images_path', '$site_url', 'pixelpost','english','No Calendar','yes','100','75','5','75','Y-m-d H:i:s')
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table {$prefix}config populated ...</li>
	<p />

	<b>Remember your data:</b><br />
	Username: <b>$admin_user</b><br />
	Password: <b>$admin_password</b>
	</u><p />";
}

// Upgrade the database from the 1.3 schema to the 1.4 schema
function UpgradeTo14( $prefix )
{
	// Version 1.4
	// Make future upgrade scripts easier by adding a version table
	mysql_query("
	CREATE TABLE IF NOT EXISTS {$prefix}version (
		`id` int(10) unsigned NOT NULL auto_increment,
		`upgrade_date` timestamp(14) NOT NULL,
		`version` float NOT NULL default '0',
		PRIMARY KEY  (`id`),
		KEY `version` (`version`)
	)
	") or die("Error: ". mysql_error());

	mysql_query("
	INSERT INTO `{$prefix}version` (version) VALUES (1.4)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table ".$prefix."version created ...</li>";

	// Multiple Categories support
	mysql_query("
	CREATE TABLE IF NOT EXISTS {$prefix}catassoc (
		id int(11) NOT NULL auto_increment,
		cat_id int(11) NOT NULL default '0',
		image_id int(11) NOT NULL default '0',
		PRIMARY KEY  (id),
		KEY cat_id (cat_id),
		KEY image_id (image_id)
	)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table ".$prefix."catassoc created ...</li>";

	// Timezone support, the 0 will be included automatically, so no need to insert
	$tz = date("Z")/3600; // set the default timezone value equal to the server timezone
	mysql_query("
	ALTER TABLE `{$prefix}config` ADD `timezone` FLOAT DEFAULT '".$tz."' NOT NULL
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Added timezone support ...</li>";

	// Customizable category links
	mysql_query("
	ALTER TABLE `{$prefix}config` ADD `catgluestart` varchar(5) DEFAULT '[' NOT NULL
	") or die("Error: ". mysql_error());
	mysql_query("
	ALTER TABLE `{$prefix}config` ADD `catglueend` varchar(5) DEFAULT ']' NOT NULL
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Added customizable category links support ...</li>";


	mysql_query("
	ALTER TABLE `{$prefix}config` ADD `htmlemailnote` CHAR(3) DEFAULT 'yes'
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Added HTML notification email support ...</li>";


	if(!mysql_query("
	ALTER TABLE `{$prefix}comments` ADD `email` varchar(100)
	")) echo("comments.email already exists: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Added email in comments support ...</li>";


	mysql_query("
	ALTER TABLE `{$prefix}comments` MODIFY  `name` varchar(30)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Longer name field in comments support ...</li>";


	mysql_query("
	ALTER TABLE `{$prefix}comments` MODIFY  `url` varchar(70)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Longer url field in comments support ...</li>";


	mysql_query("
	ALTER TABLE `{$prefix}config` ADD `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Added indexes to {$prefix}config ...</li>";

	// Indexes
	mysql_query("
	ALTER TABLE `{$prefix}categories` DROP INDEX `id`, ADD PRIMARY KEY ( `id` )
	");
	echo "<li style=\"list-style-type:none;\">Added indexes to {$prefix}categories ...</li>";
	mysql_query("
	ALTER TABLE `{$prefix}comments` DROP INDEX `id`, ADD PRIMARY KEY ( `id` ), ADD INDEX ( `parent_id` )
	");
	echo "<li style=\"list-style-type:none;\">Added indexes to {$prefix}comments ...</li>";
	mysql_query("
	ALTER TABLE `{$prefix}pixelpost` DROP INDEX `id`, ADD PRIMARY KEY ( `id` ), ADD INDEX ( `datetime` )
	");
	echo "<li style=\"list-style-type:none;\">Added indexes to {$prefix}pixelpost ...</li>";
	mysql_query("
	ALTER TABLE `{$prefix}visitors` ADD INDEX ( `datetime` ), ADD INDEX ( `referer` ), ADD INDEX ( `ip` )
	");
	echo "<li style=\"list-style-type:none;\">Added indexes to {$prefix}visitors ...</li>";

	// Move any existing categories into the new category association table
	$result = mysql_query("SELECT id, category FROM {$prefix}pixelpost") or die("Error: ". mysql_error());
	while( $row = mysql_fetch_array( $result ) ) {
		mysql_query("INSERT INTO {$prefix}catassoc VALUES ( 0, '{$row[1]}', '{$row[0]}' )") or die("Error: ". mysql_error());
	}



}



// Upgrade the version table to 1.499 (means 1.5alpha)
function UpgradeTo1501( $prefix )
{
global $pixelpost_db_prefix;
	if (!is_field_exists ('moderate_comments','config'))	{
		// add moterate_comments field to config table
		$table = $prefix."config";
		mysql_query("ALTER TABLE $table ADD `moderate_comments` VARCHAR( 3 ) DEFAULT 'no' NOT NULL ")
			or die("Error: ". mysql_error());

		// add publish field to comments table	
		$table = $prefix ."comments";
		mysql_query("ALTER TABLE $table ADD `publish` VARCHAR( 3 ) DEFAULT 'yes' NOT NULL ")
			or die("Error: ". mysql_error());

		echo "<li style=\"list-style-type:none;\">Comment moderation feature is added ...</li>";
		}
	

	// create addons table
	$query = "CREATE TABLE {$pixelpost_db_prefix}addons (
		id INT(11) NOT NULL auto_increment,
		addon_name VARCHAR(66) NOT NULL default '',		
		status VARCHAR(3) NOT NULL default 'on',		
		type VARCHAR(15) NOT NULL default 'normal',
		PRIMARY KEY  (id)
	)";
	mysql_query( $query ) or die("Error: ". mysql_error());;

	// populate the addons table
	$dir = "../addons/";
	refresh_addons_table($dir);
	echo "<li style=\"list-style-type:none;\">Addon ON/OFF switchs are added.</li>";
	
	// update version
	mysql_query("
	INSERT INTO `{$prefix}version` (version) VALUES (1.49931)
	") or die("Error: ". mysql_error());
	echo "<li style=\"list-style-type:none;\">Table ".$prefix."version updated to 1.5alpha_a03 ...</li>";
}

function UpgradeTo15011( $prefix )
{
global $pixelpost_db_prefix;
	if (is_field_exists ('clean_url','config'))	{
		// del clean_url field from config table
		$table = $prefix."config";
		mysql_query("ALTER TABLE $table DROP `clean_url` ")
			or die("Error: ". mysql_error());

		// del clean_url field from pixelpost table
		$table = $prefix."pixelpost";
		mysql_query("ALTER TABLE $table DROP `clean_url` ")
			or die("Error: ". mysql_error());

		// update version
		mysql_query("
		INSERT INTO `{$prefix}version` (version) VALUES (1.4995)
		") or die("Error: ". mysql_error());
		echo "<li style=\"list-style-type:none;\">Table ".$prefix."version updated to 1.5alpha_a04 ...</li>";
	}	
}

function UpgradeTo15012($prefix)
{
global $pixelpost_db_prefix;
	if (!is_field_exists ('timestamp','config'))	{
		// add clean_url field to config table
		$table = $prefix."config";
		mysql_query("ALTER TABLE $table ADD `timestamp` VARCHAR( 4 ) DEFAULT 'yes' NOT NULL ")
			or die("Error: ". mysql_error());

		echo "<li style=\"list-style-type:none;\">Switch ON/OFF for time stamps is added ...</li>";

		// update version
		mysql_query("
		INSERT INTO `{$prefix}version` (version) VALUES (1.4995)
		") or die("Error: ". mysql_error());
		echo "<li style=\"list-style-type:none;\">Table ".$prefix."version updated to 1.5alpha_a04_1.</li><p />";
	}	
}




// upgrade to 1.5Beta
function UpgradeTo15beta($prefix,$newversion)
{
global $pixelpost_db_prefix;

// add comment moderation
	if (!is_field_exists ('moderate_comments','config'))	{
		// add moterate_comments field to config table
		$table = $prefix."config";
		mysql_query("ALTER TABLE $table ADD `moderate_comments` VARCHAR( 3 ) DEFAULT 'no' NOT NULL ")
			or die("Error: ". mysql_error());

		// add publish field to comments table	
		$table = $prefix ."comments";
		mysql_query("ALTER TABLE $table ADD `publish` VARCHAR( 3 ) DEFAULT 'yes' NOT NULL ")
			or die("Error: ". mysql_error());

		echo "<li style=\"list-style-type:none;\">Comment moderation feature is added ...</li>";
		} // end if
	
// create addons table if necessary
	if(!is_table_created('addons')){
		// create addons table
		$query = "CREATE TABLE {$pixelpost_db_prefix}addons (
			id INT(11) NOT NULL auto_increment,
			addon_name VARCHAR(66) NOT NULL default '',		
			status VARCHAR(3) NOT NULL default 'on',		
			type VARCHAR(15) NOT NULL default 'normal',
			PRIMARY KEY  (id)
		)";
		mysql_query( $query ) or die("Error: ". mysql_error());;

		// populate the addons table
		$dir = "../addons/";
		refresh_addons_table($dir);
		echo "<li style=\"list-style-type:none;\">Addon ON/OFF switchs are added ...</li>";
		}
	

// timestamp
	if (!is_field_exists ('timestamp','config'))	{
			// add clean_url field to config table
			$table = $prefix."config";
			mysql_query("ALTER TABLE $table ADD `timestamp` VARCHAR( 4 ) DEFAULT 'yes' NOT NULL ")
				or die("Error: ". mysql_error());

			echo "<li style=\"list-style-type:none;\">Switch ON/OFF for time stamps is added ...</li>";

		}	// end if
	
// visitor booking ON/OFF switch
	if (!is_field_exists ('visitorbooking','config'))	{
		// add clean_url field to config table
		$table = $prefix."config";
		mysql_query("ALTER TABLE $table ADD `visitorbooking` VARCHAR( 4 ) DEFAULT 'yes' NOT NULL ")
			or die("Error: ". mysql_error());

		echo "<li style=\"list-style-type:none;\">Switch ON/OFF for visitor booking is added ...</li>";

		// update version
		mysql_query("
		INSERT INTO `{$prefix}version` (version) VALUES (".$newversion.")
		") or die("Error: ". mysql_error());
		echo "<li style=\"list-style-type:none;\">Table ".$prefix."version updated to 1.5Beta.</li><p />";
	}	// end if
	
} // end function UpgradeTo15beta($prefix)

function UpgradeTo15final( $prefix,$newversion)
{
global $pixelpost_db_prefix;
	if (is_field_exists ('clean_url','config'))	{
		// del clean_url field from config table
		$table = $prefix."config";
		mysql_query("ALTER TABLE $table DROP `clean_url` ")
			or die("Error: ". mysql_error());

		// del clean_url field from pixelpost table
		$table = $prefix."pixelpost";
		mysql_query("ALTER TABLE $table DROP `clean_url` ")
			or die("Error: ". mysql_error());

		// update version
		mysql_query("
		INSERT INTO `{$prefix}version` (version) VALUES (1.5)
		") or die("Error: ". mysql_error());
		echo "<li style=\"list-style-type:none;\">Table ".$prefix."version updated to 1.5 Final ...</li>";
	}	
}

// Upgrade the version table from the 1.4 to the 141
function UpgradeTo141( $prefix )
{
	mysql_query("
	INSERT INTO `{$prefix}version` (version) VALUES (1.41)
	") or die("Error: ". mysql_error());
	//echo "table ".$prefix."version updated to 1.4.1 ...<p />";
}

// Converts the password from the 1.3 base64encoded to MD5 hash
// Do not do this unless we are upgrading
function ConvertPassword( $prefix )
{
	$result = mysql_query("SELECT password FROM {$prefix}config LIMIT 1") or die("Error: ". mysql_error());
	if( $row = mysql_fetch_array( $result ) ) {
		$adm_pass = base64_decode($row[0]);
		mysql_query("UPDATE {$prefix}config SET password=MD5('$adm_pass') LIMIT 1") or die("Error: ". mysql_error());
		echo "<li style=\"list-style-type:none;\">Password updated from 1.3 to 1.4 hash ...</li>";
	}
}


?>