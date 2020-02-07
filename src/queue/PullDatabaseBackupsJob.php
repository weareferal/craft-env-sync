<?php

namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PullDatabaseBackupsJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pullDatabaseBackups();
    }

    protected function defaultDescription()
    {
        return 'Pull remote database backups from cloud';
    }
}
