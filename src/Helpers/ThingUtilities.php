<?php

namespace Hexbatch\Things\Helpers;


use Hexbatch\Things\Exceptions\HbcThingException;

class ThingUtilities {



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

    public static function  getArray(string|array $source) : ?array {
        if (is_array($source)) {
            return $source;
        } elseif (json_validate($source)) {
            return static::getArray(source: json_decode($source,true));
        } else {
           return null;
        }
    }

    public static function  isValidArray(string|array $source,bool $b_only_list_of_strings = false) : true {
        if (is_array($source)) {
            if (empty($source)) {return true;}
            if ($b_only_list_of_strings) {
                if (array_is_list($source)) {
                    foreach ($source as $what) {
                        if (is_numeric($what)) {
                            throw new HbcThingException("array is not a list of strings with an element of $what");
                        }
                    }
                }
            }
            return true;
        } elseif (json_validate($source)) {
            return static::isValidArray(source: json_decode($source,true),b_only_list_of_strings: $b_only_list_of_strings);
        } else {
            throw new HbcThingException("not an array or json");
        }
    }

}
