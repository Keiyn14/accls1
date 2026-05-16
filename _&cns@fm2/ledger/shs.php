
<br />
<?php 
	//get department

	//1->College
	 $dept='2';
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
	
	//current date
	$today=date("Y/m/d");
	//echo $today;
?>



<div class="inner">
<?php
	if(isset($_POST['btnAdd'])){
		$csid=$_POST['lnrName'];
		$cssyid=$_POST['csyid'];
		$cssid=$_POST['cssid'];
		$amount=$_POST['amount'];	
		
		//check if exist
		$checkQry="Select csid, syid,sid from ledger where csid=".$csid." and syid=".$cssyid." and sid=".$cssid."";
		//echo $checkQry;
		if (ifExist($dbcon,$checkQry)){
			?>
			<div class="bg-amber-50 border-l-4 border-amber-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! learner record already exist in the ledger.
			</div>
		<?php
		}else{
		
			//SELECT clid, syid, sid, csid, amt, dpfee, balance, remarks FROM colledger;
			$strInsert="Insert into ledger (did, syid, sid, csid,amt,tfee,balance,remarks,transdate ) 
						values (".$dept.",".$cssyid.",".$cssid.",".$csid.",'".$amount."','0','".$amount."','Unpaid','".$today."')";
			
			$execQuery=$dbcon->query($strInsert);
			if($execQuery){
				
			?>
				
				<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					Congratualation! New learner was added successfully.
				</div>
			<?php
				//get the latest clid value
				$getClid="SELECT clid,csid FROM ledger c order by clid desc limit 1";
				$gcRes=$dbcon->query($getClid);
				$gcData=$gcRes->fetch_assoc();
				$cglid=$gcData['clid'];
				$ccsid=$gcData['csid'];
				//insert to Payment
				$pQry="Insert into payment (did, clid, csid, syid, sid, amount, amtpaid, balance, paymentdate) 
							values (".$dept.",".$cglid.",".$ccsid.",".$cssyid.",".$cssid.",'".$amount."',0,'".$amount."','".$today."')";
				
				//echo $pQry;
				$dbcon->query($pQry);
			}else{
			?>
				<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					Error! Learners details value error.
				</div>
			<?php
			}
		}
	}
	
	if(isset($_POST['btnProcessPayment'])){
		$scolid=$_POST['colid'];
		$samt=$_POST['amt'];
		$scsid=$_POST['scsid'];
		
		//get the total payment by the learner
		$strTF="SELECT sum(amtpaid) as totalFee 
				FROM payment 
				where csid=".$scsid." and syid=".$csyid." and sid=".$cssid."";
		$tfRes=$dbcon->query($strTF);
		$tfData=$tfRes->fetch_assoc();
		$tFess=$tfData['totalFee']; //total amount paid
				
		//get the record of the selected learner
		$strPayment="SELECT did, pid, clid, csid, syid, sid, amount, amtpaid, balance 
					FROM payment 
					where did=".$dept." and clid=".$scolid." and csid=".$scsid." and syid=".$csyid." and sid=".$cssid." 
					order by pid desc";
		//echo $strPayment;
		$resPayment=$dbcon->query($strPayment);	
		$paymentData=$resPayment->fetch_assoc();		
		$amount=$paymentData['amount']; // total tuition fee
		//$amtpaid=$paymentData['amtpaid']; // total na nabayad/binayad
		$balance=$paymentData['balance']; // current balance
	
		//echo "<br />". $amount . " " . $amtpaid . " " . $balance;
		if($samt>$balance){
			//mas mataas ung amount enter kaysa current balance
		?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! Amount Entered is greater than the current balance..
			</div>
		<?php
		}else{
		//compute for balance
		$cAmtPaid=$tFess+$samt; //total amount paid	ex. 0 + 5000
		//$cBalance=$balance-$samt; //new balance value 	ex. 20000-5000
		$cBalance=$balance-$samt; //new balance value 	ex. 20000-5000
		
		
		//echo "<br /> Current amount paid value : " . $cAmtPaid;
		//echo "<br /> Current Balance value : " . $cBalance;
		//insert to table Payment
		$strUP="insert into payment (did, clid, csid, syid, sid, amount, amtpaid, balance,paymentdate) 
				values (".$dept.",".$scolid.",".$scsid.",".$csyid.",".$cssid.",'".$amount."',
				'".$samt."','".$cBalance."','".$today."')";
		//echo "<br />" . $strUP;
		$dbcon->query($strUP);		
		//update table college ledger
		if($cBalance==0){
			$strCledger="Update ledger set remarks='Paid', tfee='".$cAmtPaid."', balance='".$cBalance."' 
						where clid=".$scolid." and syid=".$csyid." and sid=".$cssid." and csid=".$scsid." and did=".$dept."";
		}else{
			$strCledger="Update ledger set remarks='Unpaid', tfee='".$cAmtPaid."', balance='".$cBalance."' 
						where clid=".$scolid." and syid=".$csyid." and sid=".$cssid." and csid=".$scsid." and did=".$dept."";
		//echo $strCledger;
		}
		//echo $strCledger;
		$dbcon->query($strCledger);	
		
		?>
		<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			Congratualations! Learners ledger was updated successfully.
		</div>
	<?php
		}
	}
	?>
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
					<i class="icon-group"></i> Senior High School Ledger Transaction for the school year : <?php echo $s;?> | Semester : <?php echo $cssemester;?> 
				</div>
				<div class="p-6">
					<form role="form" method="post">
						<div class="grid gap-6 md:grid-cols-12">
							<div class="md:col-span-2">								
								<label>Learners Name</label>
							</div>
							<div class="md:col-span-3 space-y-4">								
								<select name="lnrName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 chzn-select">
								<?php 
									$str="SELECT csid, syid, sid, fname, mname, lname
										FROM students
										where syid=".$csyid." and sid=".$cssid. " and did=".$dept."";
									$qryRes=$dbcon->query($str);
									while($qryData=$qryRes->fetch_assoc()){
										$csid=$qryData['csid'];
										$fname=$qryData['fname'];
										$mname=$qryData['mname'];
										$lname=$qryData['lname'];
										$lName=$fname. " ".$mname. " ". $lname;
										?>
										<option value="<?php echo $csid;?>"><?php echo $lName;?></option>
										<?php									
									}
								?>
								</select>
							</div>
							<div class="md:col-span-1">
								<label>Amount</label>
							</div>
							<div class="md:col-span-2">
								<input type="text" name="amount" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 validate[required,custom[number]]" id="number2" placeholder="0.00" required>
								<input type="hidden" name="csyid" value="<?php echo $csyid;?>">
								<input type="hidden" name="cssid" value="<?php echo $cssid;?>">
							</div>
							
							<div class="md:col-span-4">										
								<button type="reset" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold"><i class="icon-refresh"></i> Clear Entry</button>
								<button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i>  Add to Ledger</button>
							</div>									
						</div>
					</form>
					
				</div>
			</div>
		</div>
	</div>
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
					
					<i class="icon-list"></i> Senior High School Learners Ledger Monitoring for the School Year -<strong> <?php echo $s;?>  | Semester : <?php echo $cssemester;?></strong>
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr>
									<th width="6%">#</th>									
									<th width="15%">ID Picture</th>
									<th width="45%">Learners Details</th>									
									<th width="34%">Action</th>
									
								</tr>
							</thead>
							<tbody>
							<?php 
							$counter=1;
							$temp="";
							$strQry="SELECT c.did, c.clid, c.syid, c.sid,o.cid, c.csid, cs.fname, cs.mname, cs.lname, c.amt,
										c.tfee, c.balance, c.remarks, g.glevel, o.program,cs.pict,cs.ftype,
										cs.mobile,cs.address, cs.remarks as status,s.remark 
									FROM ledger c
										left join students cs on cs.csid=c.csid
										left join gradelevel g on g.gid=cs.gid
										left join offerings o on o.cid=cs.cid
										left join status s on s.sid=cs.remarks
									where c.did=".$dept." and c.syid=".$csyid." and c.sid=".$cssid." and c.did=".$dept." 
									order by cs.sid,cs.gid";	
							//echo $strQry;									
							$qryRes=$dbcon->query($strQry);
							while($qryData=$qryRes->fetch_assoc()){
								$clid=$qryData['clid'];
								$csid=$qryData['csid'];
								//$schoolyear=$qryData['syname'];
								//$sem=$qryData['semester'];
								$semid=$qryData['sid'];
								$offid=$qryData['cid'];
								$program=$qryData['program'];
								$glevel=$qryData['glevel'];
								$amt=$qryData['amt'];
								$tfee=$qryData['tfee'];
								$balance=$qryData['balance'];
								//$mobile=$qryData['mobile'];
								//$address=$qryData['address'];
								$remarks=$qryData['remarks'];
								$remark=$qryData['remark'];
								$status=$qryData['status'];
								$pict=$qryData['pict'];
								$ftype=$qryData['ftype'];
								$lname=$qryData['fname'] . " " . $qryData['mname'] . " " . $qryData['lname'];								
								?>
								<tr>
									<td><?php echo $counter;?></td>
									<!--<td><?php echo $semester;?></td>-->
									<td><?php if($pict<>""){
												echo "<img height='60%' width='90%' src=data:".$ftype.";base64," . (base64_encode(($pict))) . ">";
											}else{
												echo "<img height='60%' width='90%' src='../img/logo.png'>";
											}									
										?>
											</td>
									<td>
										<p><h4><?php 
												//echo $lname;
												if($balance==0){
													echo $lname . " ". "<font color='blue'><i>($remarks)</i></font>";
												}else{
													echo $lname . " ". "<font color='red'><i>($remarks)</i></font>";
												}
												?></h4>
											Year Level & Course : <?php echo  $glevel . "-".$program;?> <br/>
											<b>Total Tuition Fees : <?php echo number_format($amt,2);?> <br />
											Total Payment : <?php echo number_format($tfee,2);?> <br />
											Net balance : <?php echo number_format($balance,2);?> <br />
											Academic Status : <?php echo $remark;?> </b>
										</p>
									</td>										
									<td>
										<a class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"  data-toggle="modal"
											data-target="#update<?php echo $clid."-".$csid;?>" title="Update Payment">
											<i class="icon-money"></i> Update Payment
										</a>
										<a class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold" target="_blank"
											
											href="<?php echo "../fpdf/paymenthistory.php?csid=".$csid."&syid=".$csyid."&sid=".$cssid."";?>" title="View Payment History">
											<i class="icon-print"></i> Payment History
										</a>
									
									</td>
									<!--Edit -->										
									<div id="update<?php echo $clid."-".$csid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
										<form method="post" role="form">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header flex items-center justify-between rounded-t-xl bg-green-600 px-6 py-4 text-white">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close">
													<span aria-hidden="true">&times;</span>
												  </button>
												  <h4 class="modal-title"><i class="icon-edit"></i> Learners Payment </h4>
												  
												</div>
												<div class="modal-body">
													<div class="space-y-4">
														<p><h4><?php echo $lname;?></h4>
														<?php echo  $glevel . "-".$program;?> <br/>
														<b>Total Tuition Fees : <?php echo number_format($amt,2);?> <br />
														Total Payment : <?php echo number_format($tfee,2);?> <br />
														Net balance : <?php echo number_format($balance,2);?> </b>
														</p>
													</div>
													<div class="space-y-4">
														<label class="control-label">Amount</label>
														<input type="text" name="amt" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 validate[required,custom[number]]" id="number2" placeholder="0.00" />
														<input type="hidden" value="<?php echo $clid;?>" name="colid"/>
														<input type="hidden" value="<?php echo $csid;?>" name="scsid"/>
													</div>
												
												</div>
												<div class="modal-footer">
												  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
												  <button type="submit" name="btnProcessPayment" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Process Payment</button>
												</div>
											</div>
										</div>
										</form>
									</div>
									<!-- end Update --->
									
									
									
								</tr>
							<?php
							$counter++;
							}
							?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>



<script>
$("#dept").keydown(function(event){	
	
	var did = document.getElementById('dept').value;	
	alert("department");			

	$("#grdLvl").load("grdLvl.php?id="+did);
	
});

$(function () {
	   //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

    //Initialize Select2 Elements
    $('.select2').select2()
	
  } 
</script>






