<?php

namespace app\controllers;

use engine\utils\AU;

class Base {
	protected $viewOptions = array (
			"master" => "application",
			"template" => "_" 
	);
	protected function renderMaster($master) {
		AU::set ( $this->viewOptions, "master", $master );
		return $this;
	}
	protected function renderTemplate($template) {
		AU::set ( $this->viewOptions, "template", $template );
		return $this;
	}
	protected function renderingDone() {
		$this->renderMaster ( null );
		$this->renderTemplate ( null );
	}
	function getViewOptions() {
		return $this->viewOptions;
	}
}