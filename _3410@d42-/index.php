
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->
<!--[if !IE]><!--> <html lang="en"> <!--<![endif]-->

<!-- BEGIN HEAD -->
<head>
     <meta charset="UTF-8" />
    <title>ACC -[ Ledger ]- System v.1</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
	<meta content="" name="description" />
	<meta content="" name="author" />
     <!--[if IE]>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <![endif]-->
    <!-- GLOBAL STYLES -->
     <!-- PAGE LEVEL STYLES -->
     <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../assets/css/login.css" />
    <link rel="stylesheet" href="../assets/plugins/magic/magic.css" />
	
	 
    <link rel="stylesheet" href="../assets/css/main.css" />
    <link rel="stylesheet" href="../assets/css/theme.css" />
    <link rel="stylesheet" href="../assets/css/MoneAdmin.css" />
    <link rel="stylesheet" href="../assets/plugins/Font-Awesome/css/font-awesome.css" />
     <!-- END PAGE LEVEL STYLES -->
   <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
<style>
nav{
	background-color:rgb(255,255,255);
	padding:15px 20px;
	box-shadow:0 0 10px 3px rgb(216, 215, 215);
}
body{
	padding-top:0px;
}
</style>
</head>
    <!-- END HEAD -->

    <!-- BEGIN BODY -->
<body >
<?php
include "../inc/functions.php";
include "../inc/mysqli_connect.php";
session_start();
?>
<!-- PAGE CONTENT --> 
<div class="wrap">
	<nav>	
		<center><a href=""><img src="../img/logo1.jpg" height="80%" width="50%" alt="" /></a></center>
	</nav>

    <div class="text-center"> 
	
		<div class="tab-content">
			<?php 
	if(isset($_POST["btnLogIn"])){
		$usrNme=addslashes($_POST['txtname']);
		$usrPWrd=addslashes($_POST['txtpass']);
			if($usrNme=="" || $usrPWrd==""){
				
			?>
			<div class="alert alert-warning alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<i class="icon-exclamation-sign"></i> User name or password can not be empty!
			</div>			
			<?php
			}else if($usrNme!="" || $usrPWrd!=""){	
				$qryUsers="SELECT nameuser, passname, role FROM users 
						where nameuser='".$usrNme."' and passname='".$usrPWrd."'";
				$accRess=$dbcon->query($qryUsers);
				$recCount=$accRess->num_rows;
				
				//
				if($recCount>0){
					//echo $recCount;
					$accData=$accRess->fetch_assoc();
					$nameuser=$accData['nameuser'];
					$passname=$accData['passname'];
					$role=$accData['role'];					
					//echo $role;
					if($role=="1"){
						header("Location:".accls().'/_&ans@fm1');						
					}elseif($role=="2"){
						header("Location:".accls().'/_&cns@fm2');
					}
				}else{
					//students
					
				}
				
				
				
				
				
			
			}
	}
	
	?>
			<div id="login" class="tab-pane active">
				<form  role="form" class="form-signin" method="post">
					<p class="text-muted text-center btn-block btn btn-primary btn-rect">
						<i class="icon-key"></i> Security Portal
					</p>
					<input type="text" name="txtname" placeholder="Username" class="form-control" />
					<input type="password" name="txtpass" placeholder="Password" class="form-control" />
					<button name="btnLogIn" class="btn text-muted text-center btn-block btn-danger" type="submit"><i class="icon-user"></i>  Sign in</button>
				</form>
			</div>
		</div>
    </div>

</div>

	  <!--END PAGE CONTENT -->   

<!-- FOOTER -->
</br>
</br>
</br>
<div id="footer">
	<p>&copy;  Amando Cope College &nbsp;2025 &nbsp;</p>
</div>
<!--END FOOTER -->

	  
	      
      <!-- PAGE LEVEL SCRIPTS -->
      <script src="../assets/plugins/jquery-2.0.3.min.js"></script>
      <script src="../assets/plugins/bootstrap/js/bootstrap.js"></script>
   <script src="../assets/js/login.js"></script>
      <!--END PAGE LEVEL SCRIPTS -->
    <script src="../assets/plugins/modernizr-2.6.2-respond-1.1.0.min.js"></script>

</body>
    <!-- END BODY -->
</html>
