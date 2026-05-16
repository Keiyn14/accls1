<br />
<div class="inner">
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<!--<h3> Manage School Year </h3> -->
		
			<button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold" data-toggle="modal" data-target="#formModal">
                  <i class="icon-plus"> </i>Add Grade Level
             </button>
		</div>
	</div>
	<hr >
	<?php 
	if(isset($_POST['btnAdd'])){
		$glevel=$_POST['txtglevel'];
		$dept=$_POST['dept'];
		
		if($glevel==""){
			?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! Grade Level value cannot be empty.
			</div>
			<?php
		}else{
			//check if entry already exist
			$strCheck="Select glevel from gradelevel where glevel='".$glevel."'";
			if(ifExist($dbcon,$strCheck)){
				?>
				<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					Warning! Grade level value already exist.
				</div>
			<?php
			}else{		
				$strInsert="Insert into gradelevel (glevel,did) values ('".$glevel."',".$dept.")";
				$execQuery=$dbcon->query($strInsert);
				if($execQuery){
				?>
					<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Congratualation! New Grade level was added successfully.
					</div>
				<?php
				}else{
				?>
					<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Error! Program offering value error.
					</div>
				<?php
				}
			}
		}
	}
	if(isset($_POST['btnUpdate'])){
		$ugid=$_POST['ugid'];
		$uglevel=$_POST['uglevel'];
		$udept=$_POST['udept'];
		
		if($uglevel==""){
			?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Warning! Grade Level value cannot be empty.
			</div>
			<?php
		}else{
				$strUpdate="Update gradelevel set glevel='" .$uglevel."',did=".$udept." where gid=".$ugid."";
				$execQuery=$dbcon->query($strUpdate);
				if($execQuery){
				?>
					<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Congratualation! Selected grade level was updated successfully.
					</div>
				<?php
				}else{
				?>
					<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						Error! Grade level value error.
					</div>
				<?php
				}
		}		
	}
	if(isset($_POST['btnDelete'])){
		$dgid=$_POST['dgid'];
		$dglevel=$_POST['dglevel'];
		$strDelete="delete from gradelevel where gid=".$dgid."";
		$execQuery=$dbcon->query($strDelete);
		if($execQuery){
		?>
			<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Congratualation! Selected grade level was deleted successfully.
			</div>
		<?php
		}else{
		?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Error! Grade level value error.
			</div>
		<?php
		}	
	}
	?>
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
					Grade Levels
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr>
									<th width="10%">#</th>
									<th width="30%">Grade Level</th>
									<th width="40%">Department</th>
									<th width="20%">Action</th>
									
								</tr>
							</thead>
							<tbody>
							<?php 
							$counter=1;
							$strQry="SELECT g.gid, g.glevel,d.department,d.did FROM gradelevel g left join departments d on d.did=g.did";
							$qryRes=$dbcon->query($strQry);
							while($qryData=$qryRes->fetch_assoc()){
								$egid=$qryData['gid'];
								$eglevel=$qryData['glevel'];
								$edept=$qryData['department'];
								$edid=$qryData['did'];
								?>
									<tr>
										<td><?php echo $counter;?></td>
										<td><?php echo $eglevel;?></td>
										<td><?php echo $edept;?></td>
										<td>
											<a class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold"  data-toggle="modal"
												data-target="#update<?php echo $egid."".$edid;?>" title="Edit Grade Level">
												<i class="icon-edit"> Edit</i>
											</a>
											<a class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold"  data-toggle="modal"
												data-target="#delete<?php echo $egid;?>" title="Remove Grade Level">
												<i class="icon-trash"> Remove</i>
											</a>
										</td>									
										<!-- Edit -->										
										<div id="update<?php echo $egid."".$edid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
											<form method="post" role="form">
											<div class="modal-dialog">
												<div class="modal-content">
													<div class="modal-header">
													  <h4 class="modal-title"><i class="fa fas fa-list"></i> Grade Level</h4>
													  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													  </button>
													</div>
													<div class="modal-body">
														<?php
														$qrySem="SELECT gid, glevel FROM gradelevel 
																WHERE gid=".$egid."";
														$sRes=$dbcon->query($qrySem);
														$sData=$sRes->fetch_assoc();					
														$ugid=$sData['gid'];			
														$uglevel=$sData['glevel'];			
														?>
														<div class="space-y-4">
															<label class="control-label">Grade Level</label>
															<input type="hidden" name="ugid" value="<?php echo $ugid;?>">
															<input type="textbox" name="uglevel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $uglevel;?>" placeholder="School Year" required>
														</div>
														<div class="space-y-4">
															<label>Department</label>
															<select name="udept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
																
																<?php 
																	$qry="Select did, department from departments";
																	$qRes=$dbcon->query($qry);
																	while($qData=$qRes->fetch_assoc()){
																		$did=$qData['did'];
																		$department=$qData['department'];
																	?>
																	<option value="<?php echo $did;?>"><?php echo $department;?></option>
																	<?php
																	}
																	
																?>
															</select>
														</div>
													</div>
													<div class="modal-footer">
													  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
													  <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Update Grade Level</button>
													</div>
												</div>
											</div>
											</form>
										</div>
										<!--  --->
										
										<!-- Delete -->										
										<div id="delete<?php echo $egid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
											<form method="post" role="form">
											<div class="modal-dialog">
												<div class="modal-content">
													<div class="modal-header">
													  <h4 class="modal-title"><i class="fa fas fa-list"></i> Grade Level</h4>
													  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													  </button>
													</div>
													<div class="modal-body">
														<?php
														$qryDelete="SELECT gid, glevel FROM gradelevel 
																WHERE gid=".$egid."";
														$dRes=$dbcon->query($qryDelete);
														$dData=$dRes->fetch_assoc();					
														$dgid=$sData['gid'];			
														$dglevel=$sData['glevel'];			
														?>
														<div class="space-y-4">
															<label class="control-label">Grade Level</label>
															<input type="hidden" name="dgid" value="<?php echo $dgid;?>">
															<input type="textbox" name="dglevel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $dglevel;?>" placeholder="School Year" required>
														</div>
													</div>
													<div class="modal-footer">
													  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
													  <button type="submit" name="btnDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Remove Grade Level</button>
													</div>
												</div>
											</div>
											</form>
										</div>
										<!--------- Remove Program ------>
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
		<form role="form" method="post">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="H2">Grade Level</h4>
					</div>
					<div class="modal-body">					
						<div class="space-y-4">
							<label>Grade Level name</label>
							<input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" name="txtglevel" type="text" placeholder="Grade Level"/>
						</div>
						<div class="space-y-4">
							<label>Department</label>
							<select name="dept" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
								<?php 
									$qry="Select did, department from departments";
									$qRes=$dbcon->query($qry);
									while($qData=$qRes->fetch_assoc()){
										$did=$qData['did'];
										$department=$qData['department'];
									?>
									<option value="<?php echo $did;?>"><?php echo $department;?></option>
									<?php
									}
									
								?>
							</select>
						</div>
					</div>
					<div class="modal-footer">
						<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
						<button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Save Grade Level</button>
					</div>
				</div>
			</div>
		</form>
	</div>
	
</div>
  


