<?php

namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PushDatabaseBackupsJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pushDatabaseBackups();
    }

    protected function defaultDescription()
    {
        return 'Push local database backups to cloud';
    }
}
