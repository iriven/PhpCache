<?php
/*
 * iriven@yahoo.fr
 * Website: http://www.iriven.com
 * Example at our website, any bugs, problems, please visit http://www.iriven.com
 */class irivenxcache extends irivenPhpCache implements  irivenPhpCacheDriver  {

function isEnabled() 
{// Check xcache
	if(extension_loaded('xcache') and function_exists('xcache_get'))
	return true;
	return false;
}

function __construct($option = array())
{
	$this->setOption($option);
	if(!$this->isEnabled() and !isset($option['skipError']))
	throw new Exception('Can\'t use "'.ltrim(__CLASS__,'iriven').'"  driver for your website!');
}

function write($keyword, $value = '', $time = 300, $option = array() ) 
{
	if(isset($option['skipExisting']) and $option['skipExisting'] == true)
	{
		if(!$this->isCached($keyword)) return xcache_set($keyword,$value,$time);
	} 
	else return xcache_set($keyword,$value,$time);
	return false;
}

function read($keyword, $option = array()) 
{
	// return null if no caching
	// return value if in caching
	$data = xcache_get($keyword);
	if($data === false || $data == '') {
		return null;
	}
	return $data;
}

function remove($keyword, $option = array()) 
{
	return xcache_unset($keyword);
}

function getInfos($option = array()) 
{
	$res = array(
		'info'  =>  '',
		'size'  =>  '',
		'data'  =>  '',
	);

	try {
		$res['data'] = xcache_list(XC_TYPE_VAR,100);
	} catch(Exception $e) {
		$res['data'] = array();
	}
	return $res;
}

function cleanup($option = array()) 
{
	$cnt = xcache_count(XC_TYPE_VAR);
	for ($i=0; $i < $cnt; $i++) {
		xcache_clear_cache(XC_TYPE_VAR, $i);
	}
	return true;
}

function itemExists($keyword) 
{
	if(xcache_isset($keyword)) {
		return true;
	} else {
		return false;
	}
}



}