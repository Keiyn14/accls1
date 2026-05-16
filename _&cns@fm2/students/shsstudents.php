<br />
<?php 
	//session_start();
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
	
	$_SESSION['sy']=$s;
	$_SESSION['sem']=$cssemester;
	$strQry="SELECT cs.studentid, cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
				  (select syname from sy where syid=cs.syid) as syname,
				  (select semester from sem where sid=cs.sid) as semester,
				  (select program from offerings where cid=cs.cid) as program,
				  (select glevel from gradelevel where gid=cs.gid) as glevel,
				  (select department from departments where did=cs.did) as department,
					(select remark from status where sid=cs.remarks) as status,
				  cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks,
				  cs.pict,cs.ftype 
			FROM students cs
			where cs.syid=".$csyid." and cs.sid=".$cssid." and cs.did=".$dept." 
			order by cs.sid,cs.gid";
?>

<div class="inner">
	<form method="post" role="form" class="grid gap-4 grid-cols-2 md:grid-cols-12">
		<div class="md:col-span-2">
			<button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold" data-toggle="modal" data-target="#formModal">
                  <i class="icon-plus"> </i>Add Learner
             </button>			 
		</div>
		<div class="md:col-span-2">
			<select name="grade" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
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
		<div class="md:col-span-3">
			<select name="program" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
				<option value="0" selected >Program/Strand </option>
				<?php 
					$prgStr="SELECT cid, did, program FROM offerings where did=".$dept."";
					$prgRes=$dbcon->query($prgStr);
					while($prgData=$prgRes->fetch_assoc()){
						$cid=$prgData['cid'];
						$program=$prgData['program'];
					?>
						<option value="<?php echo $cid;?>"><?php echo $program;?></option>
					<?php
					}
				?>
				
			</select>
		</div>
		<div class="md:col-span-3">
			<input type="text" name="student" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Students Lastname">
		</div>
		<div class="md:col-span-1">
			<button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold" name="btnSearch">
                  <i class="icon-filter"> </i>Search
             </button>			 
		</div>
		<div class="md:col-span-1">
			<a class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold" target="_blank"											
				href="<?php echo "../fpdf/learners.php";?>" title="Print Learner/s">
				<i class="icon-print"></i> Print
			</a>		 
		</div>
		</form>
	</div>
	<hr >
	<?php
	if(isset($_POST['btnSearch'])){
		$grade=$_POST['grade'];
		$program=$_POST['program'];
		$student=$_POST['student'];
		if($grade==0 && $program==0 && $student==""){
		//walang filter
		$strQry="SELECT cs.studentid, cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
										  (select syname from sy where syid=cs.syid) as syname,
										  (select semester from sem where sid=cs.sid) as semester,
										  (select program from offerings where cid=cs.cid) as program,
										  (select glevel from gradelevel where gid=cs.gid) as glevel,
										  (select department from departments where did=cs.did) as department,
											(select remark from status where sid=cs.remarks) as status,
										  cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks,
										  cs.pict,cs.ftype 
									FROM students cs
									where cs.syid=".$csyid." and cs.sid=".$cssid." and cs.did=".$dept." 
									order by cs.sid,cs.gid";
									
		}else if($grade<>0 && $program==0 && $student==""){
		//grade level lng ang filter
		$strQry="SELECT cs.studentid, cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
										  (select syname from sy where syid=cs.syid) as syname,
										  (select semester from sem where sid=cs.sid) as semester,
										  (select program from offerings where cid=cs.cid) as program,
										  (select glevel from gradelevel where gid=cs.gid) as glevel,
										  (select department from departments where did=cs.did) as department,
											(select remark from status where sid=cs.remarks) as status,
										  cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks,
										  cs.pict,cs.ftype 
									FROM students cs
									where cs.syid=".$csyid." and cs.sid=".$cssid." and cs.gid=".$grade." and cs.did=".$dept." 
									order by cs.sid,cs.gid";
									
		}else if($grade==0 && $program<>0 && $student==""){
		//program offering lng ang filter
		$strQry="SELECT cs.studentid, cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
										  (select syname from sy where syid=cs.syid) as syname,
										  (select semester from sem where sid=cs.sid) as semester,
										  (select program from offerings where cid=cs.cid) as program,
										  (select glevel from gradelevel where gid=cs.gid) as glevel,
										  (select department from departments where did=cs.did) as department,
											(select remark from status where sid=cs.remarks) as status,
										  cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks,
										  cs.pict,cs.ftype 
									FROM students cs
									where cs.syid=".$csyid." and cs.sid=".$cssid." and cs.cid=".$program." and cs.did=".$dept."
									order by cs.sid,cs.gid";
									
		}else if($grade==0 && $program==0 && $student<>""){
		//student lng ang filter
		$strQry="SELECT cs.studentid, cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
										  (select syname from sy where syid=cs.syid) as syname,
										  (select semester from sem where sid=cs.sid) as semester,
										  (select program from offerings where cid=cs.cid) as program,
										  (select glevel from gradelevel where gid=cs.gid) as glevel,
										  (select department from departments where did=cs.did) as department,
											(select remark from status where sid=cs.remarks) as status,
										  cs.fname, '', cs.mname,' ', cs.lname, cs.address, cs.mobile, cs.remarks,
										  cs.pict,cs.ftype 
									FROM students cs
									where cs.syid=".$csyid." and cs.sid=".$cssid." and cs.lname like '%".$student."%' and cs.did=".$dept."
									order by cs.sid,cs.gid";
			//echo $strQry;
		}else if($grade<>0 && $program<>0 && $student==""){
		//grade level and program ang filter
		$strQry="SELECT cs.studentid, cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
										  (select syname from sy where syid=cs.syid) as syname,
										  (select semester from sem where sid=cs.sid) as semester,
										  (select program from offerings where cid=cs.cid) as program,
										  (select glevel from gradelevel where gid=cs.gid) as glevel,
										  (select department from departments where did=cs.did) as department,
											(select remark from status where sid=cs.remarks) as status,
										  cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks,
										  cs.pict,cs.ftype 
									FROM students cs
									where cs.syid=".$csyid." and cs.sid=".$cssid." and cs.gid=".$grade." and cs.cid=".$program." and cs.did=".$dept."
									order by cs.sid,cs.gid";
		}else if($grade<>0 && $program<>0 && $student<>""){
			$strQry="SELECT cs.studentid, cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
										  (select syname from sy where syid=cs.syid) as syname,
										  (select semester from sem where sid=cs.sid) as semester,
										  (select program from offerings where cid=cs.cid) as program,
										  (select glevel from gradelevel where gid=cs.gid) as glevel,
										  (select department from departments where did=cs.did) as department,
											(select remark from status where sid=cs.remarks) as status,
										  cs.fname, '', cs.mname,' ', cs.lname, cs.address, cs.mobile, cs.remarks,
										  cs.pict,cs.ftype 
									FROM students cs
									where cs.syid=".$csyid." and cs.sid=".$cssid." and cs.gid=".$grade." and cs.cid=".$program." and cs.lname like '%".$student."%' and cs.did=".$dept."
									order by cs.sid,cs.gid";
		}
	
		$_SESSION['qry']=$strQry;
	}
	
	if(isset($_POST['btnAdd'])){
		$studentid=$_POST['txtid'];
		$sem=$_POST['sem'];
		$sy=$_POST['syid'];
		$glevel=$_POST['grdlevel'];
		$cid=$_POST['offering'];
		$fn=$_POST['txtfn'];
		$mn=$_POST['txtmn'];
		$ln=$_POST['txtln'];
		$mobile=$_POST['txtmobile'];
		$address=$_POST['txtaddress'];
		$dept=$_POST['dept'];
		$status=$_POST['status'];
		if($fn=="" && $ln==""){
			?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! Invalid learners details.
			</div>
			<?php
		}else{
			//check if entry already exist
			$strCheck="SELECT fname, mname, lname FROM shsstudents 
			           where fname='".$fn."' and mname='".$mn ."' and lname='".$ln."'";
			if(ifExist($dbcon,$strCheck)){
				?>
				<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					Warning! learner already exist.
				</div>
			<?php
			}else{		
				$strInsert="Insert into students (studentid, syid, sid, cid, gid, fname, mname, lname, address, mobile,remarks,did) 
							values ('".$studentid."',".$sy.",".$sem.",".$cid.",".$glevel.",
									'".$fn."','".$mn."','".$ln."','".$address."','".$mobile."',".$status.",".$dept.")";
				//echo $strInsert;
				$execQuery=$dbcon->query($strInsert);
				if($execQuery){
				?>
					<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Congratualation! New learner was added successfully.
					</div>
				<?php
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
	}
	
	
	//upload student picture / ID
	if(isset($_POST['btnUpload'])){
		$ucsid=$_POST['ucsid'];
		//$file=$_POST['file'];
		$status='Error';
		if(!empty($_FILES["image"]["name"])) { 
			// Get file info 
			$fileName = basename($_FILES["image"]["name"]); 
			$fileType = pathinfo($fileName, PATHINFO_EXTENSION);        
			//echo $fileType;
			// Allow certain file formats 
			$allowTypes = array('jpg','png','jpeg'); 
			if(in_array($fileType, $allowTypes)){ 
				$image = $_FILES['image']['tmp_name']; 
				$imgContent = addslashes(file_get_contents($image));
				$qryImg="Update students set pict='".$imgContent."', ftype ='".$fileType."' where csid='".$ucsid."' and did=".$dept."";
				//echo $qryImg;
				
				$update = $dbcon->query($qryImg);
				if($update){ 
					$status = 'success'; 
					$statusMsg = "File uploaded successfully."; 
				}else{ 
					$statusMsg = "File upload failed, please try again."; 
				}  
			}else{ 
				$statusMsg = 'Sorry, only JPG, JPEG & PNG files are allowed to upload.'; 
			} 
		}else{ 
			$statusMsg = 'Please select an image file to upload.'; 
		} 
		  ?>
			<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button>
				<?php echo $statusMsg?>...
			</div>
		<?php       
	}
	
	if(isset($_POST['btnDelete'])){
		$dcsid=$_POST['colid'];
		$dstatus=$_POST['dstatus'];
		
		
		if($dstatus=='Active'){
			$msg="Delete Failed! learner is still Active";
		}else{
			$msg="Success! Selected learner was deleted successfully";
			$qryDelete="Delete from students where csid=".$dcsid."";
			
			$dbcon->query($qryDelete);
		}
		?>
		<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<?php echo $msg;?>
		</div>
		<?php
	}
	if(isset($_POST['btnUpdate'])){
		//if(isset($_POST['echkStatus'])){$Status="Active";}else{$Status="Inactive";}
		$estudentid=$_POST['estudentid'];		
		$ecsid=$_POST['ecsid'];		
		$eglevel=$_POST['egrdlevel'];
		$eprg=$_POST['offering'];
		$edept=$_POST['edept'];
		$eprg=$_POST['offering'];
		$efn=$_POST['etxtfn'];
		$emn=$_POST['etxtmn'];
		$eln=$_POST['etxtln'];
		$estat=$_POST['sstat'];
		$emobile=$_POST['etxtmobile'];
		$eaddress=$_POST['etxtaddress'];
	
		$strUpdate="Update students set studentid='".$estudentid."', cid=".$eprg.", gid=".$eglevel.", 
							fname='".$efn."', mname='".$emn."', lname='".$eln."', 
							address='".$eaddress."', mobile='".$emobile."', remarks='".$estat."',
							did=".$edept." 
					where csid=".$ecsid."";
		
		//echo $strUpdate;
		$execQuery=$dbcon->query($strUpdate);
		if($execQuery){
		?>
			<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Congratualation! Selected student  was updated successfully.
			</div>
		<?php
		}else{
		?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Error! Student details value error.
			</div>
		<?php
		}
				
	}
	
	?>
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
					
					<i class="icon-list"></i> List of Senior High School Learners for the School Year -<strong> <?php echo $s;?>  | Semester : <?php echo $cssemester;?></strong>
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr>
									<th width="6%">#</th>
									<!--<th width="10%">Semester</th>-->
									<th width="15%">ID Picture</th>
									<th width="65%">Learners Details</th>									
									<th width="14%">Action</th>
									
								</tr>
							</thead>
							<tbody>
							<?php 
							$counter=1;
							$temp="";
							//echo $strQry;
							$qryRes=$dbcon->query($strQry);
							while($qryData=$qryRes->fetch_assoc()){
								$colid=$qryData['csid'];
								//$schoolyear=$qryData['syname'];
								//$sem=$qryData['semester'];
								$studentid=$qryData['studentid'];
								$semid=$qryData['sid'];
								$offid=$qryData['cid'];
								$program=$qryData['program'];
								$glevel=$qryData['glevel'];
								$mobile=$qryData['mobile'];
								$address=$qryData['address'];
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
										<p><h4><?php echo $lname;?></h4>
											Student ID     : <?php echo $studentid;?> <br/>
											Course     : <?php echo $program;?> <br/>
											Year Level : <?php echo $glevel;?> <br />
											Mobile Nuber : <?php echo $mobile;?> <br />
											Address : <?php echo $address;?> <br />
											Academic Status : <?php echo $status;?>
										</p>
									</td>										
									<td>
										<a class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"  data-toggle="modal"
											data-target="#update<?php echo $colid;?>" title="Edit Learners Details">
											<i class="icon-edit"></i>
										</a>
										<a class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"  data-toggle="modal"
											data-target="#upload<?php echo $colid;?>" title="Upload Learners ID/Picture">
											<i class="icon-user"></i>
										</a>
										<a class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold"  data-toggle="modal"
											data-target="#delete<?php echo $colid;?>" title="Delete learner">
											<i class="icon-trash"></i>
										</a>
									</td>
									<!-- Upload picture -->									
									<div class="modal-overlay" id="upload<?php echo $colid;?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
										<form role="form" method="post" enctype="multipart/form-data"> 
											<div class="modal-dialog">
												<div class="modal-content">
													<div class="modal-header flex items-center justify-between rounded-t-xl bg-amber-600 px-6 py-4 text-white">
														<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
														<h4 class="modal-title" id="H2"><i class="icon-tasks"></i> Upload Student ID/Picture </h4>
													</div>													
													<div class="modal-body">															
														<div class="space-y-4">
															<label class="control-label">Learners Picture</label>																
															<input type="hidden" name="ucsid" value="<?php echo $colid;?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" />
															<input type="file" name="image"  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" />
														</div>																
													</div>
													
													<div class="modal-footer">
														<a href="#" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</a>
														<button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold" name="btnUpload"> <i class="icon-upload"></i> Update Picture</button>											
													</div>
												</div>
											</div>
										</form>
									</div>										
									<!-- end upload picture -->
									<!--Edit -->										
									<div id="update<?php echo $colid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
										<form method="post" role="form">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header flex items-center justify-between rounded-t-xl bg-green-600 px-6 py-4 text-white">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close">
													<span aria-hidden="true">&times;</span>
												  </button>
												  <h4 class="modal-title"><i class="icon-edit"></i> Edit Learner Data</h4>
												  
												</div>
												<div class="modal-body">
													<?php
													$qry="SELECT cs.studentid,cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
																  (select syname from sy where syid=cs.syid) as syname,
																  (select semester from sem where sid=cs.sid) as semester,
																  (select program from offerings where cid=cs.cid) as program,
																  (select glevel from gradelevel where gid=cs.gid) as glevel,
																  (select department from departments where did=cs.did) as department,
																		(select remark from status where sid=cs.remarks) as status,
																  cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks
															FROM students cs
															where cs.csid=".$colid."";
													$sRes=$dbcon->query($qry);
													$sData=$sRes->fetch_assoc();					
													$ecsid=$sData['csid'];			
													$estudentid=$sData['studentid'];			
													$esyid=$sData['syid'];			
													$esyname=$sData['syname'];			
													$esid=$sData['sid'];			
													$esemester=$sData['semester'];			
													$edid=$sData['did'];			
													$edepartment=$sData['department'];			
													$ecid=$sData['cid'];			
													$eprogram=$sData['program'];			
													$egid=$sData['gid'];			
													$eglevel=$sData['glevel'];		
													$efname=$sData['fname'];		
													$emname=$sData['mname'];		
													$elname=$sData['lname'];		
													$emobile=$sData['mobile'];		
													$eaddress=$sData['address'];		
													$estatid=$sData['remarks'];		
													$estatus=$sData['status'];		
													
													?>													
													
													<div class="grid gap-6 md:grid-cols-12">
														<div class="md:col-span-4">
															<div class="space-y-4">
																<label>First Name *</label>
																<input type="textbox" name="etxtfn" value="<?php echo $efname;?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Juan Jr.">
																<input type="hidden" name="ecsid" value="<?php echo $colid;?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
															</div>
														</div>
														<div class="md:col-span-4">
															<div class="space-y-4">
																<label>Middle Name</label>
																<input type="textbox" name="etxtmn" value="<?php echo $emname;?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Dimaano">
															</div>
														</div>
														<div class="md:col-span-4">
															<div class="space-y-4">
																<label>Last Name *</label>
																<input type="textbox" name="etxtln" value="<?php echo $elname;?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Dela Cruz">
															</div>
														</div>
													
													
													</div>
													
													<div class="grid gap-6 md:grid-cols-12">
														<div class="md:col-span-4">
															<label>Student ID</label>
															<input type="textbox" name="estudentid" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $estudentid;?>" required>
														</div>
														<div class="md:col-span-4">
															<div class="space-y-4">
																<label>Department</label>
																<select name="edept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" id="dept">
																	<option value="<?php echo $edid;?>"><?php echo $edepartment;?></option>
																	<?php 
																		$dstr="Select did, department from departments where did!=".$edid."";
																		$depRes=$dbcon->query($dstr);
																		while($dData=$depRes->fetch_assoc()){
																			$ddid=$dData['did'];
																			$dDept=$dData['department'];
																			?>
																			<option value="<?php echo $ddid;?>"><?php echo $dDept;?></option>
																			
																		<?php	
																		}
																		
																	?>
																</select>
															</div>
														</div>
														<div class="md:col-span-4">
															<div class="space-y-4">
																<label>Grade Level</label>
																<select name="egrdlevel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" >
																	<option value="<?php echo $egid;?>"><?php echo $eglevel;?></option>
																	<?php 
																	
																		$gstr="SELECT gid, glevel,did FROM gradelevel where did=".$edid." and gid!=".$egid."";
																		$gRes=$dbcon->query($gstr);
																		while($gData=$gRes->fetch_assoc()){
																			$ggid=$gData['gid'];
																			$gglevel=$gData['glevel'];
																			?>
																			<option value="<?php echo $ggid;?>"><?php echo $gglevel;?></option>
																			<?php									
																		}
																		
																	?>
																</select>
															</div>
														</div>
													</div>
													<div class="grid gap-6 md:grid-cols-12">
														<div class="md:col-span-8">
															<div class="space-y-4">
																<label>Program Name</label>																
																<select name="offering" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" >
																<option value="<?php echo $ecid;?>"><?php echo $eprogram;?></option>
																	<?php 
																		$pstr="SELECT cid, did, program FROM offerings 
																			where did=".$edid." and cid!=".$ecid."";																		
																		$pRes=$dbcon->query($pstr);
																		while($pData=$pRes->fetch_assoc()){
																			$ocid=$pData['cid'];
																			$oprogram=$pData['program'];
																			?>
																			<option value="<?php echo $ocid;?>"><?php echo $oprogram;?></option>
																			<?php									
																		}
																	?>
																</select>																
															</div>
														</div>
														
														<div class="md:col-span-4">
															<div class="space-y-4">
																<label>Academic Status</label>
																<select name="sstat" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" >
																	<option value="<?php echo $estatid;?>"><?php echo $estatus;?></option>
																	<?php 
																		$sstr="SELECT sid, remark FROM status where sid!=".$estatid."";
																		$sRes=$dbcon->query($sstr);
																		while($sData=$sRes->fetch_assoc()){
																			$ssid=$sData['sid'];
																			$sstatus=$sData['remark'];
																			?>
																			<option value="<?php echo $ssid;?>"><?php echo $sstatus;?></option>
																			<?php									
																		}
																	?>
																</select>
															</div>
														</div>
													</div>
													<div class="grid gap-6 md:grid-cols-12">						
														<div class="md:col-span-4">
															<div class="space-y-4">
																<label>Mobile Number</label>
																<input type="textbox" name="etxtmobile" value="<?php echo $emobile;?>" data-mask="+63 999 999 9999" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Mobile Number">
															</div>
														</div>
														<div class="md:col-span-8">
															<div class="space-y-4">
																<label>Address *</label>
																<input type="textbox" name="etxtaddress" value="<?php echo $eaddress;?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Address">
															</div>
														</div>
													</div>
													
												</div>
												<div class="modal-footer">
												  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
												  <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Update Learner</button>
												</div>
											</div>
										</div>
										</form>
									</div>
									<!-- end Update --->
									
									<div id="delete<?php echo $colid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
										<form method="post" role="form">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header flex items-center justify-between rounded-t-xl bg-green-600 px-6 py-4 text-white">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close">
													<span aria-hidden="true">&times;</span>
												  </button>
												  <h4 class="modal-title"><i class="icon-edit"></i> Delete Learner Data</h4>
												  
												</div>
												<div class="modal-body">
													<?php
													$qry="SELECT cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
																  (select syname from sy where syid=cs.syid) as syname,
																  (select semester from sem where sid=cs.sid) as semester,
																  (select program from offerings where cid=cs.cid) as program,
																  (select glevel from gradelevel where gid=cs.gid) as glevel,
																  (select department from departments where did=cs.did) as department,
																		(select remark from status where sid=cs.remarks) as status,
																  cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks
															FROM students cs
															where cs.csid=".$colid." and cs.did=".$dept."";
													$sRes=$dbcon->query($qry);
													$sData=$sRes->fetch_assoc();					
													$dcsid=$sData['csid'];			
													$edepartment=$sData['department'];			
													$ecid=$sData['cid'];			
													$dprogram=$sData['program'];			
																
													$dglevel=$sData['glevel'];		
													$dlname=$sData['fname']. " ". $sData['mname'] . " " . $sData['lname'];
													$dmobile=$sData['mobile'];		
													$daddress=$sData['address'];
													$dstatus=$sData['status'];		
													
													?>													
													
													<div class="grid gap-6 md:grid-cols-12">
														<div class="md:col-span-4">
															<input type="hidden" name="colid" value="<?php echo $colid;?>">
															<input type="hidden" name="dstatus" value="<?php echo $dstatus;?>">
															<img src="../img/delete.jpg" width="60%" height="60%">
														</div>
														<div class="md:col-span-8">
															<p><h4><?php echo $dlname;?></h4>
																Course     : <?php echo $dprogram;?> <br/>
																Year Level : <?php echo $dglevel;?> <br />
																Mobile Nuber : <?php echo $dmobile;?> <br />
																Address : <?php echo $daddress;?> <br />
																Academic Status : <?php echo $dstatus;?>
															</p>
														</div>
														
													</div>
												
												</div>
												
												<div class="modal-footer">
													<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
													<button type="submit" name="btnDelete" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Delete Learner</button>
												</div>
											</div>
										</div>
										</form>
									</div>
									<!--Delete -->										
									<div id="delete<?php echo $colid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
										<form method="post" role="form">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header flex items-center justify-between rounded-t-xl bg-green-600 px-6 py-4 text-white">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close">
													<span aria-hidden="true">&times;</span>
												  </button>
												  <h4 class="modal-title"><i class="icon-edit"></i> Delete Learner Data</h4>
												  
												</div>
												<div class="modal-body">
													<?php
													$qry="SELECT cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
																  (select syname from sy where syid=cs.syid) as syname,
																  (select semester from sem where sid=cs.sid) as semester,
																  (select program from offerings where cid=cs.cid) as program,
																  (select glevel from gradelevel where gid=cs.gid) as glevel,
																  (select department from departments where did=cs.did) as department,
																		(select remark from status where sid=cs.remarks) as status,
																  cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks
															FROM shsstudents cs
															where cs.csid=".$colid."";
													$sRes=$dbcon->query($qry);
													$sData=$sRes->fetch_assoc();					
													$dcsid=$sData['csid'];			
													$edepartment=$sData['department'];			
													$ecid=$sData['cid'];			
													$dprogram=$sData['program'];			
																
													$dglevel=$sData['glevel'];		
													$dlname=$sData['fname']. " ". $sData['mname'] . " " . $sData['lname'];
													$dmobile=$sData['mobile'];		
													$daddress=$sData['address'];
													$dstatus=$sData['status'];		
													
													?>													
													
													<div class="grid gap-6 md:grid-cols-12">
														<div class="md:col-span-4">
															<input type="hidden" name="colid" value="<?php echo $colid;?>">
															<input type="hidden" name="dstatus" value="<?php echo $dstatus;?>">
															<img src="../img/delete.jpg" width="60%" height="60%">
														</div>
														<div class="md:col-span-8">
															<p><h4><?php echo $dlname;?></h4>
																Course     : <?php echo $dprogram;?> <br/>
																Year Level : <?php echo $dglevel;?> <br />
																Mobile Nuber : <?php echo $dmobile;?> <br />
																Address : <?php echo $daddress;?> <br />
																Academic Status : <?php echo $dstatus;?>
															</p>
														</div>
													</div>
												</div>
												<div class="modal-footer">
													<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
													<button type="submit" name="btnDelete" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Delete Learner</button>
												</div>
											</div>
										</div>
										</form>
									</div>
									<!-- end delete --->
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
	
	
	<div class="modal-overlay" id="formModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<form role="form" method="post" id="inline-validate">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header flex items-center justify-between rounded-t-xl bg-green-600 px-6 py-4 text-white">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="H2"><i class="icon-user"></i>  Add Learner  for the SY : <?php echo $s . " | ".$cssemester;?> </h4>
					</div>
					<div class="modal-body">
						<!--
						<div class="grid gap-6 md:grid-cols-12">
							<div class="md:col-span-4">
								<div class="space-y-4">
									<label>School Year</label>
									<select name="syid" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
										<?php 
											$strSy="SELECT syid, syname, status FROM sy where status='Active'";
											$syRes=$dbcon->query($strSy);
											$syData=$syRes->fetch_assoc();
											$syid=$syData['syid'];
											$syname=$syData['syname'];
											$status=$syData['status'];
											?>
											<option value="<?php echo $syid;?>"><?php echo $syname;?></option>
											<?php
										?>
									</select>
								</div>
							</div>
							<div class="md:col-span-8">
								<div class="space-y-4">
									<label>Semester</label>
									<select name="sem" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" >
										<?php 
											$str="SELECT sid, semester FROM sem where sid=".$cssid." and status='Active'";
											$qryRes=$dbcon->query($str);
											while($qryData=$qryRes->fetch_assoc()){
												$sid=$qryData['sid'];
												$semester=$qryData['semester'];
												?>
												<option value="<?php echo $sid;?>"><?php echo $semester;?></option>
												<?php									
											}
										?>
									</select>
								</div>
							</div>
						</div>
						-->
						<div class="grid gap-6 md:grid-cols-12">
							<div class="md:col-span-4">
								<div class="space-y-4">
									<label>First Name *</label>
									<input type="textbox" name="txtfn" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Juan Jr.">
								</div>
							</div>
							<div class="md:col-span-4">
								<div class="space-y-4">
									<label>Middle Name</label>
									<input type="textbox" name="txtmn" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Dimaano">
								</div>
							</div>
							<div class="md:col-span-4">
								<div class="space-y-4">
									<label>Last Name *</label>
									<input type="textbox" name="txtln" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Dela Cruz">
								</div>
							</div>
						</div>
						
						<div class="grid gap-6 md:grid-cols-12">						
							<div class="md:col-span-4">
								<div class="space-y-4">
									<label>Student ID#</label>
									<input type="textbox" name="txtid"  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Id Number" required>
								</div>
							</div>
							<div class="md:col-span-4">
								<div class="space-y-4">
									<label>Mobile Number</label>
									<input type="textbox" name="txtmobile"  data-mask="+63 999 999 9999" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Mobile Number">
								</div>
							</div>						
							<div class="md:col-span-4">
								<div class="space-y-4">
									<label>Learners Status</label>
									<select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
										<?php 
											$qry="SELECT sid, remark FROM status";
											$qRes=$dbcon->query($qry);
											while($qData=$qRes->fetch_assoc()){
												$sid=$qData['sid'];
												$remark=$qData['remark'];
											?>
											<option value="<?php echo $sid;?>"><?php echo $remark;?></option>
											<?php
											}
										?>
									</select>
								</div>
							</div>						
						</div>
						
						<div class="grid gap-6 md:grid-cols-12">
							<div class="md:col-span-4">
								<input type="hidden" name="sem" value="<?php echo $cssid;?>">
								<input type="hidden" name="syid" value="<?php echo $csyid;?>">
								<div class="space-y-4">
									<label>Department</label>
									<select name="dept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" id="dept">
										<?php 
											$str="Select did, department from departments where did=".$dept."";
											$qryRes=$dbcon->query($str);
											while($qryData=$qryRes->fetch_assoc()){
												$did=$qryData['did'];
												$department=$qryData['department'];
												?>
												<option value="<?php echo $did;?>"><?php echo $department;?></option>
												<?php									
											}
										?>
									</select>
								</div>
							</div>
							<div class="md:col-span-8">
								<div class="space-y-4">
									<label>Program Name</label>
									<select name="offering" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" >
										<?php 
											$str="SELECT d.did, o.cid,o.program
												FROM departments d left join offerings o on o.did=d.did
												where d.did=".$dept."";
											$qryRes=$dbcon->query($str);
											while($qryData=$qryRes->fetch_assoc()){
												$ecid=$qryData['cid'];
												$program=$qryData['program'];
												?>
												<option value="<?php echo $ecid;?>"><?php echo $program;?></option>
												<?php									
											}
										?>
									</select>
								</div>
							</div>
						</div>
						<div class="grid gap-6 md:grid-cols-12">
							<div class="md:col-span-4">
								<div class="space-y-4">
									<label>Grade Level</label>
									<select name="grdlevel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" >
										<?php 
											$str="SELECT g.gid, g.glevel,d.did
												FROM gradelevel g left join departments d on d.did=g.did
												Where d.did=".$dept."";
											$qryRes=$dbcon->query($str);
											while($qryData=$qryRes->fetch_assoc()){
												$gid=$qryData['gid'];
												$glevel=$qryData['glevel'];
												?>
												<option value="<?php echo $gid;?>"><?php echo $glevel;?></option>
												<?php									
											}
										?>
									</select>
								</div>
							</div>
							<div class="md:col-span-8">
								<div class="space-y-4">
									<label>Address *</label>
									<input type="textbox" name="txtaddress" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Address">
								</div>							
							</div>
						</div>						
						
					</div>
					<div class="modal-footer">
						<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
						<button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Save Learner</button>
					</div>
				</div>
			</div>
		</form>
	</div>
	
</div>
<script src="../../assets/plugins/jquery-2.0.3.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
<script>

$("#dept").keydown(function(event){	
	
	var d = document.getElementById('dept').value;	
	//alert(d);			

	//$("#rm").load("admin/manageschedule/isroom.php?id="+id+"&sTime="+sTime+"&eTime="+eTime+"&m="+m+"&t="+t+"&w="+w+"&th="+th+"&f="+f+"&s="+s+"&subjectid="+subjectid);
	
});


</script>


