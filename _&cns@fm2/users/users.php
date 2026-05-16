<br />


<div class="inner">
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<!--<h3> Manage School Year </h3> -->
		
			<button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold" data-toggle="modal" data-target="#formModal">
                  <i class="icon-plus"> </i>Add User Role
             </button>
		</div>
	</div>
	<hr >
	<?php 
	if(isset($_POST['btnAdd'])){
		$nameuser=$_POST['nameuser'];
		$passname=$_POST['passname'];
		$role=$_POST['role'];
		
		$strInsert="Insert into users (nameuser, passname, role) values ('".$nameuser."','".$passname."',".$role.")";
		$execQuery=$dbcon->query($strInsert);
		if($execQuery){
		?>
			<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Congratualation! New user was added successfully.
			</div>
		<?php
		}else{
		?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Error! entry value error.
			</div>
		<?php
		}

	}
	if(isset($_POST['btnUpdate'])){
		$unameuser=$_POST['unameuser'];
		$upassname=$_POST['upassname'];
		$urole=$_POST['urole'];	
		$uuid=$_POST['uuid'];	
		$strUpdate="Update users set nameuser='".$unameuser."', passname='".$upassname."',role=".$urole." where uid=".$uuid."";				
		//echo $strUpdate;
		$execQuery=$dbcon->query($strUpdate);
		if($execQuery){
		?>
			<div class="bg-green-50 border-l-4 border-green-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Congratualation! Selected user was updated successfully.
			</div>
		<?php
		}else{
		?>
			<div class="bg-red-50 border-l-4 border-red-600 p-4 rounded mb-4">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				Error! user entry value error.
			</div>
		<?php
		}		
	}
	?>
	<div class="grid gap-6 md:grid-cols-12">
		<div class="md:col-span-12">
			<div class="bg-white rounded-xl shadow-lg overflow-hidden">
				<div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
					Manage System Users
				</div>
				<div class="p-6">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse" id="dataTables-example">
							<thead>
								<tr>
									<th width="10%">#</th>
									<th width="30%">user Name</th>
									<th width="30%">user Password</th>
									<th width="10%">user Role</th>
									<th width="20%">Action</th>
									
								</tr>
							</thead>
							<tbody>
							<?php 
							$counter=1;
							$strQry="SELECT u.uid, u.nameuser, u.passname, u.role,r.rname
									FROM users u inner join role r on r.rid=u.role";
							$qryRes=$dbcon->query($strQry);
							while($qryData=$qryRes->fetch_assoc()){
								$uid=$qryData['uid'];
								$nameuser=$qryData['nameuser'];
								$passname=$qryData['passname'];
								$rname=$qryData['rname'];
								
								?>
									<tr>
										<td><?php echo $counter;?></td>
										<td><?php echo $nameuser;?></td>
										<td><?php echo $passname;?></td>
										<td><?php echo $rname;?></td>
										
										<td>
											<a class="inline-flex justify-center w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"  data-toggle="modal"
												data-target="#update<?php echo $uid;?>" title="Edit User">
												<i class="icon-edit"> Edit</i>
											</a>
										</td>									
										<!-- school year -->
										
											<div id="update<?php echo $uid;?>" class="modal-overlay" tabindex="-1" role="dialog"  aria-hidden="true" style="display: none;">
												<form method="post" role="form">
												<div class="modal-dialog">
													<div class="modal-content">
														<div class="modal-header">
														  <h4 class="modal-title"><i class="fa fas fa-list"></i> Update user</h4>
														  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
															<span aria-hidden="true">&times;</span>
														  </button>
														</div>
														<div class="modal-body">
															<?php
															$qrySem="SELECT u.uid, u.nameuser, u.passname, u.role,r.rname
																	FROM users u inner join role r on r.rid=u.role
																	WHERE uid=".$uid."";
															$sRes=$dbcon->query($qrySem);
															$sData=$sRes->fetch_assoc();					
															$euid=$sData['uid'];			
															$enameuser=$sData['nameuser'];			
															$epassname=$sData['passname'];			
															$ername=$sData['rname'];			
															$erole=$sData['role'];			
															?>
															<div class="space-y-4">
																<label class="control-label">User Name</label>
																<input type="hidden" name="uuid" value="<?php echo $euid;?>">
																<input type="textbox" name="unameuser" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $enameuser;?>" placeholder="User Name" required>
															</div>
															<div class="space-y-4">
																<label class="control-label">User Password</label>																
																<input type="textbox" name="upassname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" value="<?php echo $epassname;?>" placeholder="User Password" required>
															</div>
															<div class="space-y-4">
																<label class="control-label">User Role</label>	
																	<select name="urole" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
																		<?php
																			$qry="SELECT rid, rname FROM role";
																			$sRes=$dbcon->query($qry);
																			while($sData=$sRes->fetch_assoc()){
																				$erid=$sData['rid'];			
																				$ername=$sData['rname'];
																			?>
																			<option value="<?php echo $erid;?>"><?php echo $ername;?></option>
																			<?php																			
																			}				
																		?>
																	</select>
																
															</div>
														</div>
														<div class="modal-footer">
														  <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
														  <button type="submit" name="btnUpdate" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Update Role</button>
														</div>
													</div>
												</div>
													</form>
											</div>
											<!--  --->
									
										
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
						<h4 class="modal-title" id="H2">Manage user role</h4>
					</div>
					<div class="modal-body">
					
						<div class="space-y-4">
							<label class="control-label">User Name</label>							
							<input type="textbox" name="nameuser" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"  placeholder="User Name" required>
						</div>
						<div class="space-y-4">
							<label class="control-label">User Password</label>																
							<input type="textbox" name="passname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"  placeholder="User Password" required>
						</div>
						<div class="space-y-4">
							<label class="control-label">User Role</label>	
								<select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
									<?php
										$qry="SELECT rid, rname FROM role";
												
										$sRes=$dbcon->query($qry);
										while($sData=$sRes->fetch_assoc()){
											$erid=$sData['rid'];			
											$ername=$sData['rname'];
										?>
										<option value="<?php echo $erid;?>"><?php echo $ername;?></option>
										<?php																			
										}				
									?>
								</select>
							
						</div>
					</div>
					<div class="modal-footer">
						<button type="reset" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold" data-dismiss="modal">Close</button>
						<button type="submit" name="btnAdd" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold"><i class="icon-save"></i> Save User</button>
					</div>
				</div>
			</div>
		</form>
	</div>
	
</div>
  



