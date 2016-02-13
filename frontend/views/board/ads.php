<?php

/* @var yii\web\View $this */
/* @var string $search */

use app\components\UserCookie;
use frontend\components\AdsGrid\AdsGrid;
use frontend\models\forms\SearchAdForm;
use yii\bootstrap\Nav;

\frontend\assets\AdsAsset::register($this);


?>

<style type="text/css">
    div.one_ad div.text-center {
        word-wrap: break-word;
    }
    mark {
        font-size: 10pt;
    }
</style>

<div class="page-header">
    <h1>Объявления <small>управление объявлениями</small></h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="list-group">
                <div class="list-group-item">

                    <?php $form = \yii\bootstrap\ActiveForm::begin(['options'=>['id' => 'searchForm']]); ?>
                    <?= $form->field(SearchAdForm::getInstance(), 'searchQuery', [
                        'inputTemplate' => '
                            <div class="input-group">
                                {input}
                                <span class="input-group-btn">
                                    <button
                                    data-toggle="tooltip" data-placement="top" data-original-title="Сбросить поиск"
                                     id="btnClearQuery" class="btn btn-default" type="button">
                                        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                                    </button>
                                    <a data-toggle="tooltip" data-placement="top" data-original-title="Поиск по Enter" id="btnSearchMain" href="'. AdsGrid::SEARCH_PHONE.'" class="btn btn-default btnSearchAds" type="button">Телефон</a>
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <span class="glyphicon glyphicon-search" aria-hidden="true"></span><span class="caret"></span>
                                    </button>
                                    <ul id="searchTypes" class="dropdown-menu" role="menu">
                                      <li><a class="btnSearchAds" href="'. AdsGrid::SEARCH_PHONE.'">Телефон</a></li>
                                      <li><a class="btnSearchAds" href="'. AdsGrid::SEARCH_NUMBER.'">Номер</a></li>
                                      <li><a class="btnSearchAds" href="'. AdsGrid::SEARCH_CLIENT.'">Клиент</a></li>
                                    </ul>
                                </span>
                            </div>
                            ',
                        'inputOptions' => ['id' => 'searchQuery', 'search' => $search],
                    ]); ?>
                    <?php $form->end(); ?>
                </div>
                <div class="list-group-item">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-addon" id="basic-addon1">Продлить <span id="selectedMessagesCounter" class="badge">0</span> объявлений</span>
                    <span class="input-group-btn">
                        <button id="btnActionMessages" data-toggle="tooltip" data-placement="top" title="Подтвердить" class="btn btn-default btn-block" type="button">
                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        </button>
                    </span>
                            </div>
                        </div>
                        <div class="col-md-4 col-md-offset-3">
                            <?php
                            if (Yii::$app->user->can('managePayment'))
                                echo \yii\helpers\Html
                                    ::a('Добавить объявление', ['board/ad_add'], ['class' => 'btn btn-primary btn-block']);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <?=
                Nav::widget([
                    'options' => [
                        'class' => 'nav-tabs nav-justified',
                        'id' => 'navAdsTime'
                    ],
                    'encodeLabels' => false,
                    'items' => [
                        [
                            'label' => '<span class="label label-bp">все</span>',
                            'url' => '#' . AdsGrid::TIME_ALL,
                            'active' => true
                        ],
                        [
                            'label' => '<span class="label label-bp">текущие</span>',
                            'url' => '#' . AdsGrid::TIME_CURRENT,
                        ],
                        [
                            'label' => '<span class="label label-bp">будущие</span>',
                            'url' => '#' . AdsGrid::TIME_FUTURE,
                        ],
                        [
                            'label' => '<span class="label label-bp">прошлые</span>',
                            'url' => '#' . AdsGrid::TIME_OLD,
                        ],
                        [
                            'label' => '<span class="label label-bp">Корзина</span>',
                            'url' => '#' . AdsGrid::TIME_NONE,
                        ],
                    ],
                ]);
                ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div id="ads_grid" class="col-md-12">
    </div>
</div>