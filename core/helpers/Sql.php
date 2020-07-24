<?php
namespace Core\Helpers;

use Core\Database as Database;
use Core\Helpers\Inflector as Inflector;

class Sql extends \Core\Database {

	private $pdo;
	private $_inf;
	private $_query;
	private $_select;
	private $_from;
	private $_join;
	private $_limit;
	private $_where;
	private $_order;
	private $_group;
	private $_sp;
	public $array_only;
	private $rows;

	function __construct()
	{
		$this->pdo = $this->connect();
		$this->_inf = new Inflector;
		$this->_sp = ' ';
		$this->_join = '';
		$this->_from = '';
	}

	private function get_db_name($class)
	{
		return PREFIX.$this->_inf->pluralize($this->_inf->singularize(strtolower($class)));
		// return PREFIX.strtolower($class);
	}

	public function reset() {
		// resets everything
		$this->_select = '';
		$this->_join = '';
		$this->_from = '';
		$this->_where = '';
		$this->_group = '';
		$this->_order = '';
		$this->_limit = '';
	}

	// execute stuff from the database and return an object list
	public function run($debug=false)
	{
		global $NUMRQ;
		$NUMRQ++;
		$this->_query = $this->_select.
			$this->_sp.$this->_from.
			$this->_sp.$this->_join.
			$this->_sp.$this->_where.
			$this->_sp.$this->_group.
			$this->_sp.$this->_order.
			$this->_sp.$this->_limit;
		if ($debug) _dump($this->_query);

		$this->pdo->query("SET sql_mode = ''");
		$rs = $this->pdo->prepare($this->_query);
		$rs->execute();
		if (strstr($this->_select, 'UPDATE') || strstr($this->_select, 'DELETE')) {
			return true;
		}

		$this->rows = $rs->rowCount();
		if ($this->rows == 0)
		{
			return false;
		}
		else if ($this->rows == 1)
		{
			$query = $rs->fetchObject();
			if ($this->array_only) $query = array((array)$query);
		}
		else if ($this->rows > 1)
		{
			$query = $rs->fetchAll(\PDO::FETCH_ASSOC);
		}
		// resets everything
		// $this->_select = '';
		// $this->_join = '';
		// $this->_from = '';
		// $this->_where = '';
		// $this->_group = '';
		// $this->_order = '';
		// $this->_limit = '';
		return $query;
	}

	public function select($fields)
	{
		$this->_select = "SELECT $fields";
	}

	public function delete()
	{
		$this->_select = "DELETE";
	}

	public function from($class, $alias='')
	{
		$this->_from = "FROM ".$this->get_db_name($class);
		$this->_from .= $alias ? " $alias" : '';
	}

	public function update($class, $alias='')
	{
		$this->_select = "UPDATE ".$this->get_db_name($class);
		$this->_select .= $alias ? " $alias" : '';
	}

	public function set($field, $value)
	{
		$this->_from .= $this->_from ? ", $field = '$value'" : " SET $field = '$value'";
	}

	public function where($param, $value=false)
	{
		// initializes if empty
		$this->_where = $this->_where ? $this->_where.' AND ' : "WHERE ";
		$this->_where .= $value ? $param." = '$value'" : $param;
	}

	// table alias required
	public function join($class, $on)
	{
		$class_arr = explode(" ", $class);
		$this->_join .= "JOIN ".$this->get_db_name($class_arr[0])." ".$class_arr[1]." ON (".$on.") ";
	}

	// table alias required
	public function l_join($class, $on)
	{
		$class_arr = explode(" ", $class);
		$this->_join .= "LEFT JOIN ".$this->get_db_name($class_arr[0])." ".$class_arr[1]." ON (".$on.") ";
	}

	public function limit($limit, $offset=0)
	{
		$this->_limit = "LIMIT $limit OFFSET $offset";
	}

	public function group($fields)
	{
		$this->_group = "GROUP BY $fields";
	}

	public function order($field, $order='ASC')
	{
		$this->_order = $this->_order ? $this->_order.", $field $order" : "ORDER BY $field $order";
	}

	public function _set($field, $value) {
		$this->$field = $value;
	}

	public function getCount() {
		return $this->rows;
	}

}

?>