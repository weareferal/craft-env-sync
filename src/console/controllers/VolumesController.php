<?php
/**
 * test plugin for Craft CMS 3.x
 *
 * test
 *
 * @link      test.com
 * @copyright Copyright (c) 2019 test
 */

namespace weareferal\sync\console\controllers;

use weareferal\sync\Test;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;

use weareferal\sync\Sync;

/**
 * Sync volumes backup
 *
 * @author    test
 * @package   Test
 * @since     1
 */
class VolumesController extends Controller
{
    /**
     * Create a local volumes backup
     */
    public function actionCreateBackup()
    {
        try {
            Sync::getInstance()->sync->createVolumesBackup();
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("Created local volumes backup" . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Push local volume backups to cloud
     */
    public function actionPush()
    {
        try {
            Sync::getInstance()->sync->pushVolumes();
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("Pushed volumes backups to the cloud" . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Pull remote volume backups from cloud
     */
    public function actionPull()
    {
        try {
            Sync::getInstance()->sync->pushVolumes();
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("Pulled volumes backups from the cloud" . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
