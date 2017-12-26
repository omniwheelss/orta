<?php

	// For error free page
	error_reporting (0);
	
	#
	#	File Creation 
	#
		function File_Creation($Data,$Path,$Log_Prefix,$Extra_Data){
			if(empty($Log_Prefix))
				$Log_Prefix = "";
			else
				$Log_Prefix = $Log_Prefix."_";
			$FilePath = $Path."/".$Log_Prefix."".@date("dmY").".log";
			$handle = fopen($FilePath, 'a+');
			chmod($FilePath, 0777);
			//shell_exec("sudo chmod 777 dirname");
			$Log_File_Read = file($FilePath);
			$Log_File_Read_Count = count($Log_File_Read);
			if(($Log_File_Read_Count%2) == 0)
				$Log_File_Count = ($Log_File_Read_Count/2)+1;
				
			$Test_DATA = "$Log_File_Count - ".@date("d-m-Y H:i:s")." ".$Data."".$Extra_Data;
			if(!fwrite($handle, "\n$Test_DATA\n")) die("couldn't write to file. : Check the Folder permisson for (".$FilePath.")");
			else
				return "<div id='error_text'><div class='Db_Error'>".$Log_Prefix." written done.</div></div>";
		}
		
	#
	#	ErrorLog File Creation 
	#
		function ErrorLog_Creation($Error,$Path,$Log_Prefix){
			$FilePath = $Path."/".$Log_Prefix."_".@date("dmY").".txt";
			$handle = fopen($FilePath, 'a+');
			chmod($FilePath, 0777);
			$Log_File_Read = file($FilePath);
			$Test_DATA = "ERROR TIME : ".@date("d-m-Y H:i:s")."\nERROR DETAIL : ".$Error;
			$Test_DATA.="\n---------------------------------------------------------------------------------------------------";
			if(!fwrite($handle, "\n$Test_DATA\n")) die("couldn't write to file. : Check the Folder permisson for (".$FilePath.")");
			else
				return "<div id='error_text'><div class='Db_Error'>".$Log_Prefix." written done.</div></div>";
		}
		
		
	#
	#	Function to send the data to .Net Webservice
	#

	function SoapClient_DotNetService($Url,$Data,$ServiceMethod,$LogPath){
			
		if(strlen($Url) > 0){
		
			try{
				// Create the SoapClient instance 
				$client = new SoapClient($Url);
				
				// Append data to the data object
				$query->data = $Data;
					
				// Construct an array of query objects
				$queries = array($query);

				// Set the parameters of the function that we are going to request 
				$params = array(
					'data'=>$queries
				);  
				
				// Issue the request to the Decibel Web Service
				$result = $client->$ServiceMethod($params['data'][0]); 
				
				// Writing log into status log
				$MethodResult = $ServiceMethod."Result";
				return $result->$MethodResult;
			}
			catch(Exception $e){
				PrintMessage($e,$Debug);
				//ErrorLog_Creation($e,$LogPath,'ErrorLog');
			}
		}	
	}

	#
	#	Function to get location name
	#

	function FetchLocationName($Latitude,$Longitude){

		$LocationSql = "select * from DATA_MASTER.LOCATION_MASTER where Latitude like '".$Latitude."%' and Longitude like '".$Longitude."%' limit 1";
		$LocationResult = mysql_query($LocationSql);
		$LocationCount = mysql_num_rows($LocationResult);
		if($LocationCount > 0){
			$LocationRow = mysql_fetch_array($LocationResult);
			return $Location_Name=$LocationRow['Location_Name'];
		}
		else{
			return $Location_Name = GetLocationName('google',$Longitude,$Latitude);
		}
	}

	//-------------------------------------------------------------------------------------------------------------
	// Function to Reverse Geo Code a given set of Co-ordinates
	// Parameters to be provided are
	// a. Reverese Geo Coding Engine to be used. Valid values are
	//    noapi,ceinfo,geonames,freereversegeo,geocoder_ca
	// b. Longitude
	// c. Latitude

	function GetLocationName($REV_GEO_ENGINE,$geo_x,$geo_y) {

		if ($REV_GEO_ENGINE=="google"){
			// 16 - AIzaSyAmBUJXRJvJNsUugFZHLyjA4hZ6Hkjp0Fg
			// 17 - AIzaSyCUu4GhYlLQ8wp89DpCKA6HnvY4Q9l2G4Y
			$GOOGLE_REV_GEO_API_KEY = "AIzaSyCUu4GhYlLQ8wp89DpCKA6HnvY4Q9l2G4Y";
			if($geo_y != '0.0000000' && $geo_y != '0.0000000')      {
				$url = "https://maps.googleapis.com/maps/api/geocode/xml?latlng=".$geo_y.",".$geo_x."&sensor=true&key=".$GOOGLE_REV_GEO_API_KEY."";
				//echo "<br />";
				if ($query = load_xml($url)){
						if($query->error_message == 'You have exceeded your daily request quota for this API.'){
								$GOOGLE_REV_GEO_API_KEY = "AIzaSyAmBUJXRJvJNsUugFZHLyjA4hZ6Hkjp0Fg";
								$url = "https://maps.googleapis.com/maps/api/geocode/xml?latlng=".$geo_y.",".$geo_x."&sensor=true&key=".$GOOGLE_REV_GEO_API_KEY."";
								if ($query = load_xml($url)){
										$location = $query->result->formatted_address;
								}
						}
						else{
								$location = $query->result->formatted_address;
						}
				}
			}
		}
		$location=trim($location);
		//Remove any quotes or commas in the string. Replace "," with a space and just strip the quotes. This is
		//temperory. Need to use preg_replace once the entire filter is frozen.
		$location = str_replace(","," ",$location);
		$location = str_replace("\"","",$location);
		$location = str_replace("'","",$location);
		$Server_Date_Stamp = date("Y-m-d H:i:s");
		
		if(!empty($location)){
			$InsertLocationSql = "Insert into DATA_MASTER.LOCATION_MASTER (Latitude,Longitude,Location_Name,Date_Stamp,Epoch_Time) values ('".$geo_y."','".$geo_x."','".$location."','".$Server_Date_Stamp."','".time()."')";
			$Result = mysql_query($InsertLocationSql) or die (mysql_error());
		}
		
		if (strlen($location)==0){$location="location not available";}
		return $location;
		
	 
	}


	//-------------------------------------------------------------------------------------------------------------
	// Function for Reverse Geo Coding to query the given URL using Curl.
	// This is required since the simplexml_load_file function does not offer a Timeout option.
	// By using Curl in an intermediate step, the request can be timed out

	function load_xml($request_url){
		$data = simplexml_load_file($request_url);
		return $data;
	}


	######################################################
	#
	#		Duplicate Records Checking
	#
	#######################################################
	function Check_Exist($Tab_Name,$Duplicate_Columns_Final){
		$Mysql_Query = "select * from $Tab_Name where $Duplicate_Columns_Final";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		$Mysql_Record_Count = mysql_num_rows($Mysql_Query_Result);
		if($Mysql_Record_Count>=1){
			return $Mysql_Record_Count;
		}
	}

	######################################################
	#
	#		Print Message based on the Operating System
	#
	#######################################################

	function PrintMessage($Message,$debug){
		if($debug == 1){
			if(PHP_OS === 'Linux'){
				echo $Message."<hr />";
			}
			else{
				echo $Message."<hr />";
			}
		}
	}

	######################################################
	#
	#		Print Message based on the Operating System
	#
	#######################################################

	function converttoepoch($date,$time){
		$date_dd=substr($date,0,2);
		$date=substr($date,3);          
		$date_mm=substr($date,0,2);      
		$date_yyyy=substr($date,3);         
		$time_hh=substr($time,0,2);          
		$time=substr($time,3);               
		$time_mm=substr($time,0,2);           
		$time_ss=substr($time,3);                  
		$epochtime=mktime($time_hh,$time_mm,$time_ss,$date_mm,$date_dd,$date_yyyy);   
		return $epochtime;                                             
	}                        


	//-------------------------------------------------------------------------------------------------------------
	// Function to compute geographical distance between a given set of co-ordinates.
	// function GetUnixTimestamp($Date, $Time)
	// Input Parameters:
	// $lat1 - Latitude of Co-ordinate 1
	// $lon1 - Longitude of Co-ordinate 1
	// $lat2 - Latitude of Co-ordinate 2
	// $lon2 - Longitude of Co-ordinate 2
	//
	// Output Parameters:
	// $d - Distance in Kms
	function distance($lat1, $lon1, $lat2, $lon2) {

		$iRadiusEarth = 6371; // kms
		$lat1 /= 57.29578;
		$lat2 /= 57.29578;
		$lon1 /= 57.29578;
		$lon2 /= 57.29578;
		
		$dlat=$lat2-$lat1;
		$dlon=$lon2-$lon1;
	 
		$a = ( sin($dlat/2) * sin($dlat/2) ) + ( cos($lat1) * cos($lat2) ) * ( sin($dlon/2) * sin($dlon/2) );
		$c = 2 * atan2(sqrt($a), sqrt(1-$a));
		$d = $iRadiusEarth * $c;

		// Distance is returned in Kms
		return $d;
	}


	######################################
	#
	#	Daily Logs Creation
	# Function to get Unix time stamp from ASCII values of a given time and data
	#
	# Input Parameters:
	# $Date - Date in format dd.mm.yyyy
	# $Time - Time in format hh:mm:ss
	# Output Parameters
	# int - Unix timestamp
	#
	############################################

	function GetUnixTimestamp($Date, $Time){
	  $day   = substr($Date,0,2);
	  $month = substr($Date,3,2);
	  $year  = substr($Date,6,4);

	  $hour  = substr($Time,0,2);
	  $mins  = substr($Time,3,2);
	  $secs  = substr($Time,6,2);

	  $timestamp = gmmktime($hour,$mins,$secs,$month,$day,$year);
	  return $timestamp;
	}
	
	############################################
	# 
	#	Convert into DB date format
	#
	############################################

	function Date_Format_WTGPS($Date_Stamp){
		
		$Year = substr($Date_Stamp, 0, 4);
		$Month = substr($Date_Stamp, 4, 2);
		$Date = substr($Date_Stamp, 6, 2);
		$Hour = substr($Date_Stamp, 8, 2);
		$Minute = substr($Date_Stamp, 10, 2);
		$Seconds = substr($Date_Stamp, 12, 2);
		
		$Date_Stamp1 = $Date."-".$Month."-".$Year;
		$Time_Stamp = $Hour.":".$Minute.":".$Seconds;
		$Device_Epoch_Time = converttoepoch($Date_Stamp1,$Time_Stamp);
		$Date_Stamp = $Year."-".$Month."-".$Date." ".$Time_Stamp;
		
		return array($Date_Stamp, $Device_Epoch_Time);
	}

	############################################
	# 
	#	Get AccountID by IMEI
	#
	############################################

	function Get_AccountID_IMEI($IMEI){
		$Mysql_Query = "select * from device_master where IMEI = '".$IMEI."'";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		$Mysql_Record_Count = mysql_num_rows($Mysql_Query_Result);
		if($Mysql_Record_Count > 0){
			$Query_Result = mysql_fetch_array($Mysql_Query_Result);
			return $Query_Result['user_account_id'];
		}
		return false;
	}	
	
	
	############################################
	# 
	#	Geofence Data
	#
	############################################

	function Geofence_Data($Geo_User_Account_ID){
		
		$Mysql_Query = "select * from geo_fence where user_account_id = '".$Geo_User_Account_ID."'";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		$Mysql_Record_Count = mysql_num_rows($Mysql_Query_Result);
		if($Mysql_Record_Count > 0){
			while($Query_Result = mysql_fetch_array($Mysql_Query_Result)){
				$Geo_latitude = $Query_Result['latitude'];
				$Geo_longitude = $Query_Result['longitude'];
				$radius = $Query_Result['radius'];
				$trip_index = $Query_Result['id'];
			}
		}
		
	}	

	
	############################################
	# 
	#	Geofence Calculator
	#
	############################################

	function Geofence_Calculator($Geo_User_Account_ID, $IMEI, $Latitude, $Longitude, $Location_Name, $Device_Date_Stamp, $Data){
		
		$Result = null;
		$Mysql_Query = "select * from geo_fence where user_account_id = '".$Geo_User_Account_ID."'";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		$Mysql_Record_Count = mysql_num_rows($Mysql_Query_Result);
		if($Mysql_Record_Count >= 1){
			while($Query_Result = mysql_fetch_array($Mysql_Query_Result)){
				$Geo_latitude = $Query_Result['latitude'];
				$Geo_longitude = $Query_Result['longitude'];
				$Radius = $Query_Result['radius'];
				$Trip_Index = $Query_Result['id'];
				$Distance = null;
				
				// Distance between each geofence with current data
				$Distance = distance($Geo_latitude, $Geo_longitude, $Latitude, $Longitude);
				$Distance = round($Distance);
				//echo "Trip -".$Trip_Index."---".$Device_Date_Stamp."--Distance--".$Distance;
				//echo "<br />";
				// In Condition
				if($Distance < $Radius){
					//$Result[] = array($Geo_User_Account_ID, $IMEI, $Latitude, $Longitude, $Location_Name,$Trip_Index, $Device_Date_Stamp, $Data);
					$Trip_Status = "IN";
					$Result[] = array($Trip_Index, $Trip_Status, $Distance);
				}
				else{
					$Trip_Status = "OUT";
					$Result[] = array($Trip_Index, $Trip_Status, $Distance);
				}
			}
			return $Result;
		}			
	}

	
	############################################
	# 
	#	Geofence Alert Insert
	#
	############################################

	function Geofence_Alerts_Insert($Geo_User_Account_ID, $IMEI, $Latitude, $Longitude, $Location_Name, $Trip_Status,$Alert_Dispatch, $Trip_Index, $Device_Date_Stamp, $Server_Date_Stamp, $Data){
		
		$Result = false;
		$Mysql_Query = "INSERT INTO geo_fence_alerts (date_stamp,server_date_stamp,imei,latitude,longitude,location_name,status,alert_dispatch,trip_index,raw_data,epoch_time) values ('".$Device_Date_Stamp."','".$Server_Date_Stamp."','".$IMEI."','".$Latitude."','".$Longitude."','".$Location_Name."','".$Trip_Status."','".$Alert_Dispatch."','".$Trip_Index."','".$Data."','".strtotime($Device_Date_Stamp)."')";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		if($Mysql_Query_Result){
			$Result = true;
		}			
		return $Result;
	}
		
		
	############################################
	# 
	#	Geofence Alert Check
	#
	############################################

	function Geofence_Alerts_Exist($IMEI, $Trip_Index){
		
		$Result = null;
		$Mysql_Query = "select * from geo_fence_alerts where IMEI = '".$IMEI."' and Trip_Index = '".$Trip_Index."' order by id desc limit 1";
		$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
		$Mysql_Record_Count = mysql_num_rows($Mysql_Query_Result);
		if($Mysql_Record_Count > 0){
			$Query_Result = mysql_fetch_array($Mysql_Query_Result);
			$Result = $Query_Result['status'];
		}
		else{
			$Result = null;
		}
		return $Result;
	}
	
	#####################################################
	#
	#	Dates_POI
	#
	########################################################

	function Dates_Generate($Month, $Year, $Format){
		
		$Date_List_Array = array();
		
		for($d=1; $d <= date("d"); $d++)
		{
			$Time=mktime(12, 0, 0, $Month, $d, $Year);          
			if (date('m', $Time)==$Month){
				$Date = date('Y-m-d', $Time);
				$Date_List_Array[$Date]=date($Format, $Time);
			}	
		}		

		return $Date_List_Array;
	}
	
	
	######################################
	#
	#	Seconds to Time Conversion
	#
	############################################

	function Sec2Time($time){

		global $sec_to_time_val;

	  if(is_numeric($time)){

		$sec_to_time_val = array(

		  "years" => 0, "days" => 0, "hours" => 0,

		  "minutes" => 0, "seconds" => 0

		);

		if($time >= 31556926){

		  $sec_to_time_val["years"] = floor($time/31556926);

		  $time = ($time%31556926);

		}

		if($time >= 86400){

		  $sec_to_time_val["days"] = floor($time/86400);

		  $time = ($time%86400);

		}

		if($time >= 3600){

		  $sec_to_time_val["hours"] = floor($time/3600);

		  $time = ($time%3600);

		}

		if($time >= 60){

		  $sec_to_time_val["minutes"] = floor($time/60);

		  $time = ($time%60);

		}

		$sec_to_time_val["seconds"] = floor($time);

		(array) $sec_to_time_val;

	  }else{

		return (bool) FALSE;

	  }	
		if($sec_to_time_val['days']){
			if($sec_to_time_val['days'] > 1)
				$Days = $sec_to_time_val['days']." days and ";
			else
				$Days = $sec_to_time_val['days']." day and";	
		}	
		return "".$Days." ".$sec_to_time_val['hours'] ." : ".$sec_to_time_val['minutes']." : ".$sec_to_time_val['seconds']."" ;

	}



	######################################
	#
	#	Two Date Difference
	#
	############################################

	function get_time_difference( $start, $end ){

		$uts['start']      =    strtotime( $start );
		$uts['end']        =    strtotime( $end );
		if( $uts['start']!==-1 && $uts['end']!==-1 )
		{
			if( $uts['end'] >= $uts['start'] )
			{
				$diff    =    $uts['end'] - $uts['start'];
				if( $days=intval((floor($diff/86400))) )
					$diff = $diff % 86400;
				if( $hours=intval((floor($diff/3600))) )
					$diff = $diff % 3600;
				if( $minutes=intval((floor($diff/60))) )
					$diff = $diff % 60;
				$diff    =    intval( $diff );            
				return( array('days'=>$days, 'hours'=>$hours, 'minutes'=>$minutes, 'seconds'=>$diff) );
			}
			else
			{
				trigger_error( "Ending date/time is earlier than the start date/time", E_USER_WARNING );
			}
		}
		else
		{
			trigger_error( "Invalid date/time data detected", E_USER_WARNING );
		}
		return( false );
	}
	

	######################################
	#
	#	Get Time Difference
	#
	############################################


	function Get_TimeDiff($t1,$t2){
		$a1 = explode(":",$t1);
		$a2 = explode(":",$t2);
		$time1 = (($a1[0]*60*60)+($a1[1]*60)+($a1[2]));
		$time2 = (($a2[0]*60*60)+($a2[1]*60)+($a2[2]));
		$diff = abs($time1-$time2);
		$hours = floor($diff/(60*60));
		$mins = floor(($diff-($hours*60*60))/(60));
		$secs = floor(($diff-(($hours*60*60)+($mins*60))));
		$result = $hours.":".$mins.":".$secs;
		return $result;
	}


	######################################
	#
	#	Get Epoch Difference
	#
	############################################

	function Get_EpochDiff($Epoch1,$Epoch2){
		
		$Result = null;
		if(!empty($Epoch1) && !empty($Epoch2)){	
			$Result = $Epoch2 - $Epoch1;
		}
		return $Result;
	}


	######################################
	#
	#	Get Epoch Difference for Location Summary
	#
	############################################

	function Get_EpochDiff_Vehicle($Epoch1,$Epoch2, $Array_Type){
		
		$Result = null;
		if(!empty($Epoch1) && !empty($Epoch2)){	
		
			if($Array_Type == 'Moving' || $Array_Type == 'Idle' ||  $Array_Type == 'Unknown'){
				$Result = $Epoch2 - $Epoch1;
				if($Result > 120)
					$Result = 60;
			}
			else if($Array_Type == 'Stopped'){
				$Result = $Epoch2 - $Epoch1;
				if($Result > 600)
					$Result = 300;
			}
			else{
				$Result = $Epoch2 - $Epoch1;
			}
		}
		return $Result;
	}


	######################################
	#
	#       Date Difference
	#
	############################################

	function datetime_diff($start, $end)
	{
		$sdate = strtotime($start);
		$edate = strtotime($end);

		$time = $edate - $sdate;
		if($time>=0 && $time<=59) {
			// Seconds
			//$timeshift = $time.' seconds ';
							//$timeshift = $preday[0].' : '.$prehour[0].' : '.$premin[0].' '.$time.' seconds ';
			$timeshift = '<table border="0" cellpadding="0" cellspacing="0" width="100px" class="time_tab"><tr><td width="25px;">'.$preday[0].'</td><td>'.$prehour[0].' : '.$min[0].'</td></tr></table>';

		} elseif($time>=60 && $time<=3599) {
			// Minutes + Seconds
			$pmin = ($edate - $sdate) / 60;
			$premin = explode('.', $pmin);

			$presec = $pmin-$premin[0];
			$sec = $presec*60;

			//$timeshift = $premin[0].' min '.round($sec,0).' sec ';
						$timeshift = '<table border="0" cellpadding="0" cellspacing="0" width="100px" class="time_tab"><tr><td width="25px;">'.$preday[0].'</td><td>'.$prehour[0].' : '.$min[0].'</td></tr></table>';

		} elseif($time>=3600 && $time<=86399) {
			// Hours + Minutes
			$phour = ($edate - $sdate) / 3600;
			$prehour = explode('.',$phour);

			$premin = $phour-$prehour[0];
			$min = explode('.',$premin*60);

			$presec = '0.'.$min[1];
			$sec = $presec*60;

			//$timeshift = $prehour[0].' hrs '.$min[0].' min '.round($sec,0).' sec ';
						$timeshift = '<table border="0" cellpadding="0" cellspacing="0" width="100px" class="time_tab"><tr><td width="25px;">'.$preday[0].'</td><td>'.$prehour[0].' : '.$min[0].'</td></tr></table>';

		} elseif($time>=86400) {
			// Days + Hours + Minutes
			$pday = ($edate - $sdate) / 86400;
		   $preday = explode('.',$pday);

			$phour = $pday-$preday[0];
			$prehour = explode('.',$phour*24);

			$premin = ($phour*24)-$prehour[0];

			$min = explode('.',$premin*60);

			$presec = '0.'.$min[1];
			$sec = $presec*60;

		   // $timeshift = $preday[0].' days '.$prehour[0].' hrs '.$min[0].' min '.round($sec,0).' sec ';
			$timeshift = '<table border="0" cellpadding="0" cellspacing="0" width="100px" class="time_tab"><tr><td width="25px;">'.$preday[0].'</td><td>'.$prehour[0].' : '.$min[0].'</td></tr></table>';

		}
		return $timeshift;
	}

	

	######################################
	#
	#       Date Difference
	#
	############################################

	function Epoch_To_Time($Epoch)
	{
		$time = $Epoch;

		
		if($time>=0 && $time<=59) {
			// Seconds
			//$timeshift = $time.' seconds ';
			$timeshift = $preday[0].' : '.$prehour[0].' : '.$premin[0].' min '.$time.' sec ';
			//$timeshift = '<table border="0" cellpadding="0" cellspacing="0" width="100px" class="time_tab"><tr><td width="25px;">'.$preday[0].'</td><td>'.$prehour[0].' : '.$min[0].'</td></tr></table>';

		} elseif($time>=60 && $time<=3599) {
			// Minutes + Seconds
			$pmin = $time / 60;
			$premin = explode('.', $pmin);

			$presec = $pmin-$premin[0];
			$sec = $presec*60;

			$timeshift = $premin[0].' min '.round($sec,0).' sec ';
			//$timeshift = '<table border="0" cellpadding="0" cellspacing="0" width="100px" class="time_tab"><tr><td width="25px;">'.$preday[0].'</td><td>'.$prehour[0].' : '.$min[0].'</td></tr></table>';

		} elseif($time>=3600 && $time<=86399) {
			// Hours + Minutes
			$phour = $time / 3600;
			$prehour = explode('.',$phour);

			$premin = $phour-$prehour[0];
			$min = explode('.',$premin*60);

			$presec = '0.'.$min[1];
			$sec = $presec*60;

			$timeshift = $prehour[0].' hrs '.$min[0].' min '.round($sec,0).' sec ';
			//$timeshift = '<table border="0" cellpadding="0" cellspacing="0" width="100px" class="time_tab"><tr><td width="25px;">'.$preday[0].'</td><td>'.$prehour[0].' : '.$min[0].'</td></tr></table>';

		} elseif($time>=86400) {
			// Days + Hours + Minutes
			$pday = $time / 86400;
		   $preday = explode('.',$pday);

			$phour = $pday-$preday[0];
			$prehour = explode('.',$phour*24);

			$premin = ($phour*24)-$prehour[0];

			$min = explode('.',$premin*60);

			$presec = '0.'.$min[1];
			$sec = $presec*60;

			$timeshift = $preday[0].' days '.$prehour[0].' hrs '.$min[0].' min '.round($sec,0).' sec ';
			//$timeshift = '<table border="0" cellpadding="0" cellspacing="0" width="100px" class="time_tab"><tr><td width="25px;">'.$preday[0].'</td><td>'.$prehour[0].' : '.$min[0].'</td></tr></table>';

		}
		return $timeshift;
	}	
	
	######################################
	#
	#      calculate_average Speed
	#
	############################################

	function Calculate_Average($Data_Array) {
		$Array_Count = count($Data_Array); //total numbers in array
		$Average = 0;
		
		foreach ($Array_Count as $Value) {
			$Total = $Total + $Value; // total value of array numbers
		}
		$Average = (array_sum($Data_Array)/$Array_Count); // get average value
		return $Average;
	}

	
	######################################
	#
	#       Difference between records
	#
	############################################
	
	function Diff_Between_Records($Type, $Get_Array, $Array_Type){
		$Result = null;
		$Array_Count = count($Get_Array); 
		if($Array_Count > 0){
			$I = 0;
			// Difference_between_Time
			foreach($Get_Array as $Get_Val){
				
				// Skip the last record since we added +1 to second Get_Val record
				if($I != ($Array_Count-1))
				{
					if($Type == 'time'){
						$Result[] = Get_TimeDiff($Get_Array[$I],$Get_Array[$I+1]);
					}
					else if ($Type == 'epoch'){
						$Result[] = Get_EpochDiff_Vehicle($Get_Array[$I],$Get_Array[$I+1], $Array_Type);
					}
				}
				$I++;
			}
		}	
		else{
			$Result = null;
		}
		return $Result;
	}
	
	
	############################################
	#
	#    Vehicle Data Current Status
	#
	############################################
		
	function Data_Current_Status($GPS_Move_Status, $Speed, $IGN, $Alert_Msg_Code){
		
		$Result = null;
		// Moving Status
		if($GPS_Move_Status == 1 && $Speed > 1.5 && $IGN == 1){
			$Status = "Moving";
			$IGN = "On";
			$Status_Icon = "green.png";
		}
		// Stopped Status
		else if($GPS_Move_Status == 0 && $Speed == 0  && $IGN == 0){
			$Status = "Stopped";
			$IGN = "Off";
			$Status_Icon = "red.png";
		}
		// Idle Status
		else if((
			(($GPS_Move_Status == 2 && $IGN == 1)  || ($GPS_Move_Status == 1 && $Speed == 0 && $IGN == 1)) ) || $Alert_Msg_Code[0] == 'VI'){
			$Status = "Idle";
			$IGN = "On";
			//$Speed = 0;
			$Status_Icon = "orange.png";
		}
		return $Result = array($Status, $IGN, $Status_Icon);	
	}
	
?>

