<?php

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'do'){
	
	include("./lib/includes.php");
	
	$Dates_Array = Dates_Generate(10, 2017, 'dmY');

	foreach($Dates_Array as $Dates_Val){
		$File_Path = "/tmp/logs/gps/".$Dates_Val.".log";
		if(file_exists($File_Path)){
			$Read_File_Output = Read_File_To_DB($File_Path);	
		}
	}

	function Read_File_To_DB($File_Path){
		$Final_Data = null;
		$File_Open = fopen($File_Path,"r");
		
		$i = 1;
		while(! feof($File_Open))
		{
			$Read_Data = fgets($File_Open);
			if(!empty($Read_Data)){
				$Split_Data = explode(",", $Read_Data);
				$Split_Count = count($Split_Data);
				if($Split_Count == 25){
					$Final_Data[] = $Read_Data;
				}
				$i++;
			}
		}
		fclose($File_Open);
		return $Final_Data;
	}
	
	foreach($Read_File_Output as $Read_File_Val){
		$Split_Data = explode(",", $Read_File_Val);
		$IMEI = $Split_Data [2];
		$Device_Date_Stamp = $Split_Data[3];
		$Speed = $Split_Data [9];
		
		$Final_Result = Update_Device_Data($IMEI, $Device_Date_Stamp, $Speed);
		
		print_r($Final_Result);
	}
	
	function Update_Device_Data($IMEI, $Device_Date_Stamp, $Speed){	
		$Date_Format_Val = Date_Format_WTGPS($Device_Date_Stamp);
		$Device_Date_Stamp = $Date_Format_Val[0];
		$Mysql_Query2 = "select * from device_data where imei = '".$IMEI."' and device_date_stamp = '".$Device_Date_Stamp."'";
		$Mysql_Query_Result2 = mysql_query($Mysql_Query2) or die(mysql_error());
		$device_count2 = mysql_num_rows($Mysql_Query_Result2);
		if($device_count2 == 1){
			$Rows = mysql_fetch_array($Mysql_Query_Result2);
			$Mysql_Query3 = "update device_data set speed = '".$Speed."' where imei = '".$IMEI."' and device_date_stamp = '".$Device_Date_Stamp."'";
			$Mysql_Query_Result3 = mysql_query($Mysql_Query3) or die(mysql_error());
			echo "updated for [".$Rows['id']."]<br />";
		}		
	}		
}	
else{
	echo "File to DB update - parameter empty";
}
?>