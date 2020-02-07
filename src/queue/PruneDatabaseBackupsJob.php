<?php

namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PruneDatabaseBackupsJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pruneDatabaseBackups();
    }

    protected function defaultDescription()
    {
        return 'Prune local and remote database backups';
    }
}
