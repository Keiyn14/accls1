
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
	<link rel="icon" href="../img/logo.ico" type="image/x-icon">
	<!-- refresh page every 3 seconds
	<meta http-equiv="refresh" content="3"> 
	-->
     <!--[if IE]>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <![endif]-->
    <!-- GLOBAL STYLES -->
 <!-- GLOBAL STYLES -->
    <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../assets/css/main.css" />
    <link rel="stylesheet" href="../assets/css/theme.css" />
    <link rel="stylesheet" href="../assets/css/MoneAdmin.css" />
    <link rel="stylesheet" href="../assets/plugins/Font-Awesome/css/font-awesome.css" />
    <!--END GLOBAL STYLES -->

    <!-- PAGE LEVEL STYLES -->
		<script src="../assets/js/jquery-3.7.1.js"></script>
	<script src="../assets/js/jquery-3.7.1.min.js"></script>
	
    <link href="../assets/plugins/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
	  <link rel="stylesheet" href="../assets/plugins/validationengine/css/validationEngine.jquery.css" />
	
	<link href="../assets/css/jquery-ui.css" rel="stylesheet" />
	<link rel="stylesheet" href="../assets/plugins/uniform/themes/default/css/uniform.default.css" />
	<link rel="stylesheet" href="../assets/plugins/inputlimiter/jquery.inputlimiter.1.0.css" />
	<link rel="stylesheet" href="../assets/plugins/chosen/chosen.min.css" />
	<link rel="stylesheet" href="../assets/plugins/colorpicker/css/colorpicker.css" />
	<link rel="stylesheet" href="../assets/plugins/tagsinput/jquery.tagsinput.css" />
	<link rel="stylesheet" href="../assets/plugins/daterangepicker/daterangepicker-bs3.css" />
	<link rel="stylesheet" href="../assets/plugins/datepicker/css/datepicker.css" />
	<link rel="stylesheet" href="../assets/plugins/timepicker/css/bootstrap-timepicker.min.css" />
	<link rel="stylesheet" href="../assets/plugins/switch/static/stylesheets/bootstrap-switch.css" />
    <!-- END PAGE LEVEL  STYLES -->
	
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

input[type="text"] {
  padding: 10px 5px; /* top/bottom padding, left/right padding */
  height:60px;
  width:800px;
  font-size:32px;
  border-radius:15px;
  text-align: center;
}
</style>
</head>
    <!-- END HEAD -->

    <!-- BEGIN BODY -->
<body >
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
	
	
	//ob_flush();//flush the current header
?>
<!-- PAGE CONTENT --> 
<div class="wrap">
	<nav>	
		<center><a href=""><img src="../img/logo1.jpg" height="80%" width="50%" alt="" /></a></center>
	</nav>
</div>

<br/>

<div class="content">
	<div class="row">
		<form method="post" role="form">
		<center>
		<h2>LEDGER SUMMARY FOR THE SCHOOL YEAR : <?php echo $sy;?> |  Semester : <?php echo $sem;?> </h2>
		<input type="text" name="txtId" id="txtId" class="form-control"  autofocus placeholder="Enter a valid student ID Number" >
		</center>
				
		<br/>
		<?php 
			if(isset($_POST['txtId'])){
				sleep(4);
			}
		?>
		<div id="dispArea">
			
		</div>		
		</form>
	</div>
</div>

<script>
$(document).ready(function(){  
	 $("#txtId").change(function() {	
		/*
		setTimeout(function(){
		$('#lrn').addClass("done");
		}, 5000);
		*/
		var txtid=document.getElementById('txtId').value;
		//alert(txtid);
		$("#dispArea").load("studs.php?txtid="+txtid);
		
	});
	
}); 


</script>

</br>
<div id="footer">
	<p>&copy;  Amando Cope College &nbsp;2025 &nbsp;</p>
</div>
<!--END FOOTER -->
<!-- GLOBAL SCRIPTS -->
    <script src="../assets/plugins/jquery-2.0.3.min.js"></script>
     <script src="../assets/plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="../assets/plugins/modernizr-2.6.2-respond-1.1.0.min.js"></script>
    <!-- END GLOBAL SCRIPTS -->	

    <!-- PAGE LEVEL SCRIPTS -->
    <script src="../assets/plugins/dataTables/jquery.dataTables.js"></script>
    <script src="../assets/plugins/dataTables/dataTables.bootstrap.js"></script>

	

	<script src="../assets/js/jquery-ui.min.js"></script>
	<script src="../assets/plugins/uniform/jquery.uniform.min.js"></script>
	<script src="../assets/plugins/inputlimiter/jquery.inputlimiter.1.3.1.min.js"></script>
	<script src="../assets/plugins/chosen/chosen.jquery.min.js"></script>
	<script src="../assets/plugins/colorpicker/js/bootstrap-colorpicker.js"></script>
	<script src="../assets/plugins/tagsinput/jquery.tagsinput.min.js"></script>
	<script src="../assets/plugins/validVal/js/jquery.validVal.min.js"></script>
	<script src="../assets/plugins/daterangepicker/daterangepicker.js"></script>
	<script src="../assets/plugins/daterangepicker/moment.min.js"></script>
	<script src="../assets/plugins/datepicker/js/bootstrap-datepicker.js"></script>
	<script src="../assets/plugins/timepicker/js/bootstrap-timepicker.min.js"></script>
	<script src="../assets/plugins/switch/static/js/bootstrap-switch.min.js"></script>
	<script src="../assets/plugins/jquery.dualListbox-1.3/jquery.dualListBox-1.3.min.js"></script>
	<script src="../assets/plugins/autosize/jquery.autosize.min.js"></script>
	<script src="../assets/plugins/jasny/js/bootstrap-inputmask.js"></script>	
	<script src="../assets/js/formsInit.js"></script>
	
	 <script src="../assets/plugins/validationengine/js/jquery.validationEngine.js"></script>
    <script src="../assets/plugins/validationengine/js/languages/jquery.validationEngine-en.js"></script>
    <script src="../assets/plugins/jquery-validation-1.11.1/dist/jquery.validate.min.js"></script>
    <script src="../assets/js/validationInit.js"></script>
    <script>
        $(function () { formValidation(); });
    </script>
    <script>
         $(function(){ formInit();});
	 </script>
	  <script>
         $(document).ready(function () {
             $('#dataTables-example').dataTable();
         });
    </script>
     <!-- END PAGE LEVEL SCRIPTS -->

</body>
    <!-- END BODY -->
</html>
