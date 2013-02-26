<?php

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'library',
    __DIR__
)));

spl_autoload_register(function ($class) {
   $filename = str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $class);
   foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
       if (file_exists($path . DIRECTORY_SEPARATOR . $filename . '.php')) {
           require_once $path . DIRECTORY_SEPARATOR . $filename . '.php';
           break;
       }
   }

   return class_exists($class, false);
});

?>
