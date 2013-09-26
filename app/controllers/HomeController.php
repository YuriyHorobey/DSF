<?php

namespace app\controllers;

use engine\TR;

class HomeController extends Base {
	function doIndex() {
		echo "Hello from DF HomeController<br>";
		echo TR::msg ( 'test' );
	}
}
?>