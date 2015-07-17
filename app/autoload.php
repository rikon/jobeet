<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

require_once __DIR__.'/../vendor/Zend/Loader/Autoloader.php';

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
Zend_Loader_Autoloader::getInstance();

return $loader;
