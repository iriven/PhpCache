<?php
/*
 * iriven@yahoo.fr
 * Website: http://www.iriven.com
 * Example at our website, any bugs, problems, please visit http://www.iriven.com
 */class irivenwincache extends irivenPhpCache implements  irivenPhpCacheDriver  {

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