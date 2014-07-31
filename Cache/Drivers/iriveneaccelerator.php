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
class iriveneaccelerator extends irivenPhpCache implements  irivenPhpCacheDriver {
    function isEnabled()
	 {
        // Check eaccelerator
        if(function_exists('eaccelerator_get') and function_exists('eaccelerator_put'))
		return true;
		return false;
    }

    function __construct($option = array()) {
        $this->setOption($option);
        if(!$this->isEnabled() and !isset($option['skipError'])) {
            throw new Exception('Can\'t use "'.ltrim(__CLASS__,'iriven').'"  driver for your website!');
        }
		if($this->isEnabled()){
		eaccelerator_caching(true);
		 if (function_exists('eaccelerator_optimizer')) eaccelerator_optimizer(true);
		}
    }

    function write($keyword, $value = '', $time = 300, $option = array() ) {
        if(isset($option['skipExisting']) and $option['skipExisting'] == true) 
         return (null === eaccelerator_get($keyword)) ? eaccelerator_put($keyword,$value,$time) : false;
            return eaccelerator_put($keyword,$value,$time);
    }

    function read($keyword, $option = array()) {
		$value = eaccelerator_get($keyword);
		return ($value === null) ? false : $value;
    }

    function remove($keyword, $option = array()) {
        return eaccelerator_rm($keyword);
    }

    function getInfos($option = array()) {
        return eaccelerator_info();
    }

    function cleanup($option = array()) {
		// first, remove expired content from cache
		eaccelerator_gc();
		if(function_exists('eaccelerator_clear')) return eaccelerator_clear();
		// now, remove leftover cache-keys
		$keys = eaccelerator_list_keys();
		foreach($keys as $key)
			$this->remove(substr($key['name'], 1));
		return true;
    }

    function itemExists($keyword) {
        if(eaccelerator_get($keyword)) {
            return true;
        } else {
            return false;
        }
    }
}