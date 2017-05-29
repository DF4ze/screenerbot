<?php

// need full path for command line calls
require_once('/var/www/html/screenerbot/botConfig.php');

require_once(API_PATH.'Telegram.php');
require_once(API_PATH.'IceManager.php');

/* 
 * For bot commands recognition
 * copy/past in BotFather after typing "/setcommands"
 
link - Set the area to screenshot
zoom - Set the zoom level, highest is closest
duration - Set the duration in minute
hour - Set the hour to launch
launch - Launch the configured screenshots and send a video
trylaunch - Launch 1 shot now and send a picture
simpleshot - Launch 1 shot at hour and will send a picture
iitc - Enable or Disable IITC
minportal - Set the min portal level 
maxportal - Set the max portal level
height - Set the height of the viewport
width - Set the width of the viewport	
hideres - Hide Resistance portals, links, fields
hideenl - Hide Enlightened portals, links, fields
hidelinks - Hide all links
hidefield - Hide all fields
autorm - Automaticly remove last launched screenshots
quiet - Does not make any textual feedback if set to true
settings - Show configured settings
help - Show help

*/


class ScreenerBotPHP{
	private $tg;	
	private $im;
	private $isCli;
	
	private $cmd_link 			=  "/link";
	private $cmd_zoom 			=  "/zoom";
	private $cmd_duration 		=  "/duration";
	private $cmd_iitc 			=  "/iitc";
	private $cmd_minPortal 		=  "/minportal";
	private $cmd_maxPortal 		=  "/maxportal";
	private $cmd_height 		=  "/height";
	private $cmd_width 			=  "/width";
	private $cmd_hideRes 		=  "/hideres";
	private $cmd_hideEnl 		=  "/hideenl";
	private $cmd_hideLink 		=  "/hidelink";
	private $cmd_hideField 		=  "/hidefield";
	private $cmd_getSettings 	=  "/settings";
	private $cmd_hour 			=  "/hour";
	private $cmd_launch			=  "/launch";
	private $cmd_tryLaunch		=  "/trylaunch";
	private $cmd_simpleShot		=  "/simpleshot";
	private $cmd_help			=  "/help";
	private $cmd_autoRm			=  "/autorm";
	private $cmd_quiet			=  "/quiet";
	
	
	public function __construct($bot_id, $username = "", $simpleShot = false){
		$this->tg = new Telegram( $bot_id );
		$this->im = new IceManager( $username == "" ? $this->tg->Username(): $username );
		
		if( $simpleShot && $username != ""){
			$this->im->runEngine(true);
			$this->sendPhoto( $this->shot2Link( $username ) );
			
		}else{
			if( $username == "" )
				$this->im->setChatID( $this->tg->ChatID() );
			
			if($this->isAuthorisedUser($this->tg->Username()) ){
				if( $username == "" )
					$this->manageMessage();
					
			}else if( $username == "" ){
				$this->answerText("Huu... you think you can do something there...?");
			}
		}
	}
	
	private function isAuthorisedUser( $username ){
		return in_array($username, AUTHORISED, true); 
	}
	
	private function manageMessage(){
		$report["ak"] = false;
		$report["msg"] = "";
		
		if( $this->isLocation() ){
			if( !$this->im->getQuiet() )
				$this->answerText("Screenershot is comming, please wait...\n".$this->location2IntelLink());
			
			$this->im->setArea($this->location2IntelLink());
			$this->im->runEngine(true);
			
			$this->answerPhoto( $this->shot2Link() );
			
			$report["ak"] = true;
		}else if( $this->isCmd() ){
			$report = $this->manageCommands();
		}else {
			$report["ak"] = true;
		}
		
		if( !$report["ak"] ){
			if( $report["msg"] == "" )
				$this->answerText("What are you meening..?\nPlease refer to /help for full command list");
			else
				$this->answerText($report["msg"]);
				
		}else if( $report["msg"] != "" ){
			if( !$this->im->getQuiet() ) $this->answerText($report["msg"]);
		}
	}
	
	private function manageCommands(){
		$cmds = explode( " ", $this->tg->Text() );
		$cmd = str_replace( BOT_NAME, "", $cmds[0] );
		$report["ak"] = false;
		switch($cmd){
			case $this->cmd_link:
				if( isset($cmds[1]) ){
					$this->im->setArea( $cmds[1] );
					$report["ak"] = true;
					$report["msg"] = "Area set";
				}
				break;
			case $this->cmd_zoom:
				if( isset($cmds[1]) ){
					$this->im->setZoom( $cmds[1] );
					$report["ak"] = true;
					$report["msg"] = "Zoom set to ".$cmds[1];
				}
				break;
			case $this->cmd_duration:
				if( isset($cmds[1]) ){
					$this->im->setDuration( $cmds[1] );
					$report["ak"] = true;
					$report["msg"] = "Duration set to ".$cmds[1];
				}
				break;
			case $this->cmd_iitc:
				if( isset($cmds[1]) ){
					$bool = $this->getBool( $cmds[1] );
					$this->im->setIITC( $bool );
					$report["ak"] = true;
					$report["msg"] = "IITC set to ".($bool?"true":"false");
				}
				break;
			case $this->cmd_minPortal:
				if( isset($cmds[1]) ){
					$min = $this->normalizeMinMax($cmds[1], true);
					$this->im->setMinPortal( $min );
					$report["ak"] = true;
					$report["msg"] = "Min portal set to ".$min;
				}
				break;
			case $this->cmd_maxPortal:
				if( isset($cmds[1]) ){
					$max = $this->normalizeMinMax($cmds[1], false);
					$this->im->setMaxPortal( $max );
					$report["ak"] = true;
					$report["msg"] = "Min portal set to ".$max;
				}
				break;
			case $this->cmd_height:
				if( isset($cmds[1]) ){
					$this->im->setViewHeight( $cmds[1] );
					$report["ak"] = true;
					$report["msg"] = "Height viewport set to ".$cmds[1];
				}
				break;
			case $this->cmd_width:
				if( isset($cmds[1]) ){
					$this->im->setViewWidth( $cmds[1] );
					$report["ak"] = true;
					$report["msg"] = "Width viewport set to ".$cmds[1];
				}
				break;
			case $this->cmd_hideRes:
				if( isset($cmds[1]) ){
					$bool = $this->getBool( $cmds[1] );
					$this->im->setHideRes( $bool );
					$report["ak"] = true;
					$report["msg"] = "Hide Resistance set to ".($bool?"true":"false");
				}
				break;
			case $this->cmd_hideEnl:
				if( isset($cmds[1]) ){
					$bool = $this->getBool( $cmds[1] );
					$this->im->setHideEnl( $bool );
					$report["ak"] = true;
					$report["msg"] = "Hide Enlightened set to ".($bool?"true":"false");
				}
				break;
			case $this->cmd_hideLink:
				if( isset($cmds[1]) ){
					$bool = $this->getBool( $cmds[1] );
					$this->im->setHideLink( $bool );
					$report["ak"] = true;
					$report["msg"] = "Hide Link set to ".($bool?"true":"false");
				}
				break;
			case $this->cmd_hideField:
				if( isset($cmds[1]) ){
					$bool = $this->getBool( $cmds[1] );
					$this->im->setHideField( $bool );
					$report["ak"] = true;
					$report["msg"] = "Hide Field set to ".($bool?"true":"false");
				}
				break;
			case $this->cmd_getSettings:
				$report["msg"] = $this->getSettings();
				$report["ak"] = false;
				break;
			case $this->cmd_hour:
				if( isset($cmds[1]) ){
					$this->im->setHour( $cmds[1] );
					
					$report["msg"] = "Hour set to ".$cmds[1];
					if( !preg_match('/^[0-9]{1,2}:[0-9]{2}$/', $cmds[1]) && $cmds[1] != "now" ){
						$report["msg"] .= "\nBut are you sur of want you're doing...?";
					}
					$report["ak"] = true;	
				}
				break;
			case $this->cmd_launch:
				$this->im->runEngine(false);
				$report["msg"] = "Screenshots armed and be run ".($this->im->getHour() == "" || $this->im->getHour() == "now"? "now" : "at ".$this->im->getHour());
				$report["ak"] = true;
				break;
			case $this->cmd_simpleShot:
				$this->im->runEngine(false, true);
				$report["msg"] = "SimpleShot armed and be run ".($this->im->getHour() == "" || $this->im->getHour() == "now"? "now" : "at ".$this->im->getHour());
				$report["ak"] = true;
				break;
			case $this->cmd_tryLaunch:
				if( !$this->im->getQuiet() ) $this->answerText("Cooker is baking your shot... please wait.");
				$this->im->runEngine(true);
				$this->answerPhoto( $this->shot2Link() );
				$report["msg"] = "";
				$report["ak"] = true;
				break;
			case $this->cmd_help:
				$report["msg"] = $this->getHelp();
				$report["ak"] = true;
				break;
			case $this->cmd_autoRm:
				if( isset($cmds[1]) ){
					$bool = $this->getBool( $cmds[1] );
					$this->im->setAutoRm( $bool );
					$report["ak"] = true;
					$report["msg"] = "Auto Remove set to ".($bool?"true":"false");
				}
				break;
			case $this->cmd_quiet:
				if( isset($cmds[1]) ){
					$bool = $this->getBool( $cmds[1] );
					$this->im->setQuiet( $bool );
					$report["ak"] = true;
					$report["msg"] = "Quiet set to ".($bool?"true":"false");
				}
				break;


			default:
				$report["msg"] = "";//$this->getCommands();
				$report["ak"] = false;
		}
		return $report;
	}
	
	/* 
	 * 
	 * BOT REPLY
	 * 
	 */ 
	private function answerText( $text ){
		$this->tg->sendMessage( array('chat_id' => $this->tg->ChatID(), 'text' => $text) );
	}
	private function answerPhoto( $link ){
		$this->tg->sendPhoto( array('chat_id' => $this->tg->ChatID(), 'photo' => $link) );
	}
	function sendVideo( $username ){
		$link=$this->video2Link($username);
		$this->tg->sendVideo( array('chat_id' => $this->im->getChatID(), 'video' => $link) );
	}
	
	function sendText( $text ){
		$this->tg->sendMessage( array('chat_id' => $this->im->getChatID(), 'text' => $text) );
	}
	function sendPhoto( $link ){
		$this->tg->sendPhoto( array('chat_id' => $this->im->getChatID(), 'photo' => $link) );
	}

	
	/*
	 * 
	 * BAKERS
	 * 
	 */ 
	private function location2IntelLink( $tgLocation="" ){
		$link = null;
		if( $tgLocation != "" )
			$link = "https://www.ingress.com/intel?ll=".$tgLocation["latitude"].",".$tgLocation["longitude"]."&z=".$this->im->getZoom();
		else if( $this->isLocation() )
			$link = "https://www.ingress.com/intel?ll=".$this->tg->Location()["latitude"].",".$this->tg->Location()["longitude"]."&z=".$this->im->getZoom();
			
		return $link;
	}
	private function shot2Link($username = ""){
		return "https://".DOMAIN_NAME."/".PROFILES_WEB_PATH."/"
			.($username==""?$this->tg->Username():$username)
			."/screenshots/"
			.($username==""?$this->tg->Username():$username).".png?v=".time();
	}
	private function video2Link($username){
		return "https://".DOMAIN_NAME."/".PROFILES_WEB_PATH."/".$username."/screenshots/".$username.".mp4?v=".time();
	}
	private function getCommands(){
		return "You can use this commands :\n"
			."/link https://... 	- Set the area to screenshot\n"
			."/zoom X 				- Set the zoom level, highest is closest\n"
			."/duration XX 			- Set the duration in minute\n"
			."/iitc true/false		- Enable or Disable IITC\n"
			."/minportal X 			- Set the min portal level \n"
			."/maxportal X 			- Set the max portal level\n"
			."/height XXX 			- Set the height of the viewport\n"
			."/width XXX 			- Set the width of the viewport\n"	
			."/hideres true/false 	- Hide Resistance portals, links, fields\n"
			."/hideenl true/false 	- Hide Enlightened  portals, links, fields\n"
			."/hidelink true/false 	- Hide all links\n"
			."/hidefield true/false - Hide all fields\n"
			."/quiet true/false 	- Doesn't write any textual feedback (unless /settings command)\n"
			."/hour XX:XX 			- Hour to shot (\"now\" or empty for a direct shot, if hour is passed... it'll launch tomorrow...!)\n"
			."\n"
			."/launch 				- Armed the shooter, depending to configuration, will shoot during X times and will feedback with a video\n"
			."/trylaunch 			- Made to calibrate the viewport shooter, return immediatly a shot of the seted link\n"
			."/simpleshot 			- Armed the shooter, then at configured hour, feedback with a picture\n"
			."\n"
			."To see current settings : \n"
			."/settings\n";
	}
	private function getHelp(){
		return "This bot is made for taking screenshot of Ingress Intel Map.\n"
			."It can do simple shot or permanent shots for a stopmotion video.\n"
			."\n"
			."- Send a position and you'll get a screenshot\n"
			."- Set an Intel Link and you'll get a video\n"
			."\n"
			.$this->getCommands();
			
	}
	private function getSettings(){
		$conf = "No current configuration...";
		if( isset($this->im->getConf()["ice"]) ){
	
			$conf = "Link : ".$this->im->getArea()."\n";
			$conf .= "Zoom : ".$this->im->getZoom()."\n";
			$conf .= "Duration : ".$this->im->getDuration()."\n";
			$conf .= "Hour : ".$this->im->getHour()."\n";
			$conf .= "AutoRM : ".$this->im->getAutoRm()."\n";
			$conf .= "IITC : ".$this->im->getIITC()."\n";
			$conf .= "MinPortal : ".$this->im->getMinPortal()."\n";
			$conf .= "MaxPortal : ".$this->im->getMaxPortal()."\n";
			$conf .= "Height : ".$this->im->getViewHeight()."\n";
			$conf .= "Width : "	.$this->im->getViewWidth()."\n";
			$conf .= "HideRes : ".$this->im->getHideRes()."\n";
			$conf .= "HideEnl : ".$this->im->getHideEnl()."\n";
			$conf .= "HideLink : ".$this->im->getHideLink()."\n";
			$conf .= "HideField : ".$this->im->getHideField()."\n";
			$conf .= "Quiet : ".$this->im->getQuiet()."\n";
		}
		return $conf;
	}
	
	
	/*
	 * 
	 * TOOLS
	 * 
	 */
	private function isLocation(){
		return isset( $this->tg->getData()["message"]["location"] );
	}
	private function isCmd(){
		$isCmd = false;
		if( isset( $this->tg->getData()["message"]["entities"] ))
			if( $this->tg->getData()["message"]["entities"][0]["type"] == "bot_command" )
				$isCmd = true;
		
		return $isCmd;
	}
	private function normalizeMinMax( $value, $isMin ){
		if( $isMin ){
			if( $value > $this->im->getMaxPortal() ){
				$value = $this->im->getMaxPortal();
			}else if( $value < 1){
				$value = 1;
			}
		}else{
			if( $value < $this->im->getMinPortal() ){
				$value = $this->im->getMinPortal();
			}else if( $value > 8  ){
				$value = 8;
			}
		}
		
		return $value;
	}
	private function getBool( $val ){
		return $val == "true"? true : ($val == 1)?true:false;
	}
	

}

?>
