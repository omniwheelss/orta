<?php
	
	if($_REQUEST['do'] != 'yes'){
		exit;
	}

	include_once("./lib/includes.php");

	//$IMEI = "864547034419338";
	//$IMEI = "864547034266879";
	$IMEI = "864547036439193";
	


	for($i = 1 ; $i <= 2; $i++){
		$Date_Array[] =  "2018-04-".$i;
	}


	foreach($Date_Array as $Date_Val){
		$Geofence_Calculator_Report = Geofence_Calculator_Report($Date_Val, $IMEI);
	}	

	print_r($Geofence_Calculator_Report);

	function Geofence_Calculator_Report($Date, $IMEI){
		
		$Date_From = $Date. " 00:00:00";
		$Date_To = $Date. " 23:59:59";
		
		$Mysql_Query = "select * from device_data where imei = '".$IMEI."' and device_date_stamp between '".$Date_From."' and '".$Date_To."' order by device_date_stamp asc";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		$Mysql_Record_Count = mysql_num_rows($Mysql_Query_Result);
		if($Mysql_Record_Count >= 1){
			while($Query_Result = mysql_fetch_array($Mysql_Query_Result)){
				$IMEI = $Query_Result['imei'];
				$Latitude = $Query_Result['latitude'];
				$Longitude = $Query_Result['longitude'];
				$Location_Name = $Query_Result['location'];
				$Device_Date_Stamp = $Query_Result['device_date_stamp'];
				$Server_Date_Stamp = date("Y-m-d H:i:s");
				include("geo_fence_cal.php");
			}
			echo "Finished for ".$IMEI."--On--".$Date."<br />";
		}
		
	}	

?>
