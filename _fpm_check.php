<?php

header('Content-Type: text/plain; charset=utf-8');
echo 'sapi=' . php_sapi_name() . "\n";
echo 'pdo_loaded=' . (int) extension_loaded('pdo') . "\n";
echo 'pdo_mysql_loaded=' . (int) extension_loaded('pdo_mysql') . "\n";
echo 'curl_loaded=' . (int) extension_loaded('curl') . "\n";
