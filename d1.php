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
			echo "<div>Parameter is empty</div>";
			exit;
	}

	//Variable declaration
	$Debug = null;
	$LogPath = "/tmp/gps";
	$Log_Prefix = "";
	
	//Setting up log file
	if(isset($_REQUEST['data'])){
		
		//For debug
		if(isset($_REQUEST['debug'])){
			$Debug = $_REQUEST['debug'];
		}
		
		$Data = $_REQUEST['data'];
		HTTP_API_Func($Data,$LogPath,$Log_Prefix,$Debug);
	}	

	
	function HTTP_API_Func($Data,$LogPath,$Log_Prefix,$Debug){

		try{
			// Include DB Connection
			include_once("./lib/Includes.php");
			$Server_Date_Stamp = date("Y-m-d H:i:s");
			
			PrintMessage("After Includes all the files",$Debug);
		
			// Daily Serial data File Creation
			//File_Creation($Data,$LogPath,$Log_Prefix,$Extra_Data);
			PrintMessage("Main Log File Created",$Debug);
			
			######################################################################################################
			#	
			#	Insert into temperary table
			#
			#######################################################################################################

				$Temp_Insert_Sql = "insert into temp (content,date_stamp) values ('".$Data."','".$Server_Date_Stamp."')";
				$Temp_Insert_Result = mysql_query($Temp_Insert_Sql);
				if($Temp_Insert_Result){
					PrintMessage("TEMP DATA inserted Successfully",$Debug);
				}	
				else{
					PrintMessage("TEMP DATA Insert Query Error : ".mysql_error()."",$Debug);
				}	
		
			######################################################################################################
			#	
			#	Format = $WTGPS,FP01,864547034419338,20171010000140,1,1,12.0847183,78.7310567,279.6,0.0,183,188.0,0,25.84,2|100,27,0,AA|0,0,0,0.43,0,0,750,45#
			#
			#######################################################################################################
		
			$Mysql_Query = "select * from temp where status != 'invalid' ";
			$Mysql_Query_Result = mysql_query($Mysql_Query) or die(mysql_error());
			$Mysql_Record_Count = mysql_num_rows($Mysql_Query_Result);
			if($Mysql_Record_Count>=1){
				while($Fetch_Result = mysql_fetch_array($Mysql_Query_Result)){
					$Temp_DATA = $Fetch_Result['content'];

					if ( (strpos($Temp_DATA,"WTGPS") == 1) ){
						PrintMessage("Inside GPS",$Debug);
						include_once("gps.php");
					}
				}
			}	
		}
		catch(Exception $e){
			ErrorLog_Creation($e,$LogPath,'ErrorLog');
		}
	}	
?>


