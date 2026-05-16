<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '0');
$csid = isset($_GET['csid']) ? (int) $_GET['csid'] : 0;
$syid = isset($_GET['syid']) ? (int) $_GET['syid'] : 0;
$sid = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;

$syname = '';
$sem = '';
$edepartment = '';
$program = '';
$glevel = '';
$fname = '';
$mname = '';
$lname = '';
$mobile = '';
$address = '';
$estatid = '';
$estatus = '';
$amt = 0;
$amtpaid = 0;
$balance = 0;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "accls";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {

    $dept = 1;
    $deptQry = "SELECT did FROM ledger WHERE csid=" . $csid . " AND syid=" . $syid . " AND sid=" . $sid . " LIMIT 1";
    $deptRes = $conn->query($deptQry);
    if ($deptRes && $deptRes->num_rows > 0) {
        $deptRow = $deptRes->fetch_assoc();
        $dept = (int) $deptRow['did'];
    }

    $studentTable = ($dept === 2) ? 'shsstudents' : 'students';

	$sql = "SELECT cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
				(select syname from sy where syid=cs.syid) as syname,
				(select semester from sem where sid=cs.sid) as semester,
				(select program from offerings where cid=cs.cid) as program,
				(select glevel from gradelevel where gid=cs.gid) as glevel,
				(select department from departments where did=cs.did) as department,
				(select remark from status where sid=cs.remarks) as status,
				cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks
			FROM " . $studentTable . " cs
		WHERE cs.csid=" . $csid . " AND cs.syid=" . $syid . " AND cs.sid=" . $sid;
	$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {  	  
		$sData = $result->fetch_assoc();		
	} else {
		$otherTable = ($studentTable === 'students') ? 'shsstudents' : 'students';
		$sql = "SELECT cs.csid, cs.syid,cs.sid,cs.cid,cs.gid,cs.did,
				(select syname from sy where syid=cs.syid) as syname,
				(select semester from sem where sid=cs.sid) as semester,
				(select program from offerings where cid=cs.cid) as program,
				(select glevel from gradelevel where gid=cs.gid) as glevel,
				(select department from departments where did=cs.did) as department,
				(select remark from status where sid=cs.remarks) as status,
				cs.fname, cs.mname, cs.lname, cs.address, cs.mobile, cs.remarks
			FROM " . $otherTable . " cs
		WHERE cs.csid=" . $csid . " AND cs.syid=" . $syid . " AND cs.sid=" . $sid;
		$result = $conn->query($sql);
		if ($result && $result->num_rows > 0) {
			$sData = $result->fetch_assoc();
		}
	}

	if (!empty($sData)) {
		$syname=$sData['syname'];
		$sem=$sData['semester'];
		$edepartment=$sData['department'];
		$program=$sData['program'];
		$glevel=$sData['glevel'];
		$fname=$sData['fname'];
		$mname=$sData['mname'];
		$lname=$sData['lname'];
		$mobile=$sData['mobile'];
		$address=$sData['address'];
		$estatid=$sData['remarks'];
		$estatus=$sData['status'];
	}

	if (isset($deptRes) && $deptRes) {
		$deptRes->close();
	}

if (isset($result) && $result) {
	$result->close();
}

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
	$this->Cell(10,25,'Ledger System-Payment History Report');
	$this->Ln(4);
	$this->Line(10, 30, 200, 30);
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
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);
$pdf->Ln(10);
$pdf->Cell(40,10,'Student Name : '. $fname . " " . $mname . " " . $lname );
$pdf->SetFont('Arial','',10);
$pdf->Cell(70);
$pdf->Cell(150,10,'Year Level : '. $glevel);
$pdf->Ln(5);
$pdf->Cell(40,10,'Address : '. $address);
$pdf->Cell(70);
$pdf->Cell(150,10,'Course : '. $program);
$pdf->Ln(5);
$pdf->Cell(40,10,'Mobile : '. $mobile);
$pdf->Cell(70);
$pdf->Cell(150,10,'School Year : '. $syname . "    |   ".$sem);
$pdf->Ln(4);
$pdf->Line(10, 48, 200, 48);
$pdf->SetFont('Arial','B',14);
$pdf->Ln(7);
$pdf->Cell(190,10,'TRANSACTION HISTORY',0,0,'C');
$pdf->SetFont('Arial','',12);

$pdf->Ln(9);
$pdf->Cell(20);
$pdf->Cell(10,10,' # ',1,0,'C');
$pdf->Cell(50,10,'Amount Paid ',1,0,'C');
$pdf->Cell(50,10,'Balance ',1,0,'C');
$pdf->Cell(50,10,'Date of Payment ',1,0,'C');
//$this->Cell(20,10,'Title',0,0,'C');
$strPayment="SELECT pid, csid, syid, sid, amount, amtpaid, balance, paymentdate
		FROM payment
		where csid=".$csid." and syid=".$syid." and sid=".$sid." and amtpaid<>0";
$res=$conn->query($strPayment);
$hasPayment = ($res && $res->num_rows > 0);
if(!$hasPayment){
	//wala pang payment TRANSACTION
	$pdf->Ln(10);
	$pdf->Cell(20);
	$pdf->Cell(150,10,'No payment history found for this student.',1,0,'C');
	$pdf->Ln(15);
	//kunin lang ung totalTuition fees and current Balance
	$curFees="SELECT pid, csid, syid, sid, amount, amtpaid, balance, paymentdate
		FROM payment
		where csid=".$csid." and syid=".$syid." and sid=".$sid." ORDER BY pid DESC LIMIT 1";
	$cfRes=$conn->query($curFees);
	$cfData=($cfRes ? $cfRes->fetch_assoc() : false);
	if(!$cfData){
		$ledgerSql="SELECT amt, tfee, balance FROM ledger
			WHERE csid=".$csid." and syid=".$syid." and sid=".$sid." LIMIT 1";
		$ledgerRes=$conn->query($ledgerSql);
		$ledgerData=($ledgerRes ? $ledgerRes->fetch_assoc() : false);
		$amt = $ledgerData ? $ledgerData['amt'] : 0;
		$amtpaid = $ledgerData ? $ledgerData['tfee'] : 0;
		$balance = $ledgerData ? $ledgerData['balance'] : 0;
	} else {
		$amt=$cfData['amount'];
		$amtpaid=$cfData['amtpaid'];
		$balance=$cfData['balance'];
	}
	$pdf->SetFont('Arial','B',14);
	$pdf->Ln(10);
	$pdf->Cell(10,10,'Payment Summary');
	$pdf->SetFont('Arial','',12);
	$pdf->Ln(10);
	$pdf->Cell(10,10,'Total Tuition Fees : PHP '. number_format($amt,2));
	$pdf->Ln(5);
	$pdf->Cell(10,10,'Total Amount Paid : PHP '. number_format($amtpaid,2));
	$pdf->Ln(5);
	$pdf->Cell(10,10,'Total Balance : PHP '. number_format($balance,2));
	$pdf->Ln(5);
	if($balance==0){
		$pdf->Cell(10,10,'Remarks :  Paid');
	}else{
		$pdf->Cell(10,10,'Remarks :  Unpaid');
	}
		
}else{
	$totalAmtPaid=0;
	$counter=1;
	while($data=$res->fetch_assoc()){
		$amt=$data['amount'];
		$amtpaid=$data['amtpaid'];
		$balance=$data['balance'];
		$paymentdate=$data['paymentdate'];
		$pdf->Ln(10);
		$pdf->Cell(20);
		$pdf->Cell(10,10,$counter,1,0,'C');
		$pdf->Cell(50,10,number_format($amtpaid,2),1,0,'C');
		$pdf->Cell(50,10,number_format($balance,2),1,0,'C');
		$pdf->Cell(50,10,$paymentdate,1,0,'C');
		$totalAmtPaid+=$amtpaid;
		$counter++;
	}
	$pdf->SetFont('Arial','B',14);
	$pdf->Ln(10);
	$pdf->Cell(10,10,'Payment Summary');
	$pdf->SetFont('Arial','',12);
	$pdf->Ln(10);
	$pdf->Cell(10,10,'Total Tuition Fees : PHP '. number_format($amt,2));
	$pdf->Ln(5);
	$pdf->Cell(10,10,'Total Amount Paid : PHP '. number_format($totalAmtPaid,2));
	$pdf->Ln(5);
	$pdf->Cell(10,10,'Total Balance : PHP '. number_format($balance,2));
	$pdf->Ln(5);
	if($balance==0){
		$pdf->Cell(10,10,'Remarks :  Paid');
	}else{
		$pdf->Cell(10,10,'Remarks :  Unpaid');
	}
}


/*
for($i=1;$i<=1;$i++)
    $pdf->Cell(0,10,'Printing line number '.$i,0,1);
*/
$pdf->Output();
// Close connection
$conn->close();
?>
