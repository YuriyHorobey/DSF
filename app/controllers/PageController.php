<?php

namespace app\controllers;

use app\controllers\Base;

class PageController extends Base {
	public function doIndex() {
		dbg ( $_REQUEST );
		$this->renderingDone();
	}
}

?>