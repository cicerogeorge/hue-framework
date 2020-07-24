<?php
namespace Core;

use Core\Helpers\Inflector as Inflector;
 /**
  * Core application model methods.
  *
  * This class includes all common methods for any application model,
  * including database access layer.
  *
  * The section after the description contains the tags; which provide
  * structured meta-data concerning the given element.
  *
  * @author  Cicero Monteiro <cicerogeorge@gmail.com>
  */

class App_Model extends Database {

	protected $sql;
	protected $inf;
	protected $pdo;
	private $db_prefix;// prefix of original table, in case of join statement
	private $db_join;// array of tables to join upon select. format: ['table_name'=>'alias']
	private $db_fields;// specific fields to retrieve upon select, works with aliases from $join. format: ['field'=>'alias'];
	private $bkp;// a backup of the actual object for update comparison
	private $debug;

	function __construct() {
		$this->db_prefix = 'HUEH';
		$this->db_join = $this->db_fields = [];
		$this->inf = new Inflector();
		$this->sql = '';
		$this->pdo = $this->connect();
		// parent::__construct();
	}

	/* join methods */
	
	public function join() {
		return $this;
	}

	public function prefix($prefix) {
		$this->set('db_prefix', $prefix);
	}

	public function tables($join=[]) {
		$this->db_join = $join;
	}

	public function fields($fields=[]) {
		$this->db_fields = $fields;
	}

	/* generic methods */

	public function get_by_id($id) {
		return $this->get_by(array('id'=>$id));
	}

	public function get_by($params=array()) {
		$class = get_class($this);
		if (!$obj = $this->retrieve($params)) return false;

		foreach ($obj[0] as $key => $value) {
			if (property_exists($class, $key)) {
				$this->$key = $value;
			}
		}
		// $this->sql = $this->inf = $this->pdo = null;
		$this->bkp = (array)$this;
		return $this;
	}

	public function set($param, $value) {
		$this->$param = $value;
	}

	public function get($param) {
		return $this->$param;
	}

	public function set_values($params) {
		// set values to object
		foreach ($params as $key => $value) {
			$this->$key = $value;
		}
	}

	public function _table_name($str) {
		return strtolower($this->inf->pluralize($str));
	}

	/**
	 * Returns databse table name from an application class name.
	 *
	 * @param string $class Class name to be decrypted
	 *
	 * @return string Table name for given class
	 */
	private function table_name_from_class($class) {
		$class = strtolower($class);
		$arr = explode("\\", $class);
		$model = str_replace('_model', '', $arr[2]);

		return $this->inf->pluralize($model);
	}

	public function validates_fields($params, $mandatory) {
		global $CONFIG;
		$return = true;
		foreach ($mandatory as $key=>$field) {
			if (@$params[$key] == '') {
				$CONFIG['msg']['error'][] = 'O campo '.$field.' é obrigatório';
				$return = false;
			}
		}
		return $return;
	}

	// retrieves any object from the database
    // sql injection safe, can receive params directly from user
    public function retrieve($params = [], $sql_methods = [], $limit = '', $page = 1) {

        $pdo = $this->connect();

        $class = get_class($this);

        $_where = $and = $_fields = '';

        // get join fields, if needed
        if (count($this->db_fields) > 0) {
        	foreach ($this->db_fields as $_field => $_alias) {
        		$_fields .= ', '.$_field.' AS '.$_alias;
        	}
        }

        // get table name
        $table = $this->table_name_from_class($class);

        $sql = "SELECT SQL_CALC_FOUND_ROWS ".$this->db_prefix.".* ".$_fields." FROM `{$table}` ".$this->db_prefix." ";

        // join tables, if needed
        if (count($this->db_join) > 0) {
        	foreach ($this->db_join as $_join_type => $_tables) {
        		// get tables that will be joined with inner, left or right join
        		foreach ($_tables as $_table => $_alias) {
        			// if foreign key is array then it's joining two external tables
        			if (is_array($_alias)) {
        				// get table alias and foreign table alias
        				$_table_alias = key($_alias);
        				$_table_alias_join = $_alias[$_table_alias];
        				// split table alias
	        			$_alias_arr = explode('.', $_table_alias);
	        			$sql .= " ".strtoupper($_join_type)." JOIN {$_table} {$_alias_arr[0]} ON ";
	        			$sql .= '('.$_table_alias.' = '.$_table_alias_join.')';
        			}
        			// if foreign key is the external table id, it joins using a foreign key on the main table
        			else {
        				// split table alias (0) and foreign key (1)
	        			$_alias_arr = explode('.', $_alias);
	        			$sql .= " ".strtoupper($_join_type)." JOIN {$_table} {$_alias_arr[0]} ON ";
	        			if ($_alias_arr[1] == 'id') {
	        				$fk = $this->inf->singularize($_table).'_id';
	        				$sql .= "({$this->db_prefix}.{$fk} = {$_alias_arr[0]}.id)";
	        			}
	        			// if the foreign key is on the external table, joins the main table id with the external table foreign key
	        			else {
	        				$fk = $_alias_arr[0].'.'.$this->inf->singularize($table).'_id';
	        				$sql .= "({$fk} = {$this->db_prefix}.id)";
	        			}
	        		}
        		}
        	}
        }

        $operators = array('!=', '>=', '<=', '>', '<', '=', 'LIKE', 'BETWEEN', 'IN');

        if (is_array($params) && count($params)) {
            foreach ($params as $key => $value) {
                if ($value === 'null') {
                    $_where .= $and . $key . " IS NULL";
                    unset($params[$key]);
                }
                else if (strtolower($value) === 'not null') {
                    $_where .= $and . $key . " IS NOT NULL";
                    unset($params[$key]);
                }
                else if ($op = stringContainArrayValue($operators, $value)) {
                    if ($op == 'LIKE') {
                    	$params[$key] = trim(str_replace($op, '', $value));
                    	$_where .= $and . $key .' '. $op . " ?";
                    }
                    else if ($op == 'BETWEEN') {
                    	$v = strtolower(trim(str_replace($op, '', $value)));
                    	$v_arr = explode(' and ', $v);
                    	$params[$key] = $v_arr[0];
                    	$params[$key.'1'] = $v_arr[1];
                    	$_where .= $and . '('. $key .' '. $op . " ? AND ?)";
                    }
                    else if ($op == 'IN') {
                    	$_where .= $and . $key . ' ' . $value;
                    }
                    else {
                    	$params[$key] = trim(str_replace($op, '', $value));
                    	$_where .= $and . $key .' '. $op . " ?";
                    }
                }
                else {
                    $_where .= $and . $key . " = ?";
                }
                $and = ' AND ';
            }
        }

        $sql .= $_where ? " WHERE $_where" : "";

        // $sql .= $order_by != '' ? " ORDER BY $order_by" : '';

        if (is_array($sql_methods) && count($sql_methods) >= 1) {
        	foreach ($sql_methods as $key => $value) {
        		if (strtolower($key) == 'order') {
        			$sql .= " ORDER BY ";
        			$comma = '';
	        		foreach ($value as $k => $v) {
	        			$sql .= $comma." {$k} ".strtoupper($v);
	        			$comma = ', ';
	        		}
        		}
        		else if ($key == 'group') {
        			$sql .= " GROUP BY ";
        			$comma = '';
	        		foreach ($value as $k => $v) {
	        			$sql .= $comma." {$v} ";
	        			$comma = ', ';
	        		}
        		}
        		
        	}
        }

        if ($limit) {
        	$offset = $limit * ($page-1);
        	$sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $values = is_array($params) ? array_values($params) : array();
        $rs = $pdo->prepare($sql);

        // echo $sql;_dump($values).'<br>';
        if ($rs->execute($values)) {
        	if ($this->debug == true) {
	        	echo parms($sql, $values);
	        }
            $result = $rs->fetchAll(\PDO::FETCH_ASSOC);
            if (!count($result)) {
                return false;
            }
            else if ($limit) {
            	$_sql = "SELECT FOUND_ROWS() AS 'total'";
            	$total_rs = $pdo->prepare($_sql);
            	$total_rs->execute();
            	$total_result = $total_rs->fetch();
            	return [$result, $total_result['total']];
            }
            return $result;
        }

        return false;
    }

	public function create($debug=0) {

		global $CONFIG;

		$obj = $this;

		$fields_insert = $values_insert = array();
		// get object class
		$class = strtolower(get_class($obj));
		// get database table name
		// $table = $this->_table_name(substr($class, 0, strpos($class, '_model')));
		$table = $this->table_name_from_class($class);
		// connect to database
		$pdo = $this->connect();
		// set created date
		// $obj->created_at = date('Y-m-d H:i:s');
		$obj->date_create = date('Y-m-d H:i:s');
		// transform object into array

		$obj_arr = (array)$obj;
		// filter object values
		foreach ($obj_arr as $key => $value) {
			// avoid internal objects
			if (is_object($value) || is_array($value)) continue;
			// filter float
			if (preg_match("/^([0-9]\.?){1,}\,([0-9]{1,})$/", $value)) {
				$value = str_replace('.', '', $value);
				$value = str_replace(',', '.', $value);
				$obj_arr[$key] = (float)$value;
			}
			// filter date
			else if (preg_match("/^([0-9]{2}\/){2}([0-9]{4})$/", $value)) {
				$value = format_date($value, 'Y-m-d');
				$obj_arr[$key] = $value;
			}
			else if (preg_match("/^[0-9]{1,}$/", $value)) {
				$value = (int)$value;
				$obj_arr[$key] = $value;
			}
			else if ($value == '') {
				$obj_arr[$key] = null;
			}
		}
		// iterate for reference fields for sql string
		foreach ($obj_arr as $field => $value) {
			$fields_insert[] = $field;
			$values_insert[] = ':'.$field;
			// avoid getting extended vars
			// if ($field == 'deleted_at') break;
			if ($field == 'active') break;
		}
		$fields_insert = implode('`,`', $fields_insert);
		$values_insert = implode(',', $values_insert);
		// mount sql string
		$sql = "INSERT INTO `$table`(`$fields_insert`) VALUES($values_insert)";

		$rs = $pdo->prepare($sql);
		// set reference values into PDOStatement object
		foreach ($obj_arr as $f => $v) {
			$rs->bindValue(':'.$f, $v);
			// avoid getting extended vars
			// if ($f == 'deleted_at') break;
			if ($f == 'active') break;
		}
                
		try {
			$rs->execute();
			if ($debug) {
				$rs->debugDumpParams();
			}
			return $pdo->lastInsertId();
		} catch (PDOException $e) {
			if ($debug) {
				$rs->debugDumpParams();
				core_errors($e);
			}
			return false;
		}

	}

	public function update($debug=0) {

		global $CONFIG;

		$class = strtolower(get_class($this));
		// get database table name
		// $table = $this->_table_name(substr($class, 0, strpos($class, '_model')));
		$table = $this->table_name_from_class($class);

		$obj_array = (array)$this;

		$sql = "UPDATE ".$table." SET ";
		$values_array = array();
		$comma = '';

		foreach ($obj_array as $key => $value) {
			// avoid internal objects
			if (is_object($value) || is_array($value)) continue;
			// filter float
			if (preg_match("/^([0-9]\.?){1,}\,([0-9]{1,})$/", $value)) {
				$value = str_replace('.', '', $value);
				$value = str_replace(',', '.', $value);
				$obj_array[$key] = (float)$value;
			}
			// filter date
			else if (preg_match("/^([0-9]{2}\/){2}([0-9]{4})$/", $value)) {
				$value = format_date($value, 'Y-m-d');
				$obj_array[$key] = $value;
			}
			else if (preg_match("/^[0-9]{1,}$/", $value)) {
				$value = (int)$value;
				$obj_array[$key] = $value;
			}
			else if ($value == '') {
				$obj_array[$key] = null;
			}
		}

		$system_logs_values = $log_comma = '';
		foreach ($obj_array as $key => $value) {
			// if ($this->bkp[$key] != $value) {
			// 	// save log
			// 	$system_logs_values .= $log_comma."('".$_SESSION['app']['user']['id']."', '".$table."', '".$this->id."', 'update', '".$key."', '".$this->bkp[$key]."', '".$value."', NOW())";
			// 	$log_comma = ', ';
			// }

			// update object accordingly
			$sql .= $comma.'`'.$key.'` = ?';
			$comma = ', ';
			$values_array[] = $value;
			// if ($key == 'deleted_at') break;
			if ($key == 'active') break;
		}

		$values_array[] = $this->id;
		$sql .= " WHERE id = ?";

		$pdo = $this->connect();		

		$rs = $pdo->prepare($sql);
		// if ($rs->execute($values_array)) {
		// 	return true;
		// }
		// else {
		// 	var_dump($rs->errorInfo());
		// }
		try {
			$rs->execute($values_array);
			// save log
			return true;
		} catch (PDOException $e) {
			if ($CONFIG['app']['env'] == 'development') {
				core_errors($e);
			}
			if ($debug == 1) {
				var_dump($rs->errorInfo());
			}
			return false;
		}
	}

	public function delete() {
		global $CONFIG;
		$class = strtolower(get_class($this));
		// get database table name
		$table = $this->table_name_from_class($class);

		$sql = "DELETE FROM $table WHERE id = :id";

		$pdo = $this->connect();

		$rs = $pdo->prepare($sql);
		$rs->bindParam(':id', $this->id);
		$rs->execute();
		if (!$rs->rowCount()) {
			return false;
		}
		else {
			return true;
		}
	}

	// receives array with ids to batch delete from database
	public function delete_batch($collection) {
		$class = get_class($this);

		$table = $this->table_name_from_class($class);

		$sql = '';
		foreach ($collection as $id) {
			$sql .= "DELETE FROM $table WHERE id = '$id';";
		}
		$pdo = $this->connect();

		$rs = $pdo->prepare($sql);

		// if ($rs->execute()) {
		// 	return true;
		// }
		// else {
		// 	var_dump($rs->errorInfo());
		// }
		try {
			$rs->execute();
			return true;
		} catch (PDOException $e) {
			if ($CONFIG['app']['env'] == 'development') {
				core_errors($e);
			}
			return false;
		}
	}

}