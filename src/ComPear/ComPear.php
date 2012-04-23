<?php

namespace ComPear;

use Composer\Script\Event;

class ComPear {
    public static function postUpdate(Event $event) {
        // Download the latest composer.phar.
        $base = dirname(dirname(__DIR__));
        $destination = $base . '/package/composer.phar';
        copy('http://getcomposer.org/composer.phar', $destination);

        // Update the package.xml appropriately.
        $version = '1.0.0snapshot' . date('YmdHi');
        $s = simplexml_load_file($base . '/package/package-default.xml');
        $s->version = $version;
        file_put_contents($base . '/package/package.xml', $s->asXML());

        // Compress the files to a tgz file.
        $original = getcwd();
        $path = chdir($base . '/package/');
        system("tar zcf composer-$version.tgz composer.phar package.xml");
        chdir($original);

        $pirum = $base . '/vendor/bin/pirum';
        system("$pirum build $base/web");
        system("$pirum add $base/web $base/package/composer-$version.tgz");
    }
}
