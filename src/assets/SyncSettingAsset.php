<?php
namespace weareferal\sync\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class SyncSettingAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';
        $this->depends = [
            CpAsset::class,
        ];
        $this->js = [
            'SyncSetting.js'
        ];
        parent::init();
    }
}