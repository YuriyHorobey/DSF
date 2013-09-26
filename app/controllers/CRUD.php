<?php

namespace app\controllers;

use engine\utils\AU;
use engine\RE;

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
		echo 'index';
	}
	public function doShow($id) {
		if ($this->model->show ( $id ) !== false) {
			foreach ( $this->model->getData () as $row ) {
				echo '<table><tr><td>Field</td><td>Value</td></tr>';
				foreach ( $row as $filed => $value ) {
					echo '<tr><td>' . $filed . '</td><td>' . $value . '</td></tr>';
				}
				echo '</table>';
			}
		} else {
			dbg ( $this->model->getErrors () );
		}
	}
	public function doForm($id = null) {
	}
	public function doSave($id = null) {
		$table = $this->model->getDefaultTable ();
		if (array_key_exists ( $table, $_REQUEST ) && is_array ( $_REQUEST [$table] )) {
			$this->model->setData ( $_REQUEST [$table] );
		} else {
			$this->model->setData ( $_REQUEST );
		}
		if (! $this->model->save ()) {
			dbg ( $this->model->getErrors (), "ERR" );
			dbg ( $_REQUEST );
			$this->doForm ( AU::get ( $_REQUEST, 'id' ) );
			$this->renderTemplate ( "form" );
			return;
		} else {
			RE::redirect ( 'show' );
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