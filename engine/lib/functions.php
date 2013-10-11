<?php

/**
  * Quick and dirty debug frunction.
  * 
  * Displays nicelly formatted output in the browser.
  * 
  * @param mixed $var
  * @param string $lbl a label to output before <code>$var</code>
  * @param boolean $detailed true:capture stack trace
  */
function dbg($var = null, $lbl = "dbg", $detailed = true) {
	if (is_null ( $var )) {
		$var = "__NULL__";
	}
	echo "<xmp>\n";
	// print_r(debug_backtrace());
	if ($detailed) {
		$stack = debug_backtrace ();
		
		echo "---\n";
		if (isset ( $stack [0] ["file"] )) {
			$file = str_replace ( "\\", "/", $stack [0] ["file"] );
			if (defined ( 'APP_ROOT' )) {
				$file = substr ( $file, strlen ( APP_ROOT ) ) . ": "; 
			}
			echo $file;
		}
		if (isset ( $stack [1] ["class"] )) {
			$class = $stack [1] ["class"] . $stack [1] ["type"];
			echo $class;
		}
		if (count ( $stack ) > 1) {
			$function = $stack [1] ["function"];
			echo "$function()";
		}
		
		if (isset ( $stack [0] ["line"] )) {
			if (isset ( $function )) {
				echo ", ";
			}
			echo "line: " . $stack [0] ["line"];
		}
		echo "\n";
	}
	echo "$lbl=";
	if (! is_array ( $var )) {
		echo " [" . gettype ( $var ) . "] ";
	}
	if (is_bool ( $var )) {
		$var = $var ? "true" : "false";
	}
	print_r ( $var );
	echo "\n---\n</xmp>";
}

?>