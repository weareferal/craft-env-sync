<?php

namespace weareferal\sync\helpers;

use Craft;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * https://stackoverflow.com/a/1334949
 */
class ZipHelper
{
    public static function recursiveZip($source, $dest)
    {
        $zip = new ZipArchive();
        $zip->open($dest, ZIPARCHIVE::CREATE);
        $source = str_replace('\\', '/', realpath($source));

        if (is_dir($source) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);
                // Ignore "." and ".." folders
                if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..'))) {
                    continue;
                }
                $file = realpath($file);
                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else if (is_file($file) === true) {
                    $zip->addFile($file, str_replace($source . '/', '', $file));
                }
            }
        } else if (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }

    public static function unzip($source, $dest)
    {
        $zip = new ZipArchive();
        $zip->open($source);
        $zip->extractTo($dest);
        return $zip->close();
    }
}
