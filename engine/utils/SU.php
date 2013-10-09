<?php

namespace engine\utils;

/**
 * String Utility class.
 *
 * Implemets some nice functionality about strings.
 *
 * @author Yuriy
 *        
 */
class SU {
	/**
	 * Regulrar expression to detect "variables" in the strings.
	 *
	 * Used by {@link SU::tpl() tpl()} method to detect the "hot spots" in
	 * strings and replace them with actual data.
	 *
	 * @var string
	 */
	const VAR_PATTERN = "/{{\\s*(?P<var>[^|]+)\\s*((\\|)(?P<def>.*)\\s*)?}}/msiuU";
	/**
	 * Convert given string to CamelCase.
	 *
	 * Splits a string by arbitrary number of "_" and converts first letter to
	 * upper case; then concatenates the parts.<br>
	 * So <code>my___undersored</code> becomes <code>MyUnderscored</code>
	 *
	 * @param string $in        	
	 * @return string
	 */
	static function strtocammel($in) {
		$ret = "";
		$in = preg_split ( "/_+/", $in );
		foreach ( $in as $i ) {
			if (strlen ( $i ) > 0) {
				$i [0] = strtoupper ( $i [0] );
				$ret .= $i;
			}
		}
		return $ret;
	}
	/**
	 * Convert CamelCase to under_score.
	 *
	 * Detects lowercase-Uppercase sequences (like aA) and puts "_" inbetween.
	 * <br>
	 * So ABCdE becomes abcd_e not a_b_c_d_e, aBC becomes a_bc not a_b_c
	 *
	 * @param string $in        	
	 * @return string
	 */
	static function underscore($in) {
		$ret = strtolower ( preg_replace ( '/([a-z])([A-Z])/', '$1_$2', $in ) );
		return $ret;
	}
	/**
	 * Convert first character to uppercase
	 *
	 * @param string $in        	
	 * @return string
	 */
	static function capitalize($in) {
		if (self::isBlank ( $in )) {
			return $in;
		}
		
		$ret = strtoupper ( substr ( $in, 0, 1 ) );
		if (strlen ( $in ) > 1) {
			$ret .= substr ( $in, 1 );
		}
		return $ret;
	}
	/**
	 * Checks if given string is <code>null</code> or contains whitespace only.
	 *
	 * First calls {@link SU::isEmpty() isEmpty()} then checks if all the string
	 * matches \\s*
	 *
	 * @param string $in        	
	 * @return boolean true if there is no single visible character in the
	 *         string
	 * @see isEmpty()
	 */
	static function isBlank($in) {
		if (! is_scalar ( $in )) {
			// TODO rethink this and isEmpty too -- array gives troubles
			dbg ( $in, "IN" );
			$e = new \Exception ( 'IN is not String' );
			dbg ( $e->getTrace (), 'EX' );
		}
		return self::isEmpty ( $in ) || preg_match ( '/^\s*$/', $in );
	}
	
	/**
	 * Checks if string is <code>null</code> or "".
	 *
	 * If string has at least one arbitrary character, including whitespace this
	 * method returns false.
	 *
	 * @param string $in        	
	 * @return boolean
	 * @see isBlank()
	 */
	static function isEmpty($in) {
		return is_null ( $in ) || $in === '';
	}
	
	/**
	 * Makes sure that input string starts with given character sequence.
	 *
	 * If input string <code>$in</code> does not begin with
	 * <code>$beginning</code> it will be prepended to the string.<br>
	 * There is no partial check.<br>
	 * So if your string is "ixSomething" and you invoke this method as
	 * <code>SU::ensureBeginning("ixSomething", "prefix")</code>
	 * <br>You will not get "prefixSomething" but
	 * "<code>prefix<b>ix</b>Something</code>"
	 * <br>
	 * Method is safe against <code>null</code>-s or empty strings.
	 *
	 * @param string $in        	
	 * @param string $beginning        	
	 * @param boolean $case_insensitivity        	
	 * @return string
	 * @see isEmpty()
	 * @see ensureBeginning()
	 */
	static function ensureBeginning($in, $beginning, $case_insensitivity = false) {
		if (self::isEmpty ( $beginning )) {
			return $in;
		}
		if (self::isEmpty ( $in )) {
			return $beginning;
		}
		$bcnt = strlen ( $beginning );
		if (strlen ( $in ) < $bcnt) {
			return $beginning . $in;
		}
		
		if (substr_compare ( $in, $beginning, 0, $bcnt, $case_insensitivity ) != 0) {
			$in = $beginning . $in;
		}
		return $in;
	}
	
	/**
	 * Removes <code>$beginning</code> from <code>$in</code> if it is present.
	 *
	 * If your string beginns with <code>$beginning</code> it will be
	 * removed.<br>
	 * Just like {@link SU::ensureBeginning() ensureBeginning()} -- no partial
	 * checks.<br>
	 * The <code>$beginning</code> will be removed only if it is present in
	 * full.<br>
	 * Method is safe agains <code>null</code> or empty strings.
	 *
	 * @param string $in        	
	 * @param string $beginning        	
	 * @param boolean $case_insensitivity        	
	 * @return string
	 * @see isEmpty()
	 * @see ensureBeginning()
	 */
	static function removeBeginning($in, $beginning, $case_insensitivity = false) {
		if (self::isEmpty ( $beginning ) || self::isEmpty ( $in )) {
			return $in;
		}
		$ret = self::ensureBeginning ( $in, $beginning, $case_insensitivity );
		$ret = substr ( $ret, strlen ( $beginning ) );
		return $ret;
	}
	/**
	 * Makes sure that <code>$in</code> ends with <code>$ending</code>
	 *
	 * Very same rules as {@link SU::ensureBeginning() ensureBeginning()}
	 * applied to the end of the string <code>$in</code>
	 *
	 * @param string $in        	
	 * @param string $ending        	
	 * @param boolean $case_insensitivity        	
	 * @return string
	 */
	static function ensureEnding($in, $ending, $case_insensitivity = false) {
		if (self::isEmpty ( $ending )) {
			return $in;
		}
		if (self::isEmpty ( $in )) {
			return $ending;
		}
		$ecnt = strlen ( $ending );
		if (strlen ( $in ) < $ecnt) {
			return $in . $ending;
		}
		
		if (substr_compare ( $in, $ending, - $ecnt, $ecnt, $case_insensitivity ) != 0) {
			$in .= $ending;
		}
		return $in;
	}
	
	/**
	 * Fills "hotspots" from <code>$tpl</code> with values from
	 * <code>$data</code>.
	 *
	 * <p>
	 * Consider this method to be micro templating engine.<br>
	 * It takes a string from <code>$tpl</code> where are special "hotspots" (by
	 * default {{<b>key</b> in <code>$data</code>|<b>default value</b>}}),
	 * detects them and tries to replace with actual values from <code>$data</code> array.</p>
	 * <p>
	 * So the procedure is:
	 * <ol>
	 * <li>Search <code>$tpl</code> for a hotspot, get key and optional default value</li>
	 * <li>if <code>$data</code> contains the key -- replace the hotspot with <code>$data[key]</code></li>
	 * <li>else if defaule value is set with the hotspot -- use it</li>
	 * <li>else -- hotspot is replaced with empty string</li>
	 * </ol>
	 *
	 *
	 * @param string $tpl        	
	 * @param array $data        	
	 * @return string
	 * @see SU::VAR_PATTERN
	 * @todo implement nested arrays maybe with {@link AU}
	 */
	public static function tpl($tpl, $data = array()) {
		if (self::isBlank ( $tpl )) {
			return "";
		}
		$matches = array ();
		if (preg_match_all ( self::VAR_PATTERN, $tpl, $matches )) {
			
			// dbg($matches,"found");
			for($i = 0; $i < count ( $matches ["var"] ); $i ++) {
				$var_name = $matches ["var"] [$i];
				$def_value = isset ( $matches ["def"] [$i] ) ? $matches ["def"] [$i] : "";
				$val = isset ( $data [$var_name] ) ? $data [$var_name] : $def_value;
				$tpl = str_replace ( $matches [0] [$i], $val, $tpl );
			}
		}
		return $tpl;
	}
}

?>