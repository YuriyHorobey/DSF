<?php

namespace engine;

use engine\utils\AU;

/**
 * Class to access configuration files.
 *
 * @author Yuriy
 *
 */
class CFG {
	/**
	 * Stores all configuration settings.
	 *
	 * @var array
	 */
	protected static $cfg = array ();
	/**
	 * Load config file specified by <code>$path</code>.<br>
	 * If {@link CFG::$cfg $cfg field} already contained something, values from
	 * the new config array will replace the existing ones (think
	 * array_replace_recursive()).
	 *
	 * @param string $path
	 * @return boolean
	 */
	static function load($path) {
		if (file_exists ( $path )) {
			$c = require_once $path;
			if (is_array ( $c )) {
				self::$cfg = array_replace_recursive ( self::$cfg, $c );
				return true;
			}
		}
		return false;
	}
	/**
	 * Returns value from accumulated config settings.
	 *
	 * @param string $path
	 * @param mied $def
	 *        	default value
	 * @return mixed either value under <code>$path</code> or <code>$def</code>
	 * @see AU
	 */
	static function get($path, $def = null) {
		$ret = AU::get ( self::$cfg, $path, $def );
		return $ret;
	}
	/**
	 * Sets value into configs.
	 *
	 * @param string $path
	 *        	in the config array
	 * @param mixed $value
	 *        	to be set
	 * @see AU
	 */
	static function set($path, $value) {
		AU::set ( self::$cfg, $path, $value );
	}
}

?>