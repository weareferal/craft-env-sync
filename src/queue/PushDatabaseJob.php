<?php
namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PushDatabaseJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->pushDatabase();
    }

    protected function defaultDescription()
    {
        return 'Push local database backups to cloud';
    }
}