<?php
namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PullVolumesJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pullVolumes();
    }

    protected function defaultDescription()
    {
        return 'Pull remote volume backups from cloud';
    }
}