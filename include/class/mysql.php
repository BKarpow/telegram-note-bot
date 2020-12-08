<?php 



/**
* @description MINI Class from usign MySql
* @author BogdanKarpow <xymerone@gmail.com>
* @version 0.1 (alpha)
*/


final class MySql
{
	private string $table = '';
	private mysqli $db;
	public int $count = 0;
	private string $select = '*';
	public bool $debug = false;
	function __construct(string $default_table = '')
	{
		$this->table = $default_table;
		$this->connector();
	}


	public function select(string $select)
	{
		$this->select = $select;
		return $this;
	}


	/**
	* Медод встановлює хв'хок  з бвзою двнних
	* @return void 
	*/
	private function connector()
	{
		$db = new mysqli(env('DB_HOST', 'localhost'),
            env('DB_USER', 'root'),
            env('DB_PASS', ''),
            env('DB_NAME'));
		if ($db->connect_errno){
			$this->exception("Error connect to db: ".$db->connect_error, $db->connect_errno);
		}else{
			$this->db = $db;
			$this->db->set_charset(env('DB_CHARSET', 'utf8'));
		}

	}

	/**
	* Метод для прямого доступу до бази через скл повертає масив або булеву відповідь
	* @param string $sql - SQL query string
	* @return array|bool
	*/
	public function q(string $sql)
	{
		//TODO Метод по харошому має бути приватним
		if (empty($sql)){ $this->exception("Error, empty query sql string", 1); }
		$result_query = $this->db->query($sql);
		if ($this->db->errno){
			$this->exception("Error query from db: " . $this->db->error, (int) $this->db->errno);
		}
		if ($result_query instanceof mysqli_result){
			$arr_data = [];
			while($data = $result_query->fetch_assoc()){
				$arr_data[] = $data;
				$this->count++;
			}
			$result_query->close();
			return $arr_data;
		}else{
			return (bool)$result_query;
		}


	}


	/**
	* Метод повертає масив всіх таблиць в базі двнних
	* @return array
	*/
	public function showAllTables():array
	{
		$result = (array) $this->q('SHOW TABLES');
		$data = [];
		foreach ($result as $value) {
			$data[] = array_values($value)[0];
		}
		return $data;
	}


	/**
	* Метод додає новий запис в таблицю
     * @param string $timestamp_column = 'date'
	* @param array $data - ['column1' => 'value1', ....]
	*/
	public function insert(array $data, string $timestamp_column = 'date')
	{
		if (empty($this->table)) $this->exception("Empty table name! Usign method MySql::setTable()", 5);
		if (!array_key_exists($timestamp_column, $data)){
			$data[$timestamp_column] = now();
		}
		$column_names = array_map( function($v){
			return '`'. trim($v) . '`';
		}, array_keys($data));
		$values = array_map( [$this, 'esc'], array_values($data));
		$sql = "INSERT INTO `{$this->table}` ";
		$sql .= ' (' . join(', ', $column_names) . ') ';
		$sql .= ' VALUES (' . join(', ', $values) . ')'; 
		// if ($this->debug){
		// 	echo "SQL:   ". $sql;
		// }
		return  $this->q($sql);

	}


	/**
	* Повертає всі записи з таблиці
	* @param string $select_column_raw = '*'
	* @return array
	*/
	public function all(string $select_column_raw = '*'):array
	{
		if (empty($this->table)) $this->exception("Empty table name! Usign method MySql::setTable()", 5);
		$res = $this->q('SELECT ' . $select_column_raw . " FROM `{$this->table}` ");
		return (array) $res;
	}


	/**
	* Метод поаертає масив записів за поточний день.
	* @param string $where = ''
	* @param string $date_column = 'date'
	* @return array
	*/
	public function getFromThisDay( string $where = '', string $date_column = 'date'):array
	{
		if (empty($this->table)) $this->exception("Empty table name! Usign method MySql::setTable()", 5);
		$wO = date('Y-m-d') . ' 00:00:00';
		$wD = date('Y-m-d') . ' 23:59:59';
		$w = " WHERE `{$date_column}` > '{$wO}' AND `{$date_column}` < '{$wD}' AND " . $where;
		$sql = "SELECT {$this->select} FROM `{$this->table}`  ".$w." ORDER BY `{$date_column}` DESC ";
		return $this->q($sql);
	}


	/**
	* Метод встановлює умову та поаертає результат 
	* @param string|array $colums_or_array
	* @param null|string $value
	* @return array
	*/
	public function where($colums_or_array, $value = null):array
	{
		if (is_array($colums_or_array) && is_null($value)){
			$where = " WHERE ";
			$w = [];
			foreach ($colums_or_array as $v) {
				if (!is_array($v)) continue;
				$w[] = " `{$v[0]}` {$v[1]} '{$v[2]}'' "; 
			}
			$where .= join(' AND ', $w);
			return $this->q("SELECT {$this->select} FROM `{$this->table}` {$where}");
		}elseif(is_string($colums_or_array) && !is_null($value)){
			$where = " WHERE `{$colums_or_array}` = '{$value}' ";
			return $this->q("SELECT {$this->select} FROM `{$this->table}` {$where}");
		}else{
			return [];
		}
	}


    /**
     * Оновлює записи в таблиці
     * @param array $array_values
     * @param string $where
     * @return array|bool
     */
	public function update(array $array_values, string $where)
    {
        $sql = "UPDATE `{$this->table}` SET ";
        $buf = [];
        foreach ($array_values as $column => $value){
            $buf[] = '`'.$column.'` = '.$this->esc($value);
        }
        $sql .= join(', ', $buf);
        $sql .= " WHERE ".$where;
        log_bot($sql, 'SQL');
        return $this->q($sql);
    }


    /**
     * Метод для пошуку по таблиці
     * @param string $search
     * @param string $column
     * @return array|bool
     */
    public function search(string $search, string $column)
    {
        $sql = "SELECT {$this->select} FROM ``{$this->table}` WHERE `{$column}` LIKE '%".$this->esc($search).
            "%' ORDER BY `id` DESC";
        return $this->q($sql);
    }


	/**
	* 	Видаляє запис по умові 
	* @param string $where
	* @return mixed
	*/
	public function delete(string $where)
	{
		if (empty($this->table)) $this->exception("Empty table name! Usign method MySql::setTable()", 5);
		$sql = "DELETE FROM `{$this->table}` WHERE ".$where;
		log_bot($sql ,'SQL');
		return $this->q($sql);
	}


	/**
	* Мето для опрацювання помилок в середені коду
	* @param string $message - Error message
	* @param int $code - Error code
	* @return void
	*/
	private function exception(string $message, int $code = 0)
	{
	    log_bot($message, 'ERROR');
		if ($this->debug){
			throw new ErrorException($message, $code);
			
		}else{
			die();
		}
	}


	/**
	* Метод для екранування рядка
	* @param string $string
	* @return string
	*/
	public function esc(string $string):string
	{
		return (string) '"' . $this->db->real_escape_string($string) . '"';
	}


	/**
	* Метод встановлює ім'я таблиці для вставки чи вибірки
	* @param string $name_default_table
	* @return void
	*/
	function setTable(string $name_default_table){
		$this->table = $name_default_table;
	}
}