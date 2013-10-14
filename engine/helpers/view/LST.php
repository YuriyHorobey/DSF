<?php

namespace engine\helpers\view;

use engine\VH;
use engine\utils\AU;
use engine\utils\SU;

/**
 *
 * @author Yuriy
 *        
 */
class LST {
	protected $header = '';
	protected $item = '';
	protected $footer = '';
	protected $empty = 'This list is empty.';
	protected $templateBeingCaptured = false;
	protected $name;
	/**
	 */
	function __construct($name = null) {
		$this->name = $name;
	}
	function tplHeader($header) {
		$this->header = $header;
		return $this;
	}
	function tplItem($item) {
		$this->item = $item;
		return $this;
	}
	function tplFooter($footer) {
		$this->footer = $footer;
		return $this;
	}
	function tplEmpty($empty) {
		$this->empty = $empty;
		return $this;
	}
	function startHeader() {
		if ($this->templateBeingCaptured) {
			throw new LSTException ( 'Error in view: Trying to start Header template, while "' . $this->templateBeingCaptured . '" is already being captured' );
		}
		$this->templateBeingCaptured = 'Header';
		ob_start ();
		return $this;
	}
	function endHeader() {
		if (! $this->templateBeingCaptured) {
			throw new LSTException ( 'Error in view: Trying to end capturing Header template, while startHeader() was not yet invoked' );
		}
		if ($this->templateBeingCaptured !== 'Header') {
			throw new LSTException ( 'Error in view: Trying to end capturing Header template, while "' . $this->templateBeingCaptured . '" is already being captured. Missing end' . $this->templateBeingCaptured . '(); ?' );
		}
		$this->header = ob_get_clean ();
		$this->templateBeingCaptured = false;
		return $this;
	}
	function startItem() {
		if ($this->templateBeingCaptured) {
			throw new LSTException ( 'Error in view: Trying to start Item template, while "' . $this->templateBeingCaptured . '" is already being captured' );
		}
		$this->templateBeingCaptured = 'Item';
		ob_start ();
		return $this;
	}
	function endItem() {
		if (! $this->templateBeingCaptured) {
			throw new LSTException ( 'Error in view: Trying to end capturing Item template, while startItem() was not yet invoked' );
		}
		if ($this->templateBeingCaptured !== 'Item') {
			throw new LSTException ( 'Error in view: Trying to end capturing Item template, while "' . $this->templateBeingCaptured . '" is already being captured. Missing end' . $this->templateBeingCaptured . '(); ?' );
		}
		$this->item = ob_get_clean ();
		$this->templateBeingCaptured = false;
		return $this;
	}
	function startFooter() {
		if ($this->templateBeingCaptured) {
			throw new LSTException ( 'Error in view: Trying to start Footer template, while "' . $this->templateBeingCaptured . '" is already being captured' );
		}
		$this->templateBeingCaptured = 'Footer';
		ob_start ();
		return $this;
	}
	function endFooter() {
		if (! $this->templateBeingCaptured) {
			throw new LSTException ( 'Error in view: Trying to end capturing Footer template, while startFooter() was not yet invoked' );
		}
		if ($this->templateBeingCaptured !== 'Footer') {
			throw new LSTException ( 'Error in view: Trying to end capturing Footer template, while "' . $this->templateBeingCaptured . '" is already being captured. Missing end' . $this->templateBeingCaptured . '(); ?' );
		}
		$this->footer = ob_get_clean ();
		$this->templateBeingCaptured = false;
		return $this;
	}
	function startEmpty() {
		if ($this->templateBeingCaptured) {
			throw new LSTException ( 'Error in view: Trying to start Empty template, while "' . $this->templateBeingCaptured . '" is already being captured' );
		}
		$this->templateBeingCaptured = 'Empty';
		ob_start ();
		return $this;
	}
	function endEmpty() {
		if (! $this->templateBeingCaptured) {
			throw new LSTException ( 'Error in view: Trying to end capturing Empty template, while startEmpty() was not yet invoked' );
		}
		if ($this->templateBeingCaptured !== 'Empty') {
			throw new LSTException ( 'Error in view: Trying to end capturing Empty template, while "' . $this->templateBeingCaptured . '" is already being captured. Missing end' . $this->templateBeingCaptured . '(); ?' );
		}
		$this->empty = ob_get_clean ();
		$this->templateBeingCaptured = false;
		return $this;
	}
	protected function getItems($rows) {
		$items = '';
		for($_idx = 0; $_idx < count ( $rows ); $_idx ++) {
			$row = $rows [$_idx];
			$row ['_idx'] = $_idx;
			if ($_idx % 2) {
				$row ['_even_odd'] = 'even';
				$row ['_even'] = true;
				$row ['_odd'] = false;
			} else {
				$row ['_even_odd'] = 'odd';
				$row ['_even'] = false;
				$row ['_odd'] = true;
			}
			$items .= SU::tpl ( $this->item, $row );
		}
		return $items;
	}
	protected function getData($data) {
		if ($data === '__default__' || ! is_array ( $data )) {
			if (! $this->name) {
				throw new LSTException ( 'Unable to render list. Neither name, nor data is given' );
			}
			$data = VH::dget ( $this->name, array () );
		}
		return $data;
	}
	function render($data = '__default__') {
		$data = $this->getData ( $data );
		if ($this->name) {
			$data ['_name'] = $this->name;
		}
		$rows = AU::extractIndexed ( $data );
		if (count ( $rows ) == 0) {
			echo SU::tpl ( $this->empty, $data );
			return $this;
		}
		
		echo SU::tpl ( $this->header, $data );
		$items = $this->getItems ( $rows );
		echo $items;
		echo SU::tpl ( $this->footer, $data );
	}
}
class LSTException extends \E500 {
	public function __construct($message) {
		$stack = $this->getTrace ();
		if (count ( $stack )) {
			$file = $stack [0] ['file'];
			$line = ' [' . $stack [0] ['line'] . ']';
		} else {
			$file = 'unknown';
			$line = '';
		}
		// $file = \Exception::trace[0]['file'];
		parent::__construct ( $message . "\n<br>view file: " . $file . $line );
	}
}
?>