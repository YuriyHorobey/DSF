<?php

namespace app\controllers;

use engine\TR;

class HomeController extends Base {
	function doIndex() {
		echo "Hello from DSF HomeController<br>";
		echo TR::msg ( 'test' );
	}
}
?>