<?php

use engine\VH;

?>
<form method="post" action="<?php VH::urlModule('user/user/save')?>"
	class="m_user_register_form">
	<div class="form_field_box"><label >login:</label><input type="text" name="login"/></div>
	<div class="form_field_box"><label >password:</label><input type="password" name="password"/></div>
	<div class="form_field_box"><input type="submit" value="register"/></div>
</form>
