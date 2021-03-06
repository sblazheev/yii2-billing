<?php
/**
 * @author Harry Tang <harry@powerkernel.com>
 * @link https://powerkernel.com
 * @copyright Copyright (c) 2017 Power Kernel
 */

namespace powerkernel\billing\controllers;

use common\components\BackendFilter;
use common\components\MainController;
use powerkernel\billing\components\CurrencyLayer;
use powerkernel\billing\models\BitcoinAddress;
use powerkernel\billing\models\CouponForm;
use powerkernel\billing\models\Item;
use Yii;
use powerkernel\billing\models\Invoice;
use powerkernel\billing\models\InvoiceSearch;
use yii\filters\AccessControl;
use yii\httpclient\Client;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * InvoiceController implements the CRUD actions for Invoice model.
 */
class InvoiceController extends MainController
{

    public $defaultAction = 'manage';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'roles' => ['admin'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['manage', 'show', 'pay', 'convert'],
                        'roles' => ['@'],
                        'allow' => true,
                    ],
                ],
            ],
            'backend' => [
                'class' => BackendFilter::class,
                'actions' => [
                    'index',
                    'view',
                    'create',
                    'update',
                    'discount'
                ],
            ],
        ];
    }

    /**
     * Lists all Invoice models.
     * @return mixed
     */
    public function actionIndex()
    {
        $this->view->title = Yii::t('billing', 'Invoices');
        $searchModel = new InvoiceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * User view invoices
     * @return string
     */
    public function actionManage()
    {
        $this->layout = Yii::$app->view->theme->basePath . '/account.php';
        $this->view->title = Yii::t('billing', 'My Invoices');
        $searchModel = new InvoiceSearch(['manage' => true]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('manage', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Invoice model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $info['billing'] = $model->loadInfo();
        $info['shipping'] = $model->loadShippingInfo();
        //$info=empty($model->info)?BillingInfo::getInfo($model->id_account):json_decode($model->info, true);

        /* metaData */
        //$title=$model->title;
        $this->view->title = Yii::$app->getModule('billing')->t('Invoice #{ID}', ['ID' => $model->id_invoice]);
        //$keywords = $model->tags;
        //$description = $model->desc;
        //$metaTags[]=['name'=>'keywords', 'content'=>$keywords];
        //$metaTags[]=['name'=>'description', 'content'=>$description];
        /* Facebook */
        //$metaTags[]=['property' => 'og:title', 'content' => $title];
        //$metaTags[]=['property' => 'og:description', 'content' => $description];
        //$metaTags[]=['property' => 'og:type', 'content' => '']; // article, product, profile etc
        //$metaTags[]=['property' => 'og:image', 'content' => '']; //best 1200 x 630
        //$metaTags[]=['property' => 'og:url', 'content' => ''];
        //$metaTags[]=['property' => 'fb:app_id', 'content' => ''];
        //$metaTags[]=['property' => 'fb:admins', 'content' => ''];
        /* Twitter */
        //$metaTags[]=['name'=>'twitter:card', 'content'=>'summary_large_image']; // summary, summary_large_image, photo, gallery, product, app, player
        //$metaTags[]=['name'=>'twitter:site', 'content'=>Setting::getValue('twitterSite')];
        // Can skip b/c we already have og
        //$metaTags[]=['name'=>'twitter:title', 'content'=>$title];
        //$metaTags[]=['name'=>'twitter:description', 'content'=>$description];
        //$metaTags[]=['name'=>'twitter:image', 'content'=>''];
        //$metaTags[]=['name'=>'twitter:data1', 'content'=>''];
        //$metaTags[]=['name'=>'twitter:label1', 'content'=>''];
        //$metaTags[]=['name'=>'twitter:data2', 'content'=>''];
        //$metaTags[]=['name'=>'twitter:label2', 'content'=>''];
        /* jsonld */
        //$imageObject=$model->getImageObject();
        //$jsonLd = (object)[
        //    '@type'=>'Article',
        //    'http://schema.org/name' => $model->title,
        //    'http://schema.org/headline'=>$model->desc,
        //    'http://schema.org/articleBody'=>$model->content,
        //    'http://schema.org/dateCreated' => Yii::$app->formatter->asDate($model->created_at, 'php:c'),
        //    'http://schema.org/dateModified' => Yii::$app->formatter->asDate($model->updated_at, 'php:c'),
        //    'http://schema.org/datePublished' => Yii::$app->formatter->asDate($model->published_at, 'php:c'),
        //    'http://schema.org/url'=>Yii::$app->urlManager->createAbsoluteUrl($model->viewUrl),
        //    'http://schema.org/image'=>(object)[
        //        '@type'=>'ImageObject',
        //        'http://schema.org/url'=>$imageObject['url'],
        //        'http://schema.org/width'=>$imageObject['width'],
        //        'http://schema.org/height'=>$imageObject['height']
        //    ],
        //    'http://schema.org/author'=>(object)[
        //        '@type'=>'Person',
        //        'http://schema.org/name' => $model->author->fullname,
        //    ],
        //    'http://schema.org/publisher'=>(object)[
        //    '@type'=>'Organization',
        //    'http://schema.org/name'=>Yii::$app->name,
        //   'http://schema.org/logo'=>(object)[
        //        '@type'=>'ImageObject',
        //       'http://schema.org/url'=>Yii::$app->urlManager->createAbsoluteUrl(Yii::$app->homeUrl.'/images/logo.png')
        //    ]
        //    ],
        //    'http://schema.org/mainEntityOfPage'=>(object)[
        //        '@type'=>'WebPage',
        //        '@id'=>Yii::$app->urlManager->createAbsoluteUrl($model->viewUrl)
        //    ]
        //];

        /* OK */
        //$data['title']=$title;
        //$data['metaTags']=$metaTags;
        //$data['jsonLd']=$jsonLd;
        //$this->registerMetaTagJsonLD($data);


        return $this->render('view', [
            'model' => $model,
            'info' => $info
        ]);
    }

    /**
     * users view their invoices
     * @param $id
     * @param null $cancel
     * @return string
     * @throws ForbiddenHttpException
     */
    public function actionShow($id, $cancel = null)
    {
        $model = $this->findModel($id);
        if (Yii::$app->user->can('admin') || Yii::$app->user->can('updateOwnItem', ['model' => $model])) {
            if (!empty($cancel) && $cancel == 'true') {
                Yii::$app->session->setFlash('warning', Yii::$app->getModule('billing')->t('Payment cancelled.'));
            }
            $info['billing'] = $model->loadInfo();
            //$info['shipping'] = $model->loadShippingInfo();
            //$info=empty($model->info)?BillingInfo::getInfo($model->id_account):json_decode($model->info, true);

            /* metaData */
            //$title=$model->title;
            $this->layout = Yii::$app->view->theme->basePath . '/account.php';
            $this->view->title = Yii::$app->getModule('billing')->t('Invoice #{ID}', ['ID' => $model->id_invoice]);

            /* coupon */
            $coupon=null;
            if(empty($model->coupon)){
                $coupon=new CouponForm();
                $coupon->invoice=$model;
                if ($coupon->load(Yii::$app->request->post()) && $coupon->validate()) {
                    if($model->applyCoupon($coupon->coupon)){
                        Yii::$app->session->setFlash('success', Yii::$app->getModule('billing')->t('Promotion has been applied!'));
                        return $this->refresh();
                    }
                }
            }


            return $this->render('view', [
                'model' => $model,
                'info' => $info,
                'coupon'=>$coupon
            ]);
        } else throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to perform this action.'));

    }

    /**
     * Creates a new Invoice model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $this->view->title = Yii::t('billing', 'Create Invoice');
        $model = new Invoice();
        $model->currency = 'USD';

        /* auto */
        if ($model->save()) {
            $rand = rand(1, 9);
            for ($i = 0; $i < $rand; $i++) {
                $item = new Item();
                $item->id_invoice = $model->id;
                $item->name = 'This is item name ' . rand(10, 999);
                $item->price = rand(10, 50) * 1;
                $item->quantity = rand(1, 5);
                $item->save();
                unset($item);
            }
            /* send */
            $i=Invoice::findOne($model->id);
            $i->sendMail();
        }

        return $this->redirect(['index']);

    }

    /**
     * Updates an existing Invoice model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $this->view->title = Yii::t('billing', 'Update Invoice');
        $model = $this->findModel($id);
        $model->payment_date_picker = empty($model->paymentDate)?null:strtotime($model->paymentDate);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => (string)$model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * convert currency
     * @param $id
     * @param $currency
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionConvert($id, $currency){
        $model = $this->findModel($id);
        if (Yii::$app->user->can('updateOwnItem', ['model' => $model])) {
            if ($model->currency != $currency) {
                if (!$model->convertCurrencyTo($currency)) {
                    Yii::$app->session->setFlash('error', Yii::$app->getModule('billing')->t('Cannot convert your invoice to {CURRENCY} currency!', ['CURRENCY'=>$currency]));
                }
            }
            return $this->redirect(Yii::$app->urlManager->createUrl(['billing/invoice/show', 'id' => $id]));
        }
        else throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to perform this action.'));
    }

    /**
     * user click pay button
     * @param $id
     * @param $method
     * @return \yii\web\Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionPay($id, $method)
    {
        $model = $this->findModel($id);
        if (Yii::$app->user->can('updateOwnItem', ['model' => $model])) {
            if ($model->status == Invoice::STATUS_PENDING) {
                if ($method == 'paypal') {
                    /* convert */
                    if ($model->currency != 'USD') {
                        if (!$model->convertCurrencyTo('USD')) {
                            Yii::$app->session->setFlash('error', Yii::$app->getModule('billing')->t('Only accept payments in USD'));
                            return $this->redirect(Yii::$app->urlManager->createUrl(['billing/invoice/show', 'id' => $id]));
                        }
                    }
                    return $this->redirect(Yii::$app->urlManager->createUrl(['billing/paypal/create', 'id' => (string)$model->id]));
                }
                if ($method == 'bitcoin') {
                    /* is btc enabled ?*/
                    $xpub = \powerkernel\billing\models\Setting::getValue('btcWalletXPub');
                    if(empty($xpub)){
                        Yii::$app->session->setFlash('error', Yii::$app->getModule('billing')->t('We can not process your payment right now.'));
                        return $this->redirect(Yii::$app->urlManager->createUrl(['billing/invoice/show', 'id' => $id]));
                    }
                    /* generate make sure always have new addresses */
                    BitcoinAddress::generate();
                    /* if an btc address assigned to this invoice, use that address (assigned address no payment with x days will be released by cronjob */
                    $address=BitcoinAddress::find()->where(['id_invoice'=>$model->id_invoice, 'tx_id'=>null])->one();
                    if(empty($address)){
                        $address=BitcoinAddress::find()->where(['status'=>BitcoinAddress::STATUS_NEW])->orderBy(['created_at'=>SORT_ASC])->one();
                        $address->id_invoice=$model->id_invoice;
                        $address->id_account=Yii::$app->user->id;
                        $address->status=BitcoinAddress::STATUS_USED;
                    }
                    /* set btc amount */
                    if($model->currency!='USD'){
                        $usd=(new CurrencyLayer())->convertToUSD($model->currency, $model->total);
                    }
                    else {
                        $usd=$model->total;
                    }
                    $client = new Client(['baseUrl' => 'https://blockchain.info']);
                    $response = $client->get('tobtc', ['currency'=>'USD', 'value'=>round($usd,2)])->send();
                    $btc=$response->getContent();
                    if(empty($btc)){
                        return $this->redirect($model->getInvoiceUrl());
                    }
                    $address->request_balance=(float)str_replace(',', '', $btc);
                    $address->save();
                    /* session */
                    $time=time();
                    $s=md5($time);
                    Yii::$app->session[$s]=['invoice'=>$id, 'address'=>$address->id, 'time'=>$time];
                    return $this->redirect(Yii::$app->urlManager->createUrl(['billing/bitcoin/payment', 's' => $s]));
                }
                /* return view if no payment method*/
                return $this->redirect(Yii::$app->urlManager->createUrl(['billing/invoice/show', 'id' => $id]));
            } else {
                Yii::$app->session->setFlash('error', Yii::$app->getModule('billing')->t('We can not process your payment right now.'));
                return $this->redirect(Yii::$app->urlManager->createUrl(['billing/invoice/show', 'id' => $id]));
            }
        }
        else throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to perform this action.'));
    }

    /**
     * add discount
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDiscount($id)
    {
        $model = $this->findModel($id);
        $amount = Yii::$app->request->post('discountAmount');
        if (is_numeric($amount) && $amount > 0) {
            /* add discount item */
            $item = new Item();
            $item->name = Yii::$app->getModule('billing')->t('Discount');
            $item->quantity = 1;
            $item->price = $amount * -1;
            $item->id_invoice = $model->id_invoice;
            if($item->save()){
                Yii::$app->session->setFlash('success', Yii::$app->getModule('billing')->t('Discount amount added.'));
            }
            else {
                Yii::$app->session->setFlash('error', Yii::$app->getModule('billing')->t('An error occurred when processing your request.'));
            }

        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * cancel invoice
     * @param $id
     * @return string
     */
    public function actionCancel($id){
        $model=$this->findModel($id);
        $model->cancel();
        Yii::$app->session->setFlash('success', Yii::$app->getModule('billing')->t('Invoice has been canceled.'));
        return $this->redirect(Yii::$app->urlManager->createUrl(['billing/invoice/view', 'id'=>(string)$model->id]));
    }

    /**
     * Finds the Invoice model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Invoice the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Invoice::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }

}
