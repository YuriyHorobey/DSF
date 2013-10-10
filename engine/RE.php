<?php

namespace engine;

use engine\utils\SU;
use engine\utils\AU;

/**
 * Class to handle response.
 *
 * @author Yuriy
 *        
 */
class RE {
	const TEXT = "_text";
	const PLACE = "_place";
	protected static $viewTree = array ();
	protected static $places = array ();
	static function invoke($method, $controllerObj, $argVal) {
		ob_start ();
		$method->invokeArgs ( $controllerObj, $argVal );
		
		$controllerContent = ob_get_clean ();
		$opt = $controllerObj->getViewOptions ();
		$redirectURL = AU::get ( $opt, 'redirect', false );
		if ($redirectURL !== false) {
			$redirectURL = trim ( $redirectURL );
			if ($redirectURL === '/' || SU::isBlank ( $redirectURL )) {
				$redirectURL = APP_URL;
			}
			header ( 'Location: ' . $redirectURL );
			return;
		}
		$haveMaster = false;
		if (\DSF::isRegularRequest ()) {
			// load master if any
			$master = AU::get ( $opt, "master", '_' );
			if ((! is_null ( $master )) && $master !== false) {
				if ($master === "_" || $master === true) {
					$master = 'main';
				} else {
					$master = SU::removeBeginning ( trim ( $master ), '/' );
				}
				ob_start ();
				\DSF::loadPHP ( 'app/views/layouts/' . $master );
				$masterContent = ob_get_clean ();
				self::addViewNode ( self::TEXT, $masterContent );
				$haveMaster = true;
			}
		}
		// load template if any (content can also go directly from controller by
		// 'echo')
		$template = AU::get ( $opt, "template", "_" );
		
		$template = trim ( $template );
		// here template can be
		// 1: not set or "_", or boolean true == use default
		// 2: set, but null or boolean false == don't use template at all
		// 3: path to the template file with or without leading slash
		// 3.1 with (/my/Template): will be converted to
		// /app/views/my/Template.php
		// 3.2 without (Template): will be converted to /app/views/{controller's
		// namespace without app/controllers}/Template.php
		if ($template != null && $template != false) {
			if ($template [0] == '/') {
				$idx = strrpos ( $template, '/' );
				$path = substr ( $template, 0, $idx );
				$template = substr ( trim ( $template ), $idx + 1 );
			} else {
				// use default template: ControllerClass/Method
				
				$controllerClass = get_class ( $controllerObj );
				// -10 Remove 'Controller' from name
				$controllerClass = substr ( $controllerClass, 0, - 10 );
				$path = str_replace ( 'app\\controllers\\', 'app\\views\\', $controllerClass );
			}
			if ($template === "_" || $template === true) {
				
				$methodPathPart = $method->getName ();
				// remove 'do' from name
				$template = substr ( $methodPathPart, 2 );
			}
			
			$template = $path . "/" . $template;
			ob_start ();
			$res = \DSF::loadPHP ( $template );
			if ($res === false) {
				throw new \E500 ( 'Template: "' . $template . '" is not found.' );
			}
			$templateContent = ob_get_clean ();
			// get everything outputed by controller and tempalte which never
			// was placed into viewTree
			$controllerContent .= $templateContent;
		}
		if ($haveMaster && isset ( self::$viewTree ['content'] )) {
			// try to put content into "content node"
			self::$viewTree ['content'] .= $controllerContent;
			foreach ( self::$viewTree as $node ) {
				echo $node;
			}
		} else {
			echo $controllerContent;
		}
		
		// dbg ( self::$viewTree );
	}
	static function addViewNode($type, $contentOrPlaceName) {
		static $txtNodeCounter = 0;
		
		$type = strtolower ( trim ( $type ) );
		if ($type === self::TEXT) {
			$txtNodeCounter ++;
			$type .= '_' . $txtNodeCounter;
			self::$viewTree [$type] = $contentOrPlaceName;
			return;
		}
		if ($type == self::PLACE) {
			$contentOrPlaceName = strtolower ( trim ( $contentOrPlaceName ) );
			if ($contentOrPlaceName !== 'content') {
				$placeCnt = AU::get ( self::$places, $contentOrPlaceName, - 1 );
				$placeCnt ++;
				self::$places [$contentOrPlaceName] = $placeCnt;
				$contentOrPlaceName = '_' . $placeCnt . '_' . $contentOrPlaceName;
			}
			self::$viewTree [$contentOrPlaceName] = '';
		}
	}
	static function contentFor($name, $content) {
		$name = strtolower ( trim ( $name ) );
		$placeCnt = AU::get ( self::$places, $name, - 1 );
		
		for($placeIdx = 0; $placeIdx <= $placeCnt; $placeIdx ++) {
			self::$viewTree ['_' . $placeIdx . '_' . $name] = $content;
		}
	}
	static function renderError($code, $message = "") {
		$res = \DSF::loadPHP ( "/app/views/system/" . $code, true, array (
				'message' => $message 
		) );
		if (! $res) {
			echo 'Error ' . $code;
			if ($message) {
				echo '<br>message: ' . $message;
			}
		}
	}
}

?>