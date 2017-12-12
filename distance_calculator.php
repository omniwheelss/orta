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
	
	function Get_Daily_Summary($Date, $IMEI){
		
		$Result = null;
		
		$From_Date = $Date. " 00:00:00";
		$To_Date = $Date. " 23:59:59";

		//$Mysql_Query = "select * from device_data where imei = '".$IMEI."' and device_date_stamp between '".$From_Date."' and '".$To_Date."' and alert_msg_code != 'IN|0' order by device_date_stamp asc limit 406, 11";
		$Mysql_Query = "select * from device_data where imei = '864547034419338' and device_date_stamp between '2017-10-24 08:50:00' and '2017-10-24 08:58:00' order by device_date_stamp asc";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		$Row_Count = mysql_num_rows($Mysql_Query_Result);
		if($Row_Count >=1){
			$i = 1;
			while($Result_Array = mysql_fetch_array($Mysql_Query_Result)){
				
				// Skip invalid Records
				$Valid_Records = Remove_Invalid_Records($Result_Array);
				
				
				print_r($Valid_Records);
				
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
				
				//Calculate when the status changed from the current status - difference calculation
				
				if($Data_Pre_Status_Val != $Data_Cur_Status_Val && !empty($Data_Pre_Status_Val)){
					$Pre_Cur_Diff_Array = array($Data_Pre_Array[1], $Result_Array['device_epoch_time']);
					$Pre_Cur_Diff_Val = Diff_Between_Records('epoch', $Pre_Cur_Diff_Array, null);
					echo "Diff----".$Pre_Cur_Diff_Sum = array_sum($Pre_Cur_Diff_Val);
					
					// Decide whom to assign the difference 					
					$Decision_Maker_Pocket_Diff = Decision_Maker_Pocket_Diff($Data_Pre_Status_Val, $Data_Cur_Status_Val, $Pre_Cur_Diff_Sum);
				}
				
				// Moving
				if($Data_Cur_Status_Val  == 'Moving'){
					$Device_Stamp_Moving_Array[] = $Result_Array['device_epoch_time'];
					$Result_Array['device_date_stamp'] = "Moving--".$Result_Array['device_date_stamp'];
				}
				//Stopped
				else if($Data_Cur_Status_Val == 'Stopped'){
					$Device_Stamp_Stopped_Array[] = $Result_Array['device_epoch_time'];
					$Result_Array['device_date_stamp'] = "Stopped--".$Result_Array['device_date_stamp'];
				}
				//Idle
				else if($Data_Cur_Status_Val == 'Idle'){
					$Device_Stamp_Idle_Array[] = $Result_Array['device_epoch_time'];
					$Result_Array['device_date_stamp'] = "Idle--".$Result_Array['device_date_stamp'];
				}
				//Unknown
				else{
					$Device_Stamp_Unknown_Array[] = $Result_Array['device_epoch_time'];
					$Result_Array['device_date_stamp'] = "Unknown--".$Result_Array['device_date_stamp'];
				}
				$Device_Epoch_Array[] = $Result_Array['device_epoch_time'];	
				
				$Data_Pre_Array = array($Data_Cur_Status_Val, $Result_Array['device_epoch_time']);

				print_r($i."-----".$Result_Array['device_date_stamp']."<br />");
				$i++;
			}
		}	    
		
		return $Result = array($Speed_Array, $Device_Stamp_All_Array, $Device_Epoch_Array, $Device_Stamp_Moving_Array, $Device_Stamp_Stopped_Array, $Device_Stamp_Idle_Array, $Device_Stamp_Unknown_Array, $Decision_Maker_Pocket_Diff[0], $Decision_Maker_Pocket_Diff[1], $Decision_Maker_Pocket_Diff[2]);
	}
	
	foreach($Dates_Array as $Dates_Val){

		$Get_Summary = Get_Daily_Summary($Dates_Val, $IMEI);	
		$Speed_Array = $Get_Summary[0];
		$Device_Epoch_All_Array = $Get_Summary[2]; 
		$Device_Moving_Array = $Get_Summary[3];
		$Device_Stopped_Array = $Get_Summary[4];
		$Device_Idle_Array = $Get_Summary[5];
		$Device_Unknown_Array = $Get_Summary[6];
		
		$Moving_Additional_Diff = $Get_Summary[7];
		$Stopped_Additional_Diff = $Get_Summary[8];
		$Idle_Additional_Diff = $Get_Summary[9];
		$Diff_Total_Timings = array_sum($Moving_Additional_Diff) + array_sum($Stopped_Additional_Diff) + array_sum($Idle_Additional_Diff);
		
		$Speed_Average = Calculate_Average($Speed_Array);

	/*	echo "<hr /><b>Diff Timings</b><br />";
		echo "Total Timings--".$Diff_Total_Timings."----".Epoch_To_Time($Diff_Total_Timings)."<br />";
		echo "Moving--".array_sum($Moving_Additional_Diff)."----".Epoch_To_Time(array_sum($Moving_Additional_Diff))."<br />";
		echo "Stopped--".array_sum($Stopped_Additional_Diff)."----".Epoch_To_Time(array_sum($Stopped_Additional_Diff))."<br />";
		echo "Idle--".array_sum($Idle_Additional_Diff)."----".Epoch_To_Time(array_sum($Idle_Additional_Diff))."<br />";
		*/
		// Data for all
		$All_DateTime_Diff = Diff_Between_Records('epoch', $Device_Epoch_All_Array, null);  //print_r($All_DateTime_Diff); echo "<hr />";
		$All_Epoch_Sum = array_sum($All_DateTime_Diff) + array_sum($Diff_Total_Timings);;
		$Total_Pocket_Time = Epoch_To_Time($All_Epoch_Sum);
		
		// Data for Moving
		$Moving_DateTime_Diff = Diff_Between_Records('epoch', $Device_Moving_Array, 'Moving');	//print_r($Moving_DateTime_Diff); echo "<hr />";
		$Moving_Epoch_Sum = array_sum($Moving_DateTime_Diff) + array_sum($Moving_Additional_Diff);
		$Moving_Pocket_Time = Epoch_To_Time($Moving_Epoch_Sum);
		
		// Data for Stopped
		$Stopped_DateTime_Diff = Diff_Between_Records('epoch', $Device_Stopped_Array, 'Stopped'); //print_r($Stopped_DateTime_Diff); echo "<hr />";
		$Stopped_Epoch_Sum = array_sum($Stopped_DateTime_Diff) + array_sum($Stopped_Additional_Diff);
		$Stopped_Pocket_Time = Epoch_To_Time($Stopped_Epoch_Sum);
		
		// Data for Idle
		$Idle_DateTime_Diff = Diff_Between_Records('epoch', $Device_Idle_Array, 'Idle'); //print_r($Idle_DateTime_Diff); echo "<hr />";
		$Idle_Epoch_Sum = array_sum($Idle_DateTime_Diff) + array_sum($Idle_Additional_Diff);
		$Idle_Pocket_Time = Epoch_To_Time($Idle_Epoch_Sum);
		
		// Data for Unknown
		$Unknown_DateTime_Diff = Diff_Between_Records('epoch', $Device_Unknown_Array, 'Unknown'); //print_r($Unknown_DateTime_Diff); echo "<hr />";
		$Unknown_Epoch_Sum = array_sum($Unknown_DateTime_Diff);
		$Unknown_Pocket_Time = Epoch_To_Time($Unknown_Epoch_Sum);
		
	}	
	
	$Total_Seperated_Time = $Moving_Epoch_Sum + $Stopped_Epoch_Sum + $Idle_Epoch_Sum + $Unknown_Epoch_Sum;
	
	echo "<hr /><h4>Total Up Time -- ".$Total_Pocket_Time;
	echo "<br />Total Seperated Up Time -- ".Epoch_To_Time($Total_Seperated_Time);
	/*echo "</h4><br />Moving Time -- ".$Moving_Pocket_Time;
	echo "<br />Stopped Time -- ".$Stopped_Pocket_Time;
	echo "<br />Idle Time -- ".$Idle_Pocket_Time;
	echo "<br />Unknown Time -- ".$Unknown_Pocket_Time;
	echo "<hr />";
	
	
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