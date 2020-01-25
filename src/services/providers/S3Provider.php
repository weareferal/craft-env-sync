<?php
namespace weareferal\sync\services\providers;

use Composer\Util\Platform;
use Craft;
use craft\errors\ShellCommandException;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

use weareferal\sync\Sync;
use weareferal\sync\services\Syncable;
use weareferal\sync\services\SyncService;
use weareferal\sync\exceptions\ProviderException;



class S3Service extends SyncService implements Syncable {
    /**
     * Pull database backups from cloud to local backup folder
     * 
     * @return bool If process was successful
     */
    public function pullDatabase(): bool {
        try {
            return $this->pull("sql");
        } catch (AwsException $exception) {
            throw new ProviderException($this->createErrorMessage($exception));
        }
    }

    /**
     * Push local database backups from backup folder to S3
     * 
     * @return bool If process was successful
     */
    public function pushDatabase(): bool {
        try {
            return $this->push("sql");
        } catch (AwsException $exception) {
            throw new ProviderException($this->createErrorMessage($exception));
        }
    }

    /**
     * Pull local volume backups from cloud to local backup folder
     * 
     * @return bool If process was successful
     */
    public function pullVolumes(): bool {
        try {
            return $this->pull("zip");
        } catch (AwsException $exception) {
            throw new ProviderException($this->createErrorMessage($exception));
        }
    }

    /**
     * Push local volume backups from backup folder to S3
     * 
     * @return bool If process was successful
     */
    public function pushVolumes(): bool {
        try {
            return $this->push("zip");
        } catch (AwsException $exception) {
            throw new ProviderException($this->createErrorMessage($exception));
        }
    }

    /**
     * Underlying sync with AWS via AWS Cli
     * 
     * @param $extension string The extension to pull from aws
     * @return bool If process was successful
     */
    private function pull($extension): bool {
        $settings = Sync::getInstance()->settings;
        $s3BucketName = Craft::parseEnv($settings->s3BucketName);
        $s3BucketPrefix = Craft::parseEnv($settings->s3BucketPrefix);

        $client = $this->getS3Client();
        $backupPath = Craft::$app->getPath()->getDbBackupPath();

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
                            Craft::info("Skipping '" . $key . "' as file already exists locally", "env-sync");
                        }
                    } else {
                        Craft::info("Skipping '" . $key . "' as extension doesn't match", "env-sync");
                    }
                }
            }
        }

        return true;
    }

    private function push($extension): bool {
        $backupPath = Craft::$app->getPath()->getDbBackupPath();
        $settings = Sync::getInstance()->settings;
        $s3BucketName = Craft::parseEnv($settings->s3BucketName);

        $client = $this->getS3Client();
        foreach (glob($backupPath . DIRECTORY_SEPARATOR . '*.' . $extension) as $path) {
            $key = $this->getAWSKey($path);
            $exists = $client->doesObjectExist($s3BucketName, $key);
            if (! $exists) {
                $client->putObject([
                    'Bucket' => $s3BucketName,
                    'Key' => $key,
                    'SourceFile' => $path
                ]);
            } else {
                Craft::warning("File '" . $key . "' already exists on S3", "craft-sync");
            }
        }

        return true;
    }
    
    /**
     * 
     */
    private function getAWSKey($path): string {
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
    private function getS3Client() {
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

    private function createErrorMessage($exception) {
        Craft::$app->getErrorHandler()->logException($exception);
        $awsMessage = $exception->getAwsErrorMessage();
        $message = "AWS Error";
        if ($awsMessage) {
            if (strpos($awsMessage, "The request signature we calculated does not match the signature you provided") !== false) {
                $message = $message . ' (Check secret key)';
            } else {
                $message = $message . ' ("' . $awsMessage . '")';
            }
        } else {
            $awsMessage = $exception->getMessage();
            if (strpos($awsMessage, 'Are you sure you are using the correct region for this bucket') !== false) {
                $message = $message . " (Check region credentials)";
            } else {
                $message = $message . " (Check credentials)";
            }
        }
        return $message;
    }
}
