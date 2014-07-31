<?php

/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */
class irivensqlite extends irivenPhpCache implements  irivenPhpCacheDriver  {
    private $path;
    private $db;
    /*
     * Init Main Database & Sub Database
     */
    function __construct($option = array()) {
        /*
         * init the path
         */
        $this->setOption($option);
        if(!$this->isEnabled() and !isset($option['skipError'])) {
            throw new Exception('Can\'t use "'.ltrim(__CLASS__,'iriven').'"  driver for your website!');
        }
        if(!is_dir($this->path = $this->cachePath().DIRECTORY_SEPARATOR.ltrim(__CLASS__,'iriven'))) 
        if(!@mkdir($this->path,0705,true))  die('Sorry, Please CHMOD 0705 for this path: '.$this->path);
		$this->db = new PDO('sqlite:'.$this->path.DIRECTORY_SEPARATOR.'irivendatabase.sqlite');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		// create cache database
		$this->db->exec('CREATE TABLE IF NOT EXISTS `irivencache`  (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `keyword` VARCHAR UNIQUE, `content` BLOB, `exp` INTEGER)');
		// don't verify data on disk
		$this->db->exec('PRAGMA synchronous = OFF');
		// turn off rollback
		$this->db->exec('PRAGMA journal_mode = OFF');
		// peridically clean the database
		$this->db->exec('PRAGMA auto_vacuum = INCREMENTAL');		
		@$this->db->exec('DELETE FROM `irivencache` WHERE `exp`<='.@date('U'));
		return $this;
    }
    function isEnabled() {
        if(extension_loaded('pdo_sqlite') and is_writeable($this->cachePath())) {
           return true;
        }
        return false;
    }

    function write($keyword, $content = '', $time = null, $option = array() ) {
        $skipExisting = isset($option['skipExisting']) ? $option['skipExisting'] : false;
        $toWrite = true;
		$this->db->exec('DELETE FROM `irivencache` WHERE `exp`<='.@date('U'));
        // check in cache first
        if($this->itemExists($keyword) and ($skipExisting == true)) $toWrite = false;
        if($toWrite == true)
		{
			  $stm = $this->db->prepare('INSERT OR REPLACE INTO `irivencache` (`keyword`,`content`,`exp`) values(:keyword,:content,:exp)');
			 if( $stm->execute(array(
				  ':keyword'  => $keyword,
				  ':content'   =>  $this->encode($content),
				  ':exp'      => @date('U') + (Int)$time,
			  )))

			  return true;
        }

        return false;

    }

    function read($keyword, $option = array()) {
            $stm = $this->db->prepare('SELECT * FROM `irivencache` WHERE `keyword`=:keyword LIMIT 1');
            $stm->execute(array(
                ':keyword'  =>  $keyword
            ));
            // cache miss			
            if(!$row = $stm->fetch(PDO::FETCH_ASSOC)) return false;
     // time to live elapsed			
        if(isset($row['exp']) and @date('U') >= $row['exp']) {
         $stmt = $this->db->prepare('DELETE FROM `irivencache` WHERE (`id`=:id) OR (`exp` <= :U) ');
         $stmt->execute(array(
            ':id'   => $row['id'],
            ':U'    =>  @date('U'),
        ));
            return false;
        }
        if(isset($row['id'])) {
            $data = $this->decode($row['content']);
            return $data;
        }
        return false;
    }


    function remove($keyword, $option = array()) {
        $stm = $this->db->prepare('DELETE FROM `irivencache` WHERE (`keyword`=:keyword) OR (`exp` <= :U)');
        $stm->execute(array(
            ':keyword'   => $keyword,
            ':U'    =>  @date('U'),
        ));
    }

    function getInfos($option = array()) {
        $res = array(
            'size'  =>  round(filesize($this->path.DIRECTORY_SEPARATOR.'irivendatabase.sqlite'),1)/1024 .'KB',
            'totalItems'  =>  '0',
			'filename' =>'irivendatabase.sqlite',
        );
            // get number of items in cache
			$stm = $this->db->prepare('SELECT * FROM `irivencache` WHERE (`exp` <= :U)');
			$stm->execute(array(
            		':U'    =>  @date('U'),
        			));
           // $query = $this->db->query('SELECT * FROM `irivencache`');
            if ($result = $stm->fetchAll())
                $res['totalItems'] = $result->rowCount();
            return $res;
    }

    function cleanup($option = array()) {
        
        // close connection
        $this->db = NULL;
        $paths = $this->cachePath();
		//$path = $this->cachePath().DIRECTORY_SEPARATOR.ltrim(__CLASS__,'iriven');
		$emptyDir = function ($pathname) 
				{
					if(is_file($pathname)) return unlink($pathname);
					$scandir=new RecursiveDirectoryIterator($pathname,FilesystemIterator::SKIP_DOTS);
					$iterator = new RecursiveIteratorIterator($scandir, RecursiveIteratorIterator::CHILD_FIRST); 
					foreach ($iterator as $path)
					{
						if ($path->isDir())	rmdir($path->getPathName());
						else unlink($path->getPathName());
					}
					return true;
				};
		if($emptyDir($paths)) return true;
		return false;
    }

    function itemExists($keyword) {
        $stm = $this->db->prepare('SELECT COUNT(`id`) as `total` FROM `irivencache` WHERE `keyword`=:keyword');
        $stm->execute(array(
            ':keyword'   => $keyword
        ));
        $data = $stm->fetch(PDO::FETCH_ASSOC);
        if($data['total'] >= 1) {
            return true;
        } else {
            return false;
        }
    }


}
