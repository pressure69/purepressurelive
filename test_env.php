<?php
echo "<pre>";
echo "PPLIVE_DB_DSN: " . getenv('PPLIVE_DB_DSN') . PHP_EOL;
echo "PPLIVE_DB_USER: " . getenv('PPLIVE_DB_USER') . PHP_EOL;
echo "PPLIVE_DB_PASS: " . getenv('PPLIVE_DB_PASS') . PHP_EOL;
echo "STRIPE_SECRET: " . substr(getenv('STRIPE_SECRET'), 0, 10) . "... (hidden)" . PHP_EOL;
echo "PPLIVE_ENV: " . getenv('PPLIVE_ENV') . PHP_EOL;
echo "</pre>";
?>
