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
 * Sync database backups
 *
 * @author    test
 * @package   Test
 * @since     1
 */
class DatabaseController extends Controller
{
    /**
     * Push local database backups to cloud
     */
    public function actionPush()
    {
        try {
            Sync::getInstance()->sync->pushDatabase();
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("Pushed database backups to the cloud" . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Pull remote database backups from cloud
     */
    public function actionPull()
    {
        try {
            Sync::getInstance()->sync->pullDatabase();
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("Pulled database backups from the cloud" . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }     
}
