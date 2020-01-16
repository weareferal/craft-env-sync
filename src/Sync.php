<?php
/**
 * Craft Sync plugin for Craft CMS 3.x
 *
 * Sync and backup your database and assets across environments
 *
 * @link      https://weareferal.com
 * @copyright Copyright (c) 2019 Timmy O'Mahony
 */

namespace weareferal\sync;


use Craft;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\services\Utilities;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;

use yii\base\Event;

use weareferal\sync\utilities\SyncUtility;
use weareferal\sync\models\Settings;
use weareferal\sync\services\SyncService;


class Sync extends Plugin
{
    public $hasCpSettings = true;

    public static $plugin;

    public $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'sync' => SyncService::create($this->getSettings()->cloudProvider)
        ]);

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'weareferal\sync\console\controllers';
        }

        // Register permissions
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions['Sync'] = [
                    'sync' => [
                        'label' => 'Sync database and assets',
                    ],
                ];
            }
        );

        // Register with Utilities service
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SyncUtility::class;
            }
        );
        

        Craft::info(
            Craft::t(
                'env-sync',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate(
            'env-sync/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}

?>