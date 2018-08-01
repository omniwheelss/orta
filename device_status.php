<?php

				// Insert data for current status
				$Mysql_Query1 = "select * from device_status where imei =  '".$IMEI."'";
				$Mysql_Query_Result1 = mysql_query($Mysql_Query1) or die(mysql_error());
				$Mysql_Record_Count1 = mysql_num_rows($Mysql_Query_Result1);
				if($Mysql_Record_Count1 >=1){
					$Device_Data_Ins1 = "UPDATE device_status set protocol_version = '".$Protocol_Version."',device_date_stamp = '".$Device_Date_Stamp."', gps_status = '".$GPS_Status."', latitude =  '".$Latitude."', longitude = '".$Longitude."', altitude =  '".$Altitude."', speed = '".$Speed."', direction = '".$Direction."',odometer = '".$Odometer."', gps_move_status= '".$GPS_Move_Status."', external_battery_volt ='".$External_Battery_Volt."', internal_battery_percent = '".$Internal_Battery_Percent."', gsm_signal = '".$GSM_Signal."', alert_msg_code = '".$Alert_Msg_Code."', sensor_interface = '".$Sensor_Interface."',ign = '".$IGN."',analog_input1 = '".$Analog_Input1."', digital_input1 = '".$Digital_Input1."',  location = '".$Location_Name."', device_epoch_time = '".$Device_Epoch_Time."', server_date_stamp = '".$Server_Date_Stamp."' where imei = '".$IMEI."'";
					$DDIns_Query1 = mysql_query($Device_Data_Ins1) or die (mysql_error());
					if($DDIns_Query1){
						PrintMessage("Device Data Update",$Debug);
					}
					else{
						PrintMessage("Device Data Not Update",$Debug);
					}					
				}
				else{
					$Device_Data_Ins = "INSERT INTO device_status(protocol_version,imei,device_date_stamp,live_data,gps_status,latitude,longitude,altitude,speed,direction,odometer,gps_move_status,external_battery_volt,internal_battery_percent,gsm_signal,unused,alert_msg_code,sensor_interface,ign,analog_input1,digital_input1,output1,sequence_no,check_sum,location,device_epoch_time,server_date_stamp) VALUES	('".$Protocol_Version."','".$IMEI."','".$Device_Date_Stamp."','".$Live_Data."','".$GPS_Status."','".$Latitude."','".$Longitude."','".$Altitude."','".$Speed."','".$Direction."','".$Odometer."','".$GPS_Move_Status."','".$External_Battery_Volt."','".$Internal_Battery_Percent."','".$GSM_Signal."','".$Unused."','".$Alert_Msg_Code."','".$Sensor_Interface."','".$IGN."','".$Analog_Input1."','".$Digital_Input1."','".$Output1."','".$Sequence_No."','".$Check_Sum."','".$Location_Name."','".$Device_Epoch_Time."','".$Server_Date_Stamp."')";
					$DDIns_Query = mysql_query($Device_Data_Ins) or die (mysql_error());
					if($DDIns_Query){
						PrintMessage("Device Data Inserted",$Debug);
					}
					else{
						PrintMessage("Device Data Not Inserted",$Debug);
					}
				}

?>				