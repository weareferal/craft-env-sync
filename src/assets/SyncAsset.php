<?php
namespace weareferal\sync\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class SyncAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';
        $this->depends = [
            CpAsset::class,
        ];
        $this->js = [
            'SyncUtility.js'
        ];
        $this->css = [
            'SyncUtility.css',
        ];
        parent::init();
    }
}