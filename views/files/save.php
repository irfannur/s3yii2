<?php

use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\files $model */
/** @var ActiveForm $form */
?>
<div class="files-save">

    <div class="row">
        <div class="col-md-6">
            <h4>Upload file</h4>
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

            <?= $form->field($model, 'filename')->fileInput() ?>

            <div class="form-group">
                <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
            <hr>
            <?php 
                if ($url) {
                    echo Html::tag('h4', 'Preview Latest Uploaded.');
                    echo Html::img($url, ['width' => 200, 'height' => 200]);
                }
            ?>
        </div>

        <div class="col-md-6">
            
        </div>
    </div>


</div><!-- files-save -->