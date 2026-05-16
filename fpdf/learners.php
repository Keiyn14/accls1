<?php
session_start();

$qry=$_SESSION['qry'];
$sy=$_SESSION['sy'];
$sem=$_SESSION['sem'];

//echo $qry;
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "accls";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}else{
	
	//$result -> close();
}

require('fpdf.php');
class PDF extends FPDF
{
// Page header
function Header()
{
    // Logo
    $this->Image('../img/logo.png',15,10,20);
    // Arial bold 155
    $this->SetFont('Arial','B',15);
    // Move to the right
    $this->Cell(25);
   
    //$this->Cell(20,10,'Title',0,0,'C');
    $this->Cell(20,15,'AMANDO COPE COLLEGE');
	$this->SetFont('Arial','',10);
	$this->Ln(0);
	$this->Cell(25);
	$this->Cell(10,25,'A.A. Berces Street, Baranghawon, Tabaco City');
	$this->SetFont('Arial','I',10);
    $this->Ln(4);
	$this->Cell(25);
	$this->Cell(10,25,'Ledger System Report');
	$this->Ln(4);
	$this->Line(10, 30, 290, 30);
}

// Page footer
function Footer()
{
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial','I',8);
    // Page number
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}
}
// Instanciation of inherited class
$pdf = new PDF('L');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Ln(5);
$pdf->Cell(280,25,'STUDENTS LIST REPORT',0,0,'C');
$pdf->SetFont('Arial','',11);
$pdf->Ln(5);
$pdf->Cell(280,25,'For the School Year  : '.$sy. "   |   Semester :  ".$sem ,0,0,'C');
$pdf->Line(10, 45, 290, 45);
$pdf->Ln(20);
$pdf->Cell(10,6,'#',1,0,'C');
$pdf->Cell(68,6,'Program Name',1,0,'C');
$pdf->Cell(30,6,'Grade Level',1,0,'C');
$pdf->Cell(65,6,'Student Name',1,0,'C');
$pdf->Cell(35,6,'Mobile',1,0,'C');
$pdf->Cell(72,6,'Address',1,0,'C');
//retrieve the data
$res=$conn->query($qry);
$recCount=$res->num_rows;
if($recCount!=0){
	$counter=1;
	while($data=$res->fetch_assoc()){
		$program=$data['program'];
		$grade=$data['glevel'];
		$fname=$data['fname'];
		$lname=$data['lname'];
		$mname=$data['mname'];
		$mobile=$data['mobile'];
		$address=$data['address'];
		$pdf->Ln(6);
		$pdf->Cell(10,6,$counter,1,0);
		$pdf->Cell(68,6,$program,1,0);
		$pdf->Cell(30,6,$grade,1,0);
		$pdf->Cell(65,6,$fname. " ". $mname . " " . $lname,1,0);
		$pdf->Cell(35,6,$mobile,1,0);
		$pdf->Cell(72,6,$address,1,0);
		$counter++;
	}
}
/*
for($i=1;$i<=$pdf->PageNo();$i++)
    $pdf->Cell(0,10,'Printing line number '.$i,0,1);
*/
$pdf->Output();
// Close connection
$conn->close();
?>
