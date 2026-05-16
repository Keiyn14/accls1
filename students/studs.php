<?php
include "../inc/functions.php";
include "../inc/mysqli_connect.php";
//get the current or active school year
	$str="Select syid, syname,status from sy where status='Active'";
	$res=$dbcon->query($str);
	$data=$res->fetch_assoc();
	$sy=$data['syname'];
	$syid=$data['syid'];
	
	//get the current semester
	$strCS="SELECT sid, semester, status FROM sem where status='Active'";
	$resCS=$dbcon->query($strCS);
	$csData=$resCS->fetch_assoc();
	$sid=$csData['sid'];
	$sem=$csData['semester'];
	
$id=$_GET['txtid'];
//$id=$_POST['txtId'];
					$qry="SELECT s.studentid, s.fname, s.mname, s.lname, o.program, g.glevel, l.amt, l.tfee, l.balance, l.remarks,s.pict,s.ftype
							FROM ledger l
							inner join students s on s.csid=l.csid
							inner join offerings o on o.cid=s.cid
							inner join gradelevel g on g.gid=s.gid 
						 where studentid='".$id."' and l.syid=".$syid." and l.sid=".$sid."";
						
					//echo $qry;					
					$res=$dbcon->query($qry);
					$recCount=$res->num_rows;
					if($recCount>0){
						$data=$res->fetch_assoc();
						$fname=$data['fname'];
						$mname=$data['mname'];
						$lname=$data['lname'];
						$program=$data['program'];
						$glevel=$data['glevel'];
						$amt=$data['amt'];
						$tfee=$data['tfee'];
						$balance=$data['balance'];
						$remarks=$data['remarks'];
						$pict=$data['pict'];
						$ftype=$data['ftype'];
						?>
						<div class="col-md-4">
							<?php 
							if($pict<>""){
								echo "<img align='right' height='50%' width='50%' src=data:".$ftype.";base64," . (base64_encode(($pict))) . ">";
							}else{
								echo "<img height='50%' width='50%' src='../img/logo.png' align='right'>";
							}									
							?>
							<!--<img src="../img/user.png" width="70%" height="70%"  align="right">-->
						</div>
						<div class="col-md-7">	
						<p>
						<font size="6">
						Student Name   : <?php echo $fname. " " . substr($mname,0,1)."." . " " . $lname;?><br/>
						Course/Program : <?php echo $program;?><br/>
						Year Level     : <?php echo $glevel;?><br/>
						Total Tuition Fee : <?php echo number_format($amt,2);?><br/>
						Total Amount Paid : <?php echo number_format($tfee,2);?><br/>
						Balance           : <?php echo number_format($balance,2);?></font></p>
						</div>
						<?php			
						 //header("Refresh:3; url=".$_SERVER['PHP_SELF']);
					}else{
						?>
						<center>
							<h1>No matching record found! Please try again...</h1>
						</center>
						<?php
						//header("Refresh:3; url=".$_SERVER['PHP_SELF']);
					}
?>