<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>ACC -[ Ledger ]- System v.1</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/plugins/Font-Awesome/css/font-awesome.css" />

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Combines forest green overlay with local background image fallback */
        .bg-dashboard-green {
            background: linear-gradient(rgba(17, 110, 61, 0.90), rgba(17, 110, 61, 0.90)), 
                        url('bg.jpg') no-repeat center center fixed;
            background-size: cover;
            background-color: #116e3d; /* Fallback solid color */
        }
        .btn-brand-orange {
            background-color: #f95738;
            transition: all 0.2s ease-in-out;
        }
        .btn-brand-orange:hover {
            background-color: #e24425;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-dashboard-green min-h-screen flex flex-col justify-between text-white antialiased">

<?php
// Core System Dependencies
include "inc/functions.php";
include "inc/mysqli_connect.php";
session_start();

// 🔒 SECURITY: Visiting the login page always clears any existing session.
// This ensures the back button after logout never silently restores access.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
    session_start(); // Fresh session for the login form CSRF safety
}
?>

    <header class="w-full px-6 py-4 flex justify-between items-center bg-black/10 backdrop-blur-sm fixed top-0 left-0 z-50">
        <div class="flex items-center gap-2">
            <span class="font-bold tracking-wide text-sm sm:text-base">ACC: <span class="font-light opacity-90">Registrar Ledger System</span></span>
        </div>
        <div class="flex items-center gap-3">
            <span class="font-bold tracking-wider text-xs sm:text-sm uppercase bg-white/20 px-3 py-1.5 rounded-md backdrop-blur-md">
                Amando Cope College
            </span>
        </div>
    </header>

    <main class="flex-1 flex flex-col lg:flex-row items-center justify-center px-6 sm:px-12 lg:px-24 gap-12 pt-32 pb-12 w-full max-w-7xl mx-auto">
        
        <div class="w-full lg:w-1/2 flex flex-col justify-center text-left lg:pr-8">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight mb-6 drop-shadow-sm">
                A Centralized Platform for Streamlined Ledger and Financial Management in Amando Cope College
            </h1>
            <p class="text-white/80 text-base sm:text-lg max-w-xl font-light">
                Access secure administrative utilities, check billing logs, monitor accounts, and view real-time student transaction balances smoothly.
            </p>
        </div>

        <div class="w-full lg:w-1/2 flex justify-center lg:justify-end">
            <div class="bg-white rounded-2xl shadow-2xl p-8 sm:p-10 w-full max-w-md text-gray-800 border border-gray-100 transition-all duration-300 hover:shadow-xl">
                
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                        <i class="icon-key text-gray-400 text-xl"></i> Security Portal
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Please sign in to manage ledger dashboard applications.</p>
                </div>

                <?php 
                if(isset($_POST["btnLogIn"])){
                    // Stripping white space spaces safely, removing dangerous text filters
                    $usrNme = trim($_POST['txtname']);
                    $usrPWrd = trim($_POST['txtpass']);
                    
                    if($usrNme == "" || $usrPWrd == ""){
                    ?>
                        <div class="mb-4 bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-r-lg text-sm flex items-center gap-2 shadow-sm">
                            <i class="icon-exclamation-sign text-base"></i> 
                            <span>Username or password cannot be empty!</span>
                        </div>			
                    <?php
                    } else {	
                        // SECURE: Implementing Parameterized SQL Statements to eliminate injection vectors entirely
                        $stmt = $dbcon->prepare("SELECT uid, nameuser, passname, role FROM users WHERE nameuser = ? AND passname = ?");
                        if ($stmt) {
                            $stmt->bind_param("ss", $usrNme, $usrPWrd);
                            $stmt->execute();
                            $accRess = $stmt->get_result();
                            $recCount = $accRess->num_rows;
                            
                            if($recCount > 0){
                                $accData = $accRess->fetch_assoc();
                                $role = $accData['role'];					
                                
                                // Save administrative context details to session storage
                                $_SESSION['uid'] = $accData['uid'];
                                $_SESSION['nameuser'] = $accData['nameuser'];
                                $_SESSION['role'] = $role;

                                if($role == "1"){
                                    header("Location: " . accls() . '/_&ans@fm1');
                                    exit();						
                                } elseif($role == "2"){
                                    header("Location: " . accls() . '/_&cns@fm2');
                                    exit();
                                }
                            } else {
                                ?>
                                <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-r-lg text-sm flex items-center gap-2 shadow-sm">
                                    <i class="icon-remove-sign text-base"></i> 
                                    <span>Invalid system access credentials. Please try again.</span>
                                </div>
                                <?php
                            }
                            $stmt->close();
                        }
                    }
                }
                ?>

                <form role="form" method="post" class="space-y-5" autocomplete="off">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Username Address *</label>
                        <input type="text" name="txtname" placeholder="Enter username" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition-all text-sm font-medium text-gray-900 placeholder-gray-400" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Password *</label>
                        <input type="password" name="txtpass" placeholder="Enter password" required autocomplete="current-password"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:bg-white transition-all text-sm font-medium text-gray-900 placeholder-gray-400" />
                    </div>

                    <div class="flex items-center justify-between text-sm pt-1">
                        <label class="flex items-center text-gray-600 cursor-pointer select-none">
                            <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 mr-2">
                            Remember me
                        </label>
                        <a href="#" class="text-xs font-medium text-gray-400 hover:text-emerald-600 transition-colors">Forgot Password?</a>
                    </div>

                    <button name="btnLogIn" type="submit" 
                        class="btn-brand-orange w-full py-3 px-4 rounded-xl text-white font-semibold text-sm shadow-lg shadow-orange-600/20 flex items-center justify-center gap-2 mt-2">
                        <i class="icon-user"></i> Sign in
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer class="w-full text-center py-4 text-xs tracking-wide text-white/60 bg-black/5 backdrop-blur-xs">
        <p>&copy; Amando Cope College &nbsp;2025&nbsp; | All Rights Reserved</p>
    </footer>

</body>
</html>