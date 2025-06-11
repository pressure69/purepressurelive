<?php
$output = shell_exec("cd /var/www/purepressurelive.com && git pull 2>&1");
echo "<pre>$output</pre>";
?>
