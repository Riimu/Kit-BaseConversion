<?php

set_include_path(implode(PATH_SEPARATOR, [
    get_include_path(),
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src',
]));

spl_autoload_register(function ($class) {
    $file = implode(DIRECTORY_SEPARATOR, preg_split('/\\\\|_(?=[^\\\\]*$)/', $class));
    if ($path = stream_resolve_include_path($file . '.php')) {
        require $path;
    }
});

?>
