<?php
	
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'do' && isset($_REQUEST['from']) && isset($_REQUEST['to']) ){

	include_once("./lib/includes.php");
	$Table_Name = 'geo_fence_alerts';
	
	$From_Date = $_REQUEST['from'];
	$To_Date = $_REQUEST['to'];
	
	//Call the function to generate the array
	if(!empty($From_Date) && !empty($To_Date)) {
		$Date_Array = createDateRangeArray($From_Date, $To_Date);
	}	

	// Fetching IMEI number
	if(isset($_REQUEST['accountid'])){
		$Mysql_Query1 = "select * from device_master where user_account_id = '".$_REQUEST['accountid']."'";
		$Mysql_Query_Result1 = mysql_query($Mysql_Query1) or die(mysql_error());
		$device_count1 = mysql_num_rows($Mysql_Query_Result1);
		if($device_count1>=1){
			while($device_list = mysql_fetch_array($Mysql_Query_Result1)){
				$IMEI_Array[$device_list['imei']] = $device_list['imei'];
			}
		}
	}
	else if(isset($_REQUEST['imei'])) {
		$IMEI_Array = array($_REQUEST['imei']);
	}		

	############################################
	# 
	#	Geofence Calculator Report
	#
	############################################

	function Geofence_Calculator_Report($Date, $IMEI, $Get_AccountID_IMEI){
		
		$Result = null;
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
				$Table_Name = 'geo_fence';
				
				//Geofence Calculator
				$Geofence_Decide_InOut_Array = Geofence_Decide_InOut($Get_AccountID_IMEI, $Latitude, $Longitude, $Device_Date_Stamp, $Table_Name);

				//Geofence Calculator
				$Geofence_Decision_Maker_Status = Geofence_Decision_Maker($Geofence_Decide_InOut_Array, $Get_AccountID_IMEI, $Latitude, $Longitude, $Location_Name, $IMEI, $Device_Date_Stamp, $Table_Name = 'geo_fence_alerts');
				
				if($Geofence_Decision_Maker_Status)
					$Result[] = "<br />Finished for ".$IMEI."--On--".$Date."<br />";
			}
		}
		return $Result;
	}	
	
	
	############################################
	# 
	#	Geofence Calculator
	#
	############################################	

	function Geofence_Decision_Maker($Geofence_Decide_InOut_Array, $Get_AccountID_IMEI, $Latitude, $Longitude, $Location_Name, $IMEI, $Device_Date_Stamp, $Table_Name){
		// Gefence Insert
		if(count($Geofence_Decide_InOut_Array) > 0){
			
			$G = 0;
			foreach($Geofence_Decide_InOut_Array as $Geofence_Decide_InOut_Val){
			
				$Trip_Index = $Geofence_Decide_InOut_Val[0];
				$Trip_Status[$Trip_Index] = $Geofence_Decide_InOut_Val[1];
				$Distance[$Trip_Index] = $Geofence_Decide_InOut_Val[2];
				$Alert_Dispatch = 0;
				$Server_Date_Stamp = date("Y-m-d H:i:s");

			
					
				// Check Geofence exist or not
				$Geofence_Existing_Alerts_Status[$Trip_Index] = Geofence_Alerts_Exist($IMEI, $Trip_Index, $Table_Name);
				
				// First Time Geo Fence
				if(empty($Geofence_Existing_Alerts_Status[$Trip_Index]) && $Trip_Status[$Trip_Index] != 'OUT'){
					$Geofence_Alerts_Insert = Geofence_Alerts_Insert($Get_AccountID_IMEI, $IMEI, $Latitude, $Longitude, $Location_Name, $Trip_Status[$Trip_Index],$Alert_Dispatch, $Trip_Index, $Device_Date_Stamp, $Server_Date_Stamp, $Data, $Table_Name);
				}
				else{
					//Insert Geofence once data inserted for first time
					if ($Geofence_Existing_Alerts_Status[$Trip_Index] == 'IN' && $Trip_Status[$Trip_Index] == 'OUT'){
						
						$Geofence_Alerts_Insert = Geofence_Alerts_Insert($Get_AccountID_IMEI, $IMEI, $Latitude, $Longitude, $Location_Name, $Trip_Status[$Trip_Index],$Alert_Dispatch, $Trip_Index, $Device_Date_Stamp, $Server_Date_Stamp, $Data, $Table_Name);
						
					}
					else if ($Geofence_Existing_Alerts_Status[$Trip_Index] == 'OUT' && $Trip_Status[$Trip_Index] == 'IN'){
						
						$Geofence_Alerts_Insert = Geofence_Alerts_Insert($Get_AccountID_IMEI, $IMEI, $Latitude, $Longitude, $Location_Name, $Trip_Status[$Trip_Index],$Alert_Dispatch, $Trip_Index, $Device_Date_Stamp, $Server_Date_Stamp, $Data, $Table_Name);
						
					}
				}
				$G++;
			}
			
		}	
		echo $Debug_Result.="<hr />";
		if($Geofence_Alerts_Insert)
			return true;
		else
			return false;
	}

	############################################
	# 
	#	Date Array
	#
	############################################
	
	// For all the Date
	foreach($Date_Array as $Date_Val){
		
		// For all the IMEI
		foreach($IMEI_Array as $IMEI){
			
			$Check_Exist_Result = Check_Exist($Table_Name, "date(date_stamp) = '".$Date_Val."' and imei = ".$IMEI." ");

			if($Check_Exist_Result == 0){

				//Get Account ID based on IMEI
				$Get_AccountID_IMEI = Get_AccountID_IMEI($IMEI);
					
				// Call the Geofence Calculation Report
				$Geofence_Calculator_Result = Geofence_Calculator_Report($Date_Val, $IMEI, $Get_AccountID_IMEI);
			
				echo "<br />Finished for ".$IMEI."--On--".$Date_Val."";	
			}	
			else{
				echo "Already genereated for ".$Date_Val." - ".$IMEI."";
			}
		}
	}		
}	
else{
	echo "Geofence Calculation - parameter empty";
}
?>
