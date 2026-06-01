<?php

echo "<h2>Extensões carregadas:</h2>";

echo "<pre>";
print_r(PDO::getAvailableDrivers());
echo "</pre>";

phpinfo();