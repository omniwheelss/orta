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
	
	function Get_Daily_Summary1($Date, $IMEI){
		
		$Result = null;
		
		$From_Date = $Date. " 00:00:00";
		$To_Date = $Date. " 23:59:59";

		$Mysql_Query = "select * from device_data where imei = '".$IMEI."' and device_date_stamp between '".$From_Date."' and '".$To_Date."' and alert_msg_code != 'IN|0' order by device_date_stamp asc";// limit 10, 19";
		//$Mysql_Query = "select * from device_data where imei = '864547034419338' and device_date_stamp between '2017-10-24 08:50:00' and '2017-10-24 08:58:00' order by device_date_stamp asc";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		$Row_Count = mysql_num_rows($Mysql_Query_Result);
		if($Row_Count >=1){
			$i = 1;
			$Decision_Maker_All_Diff = array();
			$Decision_Maker_Moving_Diff = array();
			$Decision_Maker_Stopped_Diff = array();
			$Decision_Maker_Idle_Diff = array();
			$Decision_Maker_Unknown_Diff = array();
			
			while($Result_Array = mysql_fetch_array($Mysql_Query_Result)){
				
				// Skip invalid Records
				//$Valid_Records = Remove_Invalid_Records($Result_Array);
				
				//foreach($Valid_Records as $Result_Array)
				{
					$Diff_Record = 0;
					$Speed_Array[] = $Result_Array['speed'];
					$Device_Stamp_All_Array[] = $Result_Array['device_date_stamp'];
					$GPS_Move_Status = $Result_Array['gps_move_status'];
					$IGN = $Result_Array['ign'];
					$Speed = $Result_Array['speed'];
					$Alert_Msg_Code = $Result_Array['alert_msg_code'];
					
					// Current Status Check
					$Data_Cur_Status = Data_Current_Status($GPS_Move_Status, $Speed, $IGN, $Alert_Msg_Code);
					$Data_Cur_Status_Val = $Data_Cur_Status[0];
					$Data_Pre_Status_Val = $Data_Pre_Array[0];
					
					// Checking Record is different and assign flag
					if($Data_Pre_Status_Val != $Data_Cur_Status_Val && !empty($Data_Pre_Status_Val)){
						$Diff_Record = 1;
					}
					
					$Pre_Cur_Diff_Array = array($Data_Pre_Array[1], $Result_Array['device_epoch_time']);
					// Calucalte only equal record - not diff record
					if($Diff_Record == 0){
						// All data sequence
						$Pre_Cur_Diff_Val = Diff_Between_Records('epoch', $Pre_Cur_Diff_Array, $Data_Pre_Status_Val, $Data_Cur_Status_Val, $Diff_Record);
						$Pre_Cur_Diff_Sum = array_sum($Pre_Cur_Diff_Val);
						$All_DateTime_Diff[] = $Pre_Cur_Diff_Sum;

						// Data by status
						// Moving
						if($Data_Cur_Status_Val  == 'Moving'){
							$Device_Stamp_Moving_Array[] = $Result_Array['device_epoch_time'];
							$Result_Array['device_date_stamp'] = "Moving--".$Result_Array['device_date_stamp'];
							$Pre_Cur_Diff_Moving_Val = Diff_Between_Records('epoch', $Pre_Cur_Diff_Array, $Data_Pre_Status_Val, $Data_Cur_Status_Val, $Diff_Record);
							$Pre_Cur_Diff_Moving_Sum = array_sum($Pre_Cur_Diff_Moving_Val);
							$DateTime_Moving_Diff[] = $Pre_Cur_Diff_Moving_Sum;

						}
						//Stopped
						else if($Data_Cur_Status_Val == 'Stopped'){
							$Device_Stamp_Stopped_Array[] = $Result_Array['device_epoch_time'];
							$Result_Array['device_date_stamp'] = "Stopped--".$Result_Array['device_date_stamp'];
							$Pre_Cur_Diff_Stopped_Val = Diff_Between_Records('epoch', $Pre_Cur_Diff_Array, $Data_Pre_Status_Val, $Data_Cur_Status_Val, $Diff_Record);
							$Pre_Cur_Diff_Stopped_Sum = array_sum($Pre_Cur_Diff_Stopped_Val);
							$DateTime_Stopped_Diff[] = $Pre_Cur_Diff_Stopped_Sum;
						}
						//Idle
						else if($Data_Cur_Status_Val == 'Idle'){
							$Device_Stamp_Idle_Array[] = $Result_Array['device_epoch_time'];
							$Result_Array['device_date_stamp'] = "Idle--".$Result_Array['device_date_stamp'];
							$Pre_Cur_Diff_Idle_Val = Diff_Between_Records('epoch', $Pre_Cur_Diff_Array, $Data_Pre_Status_Val, $Data_Cur_Status_Val, $Diff_Record);
							$Pre_Cur_Diff_Idle_Sum = array_sum($Pre_Cur_Diff_Idle_Val);
							$DateTime_Idle_Diff[] = $Pre_Cur_Diff_Idle_Sum;
						}
						//Unknown
						else{
							$Device_Stamp_Unknown_Array[] = $Result_Array['device_epoch_time'];
							$Result_Array['device_date_stamp'] = "Unknown--".$Result_Array['device_date_stamp'];
							$Pre_Cur_Diff_Unknown_Val = Diff_Between_Records('epoch', $Pre_Cur_Diff_Array, $Data_Pre_Status_Val, $Data_Cur_Status_Val, $Diff_Record);
							$Pre_Cur_Diff_Unknown_Sum = array_sum($Pre_Cur_Diff_Unknown_Val);
							$DateTime_Unknown_Diff[] = $Pre_Cur_Diff_Unknown_Sum;
						}
						
					}
					else if($Diff_Record == 1){
						// All data diff
						$Pre_Cur_Diff_Val = Diff_Between_Records('epoch', $Pre_Cur_Diff_Array, $Data_Pre_Status_Val, $Data_Cur_Status_Val, $Diff_Record);
						$Pre_Cur_Diff_Sum = array_sum($Pre_Cur_Diff_Val);
						$All_DateTime_NE_Diff[] = $Pre_Cur_Diff_Sum;

						// Decide whom to assign the difference 		
						$Decision_Maker_Pocket_Diff = Decision_Maker_Pocket_Diff($Data_Pre_Status_Val, $Data_Cur_Status_Val, $Pre_Cur_Diff_Sum);
						$Maker_Decision = $Decision_Maker_Pocket_Diff[4];
						
						if($Maker_Decision == 'Moving'){
							array_push($Decision_Maker_Moving_Diff, $Decision_Maker_Pocket_Diff[0]);
						}
						else if($Maker_Decision == 'Stopped'){
							array_push($Decision_Maker_Stopped_Diff, $Decision_Maker_Pocket_Diff[1]);
						}
						else if($Maker_Decision == 'Idle'){
							array_push($Decision_Maker_Idle_Diff, $Decision_Maker_Pocket_Diff[2]);
						}
						
						// Just for debug 
						// Moving
						if($Data_Cur_Status_Val  == 'Moving'){
							$Result_Array['device_date_stamp'] = "Moving--".$Result_Array['device_date_stamp'];
						}
						//Stopped
						else if($Data_Cur_Status_Val == 'Stopped'){
							$Result_Array['device_date_stamp'] = "Stopped--".$Result_Array['device_date_stamp'];
						}
						//Idle
						else if($Data_Cur_Status_Val == 'Idle'){
							$Result_Array['device_date_stamp'] = "Idle--".$Result_Array['device_date_stamp'];
						}
						//Unknown
						else{
							$Result_Array['device_date_stamp'] = "Unknown--".$Result_Array['device_date_stamp'];
						}
					}
					$Device_Epoch_Array[] = array($Result_Array['device_epoch_time'],$Diff_Record);
					
					// Assigning the previous value
					$Data_Pre_Array = array($Data_Cur_Status_Val, $Result_Array['device_epoch_time']);

					//echo $i."-----".$Result_Array['device_date_stamp']."<br />";
					$i++;
				}	
			}
		}	    
		
		return $Result = array($Speed_Array, $All_DateTime_Diff, $All_DateTime_NE_Diff, $DateTime_Moving_Diff, $DateTime_Stopped_Diff, $DateTime_Idle_Diff, $DateTime_Unknown_Diff, $Decision_Maker_Moving_Diff, $Decision_Maker_Stopped_Diff, $Decision_Maker_Idle_Diff, $Decision_Maker_Unknown_Diff);
	}

	foreach($Dates_Array as $Dates_Val){
		
		// Getting all the data by status
		$Get_Summary = Get_Daily_Summary1($Dates_Val, $IMEI);
		
		$Speed_Array = $Get_Summary[0];
		$All_DateTime_Diff = $Get_Summary[1];
		$All_DateTime_NE_Diff = $Get_Summary[2];
		$DateTime_Moving_Diff = $Get_Summary[3];
		$DateTime_Stopped_Diff = $Get_Summary[4];
		$DateTime_Idle_Diff = $Get_Summary[5];
		$DateTime_Unknown_Diff = $Get_Summary[6];
		
		$Decision_Maker_Moving_Diff = $Get_Summary[7];
		$Decision_Maker_Stopped_Diff = $Get_Summary[8];
		$Decision_Maker_Idle_Diff = $Get_Summary[9];
		$Decision_Maker_Unknown_Diff = $Get_Summary[10];
		

		// Data for all
		$All_DateTime_Diff = array_sum($All_DateTime_Diff) + array_sum($All_DateTime_NE_Diff);
		$Total_Pocket_Time = Epoch_To_Time($All_DateTime_Diff);

		// Data for Moving
		$DateTime_Moving_Diff = array_sum($DateTime_Moving_Diff) + array_sum($Decision_Maker_Moving_Diff);
		$Total_Moving_Pocket_Time = Epoch_To_Time($DateTime_Moving_Diff);
		
		// Data for Stopped
		$DateTime_Stopped_Diff = array_sum($DateTime_Stopped_Diff) + array_sum($Decision_Maker_Stopped_Diff);
		$Total_Stopped_Pocket_Time = Epoch_To_Time($DateTime_Stopped_Diff);
		
		// Data for Idle
		$DateTime_Idle_Diff = array_sum($DateTime_Idle_Diff) + array_sum($Decision_Maker_Idle_Diff);
		$Total_Idle_Pocket_Time = Epoch_To_Time($DateTime_Idle_Diff);
		
		// Data for Unknown
		$DateTime_Unknown_Diff = array_sum($DateTime_Unknown_Diff) + array_sum($Decision_Maker_Unknown_Diff);
		$Total_Unknown_Pocket_Time = Epoch_To_Time($DateTime_Unknown_Diff);
	}	
	
	$Total_Seperated_Time = $DateTime_Moving_Diff + $DateTime_Stopped_Diff + $DateTime_Idle_Diff + $DateTime_Unknown_Diff;
	
	echo "<hr /><h4>Total Up Time -- ".$Total_Pocket_Time;
	echo "<br />Total Seperated Up Time -- ".Epoch_To_Time($Total_Seperated_Time);
	echo "</h4><br />Moving Time -- ".$Total_Moving_Pocket_Time;
	echo "<br />Stopped Time -- ".$Total_Stopped_Pocket_Time;
	echo "<br />Idle Time -- ".$Total_Idle_Pocket_Time;
	echo "<br />Unknown Time -- ".$Total_Unknown_Pocket_Time;
	echo "<hr />";
	echo "<br />Diff Time -- ".Epoch_To_Time(array_sum($All_DateTime_NE_Diff));
	echo "<br />Diff Moving Time -- ".Epoch_To_Time(array_sum($Decision_Maker_Moving_Diff));
	echo "<br />Diff Stopped Time -- ".Epoch_To_Time(array_sum($Decision_Maker_Stopped_Diff));
	echo "<br />Diff Idle Time -- ".Epoch_To_Time(array_sum($Decision_Maker_Idle_Diff));
	echo "<br />Diff Unknown Time -- ".Epoch_To_Time(array_sum($Decision_Maker_Unknown_Diff));
	
	/*
	echo "Total Up Count -- ". count($Device_Epoch_All_Array);
	echo "<br />Moving Count -- ". count($Device_Moving_Array);
	echo "<br />Stopped Count -- ". count($Device_Stopped_Array);
	echo "<br />Idle Count -- ". count($Device_Idle_Array);
	echo "<br />Unknown Count -- ". count($Device_Unknown_Array);
	echo "<hr />";
	*/
}	
else{
	echo "Distance Calculation - parameter empty";
}
?>