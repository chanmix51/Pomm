<?php

spl_autoload_register(function ($class) {
    if (0 === strpos($class, 'Pomm\\')) {
        $class = str_replace('\\', '/', $class);
        require sprintf("%s/%s.php", dirname(dirname(__DIR__)), $class);
    }
});
