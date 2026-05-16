<?php 
include "../../inc/functions.php";
include "../../inc/mysqli_connect.php";
$did=$_GET['id'];
$prgStr="SELECT cid, did, program FROM offerings where did=".$did."";

?>
<select name='program' class='w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500'>
<option value="0">Program Offerings</option>
<?php
$prgRes=$dbcon->query($prgStr);
while($pData=$prgRes->fetch_assoc()){
		$id=$pData['cid'];
		$o=$pData['program'];
?>
<option value="<?php echo $id;?>"><?php echo $o;?></option>
<?php
}
?>
</select>

