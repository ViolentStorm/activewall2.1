<?php

namespace frontend\controllers;

use common\models\activerecords\Client;
use common\models\activerecords\Message;
use common\models\activerecords\Payment;
use common\models\LoginForm;
use frontend\components\AdsGrid\AdsGrid;
use frontend\models\activerecords\aw\ClientSearch;
use frontend\models\AdsSearch;
use frontend\models\forms\AdForm;
use frontend\models\SignupForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;

class BoardController extends Controller
{

    public $defaultAction = 'ads';

    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function($rule, $action){
                            if (Yii::$app->user->isGuest)
                                return false;
                            $perms = Yii::$app->authManager->getPermissions();
                            $perm = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;
                            return isset($perms[$perm]) ? Yii::$app->user->can($perm) : true;
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => ['signin'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['logout'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionAds()
    {
        $this->view->title = 'AW 2.1 - Доска объявлений';
        Url::remember();

        if (!($search = Yii::$app->request->post('search', false)))
            if (($search = Yii::$app->session->get('search', false)))
                Yii::$app->session->set('search', null);


        return $this->render('ads', ['search' => $search]);
    }

    public function actionClients($edit = null)
    {
        if (!$edit) {
            Url::remember('', 'clients');
            $model = new Client();
        } elseif (!($model = Client::find()->where(['id' => $edit])->with('phones')->one()))
            $this->redirect(($url = Url::previous('clients')) ? $url : ['clients']);
        if ($model->load(Yii::$app->request->post()) && $model->save())
            $this->redirect(($url = Url::previous('clients')) ? $url : ['clients']);

        $s = new ClientSearch();
        $provider = $s->search(\Yii::$app->request->get());

        return $this->render('clients', ['provider' => $provider, 'searchModel' => $s, 'model' => $model]);
    }

    public function actionClient_remove()
    {
        $id = \Yii::$app->request->get('remove');
        Client::deleteAll(['id' => $id]);
        $this->redirect(['clients']);
    }

    public function actionAd_add()
    {
        $model = new AdForm();
        if ($model->load(\Yii::$app->request->post())) {
            $msg = $model->save();
            if ($msg){
                Yii::$app->user->identity->cookies->lastClientId = $model->clientId;
                Yii::$app->session->set('lastClient', $msg->client->id);
                Yii::$app->session->set('search', AdsGrid::SEARCH_CLIENT . ':' . $msg->client->name);
                $this->redirect(['board/ads']);
            }
        }
        //Yii::$app->session->remove('lastClient');
        if (!Yii::$app->session->get('lastClient')
            && ($cl = Client::find()
                ->where(['region_id' => Yii::$app->user->identity->currentRegion])
                ->andWhere(['like', 'name', 'безымянный'])
                ->one()))
        {
            Yii::$app->session->set('lastClient', $cl->id);
        }

        return $this->render('addAd', ['model' => $model,
            'currentNumber' => Payment::currentNumber() + ((date('N_H:i') > '5_16:00')?1:0)]);
    }

    public function actionAd_remove()
    {
        if (($id = \Yii::$app->request->get('id', false))) {
            $msg = Message::find()->where(['id' => $id])->with('client')->one();
            $msg->status = Message::STATUS_DELETE;
            $msg->update(false, ['status']);
            Yii::$app->session->set('search', AdsGrid::SEARCH_CLIENT . ':' . $msg->client->name);
        }
        $this->redirect(['ads']);
    }

    public function actionAd_edit()
    {
        $id = \Yii::$app->request->get('id');
        $model = AdForm::find($id);
        if(!$model)
            $this->redirect(Url::previous());
        if ($model->load(\Yii::$app->request->post()) && ($msg = $model->save())) {
            Yii::$app->session->set('search', AdsGrid::SEARCH_CLIENT . ':' . $msg->client->name);
            Yii::$app->user->identity->cookies->lastClientId = $model->clientId;
            $this->redirect(['board/ads']);
        }
        return $this->render('addAd', ['model' => $model,
            'currentNumber' => Payment::currentNumber() + ((date('N_H:i') > '5_16:00')?1:0)]);
    }

    public function actionEmptyads()
    {

        $msgs = Message::findBySql('
          select tm.*
          from '.Message::tableName().' tm
          where (
            select count(id) as cnt
            from '.Payment::tableName().' tp
            where tp.message_id = tm.id
          ) < 1
        ')->with('category')->all();

        return $this->render('emptyads', ['messages' => $msgs]);
    }

    public function actionProcessing()
    {

        $searchModel = new AdsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());

        return $this->render('processing', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    public function actionTest()
    {
        return $this->render('test');
    }

    public function actionSignin()
    {
        if (!\Yii::$app->user->isGuest)
            return $this->goHome();

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $this->view->title = 'Авторизация';
            return $this->renderPartial('signin', [
                'model' => $model,
            ]);
        }
    }

    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }


}
