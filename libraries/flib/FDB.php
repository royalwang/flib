<?php

/**
 *
 * 作者: 范圣帅(fanshengshuai@gmail.com)
 * 时间: 2012-07-02 01:22:18
 *
 * vim: set expandtab sw=4 ts=4 sts=4
 * $Id: DB.php 273 2012-08-22 10:37:34Z fanshengshuai $
 */

class FDB {

    private static $_conns = array();

    private $_dbh;

    /**
     * db 构造函数
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param string $charset
     * @param string $failover
     * @param boolean $persistent
     * @param integer $timeout
     */
    private function __construct($dsn, $user, $password, $charset, $failover = '', $persistent = false, $timeout = 0) {

        $attr = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                      PDO::ATTR_PERSISTENT => $persistent);
        if (0 < $timeout) {
            $attr[PDO::ATTR_TIMEOUT] = $timeout;
        }

        try {
            $this->_dbh = new PDO($dsn, $user, $password, $attr);
            $this->_dbh->exec("SET NAMES '" . $charset . "'");
        } catch (PDOException $e){

            if ($failover) {
                try {
                    $this->_dbh = new PDO($failover, $user, $password, $attr);
                    $this->_dbh->exec("SET NAMES '" . $charset . "'");
                } catch (PDOException $e){
                    throw new DB_Exception("can't connect to the server because:" . $e->getMessage());
                }
            } else {

                throw new DB_Exception("can't connect to the server because:" . $e->getMessage());
            }
        }
    }


    public function table($t) {
        return $t;
    }


    /**
     * 获取数据库连接类
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param string $charset
     * @param string $failover
     * @param boolean $persistent
     * @param integer $timeout
     * @return DB 实例
     */
    public static function connect() {
        global $_G;

        if ($_G ['db'] ['default']) {
			return $_G ['db'] ['default'];
		}

		if (! include (APP_ROOT . "config/db.php")) {
			throw new Exception ( 'NO DB CONFIG EXIST ! PLEASE CHECK config/db.php' );
		}

        if (strpos($table, '.')) {
            $db = substr($table, 0, strpos($table, '.'));
            $table = substr($table, strpos($table, '.') + 1);
        } else {
            $db = 'default';
        }
        $dsn = $config_db['dsn'];

        $config_db = $_config['db'][$db];

        if (!array_key_exists($dsn, self::$_conns)) {
            self::$_conns[$dsn] = new FDB(
                $config_db['dsn'],
                $config_db['user'],
                $config_db['password'],
                $config_db['charset'],
                $config_db['failover'],
                $config_db['persistent'],
                $config_db['timeout']
            );
        }
        $_G['db'][$db] = self::$_conns[$dsn];

        return self::$_conns[$dsn];
    }

    /**
     * 开启事务
     */
    public function begin() {

        $this->_dbh->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit() {

        $this->_dbh->commit();
    }

    /**
     * 回滚事务
     */
    public function rollBack() {

        $this->_dbh->rollBack();
    }

    /**
     * 取得记录的第一行
     *
     * @param sql string $query
     * @param array $params
     */
    public function fetchRow($query, $params = array()) {
        global $_G;

        if ($_G['debug']) {
            $_G['debug_info']['sql'][] = $query;
        }

        $stmt = $this->_dbh->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;

    }

    /**
     * 取得所有的记录
     *
     * @param sql string $query
     * @param array $params
     * @return array
     */
    public function fetchAll($query, $from_cache=false) {
        global $_G;

        if ($_G['debug']) {
            $_G['debug_info']['sql'][] = $query;
        }

        $stmt = $this->_dbh->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * 获取记录的第一行第一列
     *
     * @param string sql $query
     * @param array $params
     */
    public function fetchOne($query, $params = array()) {
        global $_G;

        if ($_G['debug']) {
            $_G['debug_info']['sql'][] = $query;
        }

        $stmt = $this->_dbh->prepare($query);
        $result = $stmt->execute($params);
        if ($result) {
            $row = $stmt->fetchColumn();
        }
        return $row ;
    }

    /**
     * 执行sql 语句
     *
     * @param sqlstring $query
     * @param array $params
     * @return 更新的记录的条数
     */

    public function exec($query, $params = array()) {
        global $_G;

        if ($_G['debug']) {
            $_G['debug_info']['sql'][] = $query;
        }

        $stmt = $this->_dbh->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * 获取最后一条记录的id
     */
    public function lastInsertId() {

        return $this->_dbh->lastInsertId();
    }

    /**
     * 关闭数据库连接
     * @param string $dsn
     */
    public function close($dsn = null) {

        if ($dsn) {
            self::$_conns[$dsn] = NULL;
        } else {
            $this->_dbh = NULL;
        }
    }


    public static function query($sql) {
        global $_G;

    	$_dbh = FDB::connect();
        return $_dbh->exec($sql);
    }

    public static function fetch($sql) {
        global $_G;

        $_dbh = FDB::connect();

        return $_dbh->fetchAll($sql, $from_cache=false);
    }

    public static function fetchCached($sql, $cache_time=3600) {
        $cache_key = "sql-fetch_{$sql}";
    	$cache_content = C::get($cache_key);
    	if ($cache_content) {
    		return $cache_content;
    	}

    	$cache_content = self::fetch($sql);
    	C::set($cache_key, $cache_content, $cache_time);
    	return $cache_content;
    }

    public static function fetchFirst($sql, $from_cache=false) {
        global $_G;

        $_dbh = FDB::connect();

        return $_dbh->fetchRow($sql);
    }

    public static function fetchFirstCached($sql, $cache_time=3600) {
        $cache_key = "sql-fetchFirst_{$sql}";
        $cache_content = C::get($cache_key);
        if ($cache_content) {
            return $cache_content;
        }

        $cache_content = self::fetchFirst($sql);
        C::set($cache_key, $cache_content, $cache_time);
        return $cache_content;
    }

    public static function insert($table, $data) {
        if (!$data['create_time']) {
            $data['create_time'] = date('Y-m-d H:i:s');
        }
        if (!$data['status']) {
            $data['status'] = 1;
        }
        $table = new DB_Table($table);
        $table->save($data);
        return $table->lastInsertId();
    }

    public static function update($table, $data, $condition) {
        global $_G;

        if (!$data['update_time'] && $_G['upate_from'] != 'gather') {
            $data['update_time'] = date('Y-m-d H:i:s');
        }

        $c = '';
        if (is_array($condition)) {
            foreach ($condition as $_k => $_v) {
                $c .= " and {$_k}='{$_v}'";
            }

            $condition = ltrim($c, ' and');
        }

        $table = new DB_Table($table);
        $table->save($data, $condition);
        return true;
    }

    public static function remove($table, $condition) {

        $data = array(
            'status' => 2,
            'remove_time' => date('Y-m-d H:i:s'),
        );

        $table = new DB_Table($table);
        $table->save($data, $condition);
        return true;
    }

    public static function incr($table, $field, $conditions = null, $unit = 1) {
        $table = new DB_Table($table);
        $table->incr($field, $conditions, array(), $unit);
    }
}
