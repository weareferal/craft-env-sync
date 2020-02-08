<?php

namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class CreateDatabaseBackupJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->createDatabaseBackup();
    }

    protected function defaultDescription()
    {
        return 'Create a new database backup';
    }
}
