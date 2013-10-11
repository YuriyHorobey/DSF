<?php

namespace app\controllers;

use engine\utils\AU;
use engine\RE;
use engine\VH;

class CRUD extends Base {
	/**
	 *
	 * @var app\models\Base
	 */
	protected $model;
	public function __construct() {
		$this->makeModel ();
	}
	public function getModelClass() {
		$clz = get_class ( $this );
		$model_class = substr ( $clz, 0, strlen ( $clz ) - 10 ) . 'Model'; // 10
		                                                                   // ->
		                                                                   // strlen('Controller')
		$model_class = str_replace ( '\\controllers\\', '\\models\\', $model_class );
		return $model_class;
	}
	protected function makeModel() {
		$model_class = str_replace ( '/', '\\', $this->getModelClass () );
		try {
			$this->model = new $model_class ();
		} catch ( \Exception $e ) {
			throw new \E500 ( 'Model "' . $model_class . '" can not be instantiated, check namespace/directories' );
		}
		return $this->model;
	}
	public function doIndex() {
		$data = $this->model->lst ();
		$this->set ( $this->model->getTable (), $data );
	}
	public function doShow($id) {
		$data = $this->model->show ( $id );
		if ($data !== false) {
			$data = AU::get ( $data, 0, array () );
			$this->set ( $this->model->getTable (), $data );
		} else {
			dbg ( $this->model->getErrors (), "CRUD SHOW FAILED" );
		}
	}
	public function doForm($id = null) {
		if ($id) {
			$data = $this->model->show ( $id );
			if ($data !== false) {
				$data = AU::get ( $data, 0, array () );
			}
			$this->set ( $this->model->getTable (), $data );
		}
	}
	public function doSave($id = null) {
		$table = $this->model->getTable ();
		if (array_key_exists ( $table, $_REQUEST ) && is_array ( $_REQUEST [$table] )) {
			$this->model->setData ( $_REQUEST [$table] );
		} else {
			$this->model->setData ( $_REQUEST );
		}
		if (! $this->model->save ()) {
			$data = $this->model->getData ();
			$data = AU::get ( $data, 0, array () );
			$this->set ( $this->model->getTable (), $data );
			$this->set ( '_errors', $this->model->getErrors () );
			$this->renderTemplate ( "form" );
			return;
		} else {
			$this->redirect ( 'show' );
		}
	}
	public function doDelete($id) {
		if ($this->model->delete ( $id ) !== false) {
			echo "$id deleted";
		} else {
			dbg ( $this->model->getErrors () );
		}
	}
}

?>