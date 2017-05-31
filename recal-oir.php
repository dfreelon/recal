<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>ReCal for Ordinal, Interval, and Ratio-Level Data</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?php
include 'recal-lib.php'; 
?>

</head>
<body>

<?php
if(!isset($_FILES['file']['name'])) recalBox("tan", "black", "-oir"); 	

// error checking round 1

$errMsg1 = err1();
if($errMsg1 != false) errorEnd(1, $errMsg1, "errlog4.txt");

$errMsg7 = err7();
if($errMsg7 != false) errorEnd(7, $errMsg7, "errlog4.txt");

$errMsg2 = err2();
if($errMsg2 != false) errorEnd(2, $errMsg2, "errlog4.txt");

if($_POST["ordinal"] != 1 and $_POST["interval"] != 1 and $_POST["ratio"] != 1 and $_POST["nominal"] != 1 ) errorEnd(10, "Please select at least one coefficient to calculate (nominal, ordinal, interval, and/or ratio) and try again.", "errlog4.txt");

// uncomment the line below to enable the caching of user data files

cacheData(4);

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
if($errMsg3 != false) errorEnd(3, $errMsg3, "errlog4.txt");

foreach($entries as $key => $value){
	if(preg_match('/\.[^0-9]|\.$|\-[^0-9]|\-$/', $value)){
		$line = $key + 1;
		$filename = $_FILES['file']['name'];
		errorEnd(11, "Row $line of your file '$filename' contains either a minus sign or a decimal point that is out of place (i.e. that does not have a numeric digit immediately to the right of it). Please rectify this problem and try again. If the problem persists please notify the author at deen@dfreelon.org .", "errlog4.txt");
	}
}
//listwise remove lines with fewer than two non-missing vals (otherwise missing data will cause incorrect K coefficients)
foreach($entries as $key => $value){
    $cell_ct = 0;
    foreach(explode(',',$value) as $cell) if(is_numeric($cell)) $cell_ct++;
    
    if($cell_ct < 2) unset($entries[$key]);
}
$entries = array_values($entries);

$errMsg4 = err4($entries);
if($errMsg4 != false) errorEnd(4, $errMsg4, "errlog4.txt");

$errMsg8 = err8($entries);
if($errMsg8 != false) errorEnd(8, $errMsg8, "errlog4.txt");

$errMsg9 = err9($entries);
if($errMsg9 != false) errorEnd(9, $errMsg9, "errlog4.txt");

//This line flags the execution if the file contains any minus signs

foreach($entries as $key => $value){
	if(preg_match('/\-/', $value)){ 
        $negVal = 1;
        break;
	}
    else $negVal = 0;
}

// next line gets the number of fields in the file

$nFields = count(explode(",",$entries[0]));

// Pulls the CSV values into a 2-dimensional array in which $i = field and $key = case

$multiArr = makeMulti($entries, $multiArr, $nFields);
$nCases = count($multiArr[0]);

$invv = 1; //invariant vals
$blank = 0; //Missing data part 0 - define $blank var

foreach($multiArr as $key => $value){
	foreach($multiArr[$key] as $keyy => $val){
		if($multiArr[0][0] != $val){
			$invv = 0;
			break 2;
		}
	}
}

if($invv == 1){
	congrats();
	$filename = substr($_FILES['file']['name'], 2);
	echo "<div style='text-align:center; margin-bottom:25px'>The data set '$filename' exhibits <a href='http://dfreelon.org/2008/10/24/recal-error-log-entry-1-invariant-values/'>no variation</a>; therefore no reliability coefficients could be calculated.</div>";
	footer("tan", "black");
	exit;
}

//Missing data part 1: adjusts N of values by checking for # marks, which indicate missing data cells
foreach($multiArr as $key => $value){
	foreach($multiArr[$key] as $keyy => $val){ 
        if($val == "#"){ 
			$blank++;
			unset($multiArr[$key][$keyy]);
		}	
	}
}

//The following calculations are required for all OIR coefficients

$uArray = array();

foreach($multiArr as $key => $value){
	$tempArr = array_unique($multiArr[$key]);
	$uArray = array_merge($uArray, $tempArr);
}

$uArray = array_values(array_unique($uArray));
asort($uArray);

//begin Krippendorff's Alpha calcs

$nVals = ($nFields * $nCases) - $blank; //this will be different if any data are missing

$kripsTable = array();
foreach($uArray as $key => $value) $kripsTable[$value] = array();
$remainder = $nFields - 1;

for($var = 0; $var < $nFields - 1; $var++){
	for($rem2 = $remainder; $rem2 > 0; $rem2--){
		for($case = 0; $case < $nCases; $case++){
			$denom = 0;
			for($col = 0; $col < $nFields; $col++) if(is_numeric($multiArr[$col][$case])) $denom++; //missing data pt 2: adjust denominator based on N of values in row
			if(is_numeric($multiArr[$var][$case]) and is_numeric($multiArr[$var + $rem2][$case])){ //missing data pt 3: ensure values exist
				$kripsTable[$multiArr[$var][$case]][$multiArr[$var + $rem2][$case]] = $kripsTable[$multiArr[$var][$case]][$multiArr[$var + $rem2][$case]] + (1/($denom - 1)); 
				$kripsTable[$multiArr[$var + $rem2][$case]][$multiArr[$var][$case]] = $kripsTable[$multiArr[$var + $rem2][$case]][$multiArr[$var][$case]] + (1/($denom - 1));
			}
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

if((($nVals * ($nVals - 1)) - $sumMinus) != 0) $kripsAlpha = ((($nVals - 1) * $sumObs) - $sumMinus) / (($nVals * ($nVals - 1)) - $sumMinus);
else $kripsAlpha = "undefined*";

//testing K's alpha - INTERVAL + RATIO + ORDINAL

$offDiags = $kripsTable;
$intNumer = array();
if($negVal != 1) $ratNumer = array();

foreach($offDiags as $key => $value){
	foreach($offDiags[$key] as $keyy => $valu){
		if($key < $keyy){ 
			$intNumer[] = pow(($key - $keyy), 2) * $offDiags[$key][$keyy];
			if($negVal != 1) $ratNumer[] = pow(($key - $keyy), 2) * $offDiags[$key][$keyy] * (1/pow(($key + $keyy), 2));
		}	
	}
}

$intTop = array_sum($intNumer);
if($negVal != 1) $ratTop = array_sum($ratNumer);
$marginals = array();
$intDenom = array();
foreach($uArray as $key => $value) $marginals[$value] = 0;
$marginalsK = array();

foreach($marginals as $yek => $val){
	foreach($multiArr as $key => $value){
		foreach($multiArr[$key] as $kye => $valu){
			if($multiArr[$key][$kye] == $yek) $marginals[$yek]++;
		}
	}
	$marginalsK[] = $marginals[$yek];
}

// ORDINAL STUFF

$ordNumer = array();
$p = 0;
$m = 0;

foreach($offDiags as $key => $value){
	foreach($offDiags[$key] as $keyy => $valu){
		if($key < $keyy){ 
			foreach($marginals as $kye => $val) if($keyy > $kye) $m++;	
			$sumGK = array_sum(array_slice($marginalsK, $p + 1, $m - $p - 1));
			
			$ordNumer[] = $offDiags[$key][$keyy] * pow(($marginals[$key]/2 + $sumGK + $marginals[$keyy]/2), 2);
			$m = 0;
		} 
	}
	$p++;
}

$ordTop = array_sum($ordNumer);

// end ORDINAL

$i = 1;
$x = 0;
$uArray2 = array();

foreach($uArray as $key => $value) $uArray2[] = $value;

foreach($marginals as $yek => $val){
	for($var = $i; $var < count($marginalsK); $var++){
		$intDenom[] = $marginals[$yek] * $marginalsK[$var] * pow(($yek - $uArray2[$var]), 2);
		if($negVal != 1) $ratDenom[] = $marginals[$yek] * $marginalsK[$var] * pow(($yek - $uArray2[$var]), 2) * (1/pow(($yek + $uArray2[$var]), 2));
		
		if($var > $x){ 
			$ordCK = ($marginals[$yek]/2 + array_sum(array_slice($marginalsK, $x + 1, $var - $x - 1)) + $marginalsK[$var]/2);
		}else{
			$ordCK = 0;
		}		
		$ordDenom[] = $marginals[$yek] * $marginalsK[$var] * pow($ordCK, 2);
	}
	$i++;
	$x++;
}

$ordBott = array_sum($ordDenom);
$intBott = array_sum($intDenom);
if($negVal != 1) $ratBott = array_sum($ratDenom);
/*
echo "$intTop <br /> $intBott <br /> $blank <br /><pre>";
//print_r($kripsTable);
print_r($entries);
echo "</pre>";
*/

$ordKripsA = 1 - (($nVals - 1) * ($ordTop / $ordBott));
$intKripsA = 1 - (($nVals - 1) * ($intTop / $intBott));
if($negVal != 1) $ratKripsA = 1 - (($nVals - 1) * ($ratTop / $ratBott));
else $ratKripsA = "undefined*";

congrats();

// function to store data for a run-window-style display 

if($_POST["save"] != 1 or $_SESSION['recal'] == 2 or $_SESSION['recal'] == 3){ 
	unset($_SESSION[$runWindow]);
	$_SESSION['nHist'] = 0;
}

if(!isset($_SESSION[$runWindow])){ 
	$_SESSION[$runWindow] = array();
	$_SESSION['recal'] = 4;
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
		$_SESSION[$runWindow][$key][5] = round($ordKripsA, 3);
		$_SESSION[$runWindow][$key][6] = round($intKripsA, 3);
		if($negVal != 1) $_SESSION[$runWindow][$key][7] = round($ratKripsA, 3);
		else $_SESSION[$runWindow][$key][7] = $ratKripsA;
		
		$_SESSION[$runWindow][$key][8] = $_POST["ordinal"];
		$_SESSION[$runWindow][$key][9] = $_POST["interval"];
		$_SESSION[$runWindow][$key][10] = $_POST["ratio"];
        $_SESSION[$runWindow][$key][11] = round($kripsAlpha, 3);
        $_SESSION[$runWindow][$key][12] = $_POST["nominal"]; 
	}
     
 // start descriptive printout ?>     
 
 		<a name="result<?php echo $key; ?>"></a><div style="text-align:center; font-size: 26px; margin-top: 19px;">ReCal for Ordinal, Interval, and Ratio-Level Data<br />
		results for file "<?php echo $_SESSION[$runWindow][$key][0]; ?>"</div><br>
		<table border="0" align="center">
		<tr><td>File size:  </td>
		<td align='right'> <?php echo $_SESSION[$runWindow][$key][1]; echo " bytes"; ?> </td></tr>
        <tr><td> N coders:  </td>
		<td align='right'> <?php echo $_SESSION[$runWindow][$key][2]; ?> </td></tr>
		<tr><td> N cases:  </td>
		<td align='right'> <?php echo $_SESSION[$runWindow][$key][3]; ?> </td></tr>
		<tr><td> N decisions:</td>
  	    <td align='right'> <?php echo $_SESSION[$runWindow][$key][4]; ?> </td></tr>
  	  	</table>
        
 <?php // start OIR printout ?> 
  
		<table border="1" style="margin-top:9px" align="center">
        <?php 
        if($_SESSION[$runWindow][$key][12] == 1){  ?>
        
			<tr><td class='cellpad'><strong>Krippendorff's alpha (nominal)</strong></td>
			<td class='cellpad'><?php echo $_SESSION[$runWindow][$key][11]; ?></td></tr>
		
		<?php 
		}
        
        if($_SESSION[$runWindow][$key][8] == 1){ ?>
        
			<tr><td class='cellpad'><strong>Krippendorff's alpha (ordinal)</strong></td>
			<td class='cellpad'><?php echo $_SESSION[$runWindow][$key][5]; ?></td></tr>
		
		<?php 
		}
		
		if($_SESSION[$runWindow][$key][9] == 1){  ?>
        
			<tr><td class='cellpad'><strong>Krippendorff's alpha (interval)</strong></td>
			<td class='cellpad'><?php echo $_SESSION[$runWindow][$key][6]; ?></td></tr>
		
		<?php 
		}
		
		if($_SESSION[$runWindow][$key][10] == 1){  ?>
          
			<tr><td class='cellpad'><strong>Krippendorff's alpha (ratio)</strong></td>
			<td class='cellpad'><?php echo $_SESSION[$runWindow][$key][7]; ?></td></tr>
		
	<?php } ?>

		</table>

	<?php if(is_string($_SESSION[$runWindow][$key][7]) and $_SESSION[$runWindow][$key][10] == 1) echo "<div style='text-align: center; margin-bottom: 9px'>*Krippendorff's alpha for ratio variables is undefined when the data set contains negative values.</div>"; ?>        
        <br />

<?php
}
// exportIt();
footer("tan", "black");

?>

</body>
</html>