<?php

namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PruneVolumeBackupsJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pruneVolumeBackups();
    }

    protected function defaultDescription()
    {
        return 'Prune local and remote volume backups';
    }
}
