<?php

namespace app\common\model;

require __DIR__ . '/../../../alipay/aop/AopCertClient.php';

class MyAopCertClient extends \AopCertClient
{
    /**
     * 从证书中提取序列号
     * @param $certData
     * @return string
     */
    public function myGetCertSN($certData)
    {
        $ssl = openssl_x509_parse($certData);
        $SN = md5(array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
        return $SN;
    }

    /**
     * 提取根证书序列号
     * @param $certData  根证书
     * @return string|null
     */
    public function myGetRootCertSN($certData)
    {
        $this->alipayRootCertContent = $certData;
        $array = explode("-----END CERTIFICATE-----", $certData);
        $SN = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if (strpos($ssl[$i]['serialNumber'], '0x') === 0) {
                $ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumber']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($SN == null) {
                    $SN = md5(array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {

                    $SN = $SN . "_" . md5(array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $SN;
    }

    /**
     * 从证书中提取公钥
     * @param $certData
     * @return mixed
     */
    public function myGetPublicKey($certData)
    {
        $pkey = openssl_pkey_get_public($certData);
        $keyData = openssl_pkey_get_details($pkey);
        $public_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $keyData['key']);
        $public_key = trim(str_replace('-----END PUBLIC KEY-----', '', $public_key));
        return $public_key;
    }
}