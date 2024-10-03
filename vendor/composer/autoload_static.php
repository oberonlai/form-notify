<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd09b880c2582110a9242264be15a2f2a
{
    public static $files = array (
        '5f2aad0f1beee097fba38a252c1ebd00' => __DIR__ . '/..' . '/a7/autoload/package.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPackio\\' => 8,
        ),
        'S' => 
        array (
            'Snicco\\Component\\BetterWPDB\\' => 28,
        ),
        'P' => 
        array (
            'PostTypes\\' => 10,
        ),
        'O' => 
        array (
            'ODS\\' => 4,
        ),
        'F' => 
        array (
            'FORMNOTIFY\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPackio\\' => 
        array (
            0 => __DIR__ . '/..' . '/wpackio/enqueue/inc',
        ),
        'Snicco\\Component\\BetterWPDB\\' => 
        array (
            0 => __DIR__ . '/..' . '/snicco/better-wpdb/src',
        ),
        'PostTypes\\' => 
        array (
            0 => __DIR__ . '/..' . '/jjgrainger/posttypes/src',
        ),
        'ODS\\' => 
        array (
            0 => __DIR__ . '/..' . '/oberonlai/wp-option/src',
        ),
        'FORMNOTIFY\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd09b880c2582110a9242264be15a2f2a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd09b880c2582110a9242264be15a2f2a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd09b880c2582110a9242264be15a2f2a::$classMap;

        }, null, ClassLoader::class);
    }
}