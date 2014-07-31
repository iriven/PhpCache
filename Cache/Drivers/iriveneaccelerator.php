<?php
/*
 * iriven@yahoo.fr
 * Website: http://www.iriven.com
 * Example at our website, any bugs, problems, please visit http://www.iriven.com
 */
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