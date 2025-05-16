<?php

namespace Hexbatch\Things\Helpers;



class ThingUtilities {


    public static function boolishToBool($val) : ?bool {
        if (is_null($val)) {return null;}
        if (empty($val)) {return false;}
        $boolval = ( is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val );
        return ( $boolval===null  ? false : $boolval );
    }

    public static function getComposerPath(bool $for_lib) : string {
        if ($for_lib) {
            $root = dirname(__DIR__,2);
        } else {
            $root = base_path();
        }
        if (!$root) {
            throw new \LogicException("Cannot get root path for  ".$for_lib?'self':'parent');
        }
        $composerFile = $root . DIRECTORY_SEPARATOR . 'composer.json';
        $what =  realpath($composerFile);
        if (!$what) {
            throw new \LogicException("Composer path $composerFile does not exist");
        }
        return $what;
    }

    public static function getComposer(bool $for_lib) : array  {
        $composerFile = static::getComposerPath(for_lib: $for_lib);
        $composer = json_decode(file_get_contents($composerFile), true);
        if (empty($composer)) {
            throw new \LogicException("Cannot convert composer.json");
        }
        return $composer;
    }

    public static function getVersionAsString(bool $for_lib) : string {
        $composer = static::getComposer(for_lib: $for_lib);
        return $composer['version']??'';
    }

    public static function  getArray(string|array|null $source) : ?array {
        if (is_array($source)) {
            return $source;
        } elseif (json_validate($source)) {
            return static::getArray(source: json_decode($source,true));
        } else {
           return null;
        }
    }

}
