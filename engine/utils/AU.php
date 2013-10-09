<?php

namespace engine\utils;

/**
 * Utility class to handle arrays.
 *
 * @author Yuriy
 *        
 */
class AU {
	/**
	 * Checks if <code>$arr</code> is an array or creates new array; optionally fills it with elements.
	 * <p>
	 * First of all: if <code>$arr</code> is not an array (is null or arbitrary other data type) -- it will be converted to the array.</p>
	 * <p>
	 * The argument <code>$arr</code> can only be a variable (because passed by reference)</p>
	 * <p>
	 * All arguments after <code>$arr</code> are considered to be new elements and will be appended to the array.</p>
	 *
	 * @param
	 *        	mixed &$arr
	 * @param mixed $args
	 *        	elements to be appended to the return array
	 * @return void
	 */
	static function ensure(&$arr) {
		$arr = is_null ( $arr ) || ! is_array ( $arr ) ? array () : $arr;
		for($i = 1; $i < func_num_args (); $i ++) {
			$arr [] = func_get_arg ( $i );
		}
	}
	
	/**
	 * Checks if <code>$arr</code> is array and has at least one element.
	 *
	 * Method is safe agains arbitrary input: <code>null</code>-s, variables of arbitrary types -- all is allowed.<br>
	 * Returns false only in case when <code>$arr</code> is an array and has at least one element.
	 *
	 * @param array $arr        	
	 * @return boolean
	 */
	static function isEmpty(array &$arr) {
		return is_null ( $arr ) || ! is_array ( $arr ) || count ( $arr ) == 0;
	}
	/**
	 * Walks the <code>$arr</code> and applies {@link SU::isBlank} to each element.
	 *
	 * First of all check if <code>$arr</code> is not {@link isEmpty() empty} (if it is -- instantly returns true), then walks the array and checks if elements are not blanks.<br>
	 * Returns false as soon as a first element is either object or not {@link SU::isBlank a blank string}.<br>
	 * Since {@link SU::isBlank} is used to check, every element but Object will be casted to string.
	 *
	 * @param array $arr        	
	 * @return boolean
	 */
	static function isBlank(array &$arr) {
		if (self::isEmpty ( $arr )) {
			return true;
		}
		foreach ( $arr as $e ) {
			if (! is_null ( $e ) || ! SU::isBlank ( $e )) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Sets value to arbitrary nested array.
	 * <p>
	 * Takes <code>$data</code> array,
	 * creates (if needed) as many nested arrays as defined in <code>$path</code> then sets <code>$value</code> to the last part of the <code>path</code>.
	 * </p>
	 * <p>In details:<br>
	 * you have an array:
	 * <pre>
	 * $a = array (
	 * 'key1' => array (
	 * 'key11' => 111
	 * )
	 * );
	 * </pre>
	 * and you want to have
	 * <pre>
	 * $a['key1']['key11']['key111']='something';
	 * //and
	 * $a['key2']['key11']=211;
	 * </pre>
	 * There are two concerns:
	 * <ol>
	 * <li>Rare one: something on the path <code>['key1']['key11']['key111']</code> is already set and is not an array.<br>
	 * You don't care of this and want it to be overriden, but PHP will complain at this point (in our case on 'key11'):<br>
	 * <b>Warning</b>: Cannot use a scalar value as an array in ...<br>
	 * </li>
	 * <li>Often you don't know the path <code>['key1']['key11']['key111']</code> at the design time.<br>
	 * For example you are reading from XML file and convertig it to the nested associative array.<br>
	 * Then using standard PHP approach you will end up using <code>eval()</code> which is extremely bad practice.
	 * </li>
	 * </ol>
	 * Use <code>AU::set('key1/key11/key111', 'something');</code> and be happy.<br>
	 * The parts of the path are separated with '/' and each part is <a href="http://php.net/trim" target="_blank">trim()</a>-med.
	 * So there is no difference between 'key1/key11/key111' and 'key1 / key11 / key111'
	 *
	 * @param array $data        	
	 * @param string $path        	
	 * @param mixed $value        	
	 */
	static function set(array &$data, $path, $value) {
		$parts = explode ( "/", $path );
		$node = &$data;
		foreach ( $parts as $part ) {
			$part = trim ( $part );
			// TODO check for isset vs array_key_exists: see 'get'
			if (! (isset ( $node [$part] ) && is_array ( $node [$part] ))) {
				
				$node [$part] = array ();
			}
			$node = &$node [$part];
		}
		$node = $value;
	}
	
	/**
	 * Searches arbitrary nested array for <code>$path</code> and either returns value or <code>$def</code>.
	 *
	 * Suppose you have an array:
	 * <pre>
	 * $a = array (
	 * 'key1' => array (
	 * 'key11' => 111
	 * )
	 * );
	 * </pre>
	 * For example that is a config file.<br>
	 * Now you want to:
	 * <pre>
	 * $val = $a['a']['b']['c'];
	 * </pre>
	 * Using standard PHP approach you will end up with something like this:
	 * <pre>
	 * if(isset($a['a']['b']['c'])){
	 * $val= $a['a']['b']['c'];
	 * }else{
	 * $val='default value';
	 * }
	 *
	 * echo $val;
	 * </pre>
	 * As to me -- too much typing.
	 * Use:
	 * <pre>
	 * $val = AU::get('a/b/c', 'default value');
	 * echo $val;
	 * </pre>
	 * This method perfectly works with arbitrary input: <code>null</code>-s etc -- it is safe.
	 *
	 * @param array $data        	
	 * @param unknown_type $path        	
	 * @param unknown_type $def        	
	 * @return Ambigous <>|unknown|multitype:
	 */
	static function get(array &$data, $path, $def = null) {
		if (self::isEmpty ( $data )) {
			return $def;
		}
		$parts = explode ( "/", $path );
		$node = &$data;
		foreach ( $parts as $part ) {
			$part = trim ( $part );
			if (array_key_exists ( $part, $node )) {
				if (is_array ( $node [$part] )) {
					
					$node = &$node [$part];
				} else {
					return $node [$part];
				}
			} else {
				return $def;
			}
		}
		return $node;
	}
	static function isIndexed(&$arr) {
		return (is_array ( $arr ) && is_numeric ( implode ( "", array_keys ( $arr ) ) ));
	}
	static function isAssoc(&$arr) {
		return (is_array ( $arr ) && ! is_numeric ( implode ( "", array_keys ( $arr ) ) ));
	}
	static function extractIndexed(&$arr) {
		$ret = array ();
		if (self::isBlank ( $arr )) {
			return array ();
		}
		foreach ( $arr as $key => $val ) {
			if (is_numeric ( $key )) {
				$ret [] = $val;
			}
		}
		return $ret;
	}
	static function extractUnique(&$arr, $sort_flags = SORT_STRING) {
		$ret = array_unique ( $arr, $sort_flags );
		$ret = array_values ( $ret );
		return $ret;
	}
}

?>