<?php
namespace weareferal\sync\services;

use yii\base\Component;
use Craft;
use Craft\helpers\FileHelper;
use Craft\helpers\StringHelper;

use mikehaertl\shellcommand\Command as ShellCommand;
use weareferal\sync\services\providers\S3Service;
use weareferal\sync\helpers\ZipHelper;

interface Syncable
{
    public function pullDatabase();
    public function pushDatabase();
    public function pushVolumes();
    public function pullVolumes();
}

class SyncService extends Component
{
    /**
     * Create a SQL database dump to our backup folder
     * 
     * NOTE: Craft already has a native function for this operation, but 
     * we want to provide a little bit more control over the filename so we
     * piggy-back on the existing backup methods from 
     * 
     * https://github.com/craftcms/cms/blob/master/src/db/Connection.php
     */
    public function createDatabaseBackup() {
        $backupPath = $this->getBackupPath('sql');
        Craft::$app->getDb()->backupTo($backupPath);
    }

    /**
     * Create a zipped archive of all volumes to our backup folder
     * 
     */
    public function createVolumesBackup() 
    {
        $backupPath = $this->getBackupPath('zip');
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $tmpDirName = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . strtolower(StringHelper::randomString(10));

        foreach ($volumes as $i=>$volume) {
            $tmpPath = $tmpDirName . DIRECTORY_SEPARATOR . $volume->handle;
            if (file_exists($volume->rootPath)) {
                FileHelper::copyDirectory($volume->rootPath, $tmpPath);
            } else {
                Craft::info("Volume path doesn't exist: " . $volume->rootPath, "env-sync");
            }
        }

        $zip = ZipHelper::recursiveZip($tmpDirName, $backupPath);

        FileHelper::clearDirectory(Craft::$app->getPath()->getTempPath());
    }

    /**
     * Restore a particular volume backup
     * 
     * @param string $filename The filename (not absolute path) of the 
     * zipped volumes archive to restore
     */
    public function restoreVolumesBackup($filename)
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $backupPath = Craft::$app->getPath()->getDbBackupPath() . DIRECTORY_SEPARATOR . pathinfo($filename, PATHINFO_FILENAME) . '.zip';
        $tmpDir = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . strtolower(StringHelper::randomString(10));

        ZipHelper::unzip($backupPath, $tmpDir);

        $folders = array_diff(scandir($tmpDir), array('.', '..'));
        foreach ($folders as $folder) {
            foreach ($volumes as $volume) {
                if ($folder == $volume->handle) {
                    $dest = $tmpDir . DIRECTORY_SEPARATOR . $folder;
                    if (! file_exists($volume->rootPath)) {
                        FileHelper::createDirectory($volume->rootPath);
                    } else {
                        FileHelper::clearDirectory($volume->rootPath);
                    }
                    FileHelper::copyDirectory($dest, $volume->rootPath);
                }
            }
        }

        FileHelper::clearDirectory(Craft::$app->getPath()->getTempPath());
    }

    /**
     * Restore a particular database backup
     * 
     * @param string $filename The filename (not absolute path) of the 
     * zipped volumes archive to restore
     */
    public function restoreDatabaseBackup($filename)
    {
        $path = Craft::$app->getPath()->getDbBackupPath() . DIRECTORY_SEPARATOR . pathinfo($filename, PATHINFO_FILENAME) . '.sql';
        Craft::$app->getDb()->restore($path);
    }

    /**
     * Return available database backups
     * 
     * @return string[] A list of filename ready for an HTML select
     */
    public function getDbBackupOptions(): array
    {
        $path = Craft::$app->getPath()->getDbBackupPath();
        $filenames = preg_grep('~\.sql$~', scandir($path));
        return $this->encodeSelectOptions($filenames);
    }

    /**
     * Return the absolute path to a new backup file
     * 
     * @return string The absolute path to a new backup
     */
    private function getBackupPath($extension): string
    {
        $dir = Craft::$app->getPath()->getDbBackupPath();
        $filename = $this->getBackupFilename();
        $path = $dir . DIRECTORY_SEPARATOR . $filename . '.' . $extension;
        return mb_strtolower($path);
    }

    /**
     * Return a unique filename for new backup files
     * 
     * Based on https://github.com/craftcms/cms/tree/master/src/db/Connection.php#L203
     * 
     * @return string
     */
    private function getBackupFilename(): string
    {
        $currentVersion = 'v' . Craft::$app->getVersion();
        $systemName = FileHelper::sanitizeFilename(Craft::$app->getInfo()->name, ['asciiOnly' => true]);
        $systemEnv = Craft::$app->env;
        $filename = ($systemName ? $systemName . '_' : '') . ($systemEnv ? $systemEnv . '_' : '') . gmdate('ymd_His') . '_' . strtolower(StringHelper::randomString(10)) . '_' . $currentVersion;
        return mb_strtolower($filename);
    }

    /**
     * Return available volume backups
     * 
     * @return string[] A list of filename ready for an HTML select
     */
    public function getVolumeBackupOptions(): array
    {
        $path = Craft::$app->getPath()->getDbBackupPath();
        $filenames = preg_grep('~\.zip$~', scandir($path));
        return $this->encodeSelectOptions($filenames);
    }

    /**
     * Convert filenames into an array of date times and human readable labels
     * 
     * @param string[] A list of filenames
     * @return array An array containing:
     *  array[0] an index
     *  array[1] the full filename
     *  array[2] the datetime object
     *  array[3] a human-readable label
     */
    private function parseDates($filenames) {
        $dates = [];

        // Regex to capture/match:
        // - Site name
        // - Environment (optional and captured)
        // - Date (required and captured)
        // - Random string
        // - Version
        // - Extension
        $regex = '/^(?:[a-zA-Z0-9-]+)\_(?:([a-zA-Z]+)\_)?(\d{6}\_\d{6})\_(?:[a-zA-Z0-9]+)\_(?:[v0-9\.]+)\.(?:\w{2,10})$/';

        foreach ($filenames as $i=>$filename) {
            preg_match($regex, $filename, $matches);
            $env = $matches[1];
            $date = $matches[2];

            $datetime = date_create_from_format('ymd_Gis', $date);
            $label = $datetime->format('Y-m-d H:i:s');
            if ($env) {
                $label = $label  . ' (' . $env . ')';
            }
            array_push($dates, [$i, $filename, $datetime, $label]);
        }

        uasort($dates, function($a, $b) {
            return $a[2] <=> $b[2];
        });

        return $dates;
    }

    /**
     * Create an array of human-readable select options from backup files
     */
    private function encodeSelectOptions($filenames): array {
        $options = [];
        $dates = $this->parseDates($filenames);

        foreach ($dates as $t) {
            $options[$t[0]] = ["label"=>$t[3], "value"=>$t[1]];
        }

        return array_reverse($options);
    }

    /**
     * Delete old backups
     */
    public function prune() {

    }

    /**s
     * Factory method to return appropriate class depending on provider
     * setting
     * 
     * @return $provider class
     */
    public static function create($provider) {
        switch ($provider) {
            case "s3":
                return S3Service::class;
                break;
        } 
    }
}
