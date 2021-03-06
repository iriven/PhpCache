<?php
/**
* irivenPhpCache - PHP class to manage Cache system.
* Copyright (C) 2014 Iriven France Software, Inc. 
*
* Licensed under The GPL V3 License
* Redistributions of files must retain the above copyright notice.
*
* @Copyright 		Copyright (C) 2014 Iriven France Software, Inc.
* @package 		irivenPhpCache
* @Since 		Version 1.0.0
* @link 		https://github.com/iriven/irivenPhpCache The irivenPhpCache GitHub project
* @author 		Alfred Tchondjo (original founder) <iriven@yahoo.fr>
* @license  		GPL V3 License(http://www.gnu.org/copyleft/gpl.html)
*
* ==================  NOTICE  =======================
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 3
* of the License, or any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
* or contact the author by mail at: <iriven@yahoo.fr>.
**/class irivenfiles extends  irivenPhpCache implements  irivenPhpCacheDriver  {
 private $path;
    function isEnabled() {
        if(is_dir($this->cachePath()) and is_writable($this->cachePath())) {
            return true;
        } else {

        }
        return false;
    }

    /*
     * Init Cache Path
     */
    function __construct($option = array()) {

        $this->setOption($option);
        if(!is_dir($this->path = $this->cachePath().DIRECTORY_SEPARATOR.ltrim(__CLASS__,'iriven'))) 
        @mkdir($this->path,0705,true);
        if(!$this->isEnabled() and !isset($option['skipError'])) {
            throw new Exception('Can\'t use "'.ltrim(__CLASS__,'iriven').'"  driver for your website!');
        }

    }

    function write($keyword, $value = '', $time = null, $option = array() ) {
		$code = md5($keyword);
        $path = $this->path.DIRECTORY_SEPARATOR.substr(md5($keyword),0,2);
		if(!file_exists($path) and !@mkdir($path,0604,true)) 
			throw new Exception('PLEASE CHMOD '.$this->cachePath().' - 0777 OR ANY WRITABLE PERMISSION!',92);
		$file_path = $path.DIRECTORY_SEPARATOR.$code.'.cache';
      //  echo '<br>DEBUG SET: '.$keyword.' - '.$value.' - '.$time.'<br>';
        $data = $this->encode($value);

        $toWrite = true;
        /*
         * Skip if Existing Caching in Options
         */
        if(isset($option['skipExisting']) and $option['skipExisting'] == true and file_exists($file_path)) {
            $content = $this->readfile($file_path);
            $old = $this->decode($content);
            $toWrite = false;
            if($this->isExpired($old)) $toWrite = true;
        }
        if($toWrite == true) $this->writefile($file_path,$data);
    }




    function read($keyword, $option = array()) {

		$code = md5($keyword);
        $path = $this->path.DIRECTORY_SEPARATOR.substr(md5($keyword),0,2);
		$file_path = $path.DIRECTORY_SEPARATOR.$code.'.cache';
        if(!file_exists($file_path)) {
            return null;
        }
        $content = $this->readfile($file_path);
        $object = $this->decode($content);
        if($this->isExpired($object)) {
            @unlink($file_path);
            $this->auto_clean_expired();
            return null;
        }

        return $object;
    }

    function remove($keyword, $option = array()) {
		$code = md5($keyword);
        $path = $this->path.DIRECTORY_SEPARATOR.substr(md5($keyword),0,2);
		$file_path = $path.DIRECTORY_SEPARATOR.$code.'.cache';
        if(@unlink($file_path)) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Return total cache size + auto removed expired files
     */
    function getInfos($option = array()) {
        $res = array(
            'info'  =>  '',
            'size'  =>  '',
            'data'  =>  '',
        );

        $path = $this->cachePath();
        $dir = @opendir($path);
        if(!$dir) {
            throw new Exception('Can\'t read PATH:'.$path,94);
        }

        $total = 0;
        $removed = 0;
        while($file=readdir($dir)) {
            if($file!='.' and $file!='..' and is_dir($path.DIRECTORY_SEPARATOR.$file)) {
                // read sub dir
                $subdir = @opendir($path.DIRECTORY_SEPARATOR.$file);
                if(!$subdir) {
                    throw new Exception('Can\'t read path:'.$path.DIRECTORY_SEPARATOR.$file,93);
                }

                while($f = readdir($subdir)) {
                    if($f!='.' and $f!='..') {
                        $file_path = $path.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR.$f;
                        $size = filesize($file_path);
                        $object = $this->decode($this->readfile($file_path));
                        if($this->isExpired($object)) {
                            unlink($file_path);
                            $removed = $removed + $size;
                        }
                        $total = $total + $size;
                    }
                } // end read subdir
            } // end if
       } // end while

       $res['size']  = $total - $removed;
       $res['info'] = array(
                'Total' => $total,
                'Removed'   => $removed,
                'Current'   => $res['size'],
       );
       return $res;
    }

    function auto_clean_expired() {
        $autoclean = $this->get('keyword_clean_up_files');
        if($autoclean == null) {
            $this->set('keyword_clean_up_files',3600*24);
            $res = $this->stats();
        }
    }

    function cleanup($option = array()) {

        $paths = $this->cachePath();
		$emptyDir = function ($pathname) 
				{
					if(is_file($pathname)) return unlink($pathname);
					$scandir=new RecursiveDirectoryIterator($pathname,FilesystemIterator::SKIP_DOTS);
					$iterator = new RecursiveIteratorIterator($scandir, RecursiveIteratorIterator::CHILD_FIRST); 
					foreach ($iterator as $path)
					{
						if ($path->isDir())	rmdir($path->getPathName());
						else unlink($path->getPathName());
					}
					return true;
				};
		if($emptyDir($paths)) return true;
		return false;
    }


    function itemExists($keyword){
		$code = md5($keyword);
        $path = $this->path.DIRECTORY_SEPARATOR.substr(md5($keyword),0,2);
		$file_path = $path.DIRECTORY_SEPARATOR.$code.'.cache';
        if(!file_exists($file_path)) {
            return false;
        } else {
            // check expired or not
            $value = $this->get($keyword);
            if($value == null) {
                return false;
            } else {
                return true;
            }
        }
    }

    function isExpired($object) 
	{

        if(isset($object['expirationDate']) and @date('U') >= $object['expirationDate']) return true;
            return false;
    }




}