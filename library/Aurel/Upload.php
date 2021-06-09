<?php
/**
* Classe upload
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Upload
{	
	/**
	 * Change photo to png format
	 * @param string $path
	 * @return string
	 */
	public static function transformPng($path){
    	switch(pathinfo($path, PATHINFO_EXTENSION)) {
            case "jpeg":
                $img_src_ressource = imagecreatefromjpeg($path);
                break;
            case "jpg":
                $img_src_ressource = imagecreatefromjpeg($path);
                break;
            case "gif":
                $img_src_ressource = imagecreatefromgif ($path);
                break;
            case "png":
                $img_src_ressource = imagecreatefrompng($path);
                break;
        }
        list($width, $height) = getimagesize($path);
        
        $img_dst_ressource = imagecreatetruecolor($width, $height);
        // PRESERVE TRANSPARENCY
		imagealphablending($img_dst_ressource, false);
		imagefill($img_dst_ressource,0,0,IMG_COLOR_TRANSPARENT);
		imagesavealpha($img_dst_ressource, true);
		
        imagecopyresampled($img_dst_ressource, $img_src_ressource, 0,0,0,0, $width, $height, $width, $height);
        $img_dst_chemin = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .
        pathinfo($path, PATHINFO_FILENAME) . '.png';
        imagepng($img_dst_ressource, $img_dst_chemin);
        unlink($path);
        return $img_dst_chemin;
    }

    /**
     * Resize image by keeping ratio
     * @param string $path
     * @param int $max_width
     * @param int $max_height
     * @param string $prefix
     */
    public static function resizeImg($path, $max_width = 100, $max_height = 100, $prefix = 'thumb',$agrandi = false) {
        switch(pathinfo($path, PATHINFO_EXTENSION)) {
            case "jpeg":
                $img_src_ressource = imagecreatefromjpeg($path);
                break;
            case "jpg":
                $img_src_ressource = imagecreatefromjpeg($path);
                break;
            case "gif":
                $img_src_ressource = imagecreatefromgif ($path);
                break;
            case "png":
                $img_src_ressource = imagecreatefrompng($path);
                break;
        }

        list($width, $height) = getimagesize($path);
        list($newwidth, $newheight) = self::_getNewSize($width, $height, $max_width, $max_height, $agrandi);
        $img_dst_ressource = imagecreatetruecolor($newwidth, $newheight);
        // PRESERVE TRANSPARENCY
		imagealphablending($img_dst_ressource, false);
		imagefill($img_dst_ressource,0,0,IMG_COLOR_TRANSPARENT);
		imagesavealpha($img_dst_ressource, true);
        imagecopyresampled($img_dst_ressource, $img_src_ressource, 0,0,0,0, $newwidth, $newheight, $width, $height);
        $img_dst_chemin = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .
        $prefix . pathinfo($path, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION);
        switch(pathinfo($path, PATHINFO_EXTENSION)) {
            case "jpeg":
                imagejpeg($img_dst_ressource, $img_dst_chemin);
                break;
            case "jpg":
                imagejpeg($img_dst_ressource, $img_dst_chemin);
                break;
            case "gif":
                imagegif ($img_dst_ressource, $img_dst_chemin);
                break;
            case "png":
                imagepng($img_dst_ressource, $img_dst_chemin);
                break;
        }
    }
    
    /**
     * Resize image by keeping ratio
     * @param string $path
     * @param int $max_width
     * @param int $max_height
     * @param string $prefix
     */
    public static function resizeChalaud($path) {
    	switch(pathinfo($path, PATHINFO_EXTENSION)) {
    		case "jpeg":
    			$img_src_ressource = imagecreatefromjpeg($path);
    			break;
    		case "jpg":
    			$img_src_ressource = imagecreatefromjpeg($path);
    			break;
    		case "gif":
    			$img_src_ressource = imagecreatefromgif ($path);
    			break;
    		case "png":
    			$img_src_ressource = imagecreatefrompng($path);
    			break;
    	}
    
    	list($width, $height) = getimagesize($path);
    	if($width >= $height){
    		$max_width = 850;
    		$max_height = 850;
    	} else {
    		$max_width = 420;
    		$max_height = 1000;
    	}
    	list($newwidth, $newheight) = self::_getNewSize($width, $height, $max_width, $max_height, true);
    	$img_dst_ressource = imagecreatetruecolor($newwidth, $newheight);
    	// PRESERVE TRANSPARENCY
    	imagealphablending($img_dst_ressource, false);
    	imagefill($img_dst_ressource,0,0,IMG_COLOR_TRANSPARENT);
    	imagesavealpha($img_dst_ressource, true);
    	imagecopyresampled($img_dst_ressource, $img_src_ressource, 0,0,0,0, $newwidth, $newheight, $width, $height);
    	$img_dst_chemin = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .
    	pathinfo($path, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION);
    	switch(pathinfo($path, PATHINFO_EXTENSION)) {
    		case "jpeg":
    			imagejpeg($img_dst_ressource, $img_dst_chemin);
    			break;
    		case "jpg":
    			imagejpeg($img_dst_ressource, $img_dst_chemin);
    			break;
    		case "gif":
    			imagegif ($img_dst_ressource, $img_dst_chemin);
    			break;
    		case "png":
    			imagepng($img_dst_ressource, $img_dst_chemin);
    			break;
    	}
    }
    
    /**
     * 
     * Resize to new_width, new_height and crop if aspect ratio change
     * @param string $path
     * @param int $new_width
     * @param int $new_height
     * @param string $filename
     */
	public static function cropImg($path, $new_width = 100, $new_height = 100, $filename = 'thumb') {
        switch(pathinfo($path, PATHINFO_EXTENSION)) {
            case "jpeg":
                $img_src_ressource = imagecreatefromjpeg($path);
                break;
            case "jpg":
                $img_src_ressource = imagecreatefromjpeg($path);
                break;
            case "gif":
                $img_src_ressource = imagecreatefromgif ($path);
                break;
            case "png":
                $img_src_ressource = imagecreatefrompng($path);
                break;
        }

        list($width, $height) = getimagesize($path);
        $img_dst_ressource = imagecreatetruecolor($new_width, $new_height);
        // PRESERVE TRANSPARENCY
		imagealphablending($img_dst_ressource, false);
		imagefill($img_dst_ressource,0,0,IMG_COLOR_TRANSPARENT);
		imagesavealpha($img_dst_ressource, true);
		
        $wm = $width/$new_width;
        $hm = $height/$new_height;
        $h_height = $new_height/2;
        $w_height = $new_width/2;
        if($width > $height) { // PAYSAGE
            $adjusted_width = $width / $hm;
            $half_width = $adjusted_width / 2;
            $int_width = $half_width - $w_height;
            imagecopyresampled($img_dst_ressource,$img_src_ressource,-$int_width,0,0,0,$adjusted_width,$new_height,$width,$height);
        } elseif(($width < $height) || ($width == $height)) { // PORTRAIT OU CARRE
            $adjusted_height = $height / $wm;
            $half_height = $adjusted_height / 2;
            $int_height = $half_height - $h_height;
            imagecopyresampled($img_dst_ressource,$img_src_ressource,0,-$int_height,0,0,$new_width,$adjusted_height,$width,$height);
        } else {
            imagecopyresampled($img_dst_ressource, $img_src_ressource, 0,0,0,0, $new_width, $new_height, $width, $height);
        }
        
        $img_dst_chemin = pathinfo($path, PATHINFO_DIRNAME) . "/" . $filename . pathinfo($path, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION);
        switch(pathinfo($path, PATHINFO_EXTENSION)) {
            case "jpeg":
                imagejpeg($img_dst_ressource, $img_dst_chemin);
                break;
            case "jpg":
                imagejpeg($img_dst_ressource, $img_dst_chemin);
                break;
            case "gif":
                imagegif ($img_dst_ressource, $img_dst_chemin);
                break;
            case "png":
                imagepng($img_dst_ressource, $img_dst_chemin);
                break;
        }
        return $img_dst_chemin;
    }
    
    /**
     * 
     * @param unknown_type $path
     * @param unknown_type $thumbnail_width
     * @param unknown_type $thumbnail_height
     * @param unknown_type $prefix
     */
	public static function centerImg($path, $thumbnail_width=100,$thumbnail_height=100, $prefix = 'center') {
        switch(pathinfo($path, PATHINFO_EXTENSION)) {
            case "jpeg":
                $img_src_ressource = imagecreatefromjpeg($path);
                break;
            case "jpg":
                $img_src_ressource = imagecreatefromjpeg($path);
                break;
            case "gif":
                $img_src_ressource = imagecreatefromgif ($path);
                break;
            case "png":
                $img_src_ressource = imagecreatefrompng($path);
                break;
        }

        list($width_orig, $height_orig) = getimagesize($path);
        if($thumbnail_width >= $width_orig && $thumbnail_height >= $height_orig){
	    	$x = ($thumbnail_width - $width_orig) / 2;
	    	$y = ($thumbnail_height - $height_orig) / 2;
        } else {
        	$x = 0;
	    	$y = 0;
        }
        	
	    $img_dst_ressource = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
        // PRESERVE TRANSPARENCY
		imagealphablending($img_dst_ressource, false);
		imagefill($img_dst_ressource,0,0,IMG_COLOR_TRANSPARENT);
		imagesavealpha($img_dst_ressource, true);
   
	    imagecopyresampled($img_dst_ressource, $img_src_ressource, $x, $y, 0, 0, $width_orig, $height_orig, $width_orig, $height_orig);
	    
        $img_dst_chemin = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR .
        $prefix . pathinfo($path, PATHINFO_FILENAME) . '.' . pathinfo($path, PATHINFO_EXTENSION);
        switch(pathinfo($path, PATHINFO_EXTENSION)) {
            case "jpeg":
                imagejpeg($img_dst_ressource, $img_dst_chemin);
                break;
            case "jpg":
                imagejpeg($img_dst_ressource, $img_dst_chemin);
                break;
            case "gif":
                imagegif ($img_dst_ressource, $img_dst_chemin);
                break;
            case "png":
                imagepng($img_dst_ressource, $img_dst_chemin);
                break;
        }
    }
    
    public static function rotateImg($path){
    	$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    	switch($extension) {
    		case "jpeg":
    			$img_src_ressource = imagecreatefromjpeg($path);
    			break;
    		case "jpg":
    			$img_src_ressource = imagecreatefromjpeg($path);
    			break;
    	}
    	
    	$exif = exif_read_data($path);
    	if(isset($img_src_ressource) && isset($exif['Orientation'])){
    		$ort = $exif['Orientation'];
    		switch($ort)
    		{
    			case 3: // 180 rotate left
    				$rotation = imagerotate($img_src_ressource, 180, 0);
    				break;
    
    			case 5:
    			case 6:
    			case 7: // horizontal flip + 90 rotate right
    				$rotation = imagerotate($img_src_ressource, -90, 0);
    				break;
    
    			case 8:
    				$rotation = imagerotate($img_src_ressource, 90, 0);
    				break;
    			default:
    				$rotation = imagerotate($img_src_ressource, 0, 0);
    				break;
    		}
    		imagealphablending($rotation, false);
    		imagesavealpha($rotation, true);
    
    		$img_dst_chemin = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR
    		. pathinfo($path, PATHINFO_FILENAME) . '.' . $extension;
    		switch($extension) {
    			case "jpeg":
    				imagejpeg($rotation, $img_dst_chemin,90);
    				break;
    			case "jpg":
    				imagejpeg($rotation, $img_dst_chemin,90);
    				break;
    		}
    	}
    }
    
	/**
     * Remove file folder
     *
     * @param string $chemin
     * @param boolean paramname
     */
    public function removeFiles($chemin, $boolRmdir=false) {
        if (is_dir($chemin)) {
            if ($dh = opendir($chemin)) {
                while (($file = readdir($dh)) !== false) {
                    @unlink($chemin.$file);
                }
                closedir($dh);
            }

            if ( $boolRmdir ) {
                return rmdir($chemin);
            }
        }
    }

    /**
     * Crée un répertoire s'il n'existe pas (non récursif)
     *
     * @param string $dir
     */
    public function checkDir($dir) {
        if(!is_dir($dir)) {
            mkdir($dir);
        }
    }
    
    /**
     * récupère un fichier depuis une url
     *
     * @param string $url
     * @param string $destination
     */
    public function saveFromUrl($url, $destination) {
        file_put_contents($destination, file_get_contents($url));
    }
    
    /**
     * Get New size for photo by keeping ratio
     * @param int $width
     * @param int $height
     * @param int $max_width
     * @param int $max_height
     * @param bool $agrandi
     */
    protected static function _getNewSize ($width, $height, $max_width, $max_height, $agrandi = false) {
    	if ($agrandi || $width >= $max_width || $height >= $max_height) {
    		$imageRatio = $width / $height;
    		if ($imageRatio >= 1) {
    			// image format paysage ou carré
    			$newwidth = $max_width;
    			$newheight = floor($height *  $max_width / $width);
    			if ($newheight > $max_height) {
    				$newheight = $max_height;
    				$newwidth = floor($width *  $max_height / $height);
    			}
    		} else {
    			// image format portrait
    			$newheight = $max_height;
    			$newwidth = floor($width *  $max_height / $height);
    			if ($newwidth > $max_width) {
    				$newwidth = $max_width;
    				$newheight = floor($height *  $max_width / $width);
    			}
    		}
    	} else {
    		$newwidth = $width;
    		$newheight = $height;
    	}
    	return array($newwidth, $newheight);
    }
}