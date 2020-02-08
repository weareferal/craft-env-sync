<?php

namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PushVolumeBackupsJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pushVolumeBackups();
    }

    protected function defaultDescription()
    {
        return 'Push local volume backups to cloud';
    }
}
