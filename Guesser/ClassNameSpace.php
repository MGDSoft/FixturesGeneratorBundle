<?php

namespace MGDSoft\FixturesGeneratorBundle\Guesser;

use MGDSoft\FixturesGeneratorBundle\Extractor\Bean\PropertyDetails;
use MGDSoft\FixturesGeneratorBundle\Generator\Fixture;
use Symfony\Component\ClassLoader\ClassLoader;

class ClassNameSpace
{
    public function getPathFromPath($path)
    {
        $path = $this->normalizePath($path);

        // psr4
        $classNameSpaceMap = require(__DIR__.'/../../../../vendor/composer/autoload_psr4.php');

        $search = $replace = [];

        foreach ($classNameSpaceMap as $key => $multiplePaths) {
            foreach ($multiplePaths as $pathLibrary) {
                $search[]  = $pathLibrary . '/';
                $replace[] = $key;
            }
        }

        $search[]  = '/';
        $replace[] = '\\';

        $search[]  = '.php';
        $replace[] = '';

        $classNameSpace = str_replace(
            $search,
            $replace,
            $path
        );

        return ltrim($classNameSpace, '\\');
    }

    /**
     * Copied from https://edmondscommerce.github.io/php/php-realpath-for-none-existant-paths.html
     * because realpath only works for existing paths
     * @param $path
     * @return mixed
     */
    protected function normalizePath($path)
    {
        return array_reduce(explode('/', $path), create_function('$a, $b', '
            if($a === 0)
                $a = "/";

            if($b === "" || $b === ".")
                return $a;

            if($b === "..")
                return dirname($a);

            return preg_replace("/\/+/", "/", "$a/$b");
        '
        ),
            0
        );
    }
}