<?php
/*
 * iriven@yahoo.fr
 * Website: http://www.iriven.com
 * Example at our website, any bugs, problems, please visit http://www.iriven.com
 */
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