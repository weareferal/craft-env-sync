<?php

namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PullVolumeBackupsJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pullVolumeBackups();
    }

    protected function defaultDescription()
    {
        return 'Pull remote volume backups from cloud';
    }
}
