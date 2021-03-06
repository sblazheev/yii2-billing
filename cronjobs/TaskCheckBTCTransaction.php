<?php
/**
 * @var \powerkernel\scheduling\Schedule $schedule
 */

use common\Core;
use powerkernel\billing\models\BitcoinAddress;

$local = Core::isLocalhost();
$time = $local ? '* * * * *' : '*/15 * * * *';

$schedule->call(function (\yii\console\Application $app) {

    /* update confirmations */
    $addresses = BitcoinAddress::find()->where(['status'=>BitcoinAddress::STATUS_UNCONFIRMED])->all();

    if ($addresses) {
        $obj = [];
        foreach ($addresses as $address) {
            $address->checkPayment();
            $obj[] = $address->address;
        }
        $output = $app->getModule('billing')->t('Addresses checked: {ADDR}', ['ADDR' => implode(', ', $obj)]);
    }

    /* Result */
    if (!empty($output)) {
        $log = new \common\models\TaskLog();
        $log->task = basename(__FILE__, '.php');
        $log->result = $output;
        $log->save();
    }


    /* delete old logs never bad */
    $period = 30* 24 * 60 * 60; // 30 day
    $point = time() - $period;
    if(Yii::$app->params['mongodb']['taskLog']){
        \common\models\TaskLog::deleteAll([
            'task'=>basename(__FILE__, '.php'),
            'created_at'=>['$lte', new \MongoDB\BSON\UTCDateTime($point*1000)]
        ]);
    }
    else {
        \common\models\TaskLog::deleteAll('task=:task AND created_at<=:point', [
            ':task' => basename(__FILE__, '.php'),
            ':point' => $point
        ]);
    }
})->cron($time);