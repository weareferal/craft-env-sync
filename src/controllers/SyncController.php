<?php

namespace weareferal\sync\controllers;

use Craft;
use craft\web\Controller;

use weareferal\sync\Sync;
use weareferal\sync\queue\CreateDatabaseBackupJob;
use weareferal\sync\queue\CreateVolumeBackupJob;
use weareferal\sync\queue\PruneDatabaseBackupsJob;
use weareferal\sync\queue\PruneVolumeBackupsJob;
use weareferal\sync\queue\PullDatabaseBackupsJob;
use weareferal\sync\queue\PullVolumeBackupsJob;
use weareferal\sync\queue\PushDatabaseBackupsJob;
use weareferal\sync\queue\PushVolumeBackupsJob;
use weareferal\sync\exceptions\ProviderException;


class SyncController extends Controller
{
    public function actionCreateDatabaseBackup()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new CreateDatabaseBackupJob());
            } else {
                Sync::getInstance()->sync->createDatabaseBackups();
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error creating database backup'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionCreateVolumesBackup()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new CreateVolumeBackupJob());
            } else {
                Sync::getInstance()->sync->createDatabaseBackups();
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error creating volume backup'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionPushDatabase()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new PushDatabaseBackupsJob());
            } else {
                Sync::getInstance()->sync->pushDatabase();
            }
        } catch (ProviderException $e) {
            return $this->asErrorJson(Craft::t('env-sync', $e->getMessage()));
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error pushing database'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionPullDatabase()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new PullDatabaseBackupsJob());
            } else {
                Sync::getInstance()->sync->pullDatabase();
            }
        } catch (ProviderException $e) {
            return $this->asErrorJson(Craft::t('env-sync', $e->getMessage()));
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error pulling database'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionPushVolumes()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new PushVolumeBackupsJob());
            } else {
                Sync::getInstance()->sync->pushVolumes();
            }
        } catch (ProviderException $e) {
            return $this->asErrorJson(Craft::t('env-sync', $e->getMessage()));
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error pushing volume'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionPullVolumes()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new PullVolumeBackupsJob());
            } else {
                Sync::getInstance()->sync->pullVolumes();
            }
        } catch (ProviderException $e) {
            return $this->asErrorJson(Craft::t('env-sync', $e->getMessage()));
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error pulling volume'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionRestoreDatabase()
    {
        try {
            $databaseName = Craft::$app->getRequest()->getRequiredBodyParam('database-name');
            Sync::getInstance()->sync->restoreDatabaseBackup($databaseName);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error restoring database'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionRestoreVolumes()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            $volumeName = Craft::$app->getRequest()->getRequiredBodyParam('volume-name');
            Sync::getInstance()->sync->restoreVolumesBackup($volumeName);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error restoring assets'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }
}
