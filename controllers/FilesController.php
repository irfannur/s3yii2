<?php

namespace app\controllers;

use app\helpers\S3Helper;
use app\models\Files;
use Yii;
use yii\bootstrap4\Html;

class FilesController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionCreatebucket() {
        $bucketName = 'inf-bucket';
        $b = S3Helper::CreateBucket($bucketName);
        S3Helper::flashInfo($b);
        return $this->render('index');
    }

    public function actionListbucket() {
        $list = S3Helper::ListBuckets();
        echo '<pre>';print_r($list);die;
    }

    public function actionSave()
    {
        $model = new Files();
        if ($model->load(Yii::$app->request->post())) {
            $saved = $model->doUploadMinio();
            if ($saved['stat']) {
                S3Helper::flashSuccess($saved['msg']);
            } else {
                S3Helper::flashFailed($saved['msg']);
            }
        }

        return $this->render('save', [
            'model' => $model,
            'url' => isset($saved['url']) ? $saved['url'] : null,
        ]);
    }

    public function actionGeturl() {
        //get url file or preview
        $bucketName = 'inf-bbb-aaa';
        $fileName = '202411/Coloring police.jpg';
        $result = S3Helper::getPresignedUrl($fileName, $bucketName);
        echo Html::img($result);
    }

    public function actionGetmeta() {
        $bucketName = 'inf-bucket';
        $fileName = '202411/ang-1.png';
        $result = S3Helper::getHeadObject($fileName, $bucketName);
        echo '<pre>';print_r($result);die;
    }

    public function actionDelete() {
        $bucketName = 'inf-bucket';
        $fileName = '202411/ang-4.png';
        $result = S3Helper::delete($bucketName, $fileName);
        S3Helper::flashInfo($result);
        return $this->render('index');
    }

}
