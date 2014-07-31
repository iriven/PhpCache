<?php
/*
 * iriven@yahoo.fr
 * Website: http://www.iriven.com
 * Example at our website, any bugs, problems, please visit http://www.iriven.com
 */
interface irivenPhpCacheExtension {

     function __construct($option = array());
    /*
     * Check if this Cache driver is available for server or not
     */
     function isEnabled();

    /*
     * SET
     * set a obj to cache
     */
     function write($keyword, $value = "", $time = 300, $option = array() );

    /*
     * GET
     * return null or value of cache
     */
     function read($keyword, $option = array());

    /*
     * Stats
     * Show stats of caching
     * Return array ("info","size","data")
     */
     function getInfos($option = array());

    /*
     * Delete
     * Delete a cache
     */
     function remove($keyword, $option = array());

    /*
     * clean
     * Clean up whole cache
     */
     function cleanup($option = array());
	 
    /*
     * itemExists
     * check if an item is stored in the cache
     */	 
	 function itemExists($keyword);
}