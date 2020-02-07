<?php

namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class CreateVolumeBackupJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->createVolumeBackup();
    }

    protected function defaultDescription()
    {
        return 'Create a new volume backup';
    }
}
