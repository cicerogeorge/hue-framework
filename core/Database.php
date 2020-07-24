<?php
namespace Core;

use \PDO as PDO;

class Database {

	protected $_db_host;
	protected $_db_login;
	protected $_db_pswd;
	protected $_db_name;

	function __construct() {
		$this->_db_host = HOST;
		$this->_db_name = DATABASE;
		$this->_db_login = USERNAME;
		$this->_db_pswd = PASSWORD;
	}

	public function set($param, $value) {
		$this->$param = $value;
	}

	// connects to the database
	// returns PDO or false
	public function connect($driver='mysql') {
		$pdo = new PDO($driver.":host=".HOST.";dbname=".DATABASE, USERNAME, PASSWORD);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET CHARACTER SET utf8");

        $this->_db_host = $this->_db_name = $this->_db_login = $this->_db_pswd = null;

		return $pdo;
	}

}
