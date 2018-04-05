<?php
shell_exec("rm -rf ./private");
require_once('fn.php');
new_user("anthony", "123456", true);
new_user("theo", "123456", true);
new_user("user1", "123456");
new_category("Super Cat");
new_category("Computer");
new_category("Tech");
new_category("Other");
new_product("Norminet", array("Super Cat"), "The cat god", 100000);
new_product("iMac", array("Computer", "Tech"), "A 42 computer", 1000);
new_product("Keyboard", array("Tech"), "A magic qwerty keyboard", 99.99);
new_product("Mouse", array("Tech"), "A magic mouse", 99.99);
new_product("Chair", array("Other"), "A comfortable chair", 239.99);
header("Location: index.php");
?>
