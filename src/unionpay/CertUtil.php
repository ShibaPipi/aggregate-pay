<?php
namespace Shibapipi\Pay\unionpay;
use Bee\Logger;

const COMPANY = "中国银联股份有限公司";

// 内存泄漏问题说明：
//     openssl_x509_parse疑似有内存泄漏，暂不清楚原因，可能和php、openssl版本有关，估计有bug。
//     windows下试过php5.4+openssl0.9.8，php7.0+openssl1.0.2都有这问题。mac下试过也有问题。
//     不过至今没人来反馈过这个问题，所以不一定真有泄漏？或者因为增长量不大所以一般都不会遇到问题？
//     也有别人汇报过bug：https://bugs.php.net/bug.php?id=71519
//
// 替代解决方案：
//     方案1. 所有调用openssl_x509_parse的地方都是为了获取证书序列号，可以尝试把证书序列号+证书/key以别的方式保存，
//            从其他地方（比如数据库）读序列号，而不直接从证书文件里读序列号。
//     方案2. 代码改成执行脚本的方式执行，这样执行完一次保证能释放掉所有内存。
//     方案3. 改用下面的CertSerialUtil取序列号，
//            此方法仅用了几个测试和生产的证书做过测试，不保证没bug，所以默认注释掉了。如发现有bug或者可优化的地方可自行修改代码。
//            注意用了bcmath的方法，*nix下编译时需要 --enable-bcmath。http://php.net/manual/zh/bc.installation.php


class CertUtil{
    private static $signCerts = array();
    private static $verifyCerts510 = array();
    private static $config = array();

    public static function initConfig(){
        if (self::$config == null ) {
            self::$config = Helper::get();
        }
        return self::$config;
    }

    private static function initSignCert($certPath, $certPwd){
        $pkcs12certdata = file_get_contents ( $certPath );
        if($pkcs12certdata === false ){
        	return;
        }
        
        if(openssl_pkcs12_read ( $pkcs12certdata, $certs, $certPwd ) == FALSE ){
        	//$logger->LogInfo($certPath . ", pwd[" . $certPwd . "] openssl_pkcs12_read fail。");
        	return;
        }
        
        $cert = (object)[];
        $x509data = $certs ['cert'];

        if(!openssl_x509_read ( $x509data )){
        	//$logger->LogInfo($certPath . " openssl_x509_read fail。");
        }
        $certdata = openssl_x509_parse ( $x509data );
        $cert->certId = $certdata ['serialNumber'];

// 		$certId = CertSerialUtil::getSerial($x509data, $errMsg);
// 		if($certId === false){
//         	$logger->LogInfo("签名证书读取序列号失败：" . $errMsg);
//         	return;
// 		}
//         $cert->certId = $certId;
        
        $cert->key = $certs ['pkey'];
        $cert->cert = $x509data;

        Logger::custom_log("签名证书读取成功，序列号：" . $cert->certId);
        
        CertUtil::$signCerts[$certPath] = $cert;
    }

    public static function getSignKeyFromPfx($certPath=null, $certPwd=null)
    {
        if (!array_key_exists($certPath, CertUtil::$signCerts)) {
            self::initSignCert($certPath, $certPwd);
        }
        return CertUtil::$signCerts[$certPath] -> key;
    }

    public static function getSignCertIdFromPfx($certPath=null, $certPwd=null)
    {
        if (!array_key_exists($certPath, CertUtil::$signCerts)) {
            self::initSignCert($certPath, $certPwd);
        }
        return CertUtil::$signCerts[$certPath] -> certId;
    }
    public static function verifyAndGetVerifyCert($certBase64String)
    {
        self::$config = self::initConfig();
    	if (array_key_exists($certBase64String, CertUtil::$verifyCerts510)){
    		return CertUtil::$verifyCerts510[$certBase64String];
    	}
		if (self::$config['middleCertPath'] === null || self::$config['rootCertPath']=== null){
			Logger::custom_log("rootCertPath or middleCertPath is none, exit initRootCert");
			return null;
		}
		openssl_x509_read($certBase64String);
		$certInfo = openssl_x509_parse($certBase64String);
		$cn = CertUtil::getIdentitiesFromCertficate($certInfo);
		if(strtolower(self::$config["ifValidateCNName"]) == "true"){
			if (COMPANY != $cn){
				Logger::custom_log("cer owner is not CUP:" . $cn);
				return null;
			}
		} else if (COMPANY != $cn && "00040000:SIGN" != $cn){
            Logger::custom_log("cer owner is not CUP:" . $cn);
			return null;
		}
		
		$from = date_create ( '@' . $certInfo ['validFrom_time_t'] );
		$to = date_create ( '@' . $certInfo ['validTo_time_t'] );
		$now = date_create ( date ( 'Ymd' ) );
		$interval1 = $from->diff ( $now );
		$interval2 = $now->diff ( $to );
		if ($interval1->invert || $interval2->invert) {
            Logger::custom_log("signPubKeyCert has expired");
			return null;
		}
		 
		$result = openssl_x509_checkpurpose($certBase64String, X509_PURPOSE_ANY, array(self::$config["rootCertPath"], self::$config["middleCertPath"]));
		if($result === FALSE){
            Logger::custom_log("validate signPubKeyCert by rootCert failed");
			return null;
		} else if($result === TRUE){
			CertUtil::$verifyCerts510[$certBase64String] = $certBase64String;
    		return CertUtil::$verifyCerts510[$certBase64String];
		} else {
			Logger::custom_log("validate signPubKeyCert by rootCert failed with error");
			return null;
		}
    }
    
    public static function getIdentitiesFromCertficate($certInfo){
    	
    	$cn = $certInfo['subject'];
    	$cn = $cn['CN'];  	
    	$company = explode('@',$cn);
    	
    	if(count($company) < 3) {
    		return null;
    	} 
    	return $company[2];
    }
}



    