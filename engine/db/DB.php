<?php

namespace engine\db;

use engine\utils\SU;
use engine\CFG;

class DB {
	/**
	 *
	 * @var \PDO $dbh
	 */
	protected static $dbh;
	protected static $errorInfo = array ();
	protected static $preparedStatements = array ();
	static function connect() {
		if (is_null ( self::$dbh )) {
			$dsn = CFG::get ( 'db/dsn' );
			$username = CFG::get ( 'db/user' );
			$passwd = CFG::get ( 'db/pass' );
			$options = CFG::get ( 'db/options' );
			try {
				self::$dbh = new \PDO ( $dsn, $username, $passwd, $options );
			} catch ( \PDOException $e ) {
				$message = 'Unable to connect to the database:<br>';
				$message .= $e->getMessage () . '<br>';
				throw new \E500 ( $message );
			}
		}
	}
	static function close() {
		self::$dbh = null;
	}
	static function exec($sql, $params = array()) {
		self::connect ();
		self::$errorInfo = array ();
		$key = strtolower ( trim ( $sql ) );
		if (array_key_exists ( $key, self::$preparedStatements )) {
			$stmt = self::$preparedStatements [$key];
		} else {
			$stmt = self::$dbh->prepare ( $sql );
			self::$preparedStatements [$key] = $stmt;
		}
		$bind_status = true;
		foreach ( $params as $parameter => $value ) {
			$parameter = SU::ensureBeginning ( trim ( $parameter ), ":" );
			$type = self::PHPTypeToPDOType ( $value );
			// if (is_null ( $value )) {
			// $bind_status = $bind_status && $stmt->bindValue ( $parameter, null, \PDO::PARAM_NULL );
			// } else {
			
			// $bind_status = $bind_status && $stmt->bindValue ( $parameter, $value );
			// }
			$bind_status = $bind_status && $stmt->bindValue ( $parameter, $value, $type );
			if (! $bind_status) {
				throw new \E500 ( "Unable to prepare query. Parameter '$parameter' can not be bound. The query is: '$sql'" );
			}
		}
		$exec_res = $stmt->execute ();
		if (! $exec_res) {
			self::$errorInfo = $stmt->errorInfo ();
			return false;
		}
		$ret = $stmt->fetchAll ( \PDO::FETCH_ASSOC );
		return $ret;
	}
	public static function PHPTypeToPDOType($var) {
		$type = gettype ( $var );
		
		switch ($type) {
			case "boolean" :
				$ret = \PDO::PARAM_BOOL;
				break;
			case "double" :
			case "integer" :
				$ret = \PDO::PARAM_INT;
				break;
			
			case "string" :
				$ret = \PDO::PARAM_STR;
				break;
			
			case "NULL" :
				$ret = \PDO::PARAM_NULL;
				break;
			
			default :
				break;
		}
	}
	public static function getErrorInfo() {
		return self::$errorInfo;
	}
}

?>