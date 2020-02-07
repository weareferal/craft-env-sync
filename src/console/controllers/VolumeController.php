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
class VolumeController extends Controller
{
    /**
     * Create a local volumes backup
     */
    public function actionCreate()
    {
        try {
            $path = Sync::getInstance()->sync->createVolumeBackup();
            $this->stdout("Created local volume backup: " . $path . PHP_EOL, Console::FG_GREEN);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    /**
     * Push local volume backups to cloud
     */
    public function actionPush()
    {
        try {
            $paths = Sync::getInstance()->sync->pushVolumeBackups();
            $this->stdout("Pushed " . count($paths) . " volume backup(s) to the cloud" . PHP_EOL, Console::FG_GREEN);
            foreach ($paths as $path) {
                $this->stdout($path . PHP_EOL, Console::FG_GREEN);
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    /**
     * Pull remote volume backups from cloud
     */
    public function actionPull()
    {
        try {
            $paths = Sync::getInstance()->sync->pullVolumeBackups();
            $this->stdout("Pulled " . count($paths) . " volume backup(s) to the cloud" . PHP_EOL, Console::FG_GREEN);
            foreach ($paths as $path) {
                $this->stdout($path . PHP_EOL, Console::FG_GREEN);
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    /**
     * Prune volume backups
     */
    public function actionPrune()
    {
        if (! Sync::getInstance()->getSettings()->prune) {
            $this->stderr("Backup pruning disabled. Please enable via the Env Sync control panel settings" . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::CONFIG;
        } else {
            try {
                $paths = Sync::getInstance()->sync->pruneVolumeBackups();
                $this->stdout("Pruned " . count($paths["local"]) . " local volume backup(s)" . PHP_EOL, Console::FG_GREEN);
                foreach ($paths["local"] as $path) {
                    $this->stdout($path . PHP_EOL, Console::FG_GREEN);
                }
                $this->stdout("Pruned " . count($paths["remote"]) . " remote volume backup(s)" . PHP_EOL, Console::FG_GREEN);
                foreach ($paths["remote"] as $path) {
                    $this->stdout($path . PHP_EOL, Console::FG_GREEN);
                }
            } catch (\Exception $e) {
                Craft::$app->getErrorHandler()->logException($e);
                $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            return ExitCode::OK;
        }
       
    }
}
