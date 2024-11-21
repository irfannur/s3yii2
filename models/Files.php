<?php

namespace app\models;

use app\helpers\S3Helper;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * ContactForm is the model behind the contact form.
 */
class Files extends Model
{
    public $filename;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['filename'], 'required'],
            [['filename'], 'file', 'extensions' => ['pdf', 'png', 'jpg', 'jpeg'], 'maxSize' => 1024 * 1024 * 2],
        ];
    }

    public function doUploadMinio()
    {
        $file = UploadedFile::getInstance($this, 'filename');
        if ($file) {
            $dirTemp = Yii::getAlias('@runtime/s3temp');
            $filePath = $dirTemp . '/' . $file->name;

            // Simpan file ke path sementara
            if ($file->saveAs($filePath)) {
                $FileName = $file->name;
                $toS3Path = date('Ym') . '/' . $FileName;
                $result = S3Helper::S3Save($toS3Path, $filePath, ['bucket' => 'inf-bucket']);
                unlink($filePath);

                return $result;
            } else {
                return 'Gagal upload.';
            }
        }
    }

    
}
