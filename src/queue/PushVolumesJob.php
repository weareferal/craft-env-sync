<?php
namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PushVolumesJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pullVolumes();
    }

    protected function defaultDescription()
    {
        return 'Push local volume backups to cloud';
    }
}