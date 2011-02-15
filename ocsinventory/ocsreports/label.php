<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2006-12-21 18:13:46 $$Author: plemmet $($Revision: 1.4 $)
$sadmin_profil=1;
include('security.php');
require_once ('require/function_mdb2.php');
require_once ('require/function_misc.php');
printEntete($l->g(263));

if( check_param ($_POST, "newlabel")!="" && str_replace(" ", "", $_POST["newlabel"] )!="" ) {
	$_POST["newlabel"] = str_replace(array("\t","\n","\r"), array("","",""), $_POST["newlabel"] );
	@mdb2_query("DELETE FROM deploy WHERE name='label'");
	$queryL = "INSERT INTO deploy (name,content) VALUES('label',?)";
	mdb2_query($queryL, NULL, "blob", $_POST["newlabel"]) or die(mdb2_error());
	echo "<br><center><font color=green><b>".$l->g(260)."</b></font></center>";
}
else if(isset($_POST["newlabel"])) {
	@mdb2_query("DELETE FROM deploy WHERE name='label'");
	echo "<br><center><font color=green><b>".$l->g(261)."</b></font></center>";
}

$reqL="SELECT content FROM deploy WHERE name='label'";
$resL=mdb2_query($reqL) or die(mdb2_error());
$con = mdb2_fetch_row($resL);

if($con[0]) {
	//echo "<br><center><FONT FACE='tahoma' SIZE=2 color='green'><b>Label actuel: \"".$con[0]."\"</b></font></center>";
}
else {
	if(!isset($_POST["newlabel"]))
		echo "<br><center><FONT FACE='tahoma' SIZE=2 color='green'><b>".$l->g(264)."</b></font></center>";
}
?><br>
<center><b><?php echo $l->g(262);?>:</b>
<form name='lab' action='index.php?multi=12' method='post'>
	<textarea name='newlabel'><?php echo htmlspecialchars ($con[0])?></textarea>
	<input name='sublabel' type='submit' value='<?php echo $l->g(13);?>'>
</form>
</center>
