
<style>
table{
  border-collapse: collapse;
  width:500px;
}

td{
  padding:10px;
}

/*
.label{
  width:120px;
  font-weight:bold;
}
*/

/* bar background */
.bar-bg{
  width:100%;
  height:26px;
  background:#eee;
  border-radius:6px;
  overflow:hidden;
}

/* bar */
.bar{
  height:100%;
  width:0;
  color:white;
  text-align:right;
  padding-right:6px;
  line-height:26px;
  font-size:12px;
  animation:grow 1s forwards;
}

/* stagger animation */
tr:nth-child(1) .bar{ animation-delay:0.2s;}
tr:nth-child(2) .bar{ animation-delay:0.4s;}
tr:nth-child(3) .bar{ animation-delay:0.6s;}
tr:nth-child(4) .bar{ animation-delay:0.8s;}
tr:nth-child(5) .bar{ animation-delay:1s;}

/* animation */
@keyframes grow{
  from{width:0;}
  to{width:var(--value);}
}

/* different colors */
.bar1{background:#e74c3c;}
.bar2{background:#3498db;}
.bar3{background:#2ecc71;}
.bar4{background:#f39c12;}
.bar5{background:#9b59b6;}

</style>
<?php 
// NOTE:
// dashboard1.php was previously corrupted/garbled (broken PHP/HTML fragments),
// causing UI sections to render as plain text. We now render the working
// Tailwind-based dashboard.php content to restore the interface.
include __DIR__ . '/dashboard.php';
return;
?>

	<!--
	<divclass="grid gap-6 md:grid-cols-12">
	<<divvcllss="col-m"-6 pull-left">
			<h4> Drow"></h4> 
		</dv>
		<div class="md:col-span-6">
			<h4>For the School Year : <?php echo $sy;?> | Seste : <?php echo $em;?></h4>
		</div>
		
	</div>
	-->
	<hr >
	<?php 
	<div class="md:col-span-6">
		<h4> Dashboard </h4> 
	</div>
	<div class="md:col-span-6">
		<h4>For the School Year : <?php echo $sy;?> | Semester : <?php echo $sem;?></h4>
			</div>
	
	
	<div>
	->
	hr >
	?php 
		$strCol="SELECT count(csid) as college FROM students 
	where syid=".$syid." and sid=".$sid." and did=1";
	colRes=$dbcon->query($strCol);
	colData=$colRes->fetch_assoc();
	college=$colData['college'];
		
	
	strSSELECT count(csid) as shsM students 
	wherid=".$syiand sid=".$sid." and did=2";
	shsRes=$dbconery($strSHS);
	hsData=$shsRes->fetch_assoc();
	//c)hoo$rorCol;ere did=1";
	?=$dbcon->query($strCP);
	 Ddivaptylees->feta_igo:lf;
	$rag=$cpDatquickobonr#S="SELECT count(did) as shsprog FROM offerings where did=2";
			/fyo#3676f5;<iiv styletcg:upc5xa/c></co"
			fsaC san
			psaann class=laaegrpabelcdanger$cllege;?></span>san
		a>a
		 ass="quicquickn"tn" hrrf=##"">
			ofonttytylee=col#r:#459aad; clsfont styoc#0b"i< c5x</p></ o/s
			ss san
		<cslanass="qui a#e>labldgersan
		foacolor="orange"><i class="icon-list-ul icon-5x"></i></font>
	<sa>SHS Proquick-bmp" href="#
	<spfontnstyces=lalor:#f0b5cb;sd>2>LEDGEREcOIupc5xc/n></oass="content">
ssan
<divs anclass="clabl">lsuccesssan
<h3>alege Ledger</h3>
	<tae>quicktn" hrf="#
		<?fonthpo orral	gwhile($c$c-fhaist{uc5x	/$></ao
				s>san
			<stanr>aelabluccsss ecfrom ledger where csid=".$cid."";
				atRes=$dbcon->query($cntStr);
				$cntData=$cntRes->fetch_assoc();
		hr	$cntr=$cntData['csid'];
		sii"irnd remarks='Paid'";
					H		
			center$							
						$prcnt=ntent
	
						<td widtcp . "?></td>
					
						 class="bar-bg">
						<div class="bar bar2" style="--value:<?php echo $prcnt."%";?>"><?php echo $prcnt."%";?> </div> 
				//college chart
					<div>
				</td>
			</tr>
			<?php
		}
		?>
					?>
					<tr>
						<?php 
		</tabl>t he number of the
		
		
	</div>
	<div class="md:col-span-6">
									<h3>Senior High School Ledger</h3>
	</div>bilangon susid arogram sedgr na g statu = paid
	<table>
		<?php 
			//college chart
			//get program offerings in C=$cRes->fetch_assoc()){
				$cata['cid'];
			$cProg=$cData['program'];
					<tr>
					<?pe cid="
						
$c				ntStcsid ="rom ledger where csid=".$cid."";
							
				$cntRes=$dbcon->query($cntStr);
				$cntData=$cntRes->fetch_assoc(); 
				$cntr=$cntData['csid'];
				
				//bilangon su csid na program sa ledger na ang status = paid
					$pdSt
				=LECT count(clid)as clidCnt FROM ledger where csid=".$cid." and remarks='Paid'";
				?=$dbcon->query($pdStr);
					$pdData=$pdRes->fetch_assoc();
			Cnt=$pdData['clidCnt'];
										$prcnt=0;
			/iv
							$prcnt=c$ 
						
			/		
				
			<?php 
				//college char?
				//get wrogram offerings iniCollegedth="40%"><?php echo $cProg . " (".$pdCnt."/".$cntr.")";?></td>
				
				<td width="60%">
					
					<div class="bar-bg">
						<div class="bar bar3" st
					?>yle="--value:<?php echo $prcnt."%";?>"><?php echo $prcnt."%";?> </div> 
					<tr>							</div>
						<?php 
					<//get the total number of learners in the ledger with same cid
						/td>
				</tr>
				<?php
			}
										?>
			</t//bilangon su csid na program sa ledger na ang status = paid
					able>
	
	</table>

	NOTE:<br/>(n/n) The first number denotes the number of paid students under the program. <br />
			The econd number is the total number of students under the program that is encoded in the Ledger
</div>
  
					?="				
="
					 }
			?table/ br

