<?php
function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

	//CommandLine mode
	$username = "";
	if( isset( $argv ) && count( $argv ) > 2){
		if( $argv[1] == "-u" )
			$username = $argv[2];
	}else{
		exit(0);
	}

	$fileDir = "/var/www/html/screenerbot/ScreenerBotPHP/".$username."/screenshots/";
	$files = scandir( $fileDir );
	
	$countSizes;
	foreach( $files as $img ){
		if( $img != "." && $img != ".." && endsWith($img, ".png") ){
			list($width, $height, $type, $attr) = getimagesize($fileDir.$img);
			$key = $width.'x'.$height;
			if( isset( $countSizes[$key] ) ){
				if( isset( $countSizes[$key]['count'] ) ){
					$countSizes[$key]['count'] ++;
					$countSizes[$key]['files'][] = $img;	
				}else{
					$countSizes[$key]['count'] = 1;
					$countSizes[$key]['files'][] = $img;						
				}
			}else{
				$countSizes[$key]['count'] = 1;
				$countSizes[$key]['files'][] = $img;	
			}
		}
	}
	
	$maxSize = null;
	$maxCount = -1;
	foreach( $countSizes as $size => $lot ){
		if( $lot['count'] > $maxCount ){
			$maxCount = $lot['count'];
			$maxSize = $size;
		}
	}
	
	foreach( $countSizes as $size => $lot ){
		if( $size != $maxSize ){
			foreach( $lot['files'] as $file ){
				echo "Undesirable file : ".$file."\n";
				unlink($fileDir.$file);
			}
		}
	}	
?>
