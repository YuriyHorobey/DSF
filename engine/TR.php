<?php

namespace engine;

use engine\utils\SU;
use engine\utils\AU;

/**
 * Class to handle internatianalization.
 *
 * This class is capable to detect language requested, compare it to languages available in your application and offer the best fit.<br>
 * The class offers {@link TR::msg() message translations} too.
 * 
 * @author Yuriy
 *        
 */
class TR {
	
	/**
	 * Flag indicating if {@link TR::init()} was invoked.
	 * Allows to prevent multiple initialization calls.
	 * 
	 * @var boolean $initialized
	 */
	protected static $initialized = false;
	
	/**
	 * Current language code.
	 *
	 * This is initialized in {@link TR::init() init()} method and stores current language code as defined (<b>case sensitive</b>) in config file.
	 * See {@link TR::init() init()}
	 * 
	 * @var string $code
	 */
	protected static $code = 'default';
	
	/**
	 * Available language codes as described in the config file.
	 *
	 * See {@link TR::init() init()}
	 *
	 * @var array $available
	 */
	protected static $available = array ();
	
	/**
	 * Sorted array of user preferred language codes.
	 * The array contains codes extracted from HTTP_ACCEPT_LANGUAGE and is sorted according to quantifier described here: <a href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4" target="_blank">rfc2616</a>
	 * 
	 * @var array $preferred
	 */
	protected static $preferred = array ();
	
	/**
	 * The current dictionary.
	 *
	 * The dictionary file is stored at <code>/app/config/locales/&lt;code&gt;.php</code> and is used by {@link TR::msg() msg()} method.
	 *
	 * @var array $dict
	 */
	protected static $dict = array ();
	
	/**
	 * Initializes the class, detects required language and matches it with the available ones.
	 * <p>
	 * In order to localize (mainly translate) your application the following should be done:
	 * <ol>
	 * <li>Find which language codes user is asking for</li>
	 * <li>Compare to language codes we serve and find best match</li>
	 * <li>Remember it in session</li>
	 * <li>Load appropriate dictionary</li>
	 * </ol>
	 * The procedure is:
	 * <ol>
	 * <li>Find codes user is asking for:
	 * <ol>
	 * <li>Check if request has parameter '_lang'<br>
	 * Usefull if user has clicked language selection controll (like a button with country flag on it)<br>
	 * If yes -- skip to the next step
	 * </li>
	 * <li>If not -- check session for '_lang' key<br>
	 * --Maybe we already know the code?
	 * </li>
	 * <li>If not -- parse <code>HTTP_ACCEPT_LANGUAGE</code> header according to W3C standards (<a href="http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4" target="_blank">rfc2616</a>) </li>
	 * <li>If the user is using such a strange browser which is missing this header -- use code under "default" node in config file -- see below </li>
	 * </ol>
	 * </li>
	 * <li>Check what is available:
	 * <ol>
	 * <li>In the config file you must have the following:
	 * <pre>
	 * 'locales' => array (
	 * 'default' => 'en',
	 * 'available' => array (
	 * 'uk',
	 * 'en',
	 * 'en-US',
	 * )
	 * )
	 * </pre>
	 * </li>
	 * <li>Compare with the codes requested.<br>
	 * <b>The comparison is case insensitive.</b> If you have defined that "en-US" is available and user asked for "en-us" or "EN-us" -- we will answer "yes, we have it"<br>
	 * <b>BUT!</b> the language code returned to the application will be just exactly as you have specified in the config file: "en-US".<br>
	 * Since the code will be used to load dictionary ("en-US.php") and the code can be part of a {@link TR::getCodeAsPath() file path} -- remember that Linux and some other OS-es
	 * have case sensitive file system.
	 * </li>
	 * <li>If user has asked for "en-AU", but we have just "en" -- it will be used</li>
	 * <li>If user has asked for language we don't serve -- the value from "locales/default" config node will be used</li>
	 * <li>If you have missed the "default" -- the first one from "available" will be used</li>
	 * </ol>
	 * This class does not check if you actually have the dictionary, or if you have appropriate folders with localized Views, etc;
	 * -- if you stated in config file that "some-Code" is available, -- TR respects that, so
	 * -- first translate everything, then put it under "available"
	 * </li>
	 * <li>Attempt to load dictionary using {@link DSF::loadPHP()}.</li>
	 * <li>Store the language code (just as told above -- case sensitive, exactly as specified in the config) into the session</li>
	 * </ol>
	 * The initialization happens only once per request. Although this method is public -- you do not need to call it explicitly -- every method has
	 * <code>self::init()</code> at the first line, so this class is initialized only when needed. (haven't called {@link TR::msg() msg()}? --no need to parse the request nor to spent resources to load the dictionary)
	 * 
	 * @return void
	 */
	public static function init() {
		if (self::$initialized) {
			return;
		}
		$langs = array ();
		$unique_codes = array ();
		// get preferred lang codes array
		// the most preferred comes from request:
		$lang = AU::get ( $_REQUEST, '_lang', null );
		if (SU::isBlank ( $lang )) {
			$lang = AU::get ( $_SESSION, '_lang', null );
		}
		if (! SU::isBlank ( $lang )) {
			$unique_codes [strtolower ( trim ( $lang ) )] = 2;
		}
		
		if (isset ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'] )) {
			$lang_parse = array ();
			// break up string into pieces (languages and q factors)
			preg_match_all ( '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER ['HTTP_ACCEPT_LANGUAGE'], $lang_parse );
			
			if (count ( $lang_parse [1] )) {
				// create a list like 'en' => 0.8
				$langs = array_combine ( $lang_parse [1], $lang_parse [4] );
				// set default to 1 for any without q factor
				foreach ( $langs as $lang => $val ) {
					if ($val === '')
						$val = 1;
					$lcode = strtolower ( trim ( $lang ) );
					if (! isset ( $unique_codes [$lcode] ))
						$unique_codes [$lcode] = $val;
				}
				// sort list based on value
				arsort ( $unique_codes, SORT_NUMERIC );
			}
		}
		
		self::$preferred = array_keys ( $unique_codes );
		
		// get available languages
		$av_cfg = CFG::get ( 'locales/available', array () );
		// save available languages from config as trimmed values
		self::$available = array_map ( function ($el) {
			return trim ( $el );
		}, $av_cfg );
		// another array with available in lower case for search
		$av_lower = array_map ( function ($el) {
			return strtolower ( $el );
		}, self::$available );
		
		// check what can we serve according to visitor preferrences
		foreach ( self::$preferred as $pref ) {
			$idx = array_search ( $pref, $av_lower );
			if ($idx !== false) {
				self::$code = self::$available [$idx];
				break;
			} else {
				// if something like en-us not found
				// check if we have en
				$root_code = explode ( '-', $pref );
				$root_code = $root_code [0];
				$idx = array_search ( $root_code, $av_lower );
				if ($idx !== false) {
					self::$code = self::$available [$idx];
					break;
				}
			}
		}
		if (self::$code === 'default') {
			self::$code = CFG::get ( 'locales/default', reset ( $av_lower ) );
		}
		$_SESSION ['_lang'] = self::$code;
		// load the dictianary
		dbg ( '/app/config/locales' . self::getCodeAsPath (), 'LOADING DICT' );
		self::$dict = \DSF::loadPHP ( '/app/config/locales' . self::getCodeAsPath () );
		
		self::$initialized = true;
	}
	/**
	 * Returns current language code.
	 * 
	 * @return string
	 */
	public static function getCode() {
		self::init ();
		return self::$code;
	}
	/**
	 * Returns current language code to be used as part of file path.
	 *
	 * If {@link self::$code code} {@link SU::isBlank() is blank} returns empty string, otherwise prependes the directory separator to it.
	 * 
	 * @return string
	 */
	public static function getCodeAsPath() {
		if (SU::isBlank ( self::$code )) {
			return '';
		} else {
			return DIRECTORY_SEPARATOR . self::$code;
		}
	}
	/**
	 * Returns available language codes (if any) from config file.
	 *
	 * @return array language codes defined in config file
	 */
	public static function getAvailable() {
		self::init ();
		
		return self::$available;
	}
	/**
	 * Returns language code preferred by current user.
	 *
	 * What is preferred by the user is not what is actually in use.<br>
	 * This class tries its best to offer maximum compatible language with that prefered by the user;
	 * but if user asked for "fr" and we have no "fr", the default one will be used (see tutorials).<br>
	 *
	 * @return string
	 */
	public static function getPreferred() {
		self::init ();
		return self::$preferred;
	}
	/**
	 * Translate <code>$key</code> into curent language.
	 *
	 * In order to translate a message, this method is using dictionary files in the followinf format:
	 *
	 * <pre>
	 * &lt;?php
	 *
	 * return array (
	 * 'database' => array (
	 * 'not_connected' => 'Wrong login/password for the database',
	 * 'sql_error' => 'There is error in your sql {{{@link SU::tpl() msg}}}'
	 * ),
	 * 'some' => array (
	 * 'deep' => array (
	 * 'nested' => array (
	 * 'message' => 'Say something'
	 * )
	 * )
	 * )
	 * );
	 * ?&gt;
	 *
	 * </pre>
	 * The dictionary is located in <code>/app/config/locales/&lt;language code&gt;.php</code> file.
	 * (maybe in future I will add possibility to load arbitrary dictionaries, but is it really needed?)<br>
	 * Then in your code you can use it in this way:<br>
	 * <pre>
	 * echo TR::msg('database/not_connected');
	 * echo TR::msg('database/sql_error', array('msg'=>'Table blah blah does not exist'));
	 * echo TR::msg('some/deep/nested/message');
	 * </pre>
	 * If <code>$key</code> is not found in the current dictionary, it will be returned as is.<br>
	 * If the <code>$key</code> is found -- it will be passed through {@link SU::tpl()} with <code>$vars</code>
	 * 
	 * @param string $key
	 *        	path in the current dictionary
	 * @param array $vars
	 *        	optional data to be inserted into the translated string
	 * @return string translated string or <code>$key</code> if not found
	 * @see SU::tpl()
	 */
	public static function msg($key, $vars = array()) {
		self::init ();
		$tpl = AU::get ( self::$dict, $key, $key );
		$ret = SU::tpl ( $tpl, $vars );
		return $ret;
	}
}

?>