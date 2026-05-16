
<br />
<?php 
	//session_start();
	//get department

	//1->College
	 $dept='1';
	//2 - SHS
	
	//get the current or active school year
	$str="Select syid, syname,status from sy where status='Active'";
	$res=$dbcon->query($str);
	$data=$res->fetch_assoc();
	$s=$data['syname'];
	$csyid=$data['syid'];
	
	//get the current semester
	$strCS="SELECT sid, semester, status FROM sem where status='Active'";
	$resCS=$dbcon->query($strCS);
	$csData=$resCS->fetch_assoc();
	$cssid=$csData['sid'];
	$cssemester=$csData['semester'];
	
	$_SESSION['sy']=$s;
	$_SESSION['sem']=$cssemester;
	
	//current date
	$today=date("Y/m/d");
	//echo $today;
	
	$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid 
				where l.syid=".$csyid." and l.sid=".$cssid."";
	
	$_SESSION['qry']=$strLedger;
	
?>


<div class="inner">
	<form method="post" role="form" class="grid gap-4 grid-cols-2 md:grid-cols-12">
		<div class="md:col-span-2">
				<img src="../img/filter.jpg" width="120px" height="30px"/>
				
			</div>
			<div class="md:col-span-2">
				<select name="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" id="dept">
				<option value="0">Department</option>
					<?php 
					$prgStr="SELECT did, department FROM departments";
					$prgRes=$dbcon->query($prgStr);
					while($prgData=$prgRes->fetch_assoc()){
						$did=$prgData['did'];
						$department=$prgData['department'];
					?>
						<option value="<?php echo $did;?>"><?php echo $department;?></option>
					<?php
					}
				?>
				</select>
			</div>
			
			<div class="md:col-span-2" id="programs">				
				<select name="program" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" id="program">	
					<option value="0">Program/Strand</option>
					<?php
					$prgStr="SELECT cid, did, program FROM offerings";
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
			
			</div>
			
			<div class="md:col-span-2" id="grade">
				<select name="grdlevel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
				<option value="0" selected >Grade Level</option>
				<?php 
				$grdQry="SELECT gid, did, glevel FROM gradelevel";
				$grdRes=$dbcon->query($grdQry);
				while($grdData=$grdRes->fetch_assoc()){
					$gid=$grdData['gid'];
					$glevel=$grdData['glevel'];
					?>
					<option value="<?php echo $gid;?>"><?php echo $glevel;?></option>
					<?php
				}
				?>
				</select>
			</div>
			<div class="md:col-span-2">
				<select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" id="status">
				<option value="0" selected >Status</option>
				<option value="Paid">Paid</option>
				<option value="Unpaid">Unpaid</option>
				</select>
			</div>
				
			<div class="md:col-span-2">
				<button type="submit" name="btnSearch" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-filter"></i> Search Ledger</button>
			</div>
			<!--
			<div class="md:col-span-1">				
				
				
				<button type="submit" name="btnPrint" id="btnPrint" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold"> <i class="icon-print"></i> Print </button>
			</div>
			-->
		</form>
	</div>
	</br>
	
	<?php 
	
	
	if(isset($_POST['btnPrint'])){
		
	$dept=$_POST['department'];
		$progoff=$_POST['program'];
		$grade=$_POST['grdlevel'];
		$status=$_POST['status'];
		
		if($dept==0 && $progoff==0 && $grade==0 && $status==0){
			//walang filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid 
				where l.syid=".$csyid." and l.sid=".$cssid."";
			
		}elseif($dept<>0 && $progoff==0 && $grade==0 && $status==0){
			//department filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid 
				where l.syid=".$csyid." and l.sid=".$cssid." and l.did=".$dept."";
		}elseif($dept==0 && $progoff<>0 && $grade==0 && $status==0){
			//department filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and o.cid=".$progoff."";
		}elseif($dept==0 && $progoff==0 && $grade<>0 && $status==0){
			//grade filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and g.gid=".$grade."";
		}elseif($dept==0 && $progoff==0 && $grade==0 && $status<>0){
			//status filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."'";
		}elseif($dept<>0 && $progoff==0 && $grade==0 && $status<>0){
			//department and status filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."' and l.did=".$dept."";
			
		}elseif($dept<>0 && $progoff<>0 && $grade==0 && $status<>0){
			//department, program offering and status 
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."' 
					and l.did=".$dept." and o.cid=".$progoff."";
		}elseif($dept<>0 && $progoff<>0 && $grade<>0 && $status<>0){
			//department, program offering, grade and status filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."' 
					and l.did=".$dept." and o.cid=".$progoff." and g.gid=".$grade."";
		}elseif($dept==0 && $progoff<>0 && $grade==0 && $status<>0){
			//program and status filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."' and o.cid=".$progoff."";				
		}
	$_SESSION['qry']=$strLedger;
	
	}
	if(isset($_POST['btnSearch'])){
		$dept=$_POST['department'];
		$progoff=$_POST['program'];
		$grade=$_POST['grdlevel'];
		$status=$_POST['status'];
		
		if($dept==0 && $progoff==0 && $grade==0 && $status==0){
			//walang filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid 
				where l.syid=".$csyid." and l.sid=".$cssid."";
			
		}elseif($dept<>0 && $progoff==0 && $grade==0 && $status==0){
			//department filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid 
				where l.syid=".$csyid." and l.sid=".$cssid." and l.did=".$dept."";
		}elseif($dept==0 && $progoff<>0 && $grade==0 && $status==0){
			//department filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and o.cid=".$progoff."";
		}elseif($dept==0 && $progoff==0 && $grade<>0 && $status==0){
			//grade filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and g.gid=".$grade."";
		}elseif($dept==0 && $progoff==0 && $grade==0 && $status<>0){
			//status filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."'";
		}elseif($dept<>0 && $progoff==0 && $grade==0 && $status<>0){
			//department and status filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."' and l.did=".$dept."";
			
		}elseif($dept<>0 && $progoff<>0 && $grade==0 && $status<>0){
			//department, program offering and status 
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."' 
					and l.did=".$dept." and o.cid=".$progoff."";
		}elseif($dept<>0 && $progoff<>0 && $grade<>0 && $status<>0){
			//department, program offering, grade and status filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."' 
					and l.did=".$dept." and o.cid=".$progoff." and g.gid=".$grade."";
		}elseif($dept==0 && $progoff<>0 && $grade==0 && $status<>0){
			//program and status filter
			$strLedger="SELECT s.fname, s.mname, s.lname, l.syid, l.sid, l.did, l.csid,
					l.amt, l.tfee, l.balance, l.remarks,o.program, g.gid, g.glevel 
				FROM ledger l
				  inner join students s on s.csid=l.csid
				  inner join offerings o on o.cid=s.cid
				  inner join gradelevel g on g.gid=s.gid  
				where l.syid=".$csyid." and l.sid=".$cssid." and l.remarks='".$status."' and o.cid=".$progoff."";				
		}
		
		$_SESSION['qry']=$strLedger;
	}
	
	?>
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">					
					<i class="icon-list"></i> College Learners Ledger Monitoring for the School Year -<strong> <?php echo $s;?>  | Semester : <?php echo $cssemester;?></strong>
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr>
									<th width="6%">#</th>
									<!--<th width="10%">Semester</th>-->
									<th width="24%">Learners Name</th>	
									<th width="12%">Grade Level</th>	
									<th width="27%">Program</th>	
									<th width="10%">Tuition Fee</th>	
									<th width="12%">Amount Paid</th>	
									<th width="9%">Balance</th>										
									<th width="10%">Remarks</th>									
								</tr>
							</thead>
							<tbody>
								<?php 
								$counter=1;
								$lgMon=$dbcon->query($strLedger);
								while($monData=$lgMon->fetch_assoc()){
									$fname=$monData['fname'];
									$mname=$monData['mname'];
									$lname=$monData['lname']; 
									$glevel=$monData['glevel'];
									$program=$monData['program'];
									$syid=$monData['syid'];
									$sid=$monData['sid'];
									$did=$monData['did'];
									$csid=$monData['csid'];
									$amt=$monData['amt'];
									$tfee=$monData['tfee'];
									$balance=$monData['balance'];
									$remarks=$monData['remarks'];
									
									?>
									<tr>
										<td><?php echo $counter;?></td>
										<td><?php echo $fname . " " . $mname . " " . $lname ;?></td>
										<td><?php echo $glevel;?></td>
										<td><?php echo $program;?></td>
										<td><?php echo number_format($amt,0) ;?></td>
										<td><?php echo number_format($tfee,0) ;?></td>
										<td><?php echo number_format($balance,0) ;?></td>
										<td><?php echo $remarks ;?></td>
									</tr>
									<?php
									$counter++;
								}				
									
								?>
							</tbody>
						</table>
					</div>
					<div class="pull-right">
						<a class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold" target="_blank" href="<?php echo "../fpdf/ledger.php";?>" title="Print Ledger/s"><i class="icon-print"></i> Print Ledger</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function(){
    $("#dept").change(function() {
		var did =document.getElementById('dept').value;
		$("#programs").load("ledger/prooff.php?id="+did);
		$("#grade").load("ledger/grade.php?id="+did);
		
		
		//alert (did);
    });
	
	$("#btnPrint").click(function(){
		var dept=document.getElementById('dept').value;
		var prog=document.getElementById('program').value;
		var grade=document.getElementById('grade').value;
		var status=document.getElementById('status').value;
		$("#report").load("../fpdf/ledger.php");
		//alert ("asfhasdf ")
		
	});
});

</script>

<script>
/*
$(function () {
	   //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

    //Initialize Select2 Elements
    $('.select2').select2()
	
  }
*/  
</script>


