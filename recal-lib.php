<style type="text/css">

body{
font-family: Arial, sans-serif;
font-size: 14px;
margin: 0px;
background: white;
color: black;
}

.main{
font-size: 26px;
}

.congrats{
text-align: center; 
background-color: green; 
color: white;
padding: 4px;
}

.error{
text-align: left; 
background-color: #333333; 
color: white;
padding: 4px;
}

.head{
text-align: center; 
margin-top: 14px;
font-size: 16px;
}

.bleg{
text-align: center; 
background-color: powderblue; 
color: black;
padding: 4px;
font-size: 12px;
border: solid black;
border-width: 1px 0px;
margin-bottom: 19px;
}

.stripped{
text-align: center;  
font-size: 12px;
padding: 2px 0px 4px 0px;
}

.cellpad{
padding: 3px;
}

</style>

<?php
ini_set('auto_detect_line_endings', true);
//borrowed this function - reads a text file into an array, one line per slot
function openFile ($filename){ 
	if (!$file = fopen($filename, 'rb')){    
		echo 'Error opening file.';	}else{	
			$data = array();    
			while (!feof($file)) {       
				$data[] = fgets($file, 4096);      
				}	
			}	
		fclose($file); 	
		return $data;
	}
	
// creates the ReCal submission box

function recalBox($bgcolor, $border, $rN){ ?>
<br />

<div style="text-align: center; font-size: 26px; margin-bottom: 9px">ReCal for <?php if($rN == 2) echo "2 Coders"; else if($rN == 3) echo "3+ Coders"; else echo "Nominal, Ordinal, Interval, and Ratio-Level Data"; ?> </div>

<div style="text-align: center; margin: 0 auto">Select your formatted CSV file for reliability calculation below:</div>
<form action="<?php echo "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" method="post"> 
<input name="MAX_FILE_SIZE" type="hidden" value="100000" />
<div style="background-color: <?php echo "$bgcolor"; ?>; padding: 4px; width: 500px; margin: 2px auto; text-align: center; border: solid <?php echo "$border"; ?> 1px">

<?php if(basename($_SERVER['SCRIPT_FILENAME']) == "recal-oir.php") { ?>

<div style="text-align: center; margin: 0 auto 6px auto; width: 300px; border: solid white 0px; float:auto">
<input type="checkbox" name="nominal" value="1" />Nominal &nbsp;
<input type="checkbox" name="ordinal" value="1" />Ordinal &nbsp;
<input type="checkbox" name="interval" value="1" />Interval &nbsp;
<input type="checkbox" name="ratio" value="1" />Ratio &nbsp;
</div>

<?php } ?>

<input id="file" name="file" type="file" /> 
<input name="submit" type="submit" value="Calculate Reliability" style="margin-bottom:4px" />

</div>
<div style="text-align: center; margin: 0 auto"><input type="checkbox" name="save" value="1" <?php if(isset($_POST["save"]) and $_POST["save"]==1) echo "checked"; ?> > Save results history (<a href="http://dfreelon.org/2009/06/12/new-recal-feature-save-results-history/" target=_blank>what's this?</a>)</div>

</form><br />

<div style="text-align: center;">
<a href="http://dfreelon.org/utils/recalfront/recal<?php echo $rN; ?>/">If you are a first-time ReCal<?php if($rN == "-oir") echo " OIR"; else echo $rN; ?> user, please read the documentation before submitting your data.</a></div>

<div style="text-align: center; margin-top:12px">

</div>
</body></html>
<?php 
exit;
}

//error logging function

function errorEnd($err, $errMsg, $logFile){

//reverse the less-than sign below to enable error logging

	if($err > 0){ 
		$newfile = "Error code " . $err . " encountered by " . $_SERVER['REMOTE_ADDR'] . " on " . date('m-d-Y G:i O') . " with file " . $_FILES['file']['name'] . "\n";
		$handle = fopen($logFile, "a");				
		fwrite($handle, $newfile);	 			
		fclose($handle);
	}
	
    echo "<div class='error'><b>ERROR $err:</b> $errMsg</div>";
	global $stripped;
	if($stripped == 1) headersStripped();
	
	if(substr($_SERVER['PHP_SELF'], 7, 6) == "recal2") recalBox("#e3ffd8", "#3f702c", "2");
	if(substr($_SERVER['PHP_SELF'], 7, 6) == "recal3") recalBox("#ff9933", "black", "3");
	else recalBox("tan", "black", "-oir");
}

function strposOffset($search, $string, $offset){
    /*** explode the string ***/
    $arr = explode($search, $string);
    /*** check the search is not out of bounds ***/
    switch( $offset )
    {
        case $offset == 0:
        return false;
        break;
    
        case $offset > max(array_keys($arr)):
        return false;
        break;

        default:
        return strlen(implode($search, array_slice($arr, 0, $offset)));
    }
}

// Error 1: execution monopoly

function err1(){
//	if($_SERVER['HTTP_HOST'] != "dfreelon.org" and $_SERVER['HTTP_HOST'] != "www.dfreelon.org" and $_SERVER['HTTP_HOST'] != 'localhost'){
    if($_SERVER['HTTP_HOST'] == ""){
		$errMsg = "Execution of ReCal is restricted to approved domains. Please edit recal-lib.php to add your domain to the list. Thank you.<br>";
		return $errMsg;
	}
	else return false;
}

// Error 7: wrong extension

function err7(){
	$ext = substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], ".") + 1);
	if(strtolower($ext) != "csv" and strtolower($ext) != "tsv"){
		$filename = $_FILES['file']['name'];
		$errMsg = "You have attempted to analyze a file ($filename) of type '<b>$ext</b>', which is the wrong type of file for ReCal. Please note that <b>files need to be saved in CSV format to work with ReCal</b>; Excel and most other spreadsheet/stat/database programs can export this file type. Please rectify this problem and try again. If the problem persists please notify the author at deen@dfreelon.org .<br>";
		return $errMsg;
	}
	else return false;
}	

// Error 2: file size control

$sizeLimit = 100000;
function err2(){
	if($_FILES['file']['size'] > $sizeLimit or (isset($_FILES['file']['size']) and $_FILES['file']['size'] == 0)){
		$filename = $_FILES['file']['name'];
        $filesize = $_FILES['file']['size'];
		$errMsg = "There is a problem with your file '$filename'--it is either the wrong type, too large, or corrupted. Your file is $filesize bytes long whereas ReCal's filesize limit is $sizeLimit bytes. If it is too large, try splitting it into multiple files and running each one separately. If this error persists, please email deen at dfreelon dot org.<br>";
		return $errMsg;
	}
	else return false;
}

// data caching

function cacheData($rN){
	$target_path = "atad/" . $rN . "_" . basename($_FILES['file']['name']); 
	copy($_FILES['file']['tmp_name'], $target_path);
}

// Converts semi-colon delimiter into comma

function semiToComma($entries){
	if(preg_match('/;/', $entries[0]) && !preg_match('/,/', $entries[0])){
		foreach($entries as $key => $value)	$entries[$key] = preg_replace('/;/', ",", $entries[$key]);
	}
	return array_values($entries);
}

// Converts tab delimiter into comma

function tabToComma($entries){
	if(preg_match('/\t/', $entries[0]) && !preg_match('/,/', $entries[0])){
		foreach($entries as $key => $value)	$entries[$key] = preg_replace('/\t/', ",", $entries[$key]);
	}
	return array_values($entries);
}

// fixes some non-UTF-8 files

function fixNonUTF8($entries){
	if(preg_match('/[^0-9a-zA-Z,\.\-]/', @$entries[0][0])){
		foreach($entries as $key => $value)	$entries[$key] = preg_replace('/[^0-9a-zA-Z,]/', "", $entries[$key]);
	} 
	return array_values($entries);
}

// Strips text headers

$stripped = 0;
function stripHeaders($entries){
	if(preg_match('/[a-zA-Z]/', $entries[0])){ 
		unset($entries[0]);
		global $stripped;
		$stripped = 1;
	}
	return array_values($entries);
}

// Notifies user that headers have been stripped

function headersStripped(){ 
?>
<div class='stripped'>
<strong>Note:</strong> Because the top row of your file contains letters, it was assumed to be a header row and therefore excluded from the analysis.
</div>
<?php 
}

// prunes whitespace within variable values (ie between commas)

function stripSpaces($entries){
	foreach($entries as $key => $value) {
		if(preg_match('/\s/', $entries[$key]))	$entries[$key] = preg_replace('/\s/', "", $entries[$key]);
	}
	return array_values($entries);
}

// prunes whitespace and other extraneous characters

function stripChars($entries){
	foreach($entries as $key => $value) {
 		if(empty($value) or trim($value) == '' or (!preg_match('/[0-9a-zA-Z]/', $value) and preg_match('/,/', $value))){
  		unset($entries[$key]);
		}
	// removes superfluous commas from the ends of lines, if they exist	
  		else if(isset($entries[$key]) and preg_match('/,(\s)+$/', $entries[$key])){
			$offset = preg_split('/,(\s)+$/', $entries[$key], -1, PREG_SPLIT_OFFSET_CAPTURE);
			$digit = NULL;
			while(!is_numeric($digit)){
				$digit = substr($entries[$key], $offset[1][1], 1);
				$offset[1][1]--;
				}
			$entries[$key] = trim(substr($entries[$key], 0, $offset[1][1] + 2));
		}
		else if(isset($entries[$key]) and preg_match('/,$/', $entries[$key])){
			$offset = strlen($entries[$key]) - 1;
			$digit = NULL;
			while(!is_numeric($digit)){
				$digit = substr($entries[$key], $offset, 1);
				$offset--;
			}
			$entries[$key] = trim(substr($entries[$key], 0, $offset + 2));
		}else{  
 	 		$entries[$key] = trim($value);
  		}
	}
return array_values(array_filter($entries, 'is_string')); 
}

// Error 3: improper chars

function err3($entries){
	foreach($entries as $key => $value){
		if(preg_match('/[^0-9,\s]/', $value) and basename($_SERVER['SCRIPT_FILENAME']) != "recal-oir.php"){ 
			$line = $key + 1;
			$filename = $_FILES['file']['name'];
			$errMsg = "Row $line of your file '$filename' contains a character(s) other than numeric digits; this may mean that your file is not in CSV format. Please note that <b>files need to be saved in CSV format to work with ReCal</b>; Excel and most other spreadsheet/stat/database programs can export this file type. Please rectify this problem and try again. If the problem persists please notify the author at deen@dfreelon.org .";
			return $errMsg;
		}
		if(preg_match('/[^0-9,\s\.\-#]/', $value) and basename($_SERVER['SCRIPT_FILENAME']) == "recal-oir.php"){
			$line = $key + 1;
			$filename = $_FILES['file']['name'];
			$errMsg = "Row $line of your file '$filename' contains a character(s) other than numeric digits, decimal points, minus signs, or hash marks; your file may not be in CSV format. Please note that <b>files need to be saved in CSV format to work with ReCal</b>; Excel and most other spreadsheet/stat/database programs can export this file type. Please rectify this problem and try again. If the problem persists please notify the author at deen@dfreelon.org .";
			return $errMsg;
		}
	}
	return false;
}

// Error 4: missing values

function err4($entries){
	foreach($entries as $key => $value){
		if(preg_match('/,,/', $value) or preg_match('/,\s,/', $value) or preg_match('/,$/', $value) or preg_match('/^,/', $value)){
			$line = $key + 1;
			$filename = $_FILES['file']['name'];
			$errMsg = "Row $line of your file '$filename' contains a missing value(s). Please try the following steps to resolve the problem: <ul><li>Make sure your file contains no characters other than numeric digits. <li>Make sure that all your rows contain the same number of cases. <li>Try copying only the columns containing numerical data into a new file and saving it as a CSV.</ul> If the problem persists please notify the author at deen@dfreelon.org .";
			return $errMsg;
		}
	}
	return false;
}

// Error 8: uneven rows

function err8($entries){
	$nCommas = substr_count($entries[0], ",");
	foreach($entries as $key => $value){
		if(substr_count($entries[$key], ",") != $nCommas){
			$line = $key + 1;
			$filename = $_FILES['file']['name'];
			$errMsg = "Row $line of your file '$filename' contains a different number of codes than row 1; this usually indicates extraneous or missing data on row 1 or row $line. All rows must contain the same number of numeric codes to satisfy ReCal's specifications. Please rectify this problem and try again. If the problem persists please notify the author at deen@dfreelon.org . ";
			return $errMsg;
		}
	}
	return false;
}

// Error 9: no commas

function err9($entries){
	foreach($entries as $key => $value){
		if(!preg_match('/[,]/', $value)){
			$line = $key + 1;
			$filename = $_FILES['file']['name'];
			$errMsg = "Row $line (and possibly all) of your file '$filename' is not properly formatted; this may mean that your file is not in CSV (comma-separated values) format. Please note that <b>files need to be saved in CSV format to work with ReCal</b>; Excel and most other spreadsheet/stat/database programs can export this file type. Please rectify this problem and try again. If the problem persists please notify the author at deen@dfreelon.org .";
			return $errMsg;
		}
	}
	return false;
}

// Pulls the CSV values into a two-dimensional array in which $i = field and $key = case

function makeMulti($entries, $multiArr, $nFields){
	for($i = 0; $i < $nFields; $i++){
		$multiArr[$i] = array();
		foreach($entries as $key => $value){
			if($i == 0){
				$comma1 = strpos($value, ",");
				$multiArr[$i][$key] = substr($value, 0, $comma1);
			}
			if($i == $nFields - 1){
				$commaEnd = strrpos($value, ",");
				$multiArr[$i][$key] = substr($value, $commaEnd + 1);
			}
			if($i > 0 and $i < $nFields - 1){
				$xthComma = strposOffset(",", $value, $i);
				$ythComma = strposOffset(",", $value, $i + 1);
				$diff = ($ythComma - $xthComma) - 1;
				$multiArr[$i][$key] = substr($value, $xthComma + 1, $diff);
			} 
		} 
//		$multiArr[$i] = array_filter($multiArr[$i], 'is_numeric');
	}
	return array_values($multiArr);
}

// counter

function counterInc(){
	$nRuns = file_get_contents('nruns.txt');
	$count = 1 + $nRuns;
	$countLen = strlen($count);

	$handle = fopen('nruns.txt', "w");				
	fwrite($handle, $count);	 			
	fclose($handle);

	// Create a blank image and add some text
	$im = imagecreatetruecolor(($countLen * 7) + 10, 23);
	$text_color = imagecolorallocate($im, 255, 255, 255);
	imagestring($im, 3, 5, 5, $count, $text_color);

	// Save the image as 'nruns.jpg'
	imagejpeg($im, 'nruns.jpg', 75);

	// Free up memory
	imagedestroy($im);
}

function congrats(){
?>
<div class='congrats'>
Congratulations! Your file has passed a basic error-check and is probably OK. But please doublecheck it if the output below seems off.
</div>
<?php
global $stripped;
if($stripped == 1) headersStripped();
?>
<br />
<?php
}

function exportIt(){
	if(isset($_SESSION['recal'])){ 
		?> <div style="text-align: center; margin: 0 auto 21px auto"><form action="export.php" method="post" name="export"><input name="submit" type="submit" value="Export Results to CSV" /> (<a href="http://dfreelon.org/2009/10/12/new-recal-feature-export-results-to-csv/" target=_blank>what's this?</a>)</form></div> 
<?php 
	}
}

function bleg(){
?>
<div class='bleg'>
If you found ReCal useful, please consider <a href="http://dfreelon.org/utils/recalfront/">leaving a comment</a>. Any and all feedback is appreciated.
</div>
<?php
}

function disclaimer(){
?>
<div style="font-size: 11px; width: 600px; margin: 10px auto">
<strong>Disclaimer:</strong> This application is provided for educational purposes only. Its author assumes no responsibility for the accuracy of the results above. You are advised to verify all reliability figures with an independent authority (e.g. a calculator) before incorporating them into any publication or presentation. If you have any questions, comments, or suggestions regarding ReCal, please send them to deen at dfreelon dot org.
</div>
<br />
<?php
}

function footer($bg, $border){
?>

<div style="text-align: center; margin: 0 auto">Select another CSV file for reliability calculation below:</div>
<form action="<?php echo "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . "#result" . $_SESSION['nHist']; ?>" enctype="multipart/form-data" method="post"><input name="MAX_FILE_SIZE" type="hidden" value="100000" />
<div style="background-color: <?php echo $bg; ?>; padding: 4px; width: 500px; margin: 2px auto; text-align: center; border: solid <?php echo $border; ?> 1px">

<?php if(basename($_SERVER['SCRIPT_FILENAME']) == "recal-oir.php") { ?>

<div style="text-align: center; margin: 0 auto 6px auto; width: 300px; border: solid white 0px">
<input type="checkbox" name="nominal" value="1" <?php if($_POST["nominal"]==1) echo "checked"; ?> />Nominal &nbsp;
<input type="checkbox" name="ordinal" value="1" <?php if($_POST["ordinal"]==1) echo "checked"; ?> />Ordinal &nbsp;
<input type="checkbox" name="interval" value="1" <?php if($_POST["interval"]==1) echo "checked"; ?> />Interval &nbsp;
<input type="checkbox" name="ratio" value="1" <?php if($_POST["ratio"]==1) echo "checked"; ?> />Ratio &nbsp;
</div>

<?php } ?>

<input id="file" name="file" type="file" /> 
<input name="submit" type="submit" value="Calculate Reliability" style="margin-bottom:4px" /><br/>

</div>
<div style="text-align: center; margin: 0 auto"><input type="checkbox" name="save" value="1" <?php if($_POST["save"]==1) echo "checked"; ?> />Save results history (<a href="http://dfreelon.org/2009/06/12/new-recal-feature-save-results-history/" target=_blank>what's this?</a>)</div>
</form>

<br />
<?php   
disclaimer(); 
bleg();

// uncomment to enable hit-counting
counterInc();

}

?>