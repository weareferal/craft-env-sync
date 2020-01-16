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
        if (! extension_loaded('zip')) {
            Craft::error('sync: Couldn\'t load zip extension');
            return false;
        }

        if (! file_exists($source)) {
            Craft::error('sync: Source zip file doesn\'t exist');
            return false;
        }
    
        $zip = new ZipArchive();
        if (!$zip->open($dest, ZIPARCHIVE::CREATE)) {
            return false;
        }
    
        $source = str_replace('\\', '/', realpath($source));
    
        if (is_dir($source) === true)
        {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
    
            foreach ($files as $file)
            {
                $file = str_replace('\\', '/', $file);
    
                // Ignore "." and ".." folders
                if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                    continue;
    
                $file = realpath($file);
    
                if (is_dir($file) === true)
                {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                }
                else if (is_file($file) === true)
                {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        }
        else if (is_file($source) === true)
        {
            $zip->addFromString(basename($source), file_get_contents($source));
        }
    
        return $zip->close();
    }

    public static function unzip($source, $dest)
    {
        if (! extension_loaded('zip')) {
            Craft::error('sync: Couldn\'t load zip extension', __METHOD__);
            return false;
        }

        if (! file_exists($source)) {
            Craft::error('sync: Source zip file doesn\'t exist: ' . $source, __METHOD__);
            return false;
        }

        $zip = new ZipArchive();
        if (! $zip->open($source)) {
            Craft::error('sync: Couldn\'t open zip file: ' . $source, __METHOD__);
            return false;
        }

        $zip->extractTo($dest);
        return $zip->close();
    }
}