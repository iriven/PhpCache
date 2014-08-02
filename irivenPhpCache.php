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
**/
// main class
class irivenPhpCache {
	private $lifetime = 86400; //1 day
    private $processor = NULL;
    // default options, this will be merge to Driver's Options
    private $settings = array();
	//
   public function __construct($adapter = null, $option = array()) 
   {	$numargs = func_num_args();
   		$args = func_get_args();
   		if($numargs == '1')
		{
			if(is_array($args[0])){ $adapter = null; $option = $args[0];}
			else{ $adapter = $args[0]; $option = array();}	
		}
   		if(isset($option['lifetime']))
   		{
			if(is_numeric($option['lifetime'])) $this->lifetime = intval($option['lifetime']);
			unset($option['lifetime']);
		} 		
		$this->initialize($adapter,$option);
        if(!$this->driverExists($this->settings['adapter'])) $this->settings['adapter'] = $this->autoSetAdapter();
		$driver = 'iriven'.$this->settings['adapter'];
        require_once($this->settings['drivers']['location'].DIRECTORY_SEPARATOR.$driver.'.php');
		$this->settings['skipError'] = false;
        if(!$this->processor = new $driver($this->settings)) throw new Exception('The '.__CLASS__.' driver "'.$this->settings['adapter'].'" is not found!');
		if ($this->settings['path'] and !is_dir($this->settings['path'])) mkdir($this->settings['path'],'0705',true); // create storage dir if needed
		return $this;
   }	
	private function initialize($adapter=null,$params=array())
	{	
		  $this->settings = array(
				  'system'    		=> array(),
				  'errors'			=> array(),
				  'adapter'	=> 'auto',
				  'path'  			=> null,
				  'pathVerified'	=> false,
  				  'pluginsPath'		=> rtrim(realpath(__DIR__),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'Libraries',
				  'fallback'  		=> array('example'   =>  'files'),
				  'extensions'  	=> array(),
				  'apiKey'   		=> null,  // Key Folder, Setup Per Domain will good.				  																			
				  'drivers' 		=> array(),
				  'checked' 		=> array('fallback'  => false, 'hook'  => false),	
				  'server'      	=> array(
											  array('127.0.0.1',11211,1)
										  //  array('new.host.ip',11211,1),
											),				  
				);
		$this->settings['drivers']['location'] = $this->settings['pluginsPath'].DIRECTORY_SEPARATOR.'Drivers';
		$this->settings['extensions']['location'] = $this->settings['pluginsPath'].DIRECTORY_SEPARATOR.'Extensions';
		require $this->settings['pluginsPath'].DIRECTORY_SEPARATOR.'driver.php';	
		$this->settings = array_merge($this->settings ,$this->systemInfo());
		if(isset($this->settings['fallback'][$adapter])) $adapter = $this->settings['fallback'][$adapter];				
		if($adapter) $params['adapter']=$adapter;
		foreach($params as $key=>$value) $this->setup($key,$value);
		return $this->settings;
	}
    /*
     * Auto Create .htaccess to protect cache folder
     */

    private function createHtaccess($path = '') {	
			$file = rtrim(preg_replace('#[/\\\\]+#', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.htaccess';
			if(!file_exists($file))
			{
				$padlock  = '<IfModule mod_authz_core.c>'.PHP_EOL;
				$padlock .= 'Require local'.PHP_EOL;
				$padlock .= '</IfModule>'.PHP_EOL;
				$padlock .= '<IfModule !mod_authz_core.c>'.PHP_EOL;
				$padlock .= 'order deny, allow'.PHP_EOL;
				$padlock .= 'deny from all'.PHP_EOL;
				$padlock .= 'allow from 127.0.0.1'.PHP_EOL;
				$padlock .= '</IfModule>'.PHP_EOL;
				if(!file_put_contents($file, $padlock, LOCK_EX))
				throw new Exception('Can\'t create .htaccess',97);
			}			
    }	
    /*
     * For Auto Driver
     *
     */

   private function autoSetAdapter() {
        $driver = 'files';
        if(extension_loaded('apc') 	and ini_get('apc.enabled') 	and strpos(PHP_SAPI,'CGI') === false) $driver = 'apc';
        elseif(function_exists('eaccelerator_get') and function_exists('eaccelerator_put')) $driver = 'eaccelerator';
        elseif(extension_loaded('pdo_sqlite') and is_writeable($this->cachePath())) $driver = 'sqlite';	
        elseif(is_writeable($this->cachePath())) $driver = 'files';
        elseif(class_exists('memcached')) $driver = 'memcached';
        elseif(extension_loaded('wincache') and function_exists('wincache_ucache_set')) $driver = 'wincache';
        elseif(extension_loaded('xcache') and function_exists('xcache_get')) $driver = 'xcache';
        elseif(function_exists('memcache_connect')) $driver = 'memcache';
		else 
		{
            $path = $this->settings['drivers']['location'];
			$files = array_map('basename',glob($path.DIRECTORY_SEPARATOR.'*.php'));
			foreach($files as $file)
			{
				if(stripos($file,'index') !== false) continue;
 				require_once($path.DIRECTORY_SEPARATOR.$file);
				$class = str_replace('.php','',$file);
				$option = $this->settings;
				$option['skipError'] = true;
				$driver = new $class($option);
				if($driver->isEnabled()){ $driver = ltrim($class,'iriven'); break;}						
			}
        }
        return $driver;
    }
    /*
     * return PATH for Files & PDO only
     */
    public function cachePath($create_path = true) {
        if (!isset($this->settings['path']))
		{
            if((PHP_SAPI == 'apache2handler') or (strpos(PHP_SAPI,'handler') !== false)) 
			{
                $tmpDir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
                $this->settings['path'] = rtrim($tmpDir,DIRECTORY_SEPARATOR);
            } else $this->settings['path'] = rtrim(__DIR__,DIRECTORY_SEPARATOR);
		}
		$this->settings['apiKey'] = md5('irivenPhpcache.storage'.sha1(md5(php_uname().PHP_OS.PHP_SAPI)).(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : get_current_user()));
		$fullPath = rtrim(preg_replace('#[/\\\\]+#', DIRECTORY_SEPARATOR, $this->settings['path'].DIRECTORY_SEPARATOR.$this->settings['apiKey']), DIRECTORY_SEPARATOR);
        if($create_path == true and $this->settings['pathVerified'] == false) 
		{		
			$mkdir = function ($pathname, $mode = 0705) use (&$mkdir)
			{
				$pathname = rtrim(preg_replace('#[/\\\\]+#', DIRECTORY_SEPARATOR, $pathname), DIRECTORY_SEPARATOR);
				if(file_exists($pathname)) return true;
				$nextPathname = substr($pathname, 0, strrpos($pathname, DIRECTORY_SEPARATOR));
				if ($mkdir($nextPathname, $mode)) 
					if (!file_exists($pathname)) 
					{
						$old = umask(0);
						if (mkdir($pathname, $mode)) { umask($old); return true;}
						umask($old); return false;
					}
				return false;
			};
            if(!file_exists($fullPath) and !$mkdir($fullPath)) 
                throw new Exception('Sorry, Please create '.$fullPath.' and SET Mode 0705 or any Writable Permission!' , 100);
            $this->settings['pathVerified'] = true;
            $this->createHtaccess($fullPath);
        }
        return  $fullPath;
    }	
/*
 * Only require_once for the class u use.
 * Not use autoload default of PHP and don't need to load all classes as default
 */
private function driverExists($class) {
	$class = 'iriven'.ltrim($class,'iriven');
	if(file_exists($this->settings['drivers']['location'].DIRECTORY_SEPARATOR.$class.'.php'))
		require_once($this->settings['drivers']['location'].DIRECTORY_SEPARATOR.$class.'.php');
		if(class_exists($class))	return true;
	return false;
}
    /*
     * return System Information
     */
    private function systemInfo() {
        if(!count($this->settings['system'])) 
		{
			$this->settings['system']= array(
									  'os' => PHP_OS,
									  'php' => PHP_SAPI,
									  'description'    => php_uname(),
									  'unique'    => md5(php_uname().PHP_OS.PHP_SAPI)
								  );

            $this->settings['adapter'] = 'files';
            $path = $this->settings['drivers']['location'];
			if(!is_dir($path)) throw new Exception('Can\'t open file dir ext',100);
			$files = array_map('basename',glob($path.DIRECTORY_SEPARATOR.'*.php'));
			foreach($files as $file)
			{
				if(stripos($file,'index') !== false) continue;
 				require_once($path.DIRECTORY_SEPARATOR.$file);
				$class = str_replace('.php','',$file);
				$driverName = ltrim($class,'iriven');
					$option = $this->settings;
					$option['skipError'] = true;
					$driver = new $class($option);
					if($driver->isEnabled())
					{       $this->settings['drivers']['list'][$driverName] = true;
							$this->settings['adapter'] = $driverName;
					}	
					else	$this->settings['drivers']['list'][$driverName] = false;	

			
			}
            /*
             * PDO is highest priority with SQLite
             */
            if($this->settings['drivers']['list']['sqlite'] == true) {
                $this->settings['adapter'] = 'sqlite';
            }

        }
        return $this->settings;
    }
    /*
     * Object for Files & SQLite
     */
    protected  function encode($data) {
        return json_encode(serialize($data));
    }

   protected  function decode($value) {
		$value = json_decode($value);
        if(!$x = @unserialize($value)) return $value;
        return $x;
    }
    /*
     * Write File
     * Use file_put_contents OR ALT write
     */

   protected  function writefile($file, $data)
	{
		$file = rtrim(preg_replace('#[/\\\\]+#', DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
		if(function_exists('file_put_contents')) return file_put_contents($file, $data, LOCK_EX);
	    $f = fopen($file, 'w+');
        if(!$f) return false;
		$bytes = fwrite($f, $data);
		fclose($f);
		return $bytes;
	}

    /*
     * Read File
     * Use file_get_contents OR ALT read
     */

  protected  function readfile($file) {
		$file = rtrim(preg_replace('#[/\\\\]+#', DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
        if(function_exists('file_get_contents'))  return file_get_contents($file);
            $string = '';
            $file_handle = @fopen($file, 'r');
            if(!$file_handle) throw new Exception('Can\'t Read File',96);
            while (!feof($file_handle)) 
			{
                $line = fgets($file_handle);
                $string .= $line;
            }
            fclose($file_handle);
           return $string;
    }	
    function option($name, $value = null) 
	{
        if($value == null) 
		{
            if(isset($this->settings[$name])) return $this->settings[$name];
            return null;
        } 
		else 
		{

            if($name == 'path') 
			{
                $this->settings['pathVerified'] = false;
                $this->processor->settings['pathVerified'] = false;
            }
            $this->settings[$name] = $value;
            $this->processor->settings[$name] = $this->settings[$name];
            return $this;
        }
    }

    protected function setOption($option = array()) {
        $this->settings = array_merge($this->settings, $option);
        $this->settings['pathVerified'] = false;

    }	
    /*
     * Basic Method
     */
    public function set($keyword, $value = '', $lifetime = null, $option = array() ) 
	{
		if(func_num_args()=='1' and is_array($keyword)) 
		{
		    foreach($keyword as $item)
            $this->set($item[0],isset($item[1]) ? $item[1] : '', isset($item[2]) ? $item[2] : $this->lifetime, isset($item[3]) ? $item[3] : array());
        }
		$lifetime = ($lifetime and is_numeric($lifetime))? intval($lifetime): $this->lifetime;
        $object = array(
						  'value' => is_array($value)? array_map('utf8_encode',$value) : utf8_encode($value),
						  'creationDate'  => @date('U'),
						  'lifetime'  => $lifetime,
						  'expirationDate'  => @date('U') + (Int)$lifetime
        				);

            return $this->processor->write($keyword,$object,$lifetime,$option);

    }

   public function get($keyword, $option = array())
   {
	   if(func_num_args()=='1' and is_array($keyword)) 
		{
		   $res = array();
		  foreach($keyword as $array)
		  {
			  $name = $array[0];
			  $res[$name] = $this->get($name, isset($array[1]) ? $array[1] : array());
		  }
		  return $res;
		}
        $object = $this->processor->read($keyword,$option);
        if($object == null) return null;
        return is_array($object['value'])? array_map('utf8_decode',$object['value']) : utf8_decode($object['value']);
    }
   public function isCached($keyword) {
		if(is_array($keyword)) 
		{		
			$res = array();
			foreach($keyword as $name) 
				$res[$name] = $this->isCached($name);
        	return $res;
		}
        if(method_exists($this->processor,'itemExists')) return $this->processor->itemExists($keyword);
        $data = $this->get($keyword);
        if(!$data) return false;
        return true;
    }
   public  function getInfo($keyword, $option = array()) 
	{
		if(func_num_args()=='1' and is_array($keyword)) 
		{		
		  $res = array();
		  foreach($keyword as $array) {
			  $name = $array[0];
			  $res[$name] = $this->getInfo($name, isset($array[1]) ? $array[1] : array());
		  }
		  return $res;
		}
        $object = $this->processor->read($keyword,$option);
		if($object == null) return null;
        return $object;
    }

    public function delete($keyword, $option = array()) 
	{
		if(func_num_args()=='1' and is_array($keyword)) 
		{
		    foreach($keyword as $array)
            $this->delete($array[0], isset($array[1]) ? $array[1] : array());
			return;
        }
        return $this->processor->remove($keyword,$option);
    }

    public function stats($option = array()) 
	{
		return array_merge($this->settings,$this->processor->getInfos($option));
    }

    public function clear($option = array()) 
	{
		return $this->processor->cleanup($option);
    }

    function increment($keyword, $step = 1 , $option = array()) 
	{
		
		if(func_num_args()=='1' and is_array($keyword))
		{
			$res = array();
			foreach($keyword as $array) {
				$name = $array[0];
				$res[$name] = $this->increment($name, $array[1], isset($array[2]) ? $array[2] : array());
			}
			return $res;
		}		
        $object = $this->get($keyword);
        if(!$object) {
            return false;
        } else {
            $value = (Int)$object['value'] + (Int)$step;
            $time = $object['expirationDate'] - @date('U');
            $this->set($keyword,$value, $time, $option);
            return true;
        }
    }

    function decrement($keyword, $step = 1 , $option = array()) {
		if(func_num_args()=='1' and is_array($keyword)){
			$res = array();
			foreach($keyword as $array) {
				$name = $array[0];
				$res[$name] = $this->decrement($name, $array[1], isset($array[2]) ? $array[2] : array());
			}
			return $res;
		}		
        $object = $this->get($keyword);
        if($object == null) {
            return false;
        } else {
            $value = (Int)$object['value'] - (Int)$step;
            $time = $object['expirationDate'] - @date('U');
            $this->set($keyword,$value, $time, $option);
            return true;
        }
    }
    /*
     * Extend more time
     */
    function touch($keyword, $time = 300, $option = array()) {
		if(func_num_args()=='1' and is_array($keyword)){
        $res = array();
        foreach($list as $array) {
            $name = $array[0];
            $res[$name] = $this->touch($name, $array[1], isset($array[2]) ? $array[2] : array());
        }
        return $res;
    	}		
        $object = $this->get($keyword);
        if($object == null) {
            return false;
        } else {
            $value = $object['value'];
            $time = $object['expirationDate'] - @date('U') + $time;
            $this->set($keyword, $value,$time, $option);
            return true;
        }
    }
    /*
     * Begin Parent Classes;
     */
    public function setup($name,$value = null) 
	{
        if(is_array($name)) 
		{
			foreach($name as $n=>$value) $this->setup($n,$value);
			return;
		}
		if($name == 'adapter')
		{ 	if(!$this->driverExists($value)) return false;
			$value = 'iriven'.ltrim($value,'iriven');
			$option['skipError']=true;
			$check = new $value($option);
			if(!$check->isEnabled()) return false;
			$value = ltrim($value,'iriven');
		}
		if($name == 'path') 
		{
			$this->settings['pathVerified'] = false;
			$this->processor->settings['pathVerified'] = false;
		}
		return $this->settings[$name] = $value;
    }
/*
*
*	fin de la classe
*/
}