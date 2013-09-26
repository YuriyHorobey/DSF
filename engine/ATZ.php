<?php

namespace engine;

use engine\utils\SU;
use engine\utils\AU;

define ( 'ALLOWED', true );
define ( 'PROHIBITED', false );
class ATZ {
	protected static $initialized = false;
	protected static $rules = array ();
	protected static $uid = 0;
	protected static $roles = array ();
	public static function init() {
		if (self::$initialized) {
			return;
		}
		\DF::loadPHP ( 'app/config/atz_rules' );
		self::$initialized = true;
	}
	public static function defaultAnswerIs($answer) {
		self::init ();
		self::$rules ['*'] = $answer;
	}
	public static function setUID($uid) {
		self::$uid = intval ( $uid );
	}
	public static function getUID() {
		return self::$uid;
	}
	public static function setRoles() {
		self::$roles = array ();
		$rolesAsArray = func_get_arg ( 0 );
		if (func_num_args () == 1 && is_array ( $rolesAsArray )) {
			self::$roles = $rolesAsArray;
		} else {
			self::$roles = func_get_args ();
		}
	}
	public static function getRoles() {
		return self::$roles;
	}
	public static function getDefaultAnswer($answer) {
		self::init ();
		return AU::get ( self::$rules, '*', NO );
	}
	public static function allow($role, $action) {
		self::rule ( $role, $action, ALLOWED );
	}
	public static function prohibit($role, $action) {
		self::rule ( $role, $action, PROHIBITED );
	}
	public static function actionToPath($action) {
		$path = str_replace ( '\\', '/', strtolower ( trim ( $action ) ) );
		$path = str_replace ( '::', '/', $path );
		$path = SU::ensureBeginning ( $path, '/' );
		return $path;
	}
	public static function rule($role, $action, $answer) {
		self::init ();
		$path = strtolower ( trim ( $role ) ) . self::actionToPath ( $action );
		if (is_callable ( $answer )) {
			
			$answer = array (
					$answer 
			);
			if (func_num_args () > 3) {
				$answer [] = array_slice ( func_get_args (), 3 );
			}
		}
		AU::set ( self::$rules, $path, $answer );
	}
	public static function clear() {
		self::$rules = array ();
		self::defaultAnswerIs ( PROHIBITED );
	}
	public static function importRules(array $new_rules) {
		self::$rules = array_replace_recursive ( self::$rules, $new_rules );
	}
	public static function isAllowed($action) {
		self::init ();
		$path = self::actionToPath ( $action );
		// check with user id
		$answer = AU::get ( self::$rules, self::$uid . $path, null );
		if (! is_null ( $answer )) {
			$answer = self::answerAsBoolean ( $answer );
			return $answer;
		}
		// check by roles
		foreach ( self::$roles as $role ) {
			$answer = AU::get ( self::$rules, $role . $path, null );
			if (! is_null ( $answer )) {
				$answer = self::answerAsBoolean ( $answer );
				return $answer;
			}
		}
		// no specific role, check with *
		foreach ( self::$roles as $role ) {
			$role_path = $role . $path;
			$idx = true;
			while ( $idx !== false ) {
				$idx = strrpos ( $role_path, '/' );
				$role_path = substr ( $role_path, 0, $idx );
				dbg ( $role_path, 'rp' );
				$answer = AU::get ( self::$rules, $role_path . '/*', null );
				if (! is_null ( $answer )) {
					$answer = self::answerAsBoolean ( $answer );
					return $answer;
				}
			}
		}
		// return default answer
		$answer = AU::get ( self::$rules, '*', PROHIBITED );
		$answer = self::answerAsBoolean ( $answer );
		return $answer;
	}
	protected static function answerAsBoolean($answer) {
		if (! is_array ( $answer )) {
			return ( bool ) $answer;
		}
		$args = AU::get ( $answer, 1, array () );
		$ret = call_user_func_array ( $answer [0], $args );
		return $ret;
	}
	public static function getRules() {
		return self::$rules;
	}
}

?>