<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>ReCal2</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<?php
include 'recal-lib.php';
?>

</head>

<body>

<?php
if($_FILES['file']['name'] == "") recalBox("#e3ffd8", "#3f702c", "2");
// error checking round 1

$errMsg1 = err1();
if($errMsg1 != false) errorEnd(1, $errMsg1, "errlog2.txt");

$errMsg7 = err7();
if($errMsg7 != false) errorEnd(7, $errMsg7, "errlog2.txt");

$errMsg2 = err2();
if($errMsg2 != false) errorEnd(2, $errMsg2, "errlog2.txt");

// caches user data files

cacheData(2);

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
if($errMsg3 != false) errorEnd(3, $errMsg3, "errlog2.txt");

$errMsg4 = err4($entries);
if($errMsg4 != false) errorEnd(4, $errMsg4, "errlog2.txt");

$errMsg8 = err8($entries);
if($errMsg8 != false) errorEnd(8, $errMsg8, "errlog2.txt");

$errMsg9 = err9($entries);
if($errMsg9 != false) errorEnd(9, $errMsg9, "errlog2.txt");

// next line gets the number of fields in the file

$nFields = count(explode(",",$entries[0]));

// error-checking: even N of fields

if(!is_int($nFields / 2)){
	$filename = substr($_FILES['file']['name'], 2);
	$errMsg5 = "Your file '$filename' contains an odd number of columns. ReCal2 requires an even number of columns to fulfill its assumption that each adjacent column pair represents two coders' judgments on a single variable. Please rectify this and try again. If the problem persists please notify the author at deen@dfreelon.org .";
}
if($errMsg5 != NULL) errorEnd(5, $errMsg5, "errlog2.txt");

// Pulls the CSV values into a 2-dimensional array in which $i = field and $key = case

$multiArr = array();

$multiArr = makeMulti($entries, $multiArr, $nFields);

$nCases = count($multiArr[0]);

$nDec = $nCases * 2;

// calculates N and % agreements for each variable

$nAgr = array();

for($var = 0; $var < $nFields; $var++){
	$aCount = 0;
	if(is_int($var / 2)){
		for($case = 0; $case < $nCases; $case++){
			if($multiArr[$var][$case] == $multiArr[$var + 1][$case]){
				 $aCount++;
			}
		}
	$nAgr[$var] = $aCount; 	
	}
}

$nAgr = array_values($nAgr); 

$pctAgr = array();

foreach($nAgr as $key => $value){
	$pctAgr[$key] = $value / $nCases; 

}

// creates a 2d array of all the unique values of each variable

$uniqueArr = array();

for($var = 0; $var < $nFields; $var++){
	if(is_int($var / 2)){
		$uniqueArr[$var] = array();
		$unique1 = array_unique($multiArr[$var]);
		$unique2 = array_unique($multiArr[$var + 1]);
		$uniqueArr[$var] = array_merge($unique1, $unique2);
		$uniqueArr[$var] = array_unique($uniqueArr[$var]);
		sort($uniqueArr[$var]);
	}
}

$uniqueArr = array_values($uniqueArr);

// inserts zero values for all variables that are not represented at all in one or the other coder's output

$nVars = count($uniqueArr);

$arrFreqs = array();

for($var = 0; $var < $nFields; $var++){
	$arrFreqs[$var] = array_count_values($multiArr[$var]);
}

$stopKappa = array();

for($var = 0; $var < $nFields; $var++){
	ksort($arrFreqs[$var]);
	if(is_int($var / 2)){
		$stopKappa[$var] = 0;
		$diff = array_diff_key($arrFreqs[$var], $arrFreqs[$var + 1]);	
		if(!empty($diff)){ 
			$stopKappa[$var] = 1;
			foreach($diff as $key => $value){
				$diff[$key] = 0;
				}
			$arrFreqs[$var + 1] = $arrFreqs[$var + 1] + $diff;
			ksort($arrFreqs[$var + 1]);
		}
		unset($diff);	
		$diff = array_diff_key($arrFreqs[$var + 1], $arrFreqs[$var]);
		if(!empty($diff)){ 
			$stopKappa[$var] = 1;
			foreach($diff as $key => $value){
				$diff[$key] = 0;
				}
			$arrFreqs[$var] = $arrFreqs[$var] + $diff;
			ksort($arrFreqs[$var]);
		}	
	}
}

$stopKappa = array_values($stopKappa);

//Scott's Pi

$jmps = array();

$i = 0;

for($var = 0; $var < $nFields; $var++){
	if(is_int($var / 2)){
		$jmps[$i] = array();
		foreach($arrFreqs[$var] as $case => $value){
			$jmp = ($arrFreqs[$var][$case] + $arrFreqs[$var + 1][$case]) / $nDec;
			$jmps[$i][$case] = pow($jmp, 2);	 
		}	
	$i++;	
	}
}

$scottsEA = array();

foreach($jmps as $var => $value){
	$scottsEA[] = array_sum($jmps[$var]);
}

$scottsPi = array();

foreach($scottsEA as $key => $value){
	if(round($scottsEA[$key], 10) == 1){
		$scottsPi[] = "undefined*";
		$invVar = 1;	
	}else $scottsPi[] = ($pctAgr[$key] - $scottsEA[$key]) / (1 - $scottsEA[$key]);
}

//Cohen's Kappa

$marProds = array();

$i = 0;

for($var = 0; $var < $nFields; $var++){
	if(is_int($var / 2)){
		$marProds[$i] = array();
		foreach($arrFreqs[$var] as $case => $value){
			$marProds[$i][$case] = $arrFreqs[$var][$case] * $arrFreqs[$var + 1][$case];	 
		}	
	$i++;	
	}
}

$cohensEA = array();

$nSqd = pow($nCases, 2);

foreach($marProds as $var => $value){
	$cohensEA[$var] = (1 / $nSqd) * (array_sum($marProds[$var]));
}

$cohensK = array();

foreach($cohensEA as $key => $value){
	if(round($cohensEA[$key], 10) == 1){
		$cohensK[] = "undefined*";
		$invVar = 1;	
	}else $cohensK[] = ($pctAgr[$key] - $cohensEA[$key]) / (1 - $cohensEA[$key]);
}

//Krippendorff's Alpha

$totalNs = array();

$i = 0;

for($var = 0; $var < $nFields; $var++){
	if(is_int($var / 2)){
		$totalNs[$i] = array();
		foreach($arrFreqs[$var] as $case => $value){
			$totalNs[$i][$case] = $arrFreqs[$var][$case] + $arrFreqs[$var + 1][$case];	
		}	
	$i++;	
	}
}

foreach($totalNs as $key => $value){
	$totalNs[$key] = array_values($totalNs[$key]);
}

$marPairs = array();

for($var = 0; $var < $nVars; $var++){
	$remainder = count($totalNs[$var]) - 1;
	if($remainder == 0) $marPairs[$var][] = 1;
	foreach ($totalNs[$var] as $key => $value){	
		$rem2 = $remainder;
			while ($rem2 > $key){
				$marPairs[$var][] = $totalNs[$var][$key] * $totalNs[$var][$rem2];
				$rem2--;
		}
	}
}

$marSums = array();

foreach($marPairs as $var => $value){
	$marSums[] = array_sum($marPairs[$var]);
}

$nDis = array();

for($var = 0; $var < $nVars; $var++){
	$nDis[] = $nCases - $nAgr[$var];
}

$kripsAlpha = array();

foreach($nAgr as $var => $value){
	if($marSums[$var] == 1) $kripsAlpha[] = "undefined*";
	else $kripsAlpha[] = 1 - (($nDec - 1) * ($nDis[$var] / $marSums[$var]));
}

// begin data printout

congrats();

// function to store data for a run window-style display

if($_POST["save"] != 1 or $_SESSION['recal'] == 3 or $_SESSION['recal'] == 4){ 
	unset($_SESSION[$runWindow]);
	$_SESSION['nHist'] = 0;
}

if(!isset($_SESSION[$runWindow])){ 
	$_SESSION[$runWindow] = array();
	$_SESSION['recal'] = 2;
}	

$_SESSION[$runWindow][] = count($_SESSION[$runWindow]);
$_SESSION['nHist']++;

foreach($_SESSION[$runWindow] as $key => $value){	
	if(!isset($_SESSION[$runWindow][$key][0])){
		$_SESSION[$runWindow][$key] = array();
		foreach($nAgr as $keyy => $valu){
			$_SESSION[$runWindow][$key][] = array();
			$_SESSION[$runWindow][$key][$keyy][] = $_FILES['file']['name'];
			$_SESSION[$runWindow][$key][$keyy][] = $_FILES['file']['size'];
			$_SESSION[$runWindow][$key][$keyy][] = $nFields;
			$_SESSION[$runWindow][$key][$keyy][] = $nFields / 2;
			$_SESSION[$runWindow][$key][$keyy][] = $pctAgr[$keyy] * 100;
			$_SESSION[$runWindow][$key][$keyy][] = $scottsPi[$keyy];
			$_SESSION[$runWindow][$key][$keyy][] = $cohensK[$keyy];
			$_SESSION[$runWindow][$key][$keyy][] = $kripsAlpha[$keyy];
			$_SESSION[$runWindow][$key][$keyy][] = $nAgr[$keyy];
			$_SESSION[$runWindow][$key][$keyy][] = $nDis[$keyy];
			$_SESSION[$runWindow][$key][$keyy][] = $nCases;
			$_SESSION[$runWindow][$key][$keyy][] = $nDec;
			$_SESSION[$runWindow][$key][$keyy][] = $invVar;
		}
	}
}

foreach($_SESSION[$runWindow] as $key => $value){ ?>
	<a name="result<?php
echo $key; ?>"></a><div style="text-align:center; font-size: 26px; margin-top: 19px;">ReCal for 2 Coders<br />
	results for file "<?php
echo $_SESSION[$runWindow][$key][0][0]; ?>"</div><br>
	<table border="0" align="center">
	<tr><td>File size: </td>
	<td align='right'> <?php
echo $_SESSION[$runWindow][$key][0][1]; echo " bytes"; ?> </td></tr>
	<tr><td> N columns: </td>
	<td align='right'> <?php
echo $_SESSION[$runWindow][$key][0][2]; ?> </td></tr>
	<tr><td> N variables: </td>
	<td align='right'> <?php
echo $_SESSION[$runWindow][$key][0][3]; ?> </td></tr>
	<tr><td> N coders per variable:</td>
    <td align='right'> 2</td></tr>
    </table><br>
    <table cellpadding="3" border="1" align="center">
<tr><td width="90"></td><td><strong>Percent Agreement</strong></td><td><strong>Scott's Pi</strong></td><td><strong>Cohen's Kappa</strong></td><td><strong>Krippendorff's Alpha (nominal)</strong></td><td>N Agreements</td><td>N Disagreements</td><td>N Cases</td><td>N Decisions</td></tr>
<?php
	foreach($_SESSION[$runWindow][$key] as $keyy => $valu){
		$varNum = $keyy + 1;
		$colNum = $varNum * 2;
		$kolNum = $colNum - 1;
		echo "<tr><td>Variable $varNum (cols $kolNum & $colNum)</td>";
		echo "<td>" . round($_SESSION[$runWindow][$key][$keyy][4], 1) . "%</td>";
		echo "<td>"; 
		if(is_string($_SESSION[$runWindow][$key][$keyy][5])) echo $_SESSION[$runWindow][$key][$keyy][5]; 
		else echo round($_SESSION[$runWindow][$key][$keyy][5], 3);  
		echo "</td>";
		echo "<td>"; 
		if(is_string($_SESSION[$runWindow][$key][$keyy][6])) echo $_SESSION[$runWindow][$key][$keyy][6]; 
		else echo round($_SESSION[$runWindow][$key][$keyy][6], 3);  
		echo "</td>";
		echo "<td>";
		if(is_string($_SESSION[$runWindow][$key][$keyy][7])) echo $_SESSION[$runWindow][$key][$keyy][7]; 
		else echo round($_SESSION[$runWindow][$key][$keyy][7], 3);  
		echo "</td>";
		echo "<td>" . $_SESSION[$runWindow][$key][$keyy][8] . "</td>";
		echo "<td>" . $_SESSION[$runWindow][$key][$keyy][9] . "</td>";
		echo "<td>" . $_SESSION[$runWindow][$key][$keyy][10] . "</td>";
		echo "<td>" . $_SESSION[$runWindow][$key][$keyy][11] . "</td></tr>";
		} ?>
	</table>   
<?php
if($_SESSION[$runWindow][$key][$keyy][12] == 1) { ?>
<div style="text-align: center">*Scott's pi, Cohen's kappa, and Krippendorff's Alpha are undefined for this variable due to <a href="http://dfreelon.org/2008/10/24/recal-error-log-entry-1-invariant-values/">invariant values.</a></div>
<?php
    } 
}
?>

<br />

<?php
 
exportIt();
footer("#e3ffd8", "#3f702c");

// stats below here
?>

<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />

</body>
</html>