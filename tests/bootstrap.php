<?php
$path = realpath( dirname(__FILE__) );

define("APP_TOKEN","ABC12DE45X");

/** Part of Respect/Rest library */
/** Autoloader that implements the PSR-0 spec for interoperability between PHP software. */
spl_autoload_register(
    function($className) {
        static $composerClassmap;
        if (!isset($composerClassmap) && file_exists(dirname(__DIR__).'/vendor/composer'))
               $composerClassmap = require dirname(__DIR__).'/vendor/composer/autoload_classmap.php';
        // Also consider composer classMap of course
        if (isset($composerClassmap[$className]))
            return require $composerClassmap[$className];

	$fileParts = explode('\\', ltrim($className, '\\'));

        if (false !== strpos(end($fileParts), '_'))
            array_splice($fileParts, -1, 1, explode('_', current($fileParts)));

        $file = implode(DIRECTORY_SEPARATOR, $fileParts) . '.php';

        foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
            if (file_exists($path = $path . DIRECTORY_SEPARATOR . $file))
                return require $path;
        }
    }
);
