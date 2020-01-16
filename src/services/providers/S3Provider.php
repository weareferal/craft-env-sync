<?php
namespace weareferal\sync\services\providers;

use Composer\Util\Platform;
use Craft;
use craft\errors\ShellCommandException;
use Aws\S3\S3Client;

use weareferal\sync\Sync;
use weareferal\sync\services\Syncable;
use weareferal\sync\services\SyncService;



class S3Service extends SyncService implements Syncable {

    /**
     * Pull database backups from cloud to local backup folder
     * 
     * @return bool If process was successful
     */
    public function pullDatabase(): bool {
        return $this->_pull("sql");
    }

    /**
     * Push local database backups from backup folder to S3
     * 
     * @return bool If process was successful
     */
    public function pushDatabase(): bool {
        return $this->_push("sql");
    }

    /**
     * Pull local volume backups from cloud to local backup folder
     * 
     * @return bool If process was successful
     */
    public function pullVolumes(): bool {
        return $this->_pull("zip");
    }

    /**
     * Push local volume backups from backup folder to S3
     * 
     * @return bool If process was successful
     */
    public function pushVolumes(): bool {
        return $this->_push("zip");
    }

    /**
     * Underlying sync with AWS via AWS Cli
     * 
     * @param $extension string The extension to pull from aws
     * @return bool If process was successful
     */
    private function _pull($extension): bool {
        $settings = Sync::getInstance()->settings;
        $s3BucketName = Craft::parseEnv($settings->s3BucketName);
        $s3BucketPrefix = Craft::parseEnv($settings->s3BucketPrefix);
    
        $client = $this->_getS3Client();
        $backupPath = Craft::$app->getPath()->getDbBackupPath();
        try {
            $results = $client->getPaginator('ListObjectsV2', [
                'Bucket' => $s3BucketName,
                'Prefix' => $s3BucketPrefix,
                'MaxKeys' => 1000
            ]);

            foreach ($results as $result) {
                if ($result['KeyCount'] > 0)  {
                    foreach ($result['Contents'] as $object) {
                        $key = $object['Key'];
                        $file_info = pathinfo($key);
                        if ($file_info['extension'] == $extension) {
                            $path = $backupPath . DIRECTORY_SEPARATOR . $file_info['basename'];
                            if (! file_exists($path)) {
                                $client->getObject([
                                    'Bucket' => $s3BucketName,
                                    'Key' => $key,
                                    'SaveAs' => $path
                                ]);
                            } else {
                                Craft::info("Skipping '" . $key . "' as file already exists locally", "weareferal-sync");
                            }
                        } else {
                            Craft::info("Skipping '" . $key . "' as extension doesn't match", "weareferal-sync");
                        }
                        
                    }
                }
            }
        } catch (Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return false;
        }

        return true;
    }

    private function _push($extension): bool {
        $backupPath = Craft::$app->getPath()->getDbBackupPath();
        $settings = Sync::getInstance()->settings;
        $s3BucketName = Craft::parseEnv($settings->s3BucketName);
        $s3 = $this->_getS3Client();

        try {
            foreach (glob($backupPath . DIRECTORY_SEPARATOR . '*.' . $extension) as $path) {
                $key = $this->_getAWSKey($path);
                $exists = $s3->doesObjectExist($s3BucketName, $key);
                if (! $exists) {
                    $s3->putObject([
                        'Bucket' => $s3BucketName,
                        'Key' => $key,
                        'SourceFile' => $path
                    ]);
                } else {
                    Craft::warning("File '" . $key . "' already exists on S3", "craft-sync");
                }
            }
        } catch (Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return false;
        }

        return true;
    }

    /**
     * Return the full AWS path for backups
     * 
     * @return string The path
     */
    private function _getAWSBucketPath(): string {
        $settings = Sync::getInstance()->settings;
        $path = " s3://{s3BucketName}";
        if (strlen($settings->s3BucketPrefix) > 0) {
            $path  = $path . DIRECTORY_SEPARATOR . '{s3BucketPrefix}';
        }
        return $path;
    }
    
    /**
     * 
     */
    private function _getAWSKey($path): string {
        $settings = Sync::getInstance()->settings;
        $s3BucketPrefix = Craft::parseEnv($settings->s3BucketPrefix);

        $filename = basename($path);
        if ($s3BucketPrefix) {
            return $s3BucketPrefix . DIRECTORY_SEPARATOR . $filename;
        }
        return $filename;
    }

    /**
     * 
     */
    private function _getS3Client() {
        $settings = Sync::getInstance()->settings;
        $s3AccessKey = Craft::parseEnv($settings->s3AccessKey);
        $s3SecretKey = Craft::parseEnv($settings->s3SecretKey);
        $s3RegionName = Craft::parseEnv($settings->s3RegionName);
        return S3Client::factory([
            'credentials' => array(
                'key'    => $s3AccessKey,
                'secret' => $s3SecretKey
            ),
            'version' => 'latest',
            'region'  => $s3RegionName
        ]);
    }
}
