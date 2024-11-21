<?php

namespace app\helpers;

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Yii;
use yii\helpers\Url;

class S3Helper
{

    // const MINIO_KEY = 'QdKd6KzFU1WXFNU0g8K1';
    // const MINIO_SECRET = 'HCX1fnQ5wor95DzdDH4JP6H8XfQ4pzXg5Y5nRUos';
    // const MINIO_ENDPOINT = 'https://play.min.io:9000';

    //local mac
    const MINIO_KEY = 'd2p09cwOzmThsIKURxUF';
    const MINIO_SECRET = 'ao8RkyQyJvq7EwaTkDEq9yQC61AxSOKumqr2i9eu';
    const MINIO_ENDPOINT = 'http://192.168.1.33:9000';

    public static function s3Client($options = [])
    {
        $version = isset($options['version']) ? $options['version'] : 'latest';
        $region = isset($options['region']) ? $options['region'] : 'us-east-1';
        $endpoint = isset($options['endpoint']) ? $options['endpoint'] : self::MINIO_ENDPOINT;
        $use_path_style_endpoint = isset($options['use_path_style_endpoint']) ? $options['use_path_style_endpoint'] : true;
        $key = isset($options['key']) ? $options['key'] : self::MINIO_KEY;
        $secret = isset($options['secret']) ? $options['secret'] : self::MINIO_SECRET;

        $s3 = new S3Client([
            'version' => $version,
            'region'  => $region,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => $use_path_style_endpoint,
            'credentials' => [
                'key' => $key,
                'secret' => $secret
            ],
        ]);
        return $s3;
    }

    public static function CreateBucket($bucketName)
    {
        /*
         * create bucket
         * huruf harus lowercase, Panjang 3-63 karakter
         * Dimulai dan diakhiri dengan angka atau huruf,
         * Nama bucket harus unik di seluruh S3
         */
        $s3Client = isset($options['s3Client']) ? $options['s3Client'] : self::s3Client();
        try {
            $result = $s3Client->createBucket([
                'Bucket' => $bucketName,
            ]);

            // Menunggu hingga bucket tersedia
            $s3Client->waitUntil('BucketExists', [
                'Bucket' => $bucketName,
            ]);

            // Mengatur kebijakan publik pada bucket
            $referer = Url::base(true) . '/*';
            $policy = [
                'Version' => '2012-10-17',
                'Statement' => [
                    [
                        'Sid' => 'AllowSpecificReferer',
                        'Effect' => 'Allow',
                        'Principal' => '*',
                        'Action' => 's3:GetObject',
                        'Resource' => 'arn:aws:s3:::' . $bucketName . '/*',
                        'Condition' => [
                            'StringLike' => [
                                'aws:Referer' => $referer,
                            ],
                        ],
                    ],
                ],
            ];

            $s3Client->putBucketPolicy([
                'Bucket' => $bucketName,
                'Policy' => json_encode($policy),
            ]);

            return 'Bucket created and set to public. Location: ' .
                $result['Location'] . '. Effective URI: ' .
                $result['@metadata']['effectiveUri'];
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    public static function ListBuckets()
    {
        //list bucket
        $s3 = S3Helper::S3Client();
        $list = $s3->listBuckets();

        $list = isset($list['Buckets']) ? $list['Buckets'] : [];
        $d = [];
        foreach ($list as $key => $perBucket) {
            $creationDate = $perBucket['CreationDate'];
            $formattedDate = $creationDate->format('Y-m-d H:i:s');

            $d[$key]['bucketname'] = $perBucket['Name'];
            $d[$key]['time'] = $formattedDate;
            $d[$key]['timezone'] = $creationDate->getTimezone()->getName();
        }
        return $d;
    }

    public static function S3Save($FileName, $filePath, $options = [])
    {
        //save upload file to cloud
        $s3Client = isset($options['s3Client']) ? $options['s3Client'] : null;
        $bucket = isset($options['bucket']) ? $options['bucket'] : 'infbucket';
        $msg = $stat = $url = null;

        $s3 = $s3Client ? $s3Client : self::s3Client();
        try {
            $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $FileName,
                'Body'   => fopen($filePath, 'r'),
                'ACL'    => 'public-read',
            ]);

            $url = self::getPresignedUrl($FileName, $bucket, 2);
            $stat = true;
            $msg = 'Success upload file to Cloud. Url <a href="' . $url . '" target="_blank">' . $url . '</a>';
        } catch (S3Exception $e) {
            $msg = "There was an error uploading the file. " . $e->getMessage() . "\n";
        }
        return ['stat' => $stat, 'msg' => $msg, 'url' => $url];
    }

    public static function actionGeturl($filename, $bucket = 'infbucket')
    {
        //get url file or preview harus public bucket
        $s3 = S3Helper::s3Client();
        $url = $s3->getObjectUrl($bucket, $filename);
        return $url;
    }

    public static function getPresignedUrl($filename, $bucket, $exp = '5')
    {
        $expires = "+$exp minutes";
        $s3 = S3Helper::s3Client();
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key'    => $filename,
        ]);

        $request = $s3->createPresignedRequest($cmd, $expires);
        $presignedUrl = (string) $request->getUri();
        return $presignedUrl;
    }

    public static function getHeadObject($FileName, $bucket)
    {
        $s3 = self::s3Client();
        try {
            // Eksekusi perintah headObject
            $result = $s3->headObject([
                'Bucket' => $bucket,
                'Key'    => $FileName,
            ])->toArray();

            // Ambil metadata header
            //$headers = $result['@metadata']['headers'] ?? [];

            // Konversi header ke array metadata
            // $array = [
            //     'path' => $FileName,
            //     'contentLength' => isset($headers['content-length']) ? intval($headers['content-length']) : null,
            //     'contentType' => $headers['content-type'] ?? null,
            //     'lastModified' => isset($headers['last-modified']) ? strtotime($headers['last-modified']) : null,
            // ];

            return $result;
        } catch (S3Exception $exception) {
            return [
                'error' => $exception->getMessage(),
            ];
        }
    }

    public static function delete($bucketName, $fileKey = null)
    {
        try {
            $s3 = self::s3Client();
            $msg = null;
            // Jika fileKey disediakan, hapus file
            if ($fileKey) {
                $s3->deleteObject([
                    'Bucket' => $bucketName,
                    'Key'    => $fileKey,
                ]);
                $msg = "File '$fileKey' berhasil dihapus dari bucket '$bucketName'.\n";
            } else {
                //Jika fileKey tidak diberikan, hapus semua file dalam bucket
                $objects = $s3->listObjectsV2([
                    'Bucket' => $bucketName,
                ]);

                if (!empty($objects['Contents'])) {
                    foreach ($objects['Contents'] as $object) {
                        $s3->deleteObject([
                            'Bucket' => $bucketName,
                            'Key'    => $object['Key'],
                        ]);
                        echo "File '{$object['Key']}' berhasil dihapus.\n";
                    }
                }

                // Hapus bucket
                $s3->deleteBucket(['Bucket' => $bucketName]);
                $msg .= " Bucket '$bucketName' berhasil dihapus.\n";

                // Tunggu hingga bucket benar-benar terhapus
                $s3->waitUntil('BucketNotExists', ['Bucket' => $bucketName]);
                $msg .= ", Konfirmasi: Bucket '$bucketName' sudah tidak ada.\n";
            }
        } catch (AwsException $e) {
            // Tangani error
            $msg = "Error: " . $e->getMessage() . "\n";
        }

        return $msg;
    }

    public static function flashSuccess($description)
    {
        return Yii::$app->session->setFlash('success', $description);
    }

    public static function flashWarning($description)
    {
        return Yii::$app->session->setFlash('warning', $description);
    }

    public static function flashFailed($description)
    {
        return Yii::$app->session->setFlash('danger', $description);
    }

    public static function flashInfo($description)
    {
        return Yii::$app->session->setFlash('info', $description);
    }
}
