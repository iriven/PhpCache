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
class irivenmemcache extends irivenPhpCache implements  irivenPhpCacheDriver {

    private $instant;

    function isEnabled() {
        // Check memcache
        if(function_exists('memcache_connect')) {
            return true;
        }
        return false;
    }

    function __construct($option = array()) {
        $this->setOption($option); 
        if(!$this->isEnabled() and !array_key_exists('skipError',$option)) {
            throw new Exception('Can\'t use "'.ltrim(__CLASS__,'iriven').'"  driver for your website!');
        }
      if($this->isEnabled()) $this->instant = new Memcache();
    }

    function connectServer() {
        $server = $this->settings['server'];
        if(count($server) < 1) $server = array(
												array('127.0.0.1',11211),
												);
        foreach($server as $s) {
            $name = $s[0].'_'.$s[1];
            if(!isset($this->checked[$name])) {
                $this->instant->addserver($s[0],$s[1]);
                $this->checked[$name] = 1;
            }

        }
    }

    function write($keyword, $value = '', $time = 300, $option = array() ) {
        $this->connectServer();
        if(isset($option['skipExisting']) and $option['skipExisting'] == true) {
            return $this->instant->add($keyword, $value, false, $time );

        } else {
            return $this->instant->set($keyword, $value, false, $time );
        }

    }

    function read($keyword, $option = array()) {
        $this->connectServer();
        // return null if no caching
        // return value if in caching
        $x = $this->instant->get($keyword);
        if($x == false) {
            return null;
        } else {
            return $x;
        }
    }

    function remove($keyword, $option = array()) {
        $this->connectServer();
         $this->instant->delete($keyword);
    }

    function getInfos($option = array()) {
        $this->connectServer();
        $res = array(
            'info'  => '',
            'size'  =>  '',
            'data'  => $this->instant->getStats(),
        );

        return $res;

    }

    function cleanup($option = array()) {
        $this->connectServer();
        $this->instant->flush();
    }

    function itemExists($keyword) {
        $this->connectServer();
        $x = $this->get($keyword);
        if($x == null) {
            return false;
        } else {
            return true;
        }
    }



}