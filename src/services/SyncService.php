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
     * Create a new zipped archive of all volumes in the `storage/backup`
     * folder
     * 
     * @return bool If the backup was successful
     */
    public function createVolumesBackup(): bool 
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $source = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . strtolower(StringHelper::randomString(10));
        foreach ($volumes as $i=>$volume) {
            $tmp = $source . DIRECTORY_SEPARATOR . $volume->handle;
            FileHelper::copyDirectory($volume->rootPath, $tmp);
        }

        $dest = $this->getVolumeBackupPath();
        $zip = ZipHelper::recursiveZip($source, $dest);
        FileHelper::clearDirectory(Craft::$app->getPath()->getTempPath());

        return true;
    }

    /**
     * Restore a particular volume backup
     * 
     * @param string $filename The filename (not absolute path) of the 
     * zipped volumes archive to restore
     * @return bool If the restore was successful
     */
    public function restoreVolumesBackup($filename): bool
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $source = Craft::$app->getPath()->getDbBackupPath() . DIRECTORY_SEPARATOR . pathinfo($filename, PATHINFO_FILENAME) . '.zip';
        $tmp = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . strtolower(StringHelper::randomString(10));
        ZipHelper::unzip($source, $tmp);

        $folders = array_diff(scandir($tmp), array('.', '..'));
        foreach ($folders as $folder) {
            foreach ($volumes as $volume) {
                if ($folder == $volume->handle) {
                    $dest = $tmp . DIRECTORY_SEPARATOR . $folder;
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

        return true;
    }

    /**
     * Restore a particular database backup
     * 
     * @param string $filename The filename (not absolute path) of the 
     * zipped volumes archive to restore
     * @return bool If the restore was successful
     */
    public function restoreDatabaseBackup($filename): bool
    {
        $path = Craft::$app->getPath()->getDbBackupPath() . DIRECTORY_SEPARATOR . pathinfo($filename, PATHINFO_FILENAME) . '.sql';
        Craft::$app->getDb()->restore($path);
        return true;
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
        return $this->_encodeSelectOptions($filenames);
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
        return $this->_encodeSelectOptions($filenames);
    }

    /**
     * Return the full path to a new volume backup
     * 
     * @return string The absolute path to a new backup
     */
    private function getVolumeBackupPath(): string
    {
        $dir = Craft::$app->getPath()->getDbBackupPath();
        $filename = $this->getVolumeBackupName();
        $path = $dir . DIRECTORY_SEPARATOR . $filename . '.zip';
        return mb_strtolower($path);
    }

    /**
     * Return a unique filename for volumes zip file
     * 
     * Based on https://github.com/craftcms/cms/tree/master/src/db/Connection.php#L203
     * 
     * @return string
     */
    private function getVolumeBackupName(): string
    {
        $currentVersion = 'v' . Craft::$app->getVersion();
        $systemName = FileHelper::sanitizeFilename(Craft::$app->getInfo()->name, ['asciiOnly' => true]);
        $systemEnv = Craft::$app->env;
        $filename = ($systemName ? $systemName . '_' : '') . ($systemEnv ? $systemEnv . '_' : '') . gmdate('ymd_His') . '_' . strtolower(StringHelper::randomString(10)) . '_' . $currentVersion;
        return mb_strtolower($filename);
    }

    /**
     * Convert string command to ShellCommand
     * 
     * Base on https://github.com/craftcms/cms/tree/master/src/db/Connection.php#L534
     * 
     * @param string $command string The command to convert
     * @return ShellCommand
     */
    protected function _createShellCommand(string $command): ShellCommand
    {
        $shellCommand = new ShellCommand();
        $shellCommand->setCommand($command);
        if (!function_exists('proc_open') && function_exists('exec')) {
            $shellCommand->useExec = true;
        }
        return $shellCommand;
    }

    /**
     * Return a list of absolute paths to all volumes
     * 
     * @return string[]
     */
    protected function _getVolumePaths(): array {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $volumePaths = [];
        foreach ($volumes as $volume) {
            $volumePaths[] = $volume->rootPath;
        }
        return $volumePaths;
    }

    /**
     * Performs string interpolation on a command string
     * 
     * @param $command string the string command
     * @return $token string[] 
     */
    protected function _replaceCommandTokens($command, $tokens): string {
        return str_replace(array_keys($tokens), $tokens, $command);
    }

    /**
     * Encode options from filenames
     */
    private function _encodeSelectOptions($filenames): array {
        $options = [];
        foreach ($filenames as $i=>$filename) {
            preg_match('/(\d{6}\_\d{6})/', $filename, $matches);
            $datetime = date_create_from_format('ymd_Gis', $matches[0]);
            $datetime_str = $datetime->format('Y-m-d H:i:s');
            $options[$i] = ["label"=>$datetime_str, "value"=>$filename];
        }
        return array_reverse($options);
    }

    /**
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
