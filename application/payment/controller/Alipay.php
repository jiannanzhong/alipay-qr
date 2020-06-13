<?php


namespace app\payment\controller;

use app\common\model\ConfigM;
use PHPQRCode\QRcode;
use think\Controller;

class Alipay extends Controller
{
    protected $beforeActionList = [
        'checkQrLastCreate' => ['only' => 'qr'],
    ];

    public function qr($amount = 0.01)
    {
        $amount = number_format($amount + 0, 2);
        if ($amount <= 0 || $amount > 10) {
            $amount = 1;
        }
        $qr = \app\common\model\Alipay::payByQr($amount);
        if (is_array($qr)) {
            return json($qr);
        } else {
            QRcode::png($qr, false, 'L', 7);
            exit();
        }
    }

    public function index()
    {
        return 'alipay';
    }

    public function checkQrLastCreate()
    {
        $configM = new ConfigM();
        $qrLastCreate = (int)($configM->getConfigByName('last_qr_create'));
        if (($wait = 300 - time() + $qrLastCreate) > 0) {
            print "please try again after ${wait} seconds";
            exit();
        }
        $this->updateQrLastCreate();
    }

    public function updateQrLastCreate()
    {
        $configM = new ConfigM();
        $ret = $configM->updateConfig('last_qr_create', time());
    }

    public function check()
    {
        $ret = \app\common\model\Alipay::checkSign($_POST, request()->post());
        if ($ret['code'] === 0) {
            return 'success';
        } else {
            return 'fail';
        }
    }
}