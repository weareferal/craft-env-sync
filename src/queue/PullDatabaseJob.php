<?php
namespace weareferal\sync\queue;

use craft\queue\BaseJob;

use weareferal\sync\Sync;

class PullDatabaseJob extends BaseJob
{
    public function execute($queue)
    {
        Sync::getInstance()->sync->actionPullDatabase();
    }

    protected function defaultDescription()
    {
        return 'Pull remote database backups from cloud';
    }
}