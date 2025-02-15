<?php

namespace Hexbatch\Things\Helpers;


class Utilities {



    public static function getComposerPath(bool $for_lib) : string {
        if ($for_lib) {
            $root = realpath('../../'.__DIR__);
        } else {
            $root = base_path();
        }
        if (!$root) {
            throw new \LogicException("Cannot get root path for  ".$for_lib?'self':'parent');
        }
        $composerFile = base_path() . DIRECTORY_SEPARATOR . 'composer.json';
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

}
