<?php

namespace app\models;

use engine\db\DB;
use engine\utils\SU;
use engine\utils\AU;
use engine\db\Validator;

class Base {
	/**
	 *
	 * @var array
	 */
	protected $rows = array ();
	protected $fileds = array ();
	protected $errors = array ();
	protected $validated = false;
	protected $table;
	public function __construct() {
		$this->table = $this->getDefaultTable ();
		$this->field ( 'id' )->integer ()->numRange ( 1, null );
	}
	public function getDefaultTable() {
		$class = SU::underscore ( str_replace ( 'app\\models\\', '', get_class ( $this ) ) );
		$parts = explode ( '\\', $class );
		$modelName = end ( $parts );
		// -6 == remove _model
		$table = substr ( $modelName, 0, strlen ( $modelName ) - 6 );
		
		array_pop ( $parts );
		$prefix = implode ( '_', $parts );
		$prefix = SU::removeBeginning ( $prefix, 'modules_' );
		
		if ($prefix) {
			$table = $prefix . '_' . $table;
		}
		
		return $table;
	}
	protected function field($field_name) {
		$field_name = trim ( $field_name );
		$validator = new Validator ( $field_name );
		$this->fileds [$field_name] = $validator;
		return $validator;
	}
	public function getFieldNames() {
		$ret = array_keys ( $this->fileds );
		return $ret;
	}
	public function getFieldNamesAsString() {
		$ret = array_keys ( $this->fileds );
		$ret = implode ( ', ', $ret );
		return $ret;
	}
	public function validate($return_at_first_failure = false) {
		$this->validated = true;
		$ret = true;
		$row_count = count ( $this->rows );
		$this->errors = array ();
		for($ri = 0; $ri < $row_count; $ri ++) {
			$row = $this->rows [$ri];
			foreach ( $row as $field_name => $field_data ) {
				$validator = AU::get ( $this->fileds, $field_name );
				if ($validator) {
					$cur = $validator->validate ( $field_data );
					$ret = $ret && $cur;
					if (! $cur) {
						$this->errors [$ri] [$field_name] = $validator->getErrors ();
					}
					if ($return_at_first_failure) {
						return false;
					}
				}
			}
		}
		return $ret;
	}
	/**
	 * Returns null if model was not validated since last data changes
	 *
	 * @return Ambigous <NULL, boolean>
	 */
	public function isValid() {
		return $this->validated ? null : count ( $this->errors ) > 0;
	}
	public function getErrors() {
		return $this->errors;
	}
	protected function clearErrors() {
		$this->errors = array ();
	}
	public function filterDataRow(array $data) {
		$ret = array ();
		foreach ( $data as $field => $value ) {
			if (array_key_exists ( strtolower ( $field ), $this->fileds ) || $field == 'id') {
				$ret [$field] = $value;
			}
		}
		return $ret;
	}
	public function setData(array $data, $overwrite = true) {
		if ($overwrite) {
			$this->rows = array ();
		}
		if (AU::isEmpty ( $data )) {
			return;
		}
		if (AU::isAssoc ( $data )) {
			$this->rows [] = $this->filterDataRow ( $data );
		} else {
			foreach ( $data as $row ) {
				$this->rows [] = $this->filterDataRow ( $row );
			}
		}
	}
	public function getData() {
		return $this->rows;
	}
	public function lst($where = null, array $where_args = array(), $start = 0, $limit = 50) {
		$this->clearErrors ();
		$sql = 'SELECT ' . $this->getFieldNamesAsString () . ' FROM ' . $this->table;
		dbg ( $sql );
	}
	public function show($id) {
		$this->clearErrors ();
		$sql = 'SELECT ' . $this->getFieldNamesAsString () . ' FROM ' . $this->table . ' WHERE id=:id';
		$res = DB::exec ( $sql, array (
				':id' => $id 
		) );
		if ($res !== false) {
			$this->rows = $res;
		} else {
			$this->rows = array ();
			$this->errors = DB::getErrorInfo ();
			$this->errors [] = $sql;
		}
		return $res;
	}
	public function delete($id) {
		$this->clearErrors ();
		$sql = 'DELETE FROM ' . $this->table . ' WHERE id=:id';
		$res = DB::exec ( $sql, array (
				':id' => $id 
		) );
		if ($res === false) {
			$this->rows = array ();
			$this->errors = DB::getErrorInfo ();
			$this->errors [] = $sql;
		}
		return $res;
	}
	public function save() {
		$this->clearErrors ();
		$ret = true;
		foreach ( $this->rows as $row ) {
			$id = AU::get ( $row, 'id', false );
			if ($id === false) {
				$cur = $this->insert ( $row );
			} else {
				$cur = $this->update ( $row );
			}
			if (! $cur) {
				$ret = false;
			}
		}
		return $ret;
	}
	protected function insert($row) {
		$is_valid = true;
		$errors = array ();
		$fields = '';
		$values = '';
		foreach ( $this->fileds as $field => $validator ) {
			$value = AU::get ( $row, $field, null );
			$res = $validator->validate ( $value );
			if (! $res) {
				$errors [$field] = $validator->getErrors ();
			}
			if (! array_key_exists ( $field, $row )) {
				$row [$field] = null;
			}
			$is_valid = $is_valid && $res;
			$fields .= $field . ', ';
			$values .= ':' . $field . ', ';
		}
		if (! $is_valid) {
			$this->errors [] = $errors;
			return false;
		}
		$fields = substr ( $fields, 0, - 2 );
		$values = substr ( $values, 0, - 2 );
		
		$sql = 'INSERT INTO ' . $this->table . "\n\t(" . $fields . ")\nVALUES\n\t(" . $values . ")\n";
		$res = DB::exec ( $sql, $row );
		if ($res === false) {
			$ei = DB::getErrorInfo ();
			throw new \E500 ( "Database error:\n\t" . implode ( "\n\t", $ei ) );
		}
		
		return true;
	}
	protected function update($row) {
		$is_valid = true;
		$errors = array ();
		$fields = '';
		foreach ( $row as $field => $value ) {
			if ($field == 'id') {
				continue;
			}
			$validator = AU::get ( $this->fileds, $field );
			if (is_null ( $validator )) {
				// this should never happen!
				throw new \E500 ( 'Unknown field "' . $field . '" for "' . get_class ( $this ) . '"' );
			}
			$res = $validator->validate ( $value );
			if (! $res) {
				$errors [$field] = $validator->getErrors ();
			}
			$is_valid = $is_valid && $res;
			$fields .= $field . ' = :' . $field . ', ';
		}
		if (! $is_valid) {
			$this->errors [] = $errors;
			return false;
		}
		$fields = substr ( $fields, 0, - 2 );
		$sql = 'UPDATE ' . $this->table . " SET\n\t" . $fields . "\nWHERE id=:id";
		$res = DB::exec ( $sql, $row );
		if ($res === false) {
			$ei = DB::getErrorInfo ();
			throw new \E500 ( "Database error:\n\t" . implode ( "\n\t", $ei ) );
		}
		
		return true;
		return true;
	}
}

?>