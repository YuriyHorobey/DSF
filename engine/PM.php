<?php

namespace engine;

class PM {
	protected static $messages = array ();
	public static function getMessages() {
		return self::$messages;
	}
	public static function setMessages(array $messages) {
		self::$messages = $messages;
	}
	public static function addMessage($msg) {
		self::$messages [] = $msg;
	}
	public static function addMessages($messages) {
		self::$messages = array_merge ( self::$messages, $messages );
	}
}

?>