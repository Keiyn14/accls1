<?php 
include "../../inc/functions.php";
include "../../inc/mysqli_connect.php";
$did=$_GET['id'];
$grdQry="SELECT gid, glevel, did FROM gradelevel where did=".$did."";
//echo $grdQry;
?>
<select name="grdlevel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
	<option value='0'>Grade level</option>
	<?php
	$res=$dbcon->query($grdQry);
	while($gData=$res->fetch_assoc()){
	?>
		<option value='<?php echo $gData['gid'];?>'><?php echo $gData['glevel'];?></option>
	<?php
	}
	?>

</select>

