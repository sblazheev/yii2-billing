<?php

namespace powerkernel\billing\models;

use common\Core;
use common\models\Account;
use Yii;

/**
 * This is the model class for collection "billing_address".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property integer|string $id_account
 * @property string $country
 * @property string $contact_name
 * @property string $street_address_1
 * @property string $street_address_2
 * @property string $city
 * @property string $state
 * @property string $zip_code
 * @property string $phone
 * @property string $status
 * @property integer|\MongoDB\BSON\UTCDateTime $created_at
 * @property integer|\MongoDB\BSON\UTCDateTime $updated_at
 */
class Address extends AddressBase
{
    const STATUS_ACTIVE = 'STATUS_ACTIVE'; //10
    const STATUS_INACTIVE = 'STATUS_INACTIVE'; //20

    /**
     * get status list
     * @param null $e
     * @return array
     */
    public static function getStatusOption($e = null)
    {
        $option = [
            self::STATUS_ACTIVE => Yii::$app->getModule('billing')->t('Active'),
            self::STATUS_INACTIVE => Yii::$app->getModule('billing')->t('Inactive'),
        ];
        if (is_array($e))
            foreach ($e as $i)
                unset($option[$i]);
        return $option;
    }

    /**
     * get status text
     * @return string
     */
    public function getStatusText()
    {
        $status = $this->status;
        $list = self::getStatusOption();
        if (!empty($status) && in_array($status, array_keys($list))) {
            return $list[$status];
        }
        return Yii::$app->getModule('billing')->t('Unknown');
    }


    /**
     * color status text
     * @return mixed|string
     */
    public function getStatusColorText()
    {
        $status = $this->status;
        if ($status == self::STATUS_ACTIVE) {
            return '<span class="label label-success">' . $this->statusText . '</span>';
        }
        if ($status == self::STATUS_INACTIVE) {
            return '<span class="label label-default">' . $this->statusText . '</span>';
        }
        return $this->statusText;
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        /* date */
        if (is_a($this, '\yii\mongodb\ActiveRecord')) {
            $date = [
                [['created_at', 'updated_at'], 'yii\mongodb\validators\MongoDateValidator']
            ];
        }
        else {
            $date = [
                [['created_at', 'updated_at'], 'integer']
            ];
        }

        /* account */
        if(Yii::$app->params['mongodb']['account']){
            $account=[
                [['id_account'], 'string']
            ];
        }
        else {
            $account=[
                [['id_account'], 'integer']
            ];
        }

        $default = [
            [['street_address_2', 'zip_code'], 'default', 'value'=>null],
            [['country', 'contact_name', 'street_address_1', 'city', 'phone'], 'required'],

            [['country', 'contact_name', 'street_address_1', 'street_address_2', 'city', 'state', 'zip_code', 'phone', 'status'], 'string'],
            [['street_address_2', 'zip_code', 'state'], 'safe']
        ];

        return array_merge($default, $date, $account);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'id_account' => Yii::$app->getModule('billing')->t('Account'),
            'country' => Yii::$app->getModule('billing')->t('Country'),
            'contact_name' => Yii::$app->getModule('billing')->t('Contact Name'),
            'street_address_1' => Yii::$app->getModule('billing')->t('Street Address 1'),
            'street_address_2' => Yii::$app->getModule('billing')->t('Street Address 2'),
            'city' => Yii::$app->getModule('billing')->t('City'),
            'state' => Yii::$app->getModule('billing')->t('State'),
            'zip_code' => Yii::$app->getModule('billing')->t('Zip Code'),
            'phone' => Yii::$app->getModule('billing')->t('Phone'),
            'status' => Yii::$app->getModule('billing')->t('Status'),
            'created_at' => Yii::$app->getModule('billing')->t('Created At'),
            'updated_at' => Yii::$app->getModule('billing')->t('Updated At'),
        ];
    }

    /**
     * @inheritdoc
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if($insert){
            if(empty($this->id_account)){
                $this->id_account=Yii::$app->user->id;
            }
        }
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    /**
     * @return \yii\db\ActiveQueryInterface
     */
    public function getAccount()
    {
        if (Yii::$app->params['mongodb']['account']) {
            return $this->hasOne(Account::class, ['_id' => 'id_account']);
        } else {
            return $this->hasOne(Account::class, ['id' => 'id_account']);
        }
    }

    /**
     * get user shipping addresses
     * @param $id_account
     * @return array
     */
    public static function getAddressDataList($id_account){
        $addresses=self::find()->where(['id_account'=>$id_account])->all();
        $data=[];
        foreach($addresses as $address){
            $data[(string)$address->id]='<strong>'.$address->contact_name.'</strong>';
            $data[(string)$address->id].='<br />'.$address->street_address_1;
            if(!empty($address->street_address_2)){
                $data[(string)$address->id].='<br />'.$address->street_address_2;
            }
            $data[(string)$address->id].='<br />'.$address->city.', '.$address->state.' '.$address->zip_code;
            $data[(string)$address->id].='<br />'.Core::getCountryText($address->country);
            $data[(string)$address->id].='<br />'.$address->phone;
        }
        return $data;
    }

}
