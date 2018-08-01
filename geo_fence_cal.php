<?php

	$Get_AccountID_IMEI = Get_AccountID_IMEI($IMEI);
	
	if(isset($Get_AccountID_IMEI)){
		
		PrintMessage("Inside geo_fence check",$Debug);
		
		// Gefence Calculator
		//$Geofence_Calculator_Array = Geofence_Calculator($Get_AccountID_IMEI, $IMEI, $Latitude, $Longitude, $Location_Name, $Device_Date_Stamp, $Data );
		$Geofence_Decide_InOut_Array = Geofence_Decide_InOut($Get_AccountID_IMEI, $Latitude, $Longitude, $Device_Date_Stamp, $POI_Table_Name = 'geo_fence') ;

				// Gefence Insert
		if(count($Geofence_Decide_InOut_Array) > 0){
			//Geofence Decision Maker
			$Geofence_Decision_Maker_Status = Geofence_Decision_Maker($Geofence_Decide_InOut_Array, $Get_AccountID_IMEI, $Latitude, $Longitude, $Location_Name, $IMEI, $Device_Date_Stamp, $POI_Alerts_Table_Name = 'geo_fence_alerts_new');
		}	
	}
	else{
		PrintMessage("imei not Exist in DEVICE_REGISTER",$Debug);
	}
?>