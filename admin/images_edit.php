<?php

/**************************
*  CVS file version: $Id: images_edit.php,v 1.38 2006/07/21 17:49:14 gajcy Exp $
**************************/

if(!isset($_SESSION["pixelpost_admin"]) || $cfgrow['password'] != $_SESSION["pixelpost_admin"] || $_GET["_SESSION"]["pixelpost_admin"] == $_SESSION["pixelpost_admin"] || $_POST["_SESSION"]["pixelpost_admin"] == $_SESSION["pixelpost_admin"]) {
	die ("Try another day!!");
}

// view=images
if($_GET['view'] == "images") {
	 // Mass add or delete categories to images
	 $_GET['id'] = (int)$_GET['id'];
 		if($_GET['action'] == "masscatedit") {
 			$command = $_GET['cmd'];
 			$command = explode('-',$command);

 			// if unassign
 			if (current($command)=='unassign'){
 				$cat_id = end($command);
 				$idz= $_POST['moderate_image_boxes'];

				$query = "DELETE FROM ".$pixelpost_db_prefix."catassoc ";
				$where = "WHERE";
				for ($i=0; $i < count($idz); $i++)
				{	$where .= " (cat_id='$cat_id' and image_id='$idz[$i]' ) or ";	}

				$where .= " 0 ";
				$query .= $where;

				$query  = sql_query($query);
				$c = count($idz);
 				echo "<div class='jcaption'>$admin_lang_imgedit_mass_5 $c $admin_lang_imgedit_mass_6.</div>";
 			} // end if un-assign

 			// if assign
			if (current($command)=='assign'){

					$cat_id = end($command);
					$idz= $_POST['moderate_image_boxes'];

					// first delete all old values
					$query = "DELETE FROM ".$pixelpost_db_prefix."catassoc ";
					$where = "WHERE";
					for ($i=0; $i < count($idz); $i++)
					{	$where .= " (cat_id='$cat_id' and image_id='$idz[$i]' ) or ";	}

					$where .= " 0 ";
					$query .= $where;

					$query  = sql_query($query);

					// now assign the new values
					// ".$pixelpost_db_prefix."catassoc(id,cat_id,image_id) VALUES(NULL,'$val','$getid')";
					for ($i=0; $i < count($idz); $i++){

						$query = "insert into ".$pixelpost_db_prefix."catassoc (id,cat_id,image_id) VALUES(NULL,'$cat_id','$idz[$i]')";

						$query  = sql_query($query);

					}

					$c = count($idz);
					echo "<div class='jcaption'>$admin_lang_imgedit_mass_7 $c $admin_lang_imgedit_mass_8</div>";
 			} // end if assign
 		} // end if mass edit


  // x=update
    if($_GET['x'] == "update") {
        $headline = clean($_POST['headline']);
        $body = clean($_POST['body']);
        $getid = $_GET['imageid'];
        $newdatetime = $_POST['newdatetime'];
        $query = "delete from ".$pixelpost_db_prefix."catassoc where image_id='$getid'";
        $result = mysql_query($query) ||("Error: ".mysql_error());
        eval_addon_admin_workspace_menu('image_update'); 
//------------

         if($_FILES['userfile']['tmp_name'] != "") {
         $oldfilename = $_POST['oldfilename'];
         $userfile = strtolower($_FILES['userfile']['name']);
         $uploadfile = $upload_dir .$oldfilename;
         if(move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
						 chmod($uploadfile, 0644);
						 $result = check_upload($_FILES['userfile']['error']);
						 //$filnamn =strtolower($_FILES['userfile']['name']);
						 //$filnamn = $oldfilename
						 $filtyp = $_FILES['userfile']['type'];
						 $filstorlek = $_FILES['userfile']['size'];
						 $status = "ok";
						 createthumbnail($oldfilename);
						 $file_reupload_str = '<br />'.$admin_lang_imgedit_file.'<b>' .$oldfilename .'</b> '.$admin_lang_imgedit_file_isuploaded;
						 } else {
						 // something went wrong, try to describe what
						 if ($_FILES['userfile']['error']!='0')
						 	$result = check_upload($_FILES['userfile']['error']);
						 else $result = "$admin_lang_ni_upload_error";
						 echo "<div id='warning'>$admin_lang_error ";
						 echo "<br>$result</div><hr>";
						 $status = "no";
             } // end move
           } // end prepare of file */
//------------
		if (isset($_POST['category'])){
			foreach($_POST['category'] as $val) {
					$category = $val;
					$query  ="insert into ".$pixelpost_db_prefix."catassoc(id,cat_id,image_id) VALUES(NULL,'$val','$getid')";
					$result = mysql_query($query) || die("Error: ".mysql_error());
				}
	   }
        $query = "update ".$pixelpost_db_prefix."pixelpost set datetime='$newdatetime', headline='$headline', body='$body', category='$category' where id='$getid'";
        $result = mysql_query($query) ||("Error: ".mysql_error().$admin_lang_imgedit_db_error);


        echo "<div class='jcaption'>$admin_lang_imgedit_update</div>
        <div class='content confirm'>$admin_lang_done $admin_lang_imgedit_updated #".$getid.". ".$file_reupload_str.	"</div><p />";
        }

		echo "<div id='caption'><b>$admin_lang_images</b></div>";

 // x=delete
    if($_GET['x'] == "delete") {
	eval_addon_admin_workspace_menu('image_deleted'); 
        $getid = $_GET['imageid'];
        $imagerow = sql_array("SELECT image FROM ".$pixelpost_db_prefix."pixelpost where id='$getid'");
        $image = $imagerow['image'];
        $file_to_del = "$upload_dir".$imagerow['image'];
         echo "<div class='jcaption'>$admin_lang_imgedit_deleted	</div>
        <div class='content confirm'>";
        $query = sql_query("delete from ".$pixelpost_db_prefix."pixelpost where id='$getid'");
        $query = "delete from ".$pixelpost_db_prefix."catassoc where image_id='$getid'";
        $result = mysql_query($query) ||("Error: ".mysql_error());
        // added by ramin to delete the comments too!!
        $query = "delete from ".$pixelpost_db_prefix."comments where parent_id='$getid'";
        $result = mysql_query($query) ||("Error: ".mysql_error());

        echo "&nbsp;$admin_lang_imgedit_deleted1&nbsp;";

        if(unlink($file_to_del)) {
            $image_message = "&nbsp;$admin_lang_imgedit_deleted2&nbsp;&nbsp;";
            } else {$image_message = "$admin_lang_imgedit_delete_error<p />"; }

        echo $image_message;

        $file_to_del = "../thumbnails/thumb_".$imagerow['image'];
        if(unlink($file_to_del)) {
            $image_message = "&nbsp;$admin_lang_imgedit_deleted3";
            } else { $image_message = "$admin_lang_imgedit_delete_error2<p />"; }

        echo $image_message."</div>";
        }

    // print out a list over images/posts
   if($_GET['id'] == "") {
	// Get number of photos in database
	$photonumb = sql_array("select count(*) as count from ".$pixelpost_db_prefix."pixelpost");
	$pixelpost_photonumb = $photonumb['count'];

	if ($_POST['numimg_pp'] == ""){$numimg_pp = $pixelpost_photonumb; }
	else{
	$numimg_pp=$_POST['numimg_pp'];
	if ($pixelpost_photonumb < $numimg_pp)
		$numimg_pp = $pixelpost_photonumb;
	};

	if($_GET['page'] == "") { $page = "0"; } else { $page = $_GET['page']; }

	$currntpg = ceil($page/$numimg_pp)+1;
	// calculate the number of pages
	$num_img_pages = ceil($pixelpost_photonumb/$numimg_pp);
     echo "<div class=\"jcaption\">$admin_lang_imgedit_title1<strong><span id=\"photonumb\">$pixelpost_photonumb</span>$admin_lang_imgedit_title2$numimg_pp$admin_lang_imgedit_title3$currntpg$admin_lang_imgedit_title4$num_img_pages</strong>
		   		 </div>
           <div class=\"content\">
           <form method=\"post\" name=\"manageposts\" id=\"manageposts\"  accept-charset=\"UTF-8\" action=\"\">
           <select name=\"mass-edit-cat\" id=\"mass-edit-cat\" onchange=\"document.getElementById('manageposts').action='$PHP_SELF?view=images&amp;action=masscatedit&amp;cmd='+this.options[this.selectedIndex].value; \" >\n
           <option value=\"\">$admin_lang_imgedit_mass_1</option> \n
           <option value=\"\"></option> \n
           <option value=\"\">--- $admin_lang_imgedit_mass_2  ---</option> \n";
     $query = mysql_query("select * from ".$pixelpost_db_prefix."categories order by name");
     while(list($id,$name) = mysql_fetch_row($query)) {
	         $name = pullout($name);
	         $cat_name[] = $name;
	         $ids[] = $id;
	         echo "<option value=\"assign-$id\">$name</option>\n";
	         }
	   echo "<option value=\"\"></option> \n
         		<option value=\"\">--- $admin_lang_imgedit_mass_3 ---</option> \n";
	   for ($k=0;$k<count($cat_name);$k++)
	   {
	   	$name =$cat_name[$k];
	   	$id = $ids[$k];

	   	 echo "<option value=\"unassign-$id'\">$name</option>\n";
	   }
	     echo "</select> <input type=\"submit\" name=\"submit-mass-catedit\" id=\"submit-mass-catedit\" value=\"".$admin_lang_imgedit_mass_4."\" /><p /> <ul>";
		        $pagec = 0;
    	    $images = mysql_query("SELECT * FROM ".$pixelpost_db_prefix."pixelpost ORDER BY datetime DESC limit $page,$numimg_pp");

				 while(list($id,$datetime,$headline,$body,$image,$category) = mysql_fetch_row($images)) {
      				$headline = pullout($headline);
#  				$headline = htmlentities($headline);
      				list($local_width,$local_height,$type,$attr) = getimagesize('../images/'.$image);
      				$fs = filesize('../images/'.$image);
      				$fs*=0.001;
			echo "<li><a href=\"../index.php?showimage=$id\"><img src=\"../thumbnails/thumb_$image\" align=\"left\" hspace=\"3\" alt=\"Click to go to image\" /></a>
				<input type=\"checkbox\" class=\"images-checkbox\" name=\"moderate_image_boxes[]\" value=\"$id\" />
				<strong><a href=\"$PHP_SELF?view=images&amp;id=$id\">[$admin_lang_imgedit_edit]</a> <a href=\"../index.php?showimage=$id\" target=\"_blank\">[$admin_lang_imgedit_preview]</a></strong>
				<strong>#$id, $admin_lang_imgedit_title</strong>$headline,
				<strong>$admin_lang_imgedit_file_name</strong>$image<br />
				<strong>$admin_lang_imgedit_dimensions</strong>$local_width x $local_height, $fs Kb<br />
				<strong>$admin_lang_imgedit_tbpublished</strong> $datetime<br />";

			echo "<strong>$admin_lang_imgedit_category_plural &nbsp;</strong>";
					$category_list = mysql_query("SELECT t2.name FROM ".$pixelpost_db_prefix."catassoc t1 INNER JOIN ".$pixelpost_db_prefix."categories t2 ON t1.cat_id = t2.id WHERE t1.image_id = '$id' ORDER BY t2.name ");
				    while(list($category_name) = mysql_fetch_row($category_list)) {
	      		 	$category_name = pullout($category_name);
			   			echo "[$category_name]";
							}
			echo "&nbsp;&nbsp;<strong><a onclick=\"return confirmSubmit()\" href=\"$PHP_SELF?view=images&amp;x=delete&amp;imageid=$id\">[$admin_lang_imgedit_delete]</a></strong>";
			echo "</li>";

          $pagec++;
          }	    echo "</ul></form>";

	    if($pixelpost_photonumb >$numimg_pp ) {
	    	$pagecounter = 0;
	    	$pcntr = 0;
	    	$image_page_Links = "";
			while ($pcntr < $num_img_pages)
   			  {
					$pcntr++;
					$image_page_Links .= "<a href='index.php?view=images&amp;page=$pagecounter'>$pcntr</a> ";
					$pagecounter=$pagecounter+$numimg_pp;
					}// end while

	       $newpage = $page+$numimg_pp;
	       $image_page_Links .= "<a href='index.php?view=images&amp;page=$newpage'>$admin_lang_next</a>";

	       if ($page >= $numimg_pp) {
	         $newpage = $page - $numimg_pp;
	         $image_page_Links  = "<a href='index.php?view=images&amp;page=$newpage'>$admin_lang_prev</a> " .$image_page_Links;
	         }
	       echo $image_page_Links;
	       echo '<br />
	       <form method="post" action="'.$PHP_SELF .'?view=images&page=0" accept-charset="UTF-8">';
    		echo $admin_lang_show.' ';
		    echo '<input type="text" name="numimg_pp" size="2" value="'.$numimg_pp.'" /> '.$admin_lang_imgedit_img_page.'.
		    <input type="submit" value="'.$admin_lang_go.'" />
			     		</form>';
	      }
	    echo "</div><p />";
	    } else {
	    // an id is specified, edit the image, pull it out and put it in a form
	    $getid = $_GET['id'];
	    $imagerow = sql_array("SELECT * FROM ".$pixelpost_db_prefix."pixelpost where id='$getid'");
      $headline = pullout($imagerow['headline']);
      $headline = htmlspecialchars($headline,ENT_QUOTES);
      $body = pullout($imagerow['body']);
	    $image = $imagerow['image'];
	    $category = $imagerow['category'];
      $category = explode(",",$category);
	    echo "
		<div id='submenu'>
		<a href='?view=images&amp;id=$getid&amp;imagessview=edit' ";
		if (!isset($_GET["imagesview"])) $selecteclass = 'selectedsubmenu';
		echo "class='".$selecteclass."'>$admin_lang_imgedit_edit_post</a>";
        	echo_addon_admin_menus($addon_admin_functions,"images","&amp;id=".$getid);
		echo "</div>";

		eval_addon_admin_workspace_menu("image_edit","images");

// edit image, list categories etc.

     if ($_GET['imagesview']=='edit' or $_GET['imagesview']=='')  {
			echo "
			<form method='post' action='$PHP_SELF?view=images&amp;x=update&amp;imageid=$getid' enctype='multipart/form-data' accept-charset='UTF-8'>";
			eval_addon_admin_workspace_menu("image_edit_form","images");
			echo "
			<div class='jcaption'>$admin_lang_imgedit_reupimg</div>
			<div class='content'>
				<input name='userfile' type='file' size='60'/>
        		<input name='oldfilename' type = 'hidden' value='$image' />
			</div>
			<div class='jcaption'>$admin_lang_imgedit_title</div>
			<div class='content'>
				<input type='text' name='headline' value='$headline' style='width:300px;' />
			</div>
			<div class='jcaption'>$admin_lang_imgedit_txt_desc</div>
			<div class='content'>
				<textarea name='body' cols='50' rows='5' style='width:95%;'>$body</textarea>
			</div>
			<div class='jcaption'>$admin_lang_imgedit_category_plural</div>
			<div class='content'>
			";
			category_list_as_table();
			echo "</div>";
			
			list($img_width,$img_height,$type,$attr) = getimagesize('../images/'.$image);
			$img_size = filesize('../images/'.$image);
      $img_size = number_format($img_size);
     		echo "
			<div class='jcaption'>$admin_lang_imgedit_dtime</div>
			<div class='content'>
				<input type='text' name='newdatetime' value='".$imagerow['datetime']."' style='width:300px;' />
			</div>
			<div class='jcaption'>$admin_lang_imgedit_img</div>
			<div class='content'>
	    	<b>$admin_lang_imgedit_file_name</b> $image, <b>$admin_lang_imgedit_fsize</b> $img_width x $img_height; $img_size <b>kb</b>
			<br />
      	    <img id='itemimg' src='../thumbnails/thumb_$image' alt='' />
			</div>
			<div class='content'>
	    	<input type='submit' value='$admin_lang_imgedit_u_post_button' />
			</div>
      	    </form>
			    	  ";

// Check if the '12c' is selected as the crop then add 3 buttons to the page '+', '-', and 'crop'
         if ($cfgrow['crop']=='12c'){
            $to_echo ="
			<br /><br />
			&nbsp;&nbsp;&nbsp;<strong>$admin_lang_imgedit_12cropimg</strong><br />
            $admin_lang_imgedit_12cropimg_txt
            <input type='button' name='Submit1' value='$admin_lang_imgedit_uthmb_button' onclick=\"cropCheck('def','".$image ."');\" />
 	    	<input type='button' name='Submit3' value='".$txt['smaller']."' onmousedown=\"cropZoom('in');\" onmouseup='stopZoom();' />
	   		<input type='button' name='Submit4' value='".$txt['bigger']."' onmousedown=\"cropZoom('out');\" onmouseup='stopZoom();' />";
         	echo $to_echo;

 // set the size of the crop frame according to the uploaded image
	 setsize_cropdiv ($image);
 //--------------------------------------------------------
	 $for_echo ="<p/>
	<img src='../images/$image' id='myimg' />
	<div id='cropdiv'>
	<table width='100%' height='100%' border='1' cellpadding='0' cellspacing='0' bordercolor='#000000'>
    <tr><td><img src='".$spacer."' /></td>
	</tr></table>
	</div>
	<div id='editthumbnail'>$admin_lang_imgedit_cropbg</div> <!-- end of edit thumb div -->
	 ";
  echo $for_echo;
//--------------------------------------------------------
	 }
	 else
	 echo "$admin_lang_imgedit_12crop_opt<p />";
         echo "<!-- end of content div -->
         <p />";
	    }
	    }// end of if imagesview = edit
    } // end view=images
?>