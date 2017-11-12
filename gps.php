<?
	################################################################################################
	#
	#	Insert RAW DATA into DEVICE_DATA
	#	
	################################################################################################
	
	if(!empty($Temp_DATA)){

		$Data = $Temp_DATA;
		// Messure total length for $Temp_DATA
		$Temp_DATA_Len = strlen($Temp_DATA);
		// Remove "$WTGPS" charater from $Temp_DATA
		$Temp_DATA_String = substr($Temp_DATA,7);

		// Data parsing
		list($Format_Type,$Protocol_Version,$IMEI,$Date_Stamp,$Live_Data,$GPS_Status,$Latitude,$Longitude,$Altitude,$Speed,$Direction,$Odometer,$GPS_Move_Status,$External_Battery_Volt,$Internal_Battery_Percent,$GSM_Signal,$Unused,$Alert_Msg_Code,$Sensor_Interface,$IGN,$Analog_Input1,$Digital_Input1,$Output1,$Sequence_No,$Check_Sum)=explode (",",$Data);
		
		PrintMessage("Inside GPS and Data parsing done",$Debug);

		// Format Validation
		if ( (strpos($Data,"WTGPS") == 1 && count(explode (",",$Data)) == 25) && ($GPS_Status == 1 || $GPS_Status == 2) && $Alert_Msg_Code != 'PU'){			
		
			PrintMessage("Data Validated",$Debug);
			
			if(($IGN == 1 && $Speed < 1.3) || ($IGN == 0 && $Speed > 0 )){
				$Log_Prefix = "Error";
				if($IGN == 0){
					//File_Creation($Data,$LogPath,$Log_Prefix,$Extra_Data);
					PrintMessage("Error Log File Created",$Debug);
				}	
				$Speed = 0;
			}				

			// Daily Serial data File Creation
			$Log_Prefix = $IMEI;
			//File_Creation($Data,$LogPath,$Log_Prefix);
			PrintMessage("Individual Log File Created",$Debug);
			
			//Formatting Date
			$Date_Format_Val = Date_Format_WTGPS($Date_Stamp);
			$Device_Date_Stamp = $Date_Format_Val[0];
			$Device_Epoch_Time = $Date_Format_Val[1];
			
			// Fetching Location from Location Master
			//$Location_Name = FetchLocationName($Latitude,$Longitude);
			PrintMessage("Location Name Fetched",$Debug);

			// Check Duplicate data
			$Check_Exist = Check_Exist("device_data","imei = '".$IMEI."' and device_date_stamp = '".$Device_Date_Stamp."'");
			PrintMessage("Device data Duplicate Checking Done",$Debug);
			
			if($Check_Exist == 0){
				// Insert into DEVICE_DATA
				$Device_Data_Ins = "INSERT INTO device_data(protocol_version,imei,device_date_stamp,live_data,gps_status,latitude,longitude,altitude,speed,direction,odometer,gps_move_status,external_battery_volt,internal_battery_percent,gsm_signal,unused,alert_msg_code,sensor_interface,ign,analog_input1,digital_input1,output1,sequence_no,check_sum,location,device_epoch_time,server_date_stamp) VALUES	('".$Protocol_Version."','".$IMEI."','".$Device_Date_Stamp."','".$Live_Data."','".$GPS_Status."','".$Latitude."','".$Longitude."','".$Altitude."','".$Speed."','".$Direction."','".$Odometer."','".$GPS_Move_Status."','".$External_Battery_Volt."','".$Internal_Battery_Percent."','".$GSM_Signal."','".$Unused."','".$Alert_Msg_Code."','".$Sensor_Interface."','".$IGN."','".$Analog_Input1."','".$Digital_Input1."','".$Output1."','".$Sequence_No."','".$Check_Sum."','".$Location_Name."','".$Device_Epoch_Time."','".$Server_Date_Stamp."')";
				$DDIns_Query = mysql_query($Device_Data_Ins) or die (mysql_error());
				if($DDIns_Query){
					PrintMessage("Device Data Inserted",$Debug);
				}
				else{
					PrintMessage("Device Data Not Inserted",$Debug);
				}
				
				############################################
				#
				#	POI Calculation
				#
				##############################################
				if($GPS_Status == 1){
					include_once("geo_fence_cal.php");
					PrintMessage("GeoFence Calculation Done",$Debug);
				}
			}
			else{
				PrintMessage("XXXXXX Already Exist XXXXXXX",$Debug);
			}
			// For delete temporary records			   
			$Del_temp_Sql="delete from temp where content ='".$Temp_DATA."'";
			$Del_temp_Result = mysql_query($Del_temp_Sql);
			if($Del_temp_Result){
				PrintMessage("+++++++++ temp Data Deleted +++++++++",$Debug);
			}	
		}
		else{
			//If data format validation failes - set to invalid
			$Update_temp_Sql="update temp set status = 'invalid' where content ='".$Temp_DATA."'";
			$Update_temp_Result = mysql_query($Update_temp_Sql);
			if($Update_temp_Result){
				PrintMessage("XXXXXXX Invalid Pocket XXXXXXXXX",$Debug);
			}	
			
		}		
	}			   
?>
