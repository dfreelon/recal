<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>ReCal3</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?php
include 'recal-lib.php';
?>

</head>

<body>

<?php
if($_FILES['file']['name'] == "") recalBox("#ff9933", "black", "3");

// error checking round 1

$errMsg1 = err1();
if($errMsg1 != false) errorEnd(1, $errMsg1, "errlog3.txt");

$errMsg7 = err7();
if($errMsg7 != false) errorEnd(7, $errMsg7, "errlog3.txt");

$errMsg2 = err2();
if($errMsg2 != false) errorEnd(2, $errMsg2, "errlog3.txt");

// caches user data files

cacheData(3);

// reads file into 1d-array

$entries = openFile($_FILES['file']['tmp_name']);
$entries = semiToComma($entries);
$entries = tabToComma($entries);
$entries = fixNonUTF8($entries);
$entries = stripHeaders($entries);
$entries = stripSpaces($entries);
$entries = stripChars($entries);

// error-checking round 2

$errMsg3 = err3($entries);
if($errMsg3 != false) errorEnd(3, $errMsg3, "errlog3.txt");

$errMsg4 = err4($entries);
if($errMsg4 != false) errorEnd(4, $errMsg4, "errlog3.txt");

$errMsg8 = err8($entries);
if($errMsg8 != false) errorEnd(8, $errMsg8, "errlog3.txt");

$errMsg9 = err9($entries);
if($errMsg9 != false) errorEnd(9, $errMsg9, "errlog3.txt");

// next line gets the number of fields in the file

$nFields = count(explode(",",$entries[0]));

// Pulls the CSV values into a 2-dimensional array in which $i = field and $key = case

$multiArr = array();

$multiArr = makeMulti($entries, $multiArr, $nFields);

$nCases = count($multiArr[0]);

//begin Fleiss' Kappa calcs

$uArray = array();

foreach($multiArr as $key => $value){
	$tempArr = array_unique($multiArr[$key]);
	$uArray = array_merge($uArray, $tempArr);
}

$uArray = array_values(array_unique($uArray));

$nUnique = count($uArray);

for($i = 0; $i < $nUnique; $i++){
	$cohensTable[$i] = array();
}	

for($i = 0; $i < $nCases; $i++){
	for($n = 0; $n < $nFields; $n++){
		for($x = 0; $x < $nUnique; $x++){
			if(!isset($cohensTable[$x][$i])) $cohensTable[$x][$i] = 0;
			if($multiArr[$n][$i] == $uArray[$x]) $cohensTable[$x][$i]++;
		}
	}
}
	
//generate P-sub-j's and P Bar E	

for($x = 0; $x < $nUnique; $x++){
	$pSubj[] = array_sum($cohensTable[$x]);
}	

$cellTotal = array_sum($pSubj);

foreach($pSubj as $key => $value){	
	$pSubj[$key] = $value / $cellTotal;
	$pSubj[$key] = pow($pSubj[$key], 2);
}

$pBarE = array_sum($pSubj);

//generate P-sub-i's and P Bar	

for($i = 0; $i < $nCases; $i++){
	for($x = 0; $x < $nUnique; $x++){
		if(!isset($pSubi[$i])) $pSubi[$i] = 0;
		$temp = pow($cohensTable[$x][$i], 2) - $cohensTable[$x][$i];
		$pSubi[$i] = $temp + $pSubi[$i];
	}	
	$pSubi[$i] = (1 / ($nFields * ($nFields - 1))) * $pSubi[$i];
}

$pBar = (1 / $nCases) * array_sum($pSubi);

if($pBarE != 1) $fleissKappa = ($pBar - $pBarE) / (1 - $pBarE);
else{ 
	$fleissKappa = "undefined*";
	$noFleiss = 1;
}		

//begin average pairwise percent calcs

$nAgr = array();

$remainder = $nFields - 1;

for($var = 0; $var < $nFields - 1; $var++){
	for ($rem2 = $remainder; $rem2 > 0; $rem2--){
		$aCount = 0;
		for($case = 0; $case < $nCases; $case++){
			if($multiArr[$var][$case] == $multiArr[$var + $rem2][$case]) $aCount++;
		}	
		$nAgr[] = $aCount / $nCases; 	
	}
	$remainder--;
}

$app = array_sum($nAgr) / count($nAgr);

//begin Krippendorff's Alpha calcs

$nVals = $nFields * $nCases;

$kripsTable = array();

for($i = 0; $i < $nUnique; $i++){
	$kripsTable[$i] = array();
}	

$remainder = $nFields - 1;

for($var = 0; $var < $nFields - 1; $var++){
	for ($rem2 = $remainder; $rem2 > 0; $rem2--){
		for($case = 0; $case < $nCases; $case++){
			$kripsTable[$multiArr[$var][$case]][$multiArr[$var + $rem2][$case]] = $kripsTable[$multiArr[$var][$case]][$multiArr[$var + $rem2][$case]] + (1/($nFields - 1));
			$kripsTable[$multiArr[$var + $rem2][$case]][$multiArr[$var][$case]] = $kripsTable[$multiArr[$var + $rem2][$case]][$multiArr[$var][$case]] + (1/($nFields - 1));
		}	
	}
	$remainder--;
}

$nSubC = array();

foreach($kripsTable as $key => $value){
	if($kripsTable[$key] != NULL){
		$nSubC[] = array_sum($kripsTable[$key]);
	}
}

$obsMatch = array();

foreach($kripsTable as $key => $value){
	foreach($kripsTable[$key] as $keyy => $val2){
		if($key == $keyy){
			$obsMatch[] = $kripsTable[$key][$keyy];
		}
	}
}

$sumObs = array_sum($obsMatch);

$nCMinus = array();

foreach($nSubC as $key => $value){
	$nCMinus[] = $value * ($value - 1);
}

$sumMinus = array_sum($nCMinus);

if((($nVals * ($nVals - 1)) - $sumMinus) != 0 and $nUnique > 1) $kripsAlpha = ((($nVals - 1) * $sumObs) - $sumMinus) / (($nVals * ($nVals - 1)) - $sumMinus);
else $kripsAlpha = "undefined*";

//begin avg pairwise Cohen's Kappa calcs

$arrFreqs = array();

for($var = 0; $var < $nFields; $var++){
	$arrFreqs[$var] = array_count_values($multiArr[$var]);
}

$marProds = array();

$i = 0;

for($var = 0; $var < $nFields; $var++){
	$rem2 = $nFields - 1;
	while($rem2 > $var){
		$marProds[$i] = array();
		foreach($arrFreqs[$var] as $case => $value){
			$marProds[$i][$case] = $arrFreqs[$var][$case] * $arrFreqs[$rem2][$case];	 
		}	
	$i++;	
	$rem2--;
	}
}

$cohensEA = array();

$nSqd = pow($nCases, 2);

foreach($marProds as $var => $value){
	$cohensEA[$var] = (1 / $nSqd) * (array_sum($marProds[$var]));
}

$cohensK = array();

foreach($cohensEA as $key => $value){
	if(round($cohensEA[$key], 10) != 1) $cohensK[] = ($nAgr[$key] - $cohensEA[$key]) / (1 - $cohensEA[$key]);
	else{ 
		$cohensK[] = "undefined**";
		$noCohen = 1;
	}
}

if($noCohen != 1) $apCK = array_sum($cohensK) / count($cohensK);
else $apCK = "undefined**";

// begin data printout

congrats();

// function to store data for a run-window-style display

if($_POST["save"] != 1 or $_SESSION['recal'] == 2 or $_SESSION['recal'] == 4){ 
	unset($_SESSION[$runWindow]);
	$_SESSION['nHist'] = 0;
}

if(!isset($_SESSION[$runWindow])){ 
	$_SESSION[$runWindow] = array();
	$_SESSION['recal'] = 3;
}	

$_SESSION[$runWindow][] = count($_SESSION[$runWindow]);
$_SESSION['nHist']++;

foreach($_SESSION[$runWindow] as $key => $value){
	if(!isset($_SESSION[$runWindow][$key][0])){
		$_SESSION[$runWindow][$key] = array();
		$_SESSION[$runWindow][$key][0] = $_FILES['file']['name'];
		$_SESSION[$runWindow][$key][1] = $_FILES['file']['size'];
		$_SESSION[$runWindow][$key][2] = $nFields;
		$_SESSION[$runWindow][$key][3] = $nCases;
		$_SESSION[$runWindow][$key][4] = $nVals;
		$_SESSION[$runWindow][$key][5] = $app * 100;
		$_SESSION[$runWindow][$key][6] = $fleissKappa;
		$_SESSION[$runWindow][$key][7] = $apCK;
		$_SESSION[$runWindow][$key][8] = $kripsAlpha;
		
		$_SESSION[$runWindow][$key][9] = array();
		foreach($nAgr as $keyy => $value) $_SESSION[$runWindow][$key][9][$keyy] = $value;
		
		$_SESSION[$runWindow][$key][10] = array();
		foreach($cohensK as $keyy => $value) $_SESSION[$runWindow][$key][10][$keyy] = $value; 
		
		$_SESSION[$runWindow][$key][11] = $pBar;
		$_SESSION[$runWindow][$key][12] = $pBarE;
		$_SESSION[$runWindow][$key][13] = $sumObs;
		$_SESSION[$runWindow][$key][14] = $sumMinus;
		$_SESSION[$runWindow][$key][15] = $noCohen;
		$_SESSION[$runWindow][$key][16] = $noFleiss;
	}
}     

foreach($_SESSION[$runWindow] as $key => $value){
        
 // start avg pairwise pct agr printout ?>     
 
 		<a name="result<?php
echo $key; ?>"></a><div style="text-align:center; font-size: 26px; margin-top: 19px;">ReCal for 3+ Coders<br />
		results for file "<?php
echo $_SESSION[$runWindow][$key][0]; ?>"</div><br>
		<table border="0" align="center">
		<tr><td>File size:  </td>
		<td align='right'><?php
echo $_SESSION[$runWindow][$key][1]; echo " bytes"; ?> </td></tr>
        <tr><td> N coders:  </td>
		<td align='right'> <?php
echo $_SESSION[$runWindow][$key][2]; ?> </td></tr>
		<tr><td> N cases:  </td>
		<td align='right'> <?php
echo $_SESSION[$runWindow][$key][3]; ?> </td></tr>
		<tr><td> N decisions:</td>
  	    <td align='right'> <?php
echo $_SESSION[$runWindow][$key][4]; ?></td></tr>
  	  	</table>
  
        <div class="head">Average Pairwise Percent Agreement</div>
		<table border="1" align="center">
		<tr><td width="85"><strong>Average pairwise percent agr.</strong></td>
		<?php

		foreach($_SESSION[$runWindow][$key][9] as $keyy => $value){
			$pVal = $keyy + 1;
			$rem2 = $_SESSION[$runWindow][$key][2];
			while($rem2 > $pVal){
				echo "<td width='65'>";
				echo "Pairwise pct. agr. <br>cols $pVal & $rem2";
				echo "</td>";
				$rem2--;
			}
		}
		?>

		</tr>
		<tr>
		<td><?php
echo round($_SESSION[$runWindow][$key][5], 3); echo "%"; ?></td>
		<?php
foreach($_SESSION[$runWindow][$key][9] as $value){
				echo "<td>";
				echo round($value * 100, 3);
				echo "%</td>";
			}
		?>

		</tr>
		</table>
        
<?php
// start FK printout ?>          
        
        <div class="head">Fleiss' Kappa</div>
		<table border="1" align="center" width="250">
		<tr><td><strong>Fleiss' Kappa</strong></td><td>Observed Agreement</td><td>Expected Agreement</td></tr>
		<tr><td><?php

		if(!is_string($_SESSION[$runWindow][$key][6])) echo round($_SESSION[$runWindow][$key][6], 3); 
		else echo $_SESSION[$runWindow][$key][6];
		?></td><td><?php
echo round($_SESSION[$runWindow][$key][11], 3); ?></td><td><?php
echo round($_SESSION[$runWindow][$key][12], 3); ?></td>
		</tr>
		</table>
        
<?php
// start avg CK printout ?>          
        
        <div class="head">Average Pairwise Cohen's Kappa</div>
		<table border="1" align="center">
		<tr><td width="85"><strong>Average pairwise CK</strong></td>
		<?php
		foreach($_SESSION[$runWindow][$key][10] as $keyy => $value){
			$pVal = $keyy + 1;
			$rem2 = $_SESSION[$runWindow][$key][2];
			while($rem2 > $pVal){
				echo "<td width='65'>";
				echo "Pairwise CK <br>cols $pVal & $rem2";
				echo "</td>";
				$rem2--;
			}
		}
		?>

		</tr>
		<tr>
		<td><?php
		if(!is_string($_SESSION[$runWindow][$key][7])) echo round($_SESSION[$runWindow][$key][7], 3); 
		else echo $_SESSION[$runWindow][$key][7];
		?></td>
		<?php
foreach($_SESSION[$runWindow][$key][10] as $value){
				echo "<td>";
				if(!is_string($value)) echo round($value, 3);
				else echo $value;
				echo "</td>";
			}
		?>

		</tr>
		</table>
 
<?php
// start KA printout ?>          

		<div class="head">Krippendorff's Alpha (nominal)</div>
		<table border="1" align="center">
		<tr><td><strong>Krippendorff's Alpha</strong></td><td>N Decisions</td><td>&Sigma;<sub>c</sub>o<sub>cc</sub>***</td><td>&Sigma;<sub>c</sub>n<sub>c</sub>(n<sub>c</sub> - 1)***</td></tr>
		<tr><td><?php
if(!is_string($_SESSION[$runWindow][$key][8])) echo round($_SESSION[$runWindow][$key][8], 3); else echo $_SESSION[$runWindow][$key][8]; ?></td><td><?php
echo $_SESSION[$runWindow][$key][4]; ?></td><td><?php
echo $_SESSION[$runWindow][$key][13]; ?></td><td><?php
echo $_SESSION[$runWindow][$key][14]; ?></td>
		</tr>
		</table>
        <?php
if($_SESSION[$runWindow][$key][16] == 1){ ?>
        <div style="text-align: center">*Fleiss' kappa and Krippendorf's Alpha are undefined for this variable due to <a href="http://dfreelon.org/2008/10/24/recal-error-log-entry-1-invariant-values/">invariant values.</a></div>
        <?php
} ?>
        <?php
if($_SESSION[$runWindow][$key][15] == 1){ ?>
        <div style="text-align: center">**Cohen's kappa is undefined for this variable due to <a href="http://dfreelon.org/2008/10/24/recal-error-log-entry-1-invariant-values/">invariant values.</a></div>
        <?php
} ?>
       
		<div style="text-align:center;">***These figures are drawn from <a href="http://repository.upenn.edu/asc_papers/43/">Krippendorff (2007, case C.)</a></div>

		<br />
<?php

}

exportIt();
footer("#ff9933", "black");
?>

</body>
</html>