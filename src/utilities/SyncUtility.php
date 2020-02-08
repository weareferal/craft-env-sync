<?php

namespace weareferal\sync\utilities;

use Craft;
use craft\base\Utility;

use weareferal\sync\assets\SyncUtilityAsset;
use weareferal\sync\Sync;

class SyncUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('app', 'Sync');
    }

    public static function id(): string
    {
        return 'env-sync';
    }

    public static function iconPath()
    {
        return Sync::getInstance()->getBasePath() . DIRECTORY_SEPARATOR . 'utility-icon.svg';
    }

    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(SyncUtilityAsset::class);
        $forms = [
            ['create-database-backup', true],
            ['create-volumes-backup', true],
            ['push-database', false],
            ['push-volumes', false],
            ['pull-database', true],
            ['pull-volumes', true],
            ['restore-database-backup', false],
            ['restore-volumes-backup', false]
        ];
        foreach ($forms as $form) {
            $view->registerJs("new Craft.SyncUtility('" . $form[0] . "', " . $form[1] . ");");
        }

        $dbBackupOptions = Sync::getInstance()->sync->getDbBackupOptions();
        $volumeBackupOptions = Sync::getInstance()->sync->getVolumeBackupOptions();

        return $view->renderTemplate('env-sync/_components/utilities/sync', [
            "settingConfigured" => Sync::getInstance()->getSettings()->isConfigured(),
            "dbBackupOptions" => $dbBackupOptions,
            "volumes" => Craft::$app->getVolumes()->getAllVolumes(),
            "volumeBackupOptions" => $volumeBackupOptions
        ]);
    }
}
