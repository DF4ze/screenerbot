<?php
	// need full path for command line calls
	require_once('/var/www/html/screenerbot/botConfig.php');
	require_once( API_PATH.'ScreenerBotPHP.php');

	function recur( $var ){
		$return = "";
		if( is_array($var) ){ 
			foreach ($var as $key => $value){
				$return .= $key."=[".recur($value)."], ";
			}
		}else{
			$return .= "".$var;
		}
		
		return $return;
	}


	//CommandLine mode
	$username = "";
	if( isset( $argv ) && count( $argv ) > 2){
		if( $argv[1] == "-u" )
			$username = $argv[2];
	}
	$start = false;
	$simpleShot = false;
	if( isset( $argv ) && count( $argv ) > 3){
		if( $argv[3] == "-s" )
			$start = true;
		if( $argv[3] == "-ss" ){
			$simpleShot = true;
		}
	}
	
	// stockage dans le fichier de log
	if( $username == "" ){
		$update = json_decode(file_get_contents("php://input"), true);
		$entry = "JSON : ".recur($update)."\n---------------------\n";
		
		$file = 'ot.txt';
		file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
	}

	
	// Launching bot
	$bot_id = "341233753:AAHnxj2zbw4ts1CAt1lIIt5aZ3v4mZqzU5Q";
	$sc = new ScreenerBotPHP( $bot_id, $username, $simpleShot );
	
	// post traitment
	if( $username != null ){
		if( $start ){
			$sc->sendText( "@".$username." shots has just started!" );
		}else if( !$simpleShot ){
			$sc->sendVideo( $username );
		}
	}
	
	
	
?>
