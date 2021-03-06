<?php
if (!defined('SYSTEM_ROOT')) { die('Insufficient Permissions'); } 

/**
 * cron 计划任务操作类
 */
class cron Extends option {
	/**
	 * 获取计划任务所有数据
	 * $name 计划任务名称
	 * @return array
	*/
	public static function get($name) {
		global $i;
		if (isset($i['cron'][$name])) {
			return $i['cron'][$name];
		}
	}

	/**
	 * 获取计划任务指定数据
	 * @param $name 计划任务名称
	 * @param $set  设置项名
	 */
	public static function sget($name,$set) {
		global $i;
		if (isset($i['cron'][$name][$set])) {
			return $i['cron'][$name][$set];
		}
	}

	/**
	 * 通过数组改变或添加计划任务 (不存在时自动添加)
	 * @param $name string 全局唯一计划任务名称
	 * @param $set  array 设置项
	 */
	public static function aset($name, $set) {
		global $m;

		$set = adds($set);

		$sql = "INSERT INTO  `".DB_PREFIX."cron` (`name`";
		$a = '';
		$b = "'{$name}'";
		$c = "`name` = '{$name}'";

		if (isset($set['file'])) {
			$a .= ', `file`';
			$b .= ", '{$set['file']}'";
			$c .= ", `file` = '{$set['file']}'";
		}
		if (isset($set['no'])) {
			$a .= ', `no`';
			$b .= ", '{$set['no']}'";
			$c .= ", `no` = '{$set['no']}'";
		}
		if (isset($set['status'])) {
			$a .= ', `status`';
			$b .= ", '{$set['status']}'";
			$c .= ", `status` = '{$set['status']}'";
		}
		if (isset($set['freq'])) {
			$a .= ', `freq`';
			$b .= ", '{$set['freq']}'";
			$c .= ", `freq` = '{$set['freq']}'";
		}
		if (isset($set['lastdo'])) {
			$a .= ', `lastdo`';
			$b .= ", '{$set['lastdo']}'";
			$c .= ", `lastdo` = '{$set['lastdo']}'";
		}
		if (isset($set['orde'])) {
			$a .= ', `orde`';
			$b .= ", '{$set['orde']}'";
			$c .= ", `orde` = '{$set['orde']}'";
		}
		if (isset($set['log'])) {
			$a .= ', `log`';
			$b .= ", '{$set['log']}'";
			$c .= ", `log` = '{$set['log']}'";
		}

		$sql .= $a . ' ) VALUES (' . $b . ') ON DUPLICATE KEY UPDATE '. $c . ';';
		
		$m->query($sql);

	}

	/**
	 * 改变或添加计划任务 (不存在时自动添加)
	 * WARNING:请使用更先进的 aset() 代替他
	 * $name 全局唯一计划任务名称
	 * $file 计划任务文件，执行时以include方式执行function，function名称为cron_计划任务名称
	 * $no 忽略任务
	 * $status 计划任务状态，系统会写入
	 * $freq 执行频率
	 *       -1：一次性任务，执行完毕后系统会删除
	 *       0 ：默认，当do.php被执行时，该任务始终被运行
	 *       其他正整数：运行时间间隔，单位秒（$lastdo - $freq）
	 * $lastdo 上次执行，系统会写入
	 * $log 执行日志，系统会写入
	*/
	public static function set($name, $file = '', $no = 0, $status = 0, $freq = 0, $lastdo = '', $log = '') {
		global $m;
		$set = array();

		if (!empty($file)) {
			$set['file'] = $file;
		}
		if (!empty($no)) {
			$set['no'] = $no;
		}
		if (!empty($status)) {
			$set['status'] = $status;
		}
		if (!empty($freq)) {
			$set['freq'] = $freq;
		}
		if (!empty($lastdo)) {
			$set['lastdo'] = $lastdo;
		}
		if (!empty($log)) {
			$set['log'] = $log;
		}

		self::aset($name , $set);
	}

	/**
	 * 直接添加一个计划任务
	 * @param $name 计划任务名
	 * @param $set  任务设置
	 */
	public static function add($name , $set) {
		global $m;

		$set = adds($set);

		$sql = "INSERT IGNORE INTO  `".DB_PREFIX."cron` (`name`";
		$a = '';
		$b = "'{$name}'";

		if (isset($set['file'])) {
			$a .= ', `file`';
			$b .= ", '{$set['file']}'";
		}
		if (isset($set['no'])) {
			$a .= ', `no`';
			$b .= ", '{$set['no']}'";
		}
		if (isset($set['status'])) {
			$a .= ', `status`';
			$b .= ", '{$set['status']}'";
		}
		if (isset($set['freq'])) {
			$a .= ', `freq`';
			$b .= ", '{$set['freq']}'";
		}
		if (isset($set['lastdo'])) {
			$a .= ', `lastdo`';
			$b .= ", '{$set['lastdo']}'";
		}
		if (isset($set['log'])) {
			$a .= ', `log`';
			$b .= ", '{$set['log']}'";
		}

		$sql .= $a . ' ) VALUES (' . $b . ')';
		$m->query($sql);
	}

	/**
	 * 删除计划任务
	 */
	public static function del($name) {
		global $m;
		$m->query("DELETE FROM `".DB_NAME."`.`".DB_PREFIX."cron` WHERE `name` = '{$name}'");
	}

	/**
	 * 执行一个计划任务
	 * 
	 * @param 计划任务文件
	 * @param 计划任务名称
	 * @return 执行成功true，否则false
	 */

	public static function run($file,$name) {
		$GLOBALS['in_cron'] = true;
		if (file_exists(SYSTEM_ROOT.'/'.$file)) {
			include_once SYSTEM_ROOT.'/'.$file;
			if (function_exists('cron_'.$name)) {
				return call_user_func('cron_'.$name);
			}
		}
	}

	/**
	 * 按运行顺序运行所有计划任务
	 *
	 */
	public static function runall() {
		global $m;
		$time = time();
		$cron = $m->query("SELECT *  FROM `".DB_NAME."`.`".DB_PREFIX."cron` ORDER BY  `orde` ASC ");
		while ($cs = $m->fetch_array($cron)) {
			if ($cs['no'] != '1') {
				if ($cs['freq'] == '-1') {
					self::run($cs['file'],$cs['name']);
					$m->query("DELETE FROM `".DB_NAME."`.`".DB_PREFIX."cron` WHERE `".DB_PREFIX."cron`.`id` = ".$cs['id']);
				}
				elseif ( empty($cs['freq']) || empty($cs['lastdo']) || $cs['lastdo'] - $cs['freq'] >= $cs['freq'] ) {
					$return=self::run($cs['file'],$cs['name']);
					$m->query("UPDATE `".DB_NAME."`.`".DB_PREFIX."cron` SET `lastdo` =  '{$time}',`log` = '{$return}' WHERE `".DB_PREFIX."cron`.`id` = ".$cs['id']);
				}
			}
		}
	}
}
