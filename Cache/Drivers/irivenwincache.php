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
**/class irivenwincache extends irivenPhpCache implements  irivenPhpCacheDriver  {

    function isEnabled() {
        if(extension_loaded('wincache') and function_exists('wincache_ucache_set'))
        {
            return true;
        }
        return false;
    }

    function __construct($option = array()) {
        $this->setOption($option);
        if(!$this->isEnabled() and !isset($option['skipError'])) {
            throw new Exception('Can\'t use "'.ltrim(__CLASS__,'iriven').'"  driver for your website!');
        }

    }

    function write($keyword, $value = '', $time = 300, $option = array() ) {
        if(isset($option['skipExisting']) and $option['skipExisting'] == true) {
            return wincache_ucache_add($keyword, $value, $time);
        } else {
            return wincache_ucache_set($keyword, $value, $time);
        }
    }

    function read($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching

        $x = wincache_ucache_get($keyword,$suc);

        if($suc == false) {
            return null;
        } else {
            return $x;
        }
    }

    function remove($keyword, $option = array()) {
        return wincache_ucache_delete($keyword);
    }

    function getInfos($option = array()) {
        $res = array(
            'info'  =>  '',
            'size'  =>  '',
            'data'  =>  wincache_scache_info(),
        );
        return $res;
    }

    function cleanup($option = array()) {
        wincache_ucache_clear();
        return true;
    }

    function itemExists($keyword) {
        if(wincache_ucache_exists($keyword)) {
            return true;
        } else {
            return false;
        }
    }



}