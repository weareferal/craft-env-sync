<?php

namespace weareferal\sync\controllers;

use Craft;
use craft\web\Controller;

use weareferal\sync\Sync;
use weareferal\sync\queue\PullDatabaseJob;
use weareferal\sync\queue\PullVolumesJob;
use weareferal\sync\queue\PushDatabaseJob;
use weareferal\sync\queue\PushVolumesJob;
use weareferal\sync\exceptions\ProviderException;


class SyncController extends Controller
{
    public function actionCreateDatabaseBackup ()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            Sync::getInstance()->sync->createDatabaseBackup();
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error creating database backup'));
        }
    
        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionCreateVolumesBackup ()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            Sync::getInstance()->sync->createVolumesBackup();
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('env-sync', 'Error creating volume backup'));
        }
    
        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionPushDatabase ()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new PushDatabaseJob());
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

    public function actionPullDatabase ()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new PullDatabaseJob());
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

    public function actionPushVolumes ()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new PushVolumesJob());
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

    public function actionPullVolumes ()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('sync');

        try {
            if (Sync::getInstance()->getSettings()->useQueue) {
                Craft::$app->queue->push(new PullVolumesJob());
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

    public function actionRestoreDatabase ()
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

    public function actionRestoreVolumes ()
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