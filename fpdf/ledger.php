<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "accls";
session_start();
$strLedger=$_SESSION['qry'];
$sy=$_SESSION['sy'];
$sem=$_SESSION['sem'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}else{
	$res=$conn->query($strLedger);
	//$result -> close();
}
	//echo $strLedger;
require('fpdf.php');
class PDF extends FPDF
{
	// Page header
	function Header(){
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
		$this->Line(10, 30, 285, 30);
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
$pdf = new PDF("L");
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);
$pdf->Ln(13);
$pdf->Cell(275,10,'LEDGER PAYMENT MONITORING REPORT',0,0,'C');
$pdf->Ln(5);
$pdf->SetFont('Arial','',11);
$pdf->Cell(275,10,'For the School Year : '.$sy . '  |  Semester : '. $sem,0,0,'C');
$pdf->Ln(10);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(12,8,' # ',1,0,'C');
$pdf->Cell(55,8,'Learners Name',1,0,'C');
$pdf->Cell(30,8,'Grade Level',1,0,'C');
$pdf->Cell(70,8,'Program',1,0,'C');
$pdf->Cell(27,8,'Tuition Fee',1,0,'C');
$pdf->Cell(30,8,'Amount Paid',1,0,'C');
$pdf->Cell(27,8,'Balance',1,0,'C');
$pdf->Cell(25,8,'Remarks',1,0,'C');
//retrieve the records
$pdf->SetFont('Arial','',11);
$recCount=$res->num_rows;
if($recCount==0){
	$pdf->Ln(23);
	$pdf->Cell(250,8,'No Record Found...',1,0,'C');
}else{
	$counter=1;
	$ttf=0;
	$taf=0;
	$tcol=0;
	while($data=$res->fetch_assoc()){
		$fname=$data['fname'];
		$mname=$data['mname'];
		$lname=$data['lname']; 
		$glevel=$data['glevel'];
		$program=$data['program'];
		$did=$data['did'];
		$csid=$data['csid'];
		$amt=$data['amt'];
		$tfee=$data['tfee'];
		$balance=$data['balance'];
		$remarks=$data['remarks'];
		$pdf->Ln(8);
		$pdf->Cell(12,8,$counter,1,0,'C');
		$pdf->Cell(55,8,$fname . " " . $mname . " " . $lname,1,0,'L');
		$pdf->Cell(30,8,$glevel,1,0,'');
		$pdf->Cell(70,8,$program,1,0,'');
		$pdf->Cell(27,8,number_format($amt,2),1,0,'R');
		$pdf->Cell(30,8,number_format($tfee,2),1,0,'R');
		$pdf->Cell(27,8,number_format($balance,2),1,0,'R');
		$pdf->Cell(25,8,$remarks,1,0,'');
		$counter++;
		$ttf+=$amt;
		$taf+=$tfee;
		$tcol+=$balance;
	}
	
	$pdf->Ln(10);
	$pdf->Cell(12,8,'Total Tuition Fee : PHP ' .number_format($ttf,2),0,0,'');
	$pdf->Ln(6);
	$pdf->Cell(12,8,'Total Collected Fee : PHP ' .number_format($taf,2),0,0,'');
	$pdf->Ln(6);
	$pdf->Cell(12,8,'Total Collectible Fee : PHP ' .number_format($tcol,2),0,0,'');
	
}

/*
for($i=1;$i<=1;$i++)
    $pdf->Cell(0,10,'Printing line number '.$i,0,1);
*/
$pdf->Output();
// Close connection
$conn->close();
?>
