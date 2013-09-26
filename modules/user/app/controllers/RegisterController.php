<?php

namespace modules\user\app\controllers;

use app\controllers\CRUD;


class RegisterController extends CRUD{
	public function getModelClass(){
		return 'modules/user/app/models/UserModel';
	}
	public function doForm() {
	}
}

?>