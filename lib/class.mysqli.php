<?php
/**
 * StusGame Framework MYSQL数据操作方法封装类
 * [MySQLi]
 * @note 已针对贴吧云签到优化并删除了对不支持MySqli的主机的兼容措施
 * @copyright (c) 无名智者
 */

class wmysql {

	/**
	 * 查询次数
	 * @var int
	 */
	public $queryCount = 0;

	/**
	 * 内部数据连接对象
	 * @var mysqli
	 */
	private $conn;

	/**
	 * 内部数据结果
	 * @var mysqli_result
	 */
	private $result;

	/**
	 * 内部实例对象
	 * @var object MySql
	 */
	private static $instance = null;

	/**
	 * 构造函数
	 */
	public function __construct() {
		if (!class_exists('mysqli')) {
			msg('服务器不支持MySqli类');
		}
		if (stristr(DB_HOST, ':')) {
			preg_match('/(.*):(.*)/', DB_HOST, $coninfo);
			@$this->conn = new mysqli($coninfo[1], DB_USER, DB_PASSWD, DB_NAME, $coninfo[2]);
		} else {
			@$this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
		}

		if ($this->conn->connect_error) {
			switch ($this->conn->connect_errno) {
				case 1044:
				case 1045:
					msg("连接数据库失败，数据库用户名或密码错误");
					break;

                case 1049:
					msg("连接数据库失败，未找到您填写的数据库");
					break;

				case 2003:
					msg("连接数据库失败，数据库端口错误");
					break;

				case 2005:
					msg("连接数据库失败，数据库地址错误或者数据库服务器不可用");
					break;

				case 2006:
					msg("连接数据库失败，数据库服务器不可用");
					break;

				default :
					msg("连接数据库失败，请检查数据库信息。错误编号：" . $this->conn->connect_errno);
					break;
			}
		}

		$this->conn->set_charset('utf8');
		self::$instance = $this->conn;
		return self::$instance;
	}

	/**
	 * [弃用] 静态方法，返回数据库连接实例
	 */
	public static function con() {
		return self::$instance;
	}

	/**
	 * 关闭数据库连接
	 */
	public function close() {
		return $this->conn->close();
	}

	/**
	 * 发送查询语句
	 *
	 */
	public function query($sql,$noerror = false) {
		$this->result = $this->conn->query($sql);
		$this->queryCount++;
		if (!$this->result) {
			if ($noerror == true) {
				return false;
			} else {
				msg("警告：MySQL 语句执行错误：<br/><br/>语句：$sql<br/><br/>错误：" . $this->geterror());
			}	
		} else {
			return $this->result;
		}
	}

	/**
	 * 发送批量查询语句
	 * 记作一次查询，只返回最后的查询结果
	 */
	public function xquery($sql,$noerror = false) {
		$this->result = $this->conn->multi_query($sql);
		$this->queryCount++;
		if (!$this->result) {
			if ($noerror == true) {
				return false;
			} else {
				msg("警告：MySQL 语句执行错误：<br/><br/>语句：$sql<br/><br/>错误：" . $this->geterror());
			}	
		} else {
			return $this->result;
		}
	}
	// 别名
	public function multi_query($sql,$noerror = false) { 
		return $this->xquery($sql,$noerror = false);
	}

	/**
	 * 从结果集中取得一行作为关联数组/数字索引数组
	 *
	 */
	public function fetch_array(mysqli_result $query, $type = MYSQLI_ASSOC) {
		return $query->fetch_array($type);
	}

	public function once_fetch_array($sql) {
		$this->result = $this->query($sql);
		return $this->fetch_array($this->result);
	}

	/**
	 * 从结果集中取得一行作为数字索引数组
	 *
	 */
	public function fetch_row(mysqli_result $query) {
		return $query->fetch_row();
	}

	/**
	 * 取得行的数目
	 *
	 */
	public function num_rows(mysqli_result $query) {
		return $query->num_rows;
	}

	/**
	 * 取得结果集中字段的数目
	 */
	public function num_fields(mysqli_result $query) {
		return $query->field_count;
	}

	/**
	 * 取得上一步 INSERT 操作产生的 ID 自增值
	 */
	public function insert_id() {
		return $this->conn->insert_id;
	}

	/**
	 * 获取mysql错误
	 */
	public function geterror() {
		return '#' . $this->geterrno() . ' - ' . $this->conn->error;
	}

	/**
	 * 获取mysql错误编码
	 */
	public function geterrno() {
		return $this->conn->errno;
	}

	/**
	 * Get number of affected rows in previous MySQL operation
	 */
	public function affected_rows() {
		return $this->conn->affected_rows;
	}

	/**
	 * 取得数据库版本信息
	 */
	public function getMysqlVersion() {
		return $this->conn->server_info;
	}

	/**
	 * 取得数据库查询次数
	 */
	public function getQueryCount() {
		return $this->queryCount;
	}

    /**
	 *  Escapes special characters
	 */
	public function escape_string($sql) {
		return $this->conn->real_escape_string($sql);
	}
}
