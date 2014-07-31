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
interface irivenPhpCacheDriver {

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