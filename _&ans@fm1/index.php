
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>ACC - Ledger System v.2</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="description" content="ACC Ledger Management System" />
    <meta name="author" content="ACC" />
    <link rel="icon" href="../img/logo.ico" type="image/x-icon">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="../assets/plugins/Font-Awesome/css/font-awesome.css" />
    
    <!-- jQuery -->
    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables -->
    <link href="../assets/plugins/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
    
    <!-- Custom Tailwind Config -->
    <style>
        :root {
            --primary-green: #10b981;
            --dark-green: #059669;
            --light-green: #d1fae5;
        }
        
        * {
            scroll-behavior: smooth;
        }
        
        body {
            @apply bg-gray-50;
        }
        
        .sidebar-open #content {
            @apply ml-64;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.7);
            padding: 1.5rem;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-overlay .modal-content {
            max-width: 760px;
            width: 100%;
            border-radius: 1rem;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 35px 60px rgba(0, 0, 0, 0.18);
        }
    </style>
</head>
    <!-- END HEAD -->
    <!-- BEGIN BODY -->
<body class="bg-gray-50">
<?php
include "../inc/functions.php";
include "../inc/mysqli_connect.php";
session_start();
?>

<!-- MAIN WRAPPER -->
<div class="flex h-screen bg-gray-50">
    <!-- LEFT SIDEBAR -->
    <div id="left" class="w-64 bg-gradient-to-b from-green-700 to-green-900 shadow-lg overflow-y-auto">
        <div class="p-4 border-b border-green-600">
            <a href="<?php echo accls()."/"?>" class="flex items-center justify-center">
                <img src="../img/logo1.jpg" class="w-full h-auto rounded" alt="ACC Logo" />
            </a>
        </div>
        <?php include "navbar.php";?>
    </div>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- TOP HEADER -->
        <nav class="bg-white shadow-md border-b-4 border-green-600">
            <div class="px-6 py-4 flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-800">ACC Ledger System</h1>
                </div>
                <div class="text-gray-600 text-sm">
                    <span class="font-semibold">Welcome back!</span>
                </div>
            </div>
        </nav>

        <!-- PAGE CONTENT -->
        <div id="content" class="flex-1 overflow-y-auto p-6">
			<?php
				if(isset($_GET['_a!%@1!2%'])){$pgload=$_GET['_a!%@1!2%'];}else{$pgload="";}				
				//$link=decCode($lnk);
				$pgload=decCode($pgload);
				if($pgload=="schoolyear"){
					include "sy/sy.php";				
				}elseif($pgload=="semester"){
					include "sem/sem.php";
				}elseif($pgload=="offerings"){
					include "offerings/offerings.php";
				}elseif($pgload=="gradelevel"){
					include "glevel/gradelevel.php";
				}elseif($pgload=="collegestudents"){
					include "students/colstudents.php";
				}elseif($pgload=="shsstudents"){
					include "students/shsstudents.php";
				}elseif($pgload=="status"){
					include "status/stats.php";
				}elseif($pgload=="Departments"){
					include "departments/department.php";
				}elseif($pgload=="collegeledger"){
					include "ledger/college.php";
				}elseif($pgload=="shsledger"){
					include "ledger/shs.php";
				}elseif($pgload=="ledgermonitoring"){
					include "ledger/ldgrmonitoring.php";
				}elseif($pgload=="userroles"){
					include "users/role.php";
				}elseif($pgload=="ledgerusers"){
					include "users/users.php";
				}else{
					include "dashboard.php";
				}
			?>
        </div>
       <!--END PAGE CONTENT -->
    </div>
</div>
<!--END MAIN WRAPPER -->

<!-- PAGE LEVEL SCRIPTS -->
<script src="../assets/plugins/dataTables/jquery.dataTables.js"></script>
<script src="../assets/plugins/dataTables/dataTables.bootstrap.js"></script>
<script src="../assets/js/jquery-ui.min.js"></script>

<script>
    $(document).ready(function () {
        $('#dataTables-example').dataTable();

        $('[data-toggle="modal"]').on('click', function () {
            var target = $(this).data('target');
            if (target) {
                $(target).addClass('active');
            }
        });

        $('[data-dismiss="modal"]').on('click', function () {
            $(this).closest('.modal-overlay').removeClass('active');
        });
    });
</script>

</body>
