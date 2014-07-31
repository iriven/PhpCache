<?php
/*
 * iriven@yahoo.fr
 * Website: http://www.iriven.com
 * Example at our website, any bugs, problems, please visit http://www.iriven.com
 */class irivenmemcached extends irivenPhpCache implements  irivenPhpCacheDriver  {

    private $instant;

    function isEnabled() {
        if(class_exists('Memcached')) {
            return true;
        }
       return false;
    }

    function __construct($options = array()) {
        $this->setOption($options);
        if(!$this->isEnabled() and !isset($options['skipError'])) {
            throw new Exception('Can\'t use "'.ltrim(__CLASS__,'iriven').'"  driver for your website!');
        }

       if($this->isEnabled()) $this->instant = new Memcached();
    }

    function connectServer() {
        $s = $this->settings['server'];
        if(count($s) < 1) {
            $s = array(
                array('127.0.0.1',11211,100),
            );
        }

        foreach($s as $server) {
            $name = isset($server[0]) ? $server[0] : '127.0.0.1';
            $port = isset($server[1]) ? $server[1] : 11211;
            $sharing = isset($server[2]) ? $server[2] : 0;
            $checked = $name.'_'.$port;
            if(!isset($this->checked[$checked])) {
                if($sharing >0 ) {
                    $this->instant->addServer($name,$port,$sharing);
                } else {
                    $this->instant->addServer($name,$port);
                }
                $this->checked[$checked] = 1;
            }
        }
    }

    function write($keyword, $value = '', $time = 300, $option = array() ) {
        $this->connectServer();
        if(isset($option['isExisting']) and $option['isExisting'] == true) {
            return $this->instant->add($keyword, $value, time() + $time );
        } else {
            return $this->instant->set($keyword, $value, time() + $time );

        }
    }

    function read($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching
        $this->connectServer();
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
        'info' => '',
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