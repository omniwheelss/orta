<?php
/**

 * Parses Device Data from URI and sending to Windows Server using Soap 
 *
 * @package		DEMO
 * @subpackage	Libraries
 * @category	API
 * @author		Seeni Dev Team
 */

	include_once("./lib/includes.php");

	// Device Reponse
	if(isset($_REQUEST['reply'])){
		$deviceResponseData = $_REQUEST;
		insertDeviceResponseData($deviceResponseData);
	}
	
	//Getting the data from Server
	if(empty($_REQUEST['data'])){
			echo "<div>Parameter is empty</div>";
			exit;
	}

	//Variable declaration
	$Debug = null;
	$LogPath = "/data/logs/gps";
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
		echo triggerSMSAlertForDevices($Data);
	}
?>