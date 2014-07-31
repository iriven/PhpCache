<?php
/*
 * iriven@yahoo.fr
 * Website: http://www.iriven.com
 * Example at our website, any bugs, problems, please visit http://www.iriven.com
 */
class irivenapc extends irivenPhpCache implements  irivenPhpCacheDriver {
    function isEnabled()
	 {
        // Check apc
        if(extension_loaded('apc') and ini_get('apc.enabled'))
		return true;
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
            return apc_add($keyword,$value,$time);
        } else {
            return apc_store($keyword,$value,$time);
        }
    }

    function read($keyword, $option = array()) {
        // return null if no caching
        // return value if in caching

        $data = apc_fetch($keyword,$bo);
        if($bo === false) {
            return null;
        }
        return $data;

    }

    function remove($keyword, $option = array()) {
        return apc_delete($keyword);
    }

    function getInfos($option = array()) {
        $res = array(
            'info' => '',
            'size'  => '',
            'data'  =>  '',
        );

        try {
            $res['data'] = apc_cache_info('user');
        } catch(Exception $e) {
            $res['data'] =  array();
        }

        return $res;
    }

    function cleanup($option = array()) {
        @apc_clear_cache();
        @apc_clear_cache('user');
    }

    function itemExists($keyword) {
        if(apc_exists($keyword)) {
            return true;
        } else {
            return false;
        }
    }
}