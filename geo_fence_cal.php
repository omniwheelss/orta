<?php

	$Get_AccountID_IMEI = Get_AccountID_IMEI($IMEI);
	
	if(isset($Get_AccountID_IMEI)){
		
		PrintMessage("Inside geo_fence check",$Debug);
		
		// Gefence Calculator
		$Geofence_Calculator_Array = Geofence_Calculator($Get_AccountID_IMEI, $Latitude, $Longitude, $Device_Date_Stamp, $Table_Name = 'geo_fence1' );

		PrintMessage("After Geofence Calculator check",$Debug);
		
		// Gefence Insert
		if(count($Geofence_Calculator_Array) > 0){
			
			
			foreach($Geofence_Calculator_Array as $Geofence_Calculator_Val){
			
				$Trip_Index = $Geofence_Calculator_Val[0];
				$Trip_Status[$Trip_Index] = $Geofence_Calculator_Val[1];
				$Distance[$Trip_Index] = $Geofence_Calculator_Val[2];
				$Alert_Dispatch = 0;
				$Table_Name = 'geo_fence_alerts1';
				
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
			}
			
		}
	}
	else{
		PrintMessage("imei not Exist in DEVICE_REGISTER",$Debug);
	}
?>