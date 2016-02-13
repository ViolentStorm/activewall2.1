<?php

namespace frontend\assets;


use yii\web\AssetBundle;
use yii\web\View;

class AdAddAsset extends AssetBundle
{

    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $js = [
        'js/ad_add.js',
    ];

    public $css = [
        'css/ad_add.css',
    ];

    public $jsOptions = array(
        'position' => View::POS_END
    );

} 