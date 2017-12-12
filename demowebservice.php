<?php
/**

 * Parses Device Data from URI and sending to Windows Server using Soap 
 *
 * @package		DEMO
 * @subpackage	Libraries
 * @category	API
 * @author		Seeni Dev Team
 */

	//Getting the data from Server
	if(empty($_REQUEST['data'])){
			echo "<div id='error_text'><div class='Db_Error'>Parameter is empty</div></div>";
			exit;
	}
	//Call Function
	if(isset($_REQUEST['data'])){
		$Debug = $_REQUEST['debug'];
		$Data = $_REQUEST['data'];
		$LogPath = "/tmp/demo";
		$Log_Prefix = "";
		
		HTTP_API($Data,$LogPath,$Log_Prefix,$Debug);
	}	

	
	function HTTP_API($Data,$LogPath,$Log_Prefix,$Debug){

		try{
			// Include DB Connection
			include_once("./Lib/Includes.php");
			
			PrintMessage("After Includes all the files",$Debug);
		
			// Daily Serial data File Creation
			File_Creation($Data,$LogPath,$Log_Prefix,$Extra_Data);
			PrintMessage("Main Log File Created",$Debug);
			######################################################################################################
			#	
			#	Insert into temperary table
			#
			#######################################################################################################
				$Server_Date_Stamp = date("Y-m-d H:i:s");
				$Temp_Insert_Sql = "insert into TEMP (Content,Server_Date_Stamp) values ('".$Data."','".$Server_Date_Stamp."')";
				$Temp_Insert_Result = mysql_query($Temp_Insert_Sql);
				if($Temp_Insert_Result){
					PrintMessage("TEMP DATA inserted Successfully",$Debug);
				}	
				else{
					PrintMessage("TEMP DATA Insert Query Error : ".mysql_error()."",$Debug);
				}	
		
		
			######################################################################################################
			#	
			#	Format = $HCTGPS,FP01,862118023947580,20140820144526,1,1,13.0500067,80.2320700,60.1,0.0,0,0,0,
			#			0.00,0|0,22,195|b389|46|99,AA,0,1,0.00,0,0,0004,54
			#######################################################################################################
		
			$Mysql_Query = "select * from TEMP where Status != 'Invalid' ";
			$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
			$Mysql_Record_Count = mysql_num_rows($Mysql_Query_Result);
			if($Mysql_Record_Count>=1){
				while($Fetch_Result = mysql_fetch_array($Mysql_Query_Result)){
					$Temp_DATA = $Fetch_Result['Content'];

					if ( (strpos($Temp_DATA,"HCTGPS") == 1) ){
						PrintMessage("Inside GPS",$Debug);
						include_once("GPS.php");
					}
					if ( (strpos($Temp_DATA,"WTGPS") == 1) ){
						PrintMessage("Inside GPS",$Debug);
						include_once("GPS.php");
					}
				}
			}	
		}
		catch(Exception $e){
			ErrorLog_Creation($e,$LogPath,'ErrorLog');
		}
	}	
?>


