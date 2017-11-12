<?php

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'do'){
	
	include("./lib/includes.php");
	
	if(isset($_REQUEST['imei']))
		$IMEI = $_REQUEST['imei'];
	
	if(isset($_REQUEST['date']))
		$Date = $_REQUEST['date'];
	
	if(!isset($IMEI) && !isset($Date) )
		$Dates_Array = Dates_Generate(10, 2017, 'dmY');
	else
		$Dates_Array = array($Date);
	
	foreach($Dates_Array as $Dates_Val){
		
		// Getting all the data by status
		$Get_Summary = Get_Daily_Summary($Dates_Val, $IMEI);

		echo "<hr /><h4>Total Up Time -- ".$Get_Summary[1];
		echo "<br />Total Seperated Up Time -- ".$Get_Summary[2];
		echo "</h4><br />Moving Time -- ".$Get_Summary[3];
		echo "<br />Stopped Time -- ".$Get_Summary[4];
		echo "<br />Idle Time -- ".$Get_Summary[5];
		echo "<br />Unknown Time -- ".$Get_Summary[6];
		echo "<hr />";
		
	}	
}	
else{
	echo "Distance Calculation - parameter empty";
}
?>