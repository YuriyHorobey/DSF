<?php

namespace engine\helpers\view;

use engine\helpers\view\LST;

/**
 *
 * @author Yuriy
 *        
 */
class MSG extends LST {
	protected $innerList;
	function __construct($name = null) {
		parent::__construct ( $name );
		$this->innerList = new parent ();
		$this->header = '<div class="msg_container {{_name}}">' . "\n" . '<ul>';
		$this->item = "<li><h2>{{_title}}</h2>{{_subitems}}</li>\n";
		$this->footer = "</ul>\n</div>";
		$this->empty = '';
		
		$this->innerList->tplHeader ( '<ul>' );
		$this->innerList->tplItem ( '<ul><li>{{_msg}}</li></ul>' );
		$this->innerList->tplFooter ( '</ul>' );
		$this->innerList->tplEmpty ( '' );
	}
	function render($data = '__default__') {
		$data = $this->getData ( $data );
		$content = array ();
		foreach ( $data as $issue ) {
			foreach ( $issue as $title => $rows ) {
				$su = array ();
				
				$subitems = $this->innerList->getItems ( $rows );
				$su ['_title'] = $title;
				$su ['_subitems'] = $subitems;
				$content [] = $su;
			}
		}
	 
		parent::render ($content);
	}
}

?>