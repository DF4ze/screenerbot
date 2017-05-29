<?php 

require_once('/var/www/html/screenerbot/botConfig.php');

class IceManager{
	
	private $confFileName = "_ice.conf";
	private $folderPath = API_PATH;
	private $screenShotsFolderName = SHOTS_FOLDER_NAME;
	private $userName = null;
	
	private $engineFilePath = ENGINE_SCRIPT_PATH;
	
	private $conf=null;
	
	function __construct( $userName ){
		$this->userName = $userName;
		
		$this->confFileName = $userName.$this->confFileName;
		$this->folderPath = $this->folderPath.$userName."/";
		
		if( !file_exists( $this->folderPath )){
			if( !mkdir( $this->folderPath  )) die("mkdir 'User Dir' error, please verify access rigth");	
		}
		
		if( !file_exists( $this->folderPath.$this->screenShotsFolderName )){
			if( !mkdir( $this->folderPath.$this->screenShotsFolderName )) die("mkdir 'ScreenShots' error, please verify access rigth");
		}
		
		if( !file_exists( $this->folderPath.$this->confFileName ) ){
			echo "Con not exists, copying from : ".INITIAL_CONF_PATH;
			
			$this->conf = $this->parse_conf_file(INITIAL_CONF_PATH, true, INI_SCANNER_TYPED);
			// !!! relative to script execution...
			$this->setDirectory("ScreenerBotPHP/".$userName."/".$this->screenShotsFolderName);
			$this->write_conf_file( $this->conf, $this->folderPath.$this->confFileName );
		}
		
		$this->conf = $this->parse_conf_file($this->folderPath.$this->confFileName, true, INI_SCANNER_TYPED);
	}
	
	
	/*
	 * 
	 * Config
	 * 
	 */
	private function updateConfig($cat, $option, $text){
		if( $cat == "" || $cat == null )
			$this->conf[$option] = $text;
		else 
			$this->conf[$cat][$option] = $text;
		
		$this->write_conf_file($this->conf, $this->folderPath.$this->confFileName);
	}
	
	private function write_conf_file($assoc_arr, $path)
	{
	    $content = "";
	    foreach ($assoc_arr as $key => $elem) {
	        $content .= "\n[" . $key . "]\n";
	        foreach ($elem as $key2 => $elem2) {
	            if ($elem2 == "") {
	                $content .= $key2 . "=\n";
	            } else {
	                $content .= $key2 . "=" . $elem2 . "\n";
	            }
	        }
	    }
	    if (!$handle = fopen($path, 'w')) {
	        return false;
	    }
	    if (!fwrite($handle, $content)) {
	        return false;
	    }
	    fclose($handle);
	    return true;
	}
	
	private function parse_conf_file( $filename ){
		$content = file_get_contents( $filename );
		$lines = explode( "\n", $content );
		$current = "";
		$final;
		foreach ( $lines as $line ){
			//echo $line."<br/>";
			if( substr( $line, 0, 1 ) === "#" )
				continue;
			
			$params = explode("=", $line, 2);
		
			if( count($params) > 1 ){
				if( $current != "" ){
					$final[$current][$params[0]] = $params[1];
				}else{
					$final[$params[0]] = $params[1];
				}
			}else{
				$current = substr($params[0], 1, -1);
			}
			
		}
		return $final;
	}
	
	function dumpConf( ){
		var_dump( $this->conf );
	}
	
	function getConf( ){
		return $this->conf;
	}
	
	
	/*
	 * 
	 * Engine
	 * 
	 */ 
	
	function runEngine($once, $simpleShot=false){
		$duration = $this->getDuration();
		if( $once || $simpleShot ){
			$duration = 2;
			$this->setName($this->userName);
		}else{
			$this->setName("");
		}

		$hour = $this->getHour();
		if( $hour == "" || $once )
			$hour = "now";

		$cli = $this->buildCli($duration, $hour, $once, $simpleShot);
		error_log("Cmd : ".$cli);
		
		exec( $cli, $output );
		$out = "";
		foreach( $output as $key => $value){
			$out .= $key." = ".$value."\n";
		}
		
		error_log("Output : ".$out);
		
		return $output;
	}
	
	private function buildCli( $duration, $hour, $once, $simpleShot ){
		$iceCli = $this->engineFilePath." -i ".$this->folderPath.$this->confFileName
			." -c ".$duration
			." > ".$this->folderPath.$this->screenShotsFolderName."../ice.log 2>&1";
			
		$videoCli = "mencoder ".$this->folderPath.$this->screenShotsFolderName."*.png "
			."-mf w=800:h=600:fps=10:type=jpg -ovc lavc -lavcopts vcodec=mpeg4 -oac copy -o "
			.$this->folderPath.$this->screenShotsFolderName.$this->userName.".mp4 "
			."> ".$this->folderPath.$this->screenShotsFolderName."../mencoder.log 2>&1";
			
		$rmCli = "rm ".$this->folderPath.$this->screenShotsFolderName."*.png";

		$reviewCli = PHP_PATH." ".API_PATH."reviewFilesSizes.php -u ".$this->userName;
		
		$alertStartCli = PHP_PATH." ".ROOT_PATH."index.php -u ".$this->userName." -s";
		
		$alertVidCli = PHP_PATH." ".ROOT_PATH."index.php -u ".$this->userName;

		$simpleShotCli = PHP_PATH." ".ROOT_PATH."index.php -u ".$this->userName." -ss";

		$atCli = "at ".$hour;
		
		
		
		$finalCli = "";
		
		$test = false;
		if( $test ){
			$finalCli = ( $this->getQuiet()?'' :$alertStartCli.' ; ')
				.($this->getAutoRm()?$rmCli.' ; ':'')
				.$iceCli.' ; '
				.$reviewCli.' ; '
				.$videoCli.' ; '
				.$alertVidCli;
				
		}else if( $once ){
			$finalCli = $iceCli;
			
		}else if( $simpleShot ){
			$finalCli = 'echo "'
				.( $this->getQuiet()?'' :$alertStartCli.' ; ')
				.$simpleShotCli
				.' " | '.$atCli;
				
		}else{
			$finalCli = 'echo "'
				.( $this->getQuiet()?'' :$alertStartCli.' ; ')
				.($this->getAutoRm()?$rmCli.' ; ':'')
				.$iceCli.' ; '
				.$reviewCli.' ; '
				.$videoCli.' ; '
				.$alertVidCli
				.' " | '.$atCli;
		}
		 
		return $finalCli;
	}
	
	
	
	
	/*
	 * 
	 * Getters & Setters
	 * 
	 */ 
		
	function getArea(){
		return $this->conf["ice"]["area"];
	}
	function setArea( $link ){
		$this->updateConfig("ice", "area", $link);
	}

	function getMinPortal(){
		return $this->conf["ice-optional"]["minlevel"];
	}
	function setMinPortal( $level ){
		$this->updateConfig("ice-optional", "minlevel", $level);
	}
	
	function getMaxPortal(){
		return $this->conf["ice-optional"]["maxlevel"];
	}
	function setMaxPortal( $level ){
		$this->updateConfig("ice-optional", "maxlevel", $level);
	}
	
	function getViewWidth(){
		return $this->conf["ice-optional"]["width"];
	}
	function setViewWidth( $width ){
		$this->updateConfig("ice-optional", "width", $width);
	}
	
	function getViewHeight(){
		return $this->conf["ice-optional"]["height"];
	}
	function setViewHeight( $height ){
		$this->updateConfig("ice-optional", "height", $height);
	}
	
	function getIITC(){
		return $this->conf["ice-optional"]["iitc"];
	}
	function setIITC( $isSet ){
		$this->updateConfig("ice-optional", "iitc", $isSet?"true":"false");
	}
	
	function getHideRes(){
		return $this->conf["ice-optional"]["hideRes"];
	}
	function setHideRes( $isSet ){
		$this->updateConfig("ice-optional", "hideRes", $isSet?"true":"false");
	}
	
	function getHideEnl(){
		return $this->conf["ice-optional"]["hideEnl"];
	}
	function setHideEnl( $isSet ){
		$this->updateConfig("ice-optional", "hideEnl", $isSet?"true":"false");
	}
	
	function getHideLink(){
		return $this->conf["ice-optional"]["hideLink"];
	}
	function setHideLink( $isSet ){
		$this->updateConfig("ice-optional", "hideLink", $isSet?"true":"false");
	}
	
	function getHideField(){
		return $this->conf["ice-optional"]["hideField"];
	}
	function setHideField( $isSet ){
		$this->updateConfig("ice-optional", "hideField", $isSet?"true":"false");
	}
	
	function getDirectory(){
		return $this->conf["ice-optional"]["directory"];
	}
	private function setDirectory( $dir ){
		$this->updateConfig("ice-optional", "directory", $dir);
	}
	
	function getZoom(){
		return $this->conf["ice-optional"]["zoom"];
	}
	function setZoom( $zoom ){
		$this->updateConfig("ice-optional", "zoom", $zoom);
	}
	
	function getName(){
		return $this->conf["ice-optional"]["forceName"];
	}
	private function setName( $name ){
		$this->updateConfig("ice-optional", "forceName", $name);
	}
	
	function getDuration(){
		return $this->conf["ice-optional"]["duration"];
	}
	function setDuration( $duration ){
		$this->updateConfig("ice-optional", "duration", $duration);
	}

	function getHour(){
		return $this->conf["ice-optional"]["hour"];
	}	
	function setHour( $value ){
		$this->updateConfig("ice-optional", "hour", $value);
	}
	
	function getChatID(){
		return $this->conf["ice-optional"]["chatID"];
	}	
	function setChatID( $value ){
		$this->updateConfig("ice-optional", "chatID", $value);
	}
	
	function getAutoRm(){
		return $this->conf["ice-optional"]["autoRm"];
	}	
	function setAutoRm( $isSet ){
		$this->updateConfig("ice-optional", "autoRm", $isSet?"true":"false");
	}
	function getQuiet(){
		return $this->conf["ice-optional"]["quiet"] == "true";
	}	
	function setQuiet( $isSet ){
		$this->updateConfig("ice-optional", "quiet", $isSet?"true":"false");
	}
}


?>
