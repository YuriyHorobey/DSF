<?php

namespace engine;

use engine\utils\SU;

class VH {
	static function baseTag() {
		echo '<base href="';
		VH::url ( "" );
		echo '" />';
	}
	static function place($name) {
		$currentText = ob_get_clean ();
		RE::addViewNode ( RE::TEXT, $currentText );
		RE::addViewNode ( RE::PLACE, $name );
		ob_start ();
	}
	static function contentFor($name, $content) {
	}
	static function url($url) {
		$url = trim ( $url );
		$url = SU::ensureBeginning ( $url, '/' );
		$url = substr ( $url, 1 );
		echo APP_URL . $url;
	}
	static function urlModule($url) {
		$url = trim ( $url );
		$url = SU::ensureBeginning ( $url, '/' );
		echo APP_URL . 'module' . $url;
	}
	static function linkJS($url) {
		if (func_num_args () > 1) {
			$args = func_get_args ();
		} else {
			$args = $url;
		}
		if (is_array ( $args )) {
			foreach ( $args as $u ) {
				self::linkJS ( $u );
			}
		} else {
			$args = SU::ensureBeginning ( $args, APP_URL . 'public/js/' );
			$args = SU::ensureEnding ( $args, '.js' );
			echo '<script type="text/javascript" src="' . $args . '"></script>' . "\n";
		}
	}
	static function linkCSS($url) {
		if (func_num_args () > 1) {
			$args = func_get_args ();
		} else {
			$args = $url;
		}
		if (is_array ( $args )) {
			foreach ( $args as $u ) {
				self::linkJS ( $u );
			}
		} else {
			$args = SU::ensureBeginning ( $args, APP_URL . 'public/css/' );
			$args = SU::ensureEnding ( $args, '.css' );
			echo '<link type="text/css" media="all" rel="stylesheet" href="' . $args . '">' . "\n";
		}
	}
}

?>