<?php

namespace app\controllers;

use engine\utils\AU;
use engine\VH;

class Base {
	protected $viewOptions = array (
			'master' => 'main',
			'template' => '_' 
	);
	protected function renderMaster($master) {
		AU::set ( $this->viewOptions, 'master', $master );
		$this->viewOptions ['redirect'] = null;
		return $this;
	}
	protected function renderTemplate($template) {
		AU::set ( $this->viewOptions, 'template', $template );
		$this->viewOptions ['redirect'] = null;
		return $this;
	}
	protected function renderingDone() {
		$this->renderMaster ( null );
		$this->renderTemplate ( null );
		return $this;
	}
	protected function redirect($url) {
		$this->renderingDone ();
		$this->viewOptions ['redirect'] = $url;
		return $this;
	}
	function getViewOptions() {
		return $this->viewOptions;
	}
	function get($path, $def = '') {
		$ret = VH::dget ( $path, $def );
	}
	function set($path, $value) {
		VH::dset ( $path, $value );
	}
}