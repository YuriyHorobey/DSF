<?php

namespace engine\db;

use engine\utils\SU;

class Validator {
	protected $field_name;
	protected $methods = array ();
	protected $errors = array ();
	protected $validated = false;
	protected $is_mandatory = false;
	public function __construct($field_name) {
		$this->field_name = trim ( $field_name );
	}
	protected function addMethod($method_name, $args = array()) {
		// leave room for $data argument for vldXXX methods
		$args = array_merge ( array (
				null 
		), $args );
		
		$method_name = explode ( "::", $method_name );
		$method_name = end ( $method_name );
		$method_name = SU::capitalize ( $method_name );
		$method_name = "vld" . $method_name;
		
		$this->methods [] = array (
				"method" => $method_name,
				"args" => $args 
		);
	}
	protected function addError($err, $args = array()) {
		$args ['field_name'] = $this->field_name;
		$this->errors []['_msg'] = SU::tpl ( $err, $args );
	}
	public function isValid() {
		if ($this->validated == false) {
			// we do not know if it is valid
			return null;
		}
		$ret = count ( $this->errors ) == 0;
		return $ret;
	}
	public function getErrors() {
		return $this->errors;
	}
	public function validate($data) {
		$this->errors = array ();
		$ret = true;
		$res = true;
		$this->validated = true;
		if (SU::isBlank ( $data ) && ! $this->is_mandatory) {
			return true;
		}
		foreach ( $this->methods as $method_entry ) {
			$method = $method_entry ["method"];
			$args = $method_entry ["args"];
			$args [0] = $data;
			$res = call_user_func_array ( array (
					$this,
					$method 
			), $args );
			$ret = $ret && $res;
		}
		
		return $ret;
	}
	public function mandatory($err = "Field '{{field_name}}' is mandatory.") {
		$this->is_mandatory = true;
		$this->addMethod ( __METHOD__, func_get_args () );
		return $this;
	}
	public function number($err = "Field '{{field_name}}' must be integer number.") {
		$this->addMethod ( __METHOD__, func_get_args () );
		return $this;
	}
	public function integer($err = "Field '{{field_name}}' must a number.") {
		$this->addMethod ( __METHOD__, func_get_args () );
		return $this;
	}
	public function numRange($min, $max, $err = "Field '{{field_name}}' must be in range {{min|-infinity}}..{{max|+infinity}}.") {
		$this->addMethod ( __METHOD__, func_get_args () );
		return $this;
	}
	public function strRange($min, $max, $err = "Field '{{field_name}}' must be {{min|0}}..{{max|+infinity}} characters long.") {
		$this->addMethod ( __METHOD__, func_get_args () );
		return $this;
	}
	public function strNonBlank($err = "Field '{{field_name}}' must contain at least one visible character.") {
		$this->addMethod ( __METHOD__, func_get_args () );
		return $this;
	}
	public function email($err = "Field '{{field_name}}' was not recognized as valid email.") {
		$this->addMethod ( __METHOD__, func_get_args () );
		return $this;
	}
	public function matches($data1, $data2, $err = "Field '{{field_name1}}' must match '{{field_name2}}.") {
		$this->addMethod ( __METHOD__, func_get_args () );
		return $this;
	}
	// Actual validator methods
	protected function vldMandatory($data, $err = "Field '{{field_name}}' is mandatory.") {
		if (SU::isBlank ( $data )) {
			$this->addError ( $err );
			return false;
		}
		return true;
	}
	protected function vldNumber($data, $err = "Field '{{field_name}}' must be integer number.") {
		$ret = filter_var ( $data, FILTER_VALIDATE_FLOAT );
		if (! $ret) {
			$this->addError ( $err );
		}
		return $ret;
	}
	protected function vldInteger($data, $err = "Field '{{field_name}}' must a number.") {
		$ret = filter_var ( $data, FILTER_VALIDATE_INT );
		if (! $ret) {
			$this->addError ( $err );
		}
		return $ret;
	}
	protected function vldNumRange($data, $min, $max, $err = "Field '{{field_name}}' must be in range {{min|-infinity}}..{{max|+infinity}}.") {
		$ret = $this->vldNumber ( $data );
		
		if ($ret) {
			if ((! is_null ( $min ) && $data < $min) || (! is_null ( $max ) && $data > $max)) {
				$this->addError ( $err, array (
						'min' => $min,
						'max' => $max 
				) );
				return false;
			}
			return true;
		} else {
			$this->addError ( $err, array (
					"min" => $min,
					'max' => $max 
			) );
		}
	}
	protected function vldStrRange($data, $min, $max, $err = "Field '{{field_name}}' must be {{min|0}}..{{max|+infinity}} characters long.") {
		if (SU::isEmpty ( $data ) && ! is_null ( $min )) {
			$this->addError ( $err, array (
					'min' => $min,
					'max' => $max 
			) );
			return false;
		}
		$len = strlen ( $data );
		if ((! is_null ( $min ) && $len < $min) || (! is_null ( $max ) && $len > $max)) {
			$this->addError ( $err, array (
					'min' => $min,
					'max' => $max 
			) );
			return false;
		}
		return true;
	}
	protected function vldStrNonBlank($data, $err = "Field '{{field_name}}' must contain at least one visible character.") {
		if (SU::isBlank ( $data )) {
			$this->addError ( $err );
			return false;
		}
		return true;
	}
	protected function vldRegex($data, $err = "Field '{{field_name}}' is in vrong format.") {
	}
	protected function vldEmail($data, $err = "Field '{{field_name}}' was not recognized as valid email.") {
		if (! filter_var ( $data, FILTER_VALIDATE_EMAIL )) {
			$this->addError ( $err );
			return false;
		}
		return true;
	}
	protected function vldMatches($data1, $data2, $err = "Field '{{field_name1}}' must match '{{field_name2}}.") {
	}
	public function __toString() {
		return "Validator for " . $this->field_name;
	}
}

?>