<?php
/*
    This Version was updated by Frank Breitinger and is based on the original
    version of S.Körfgen.
        
    CertLE - A Let's Encrypt PHP Command Line ACME Client
	Copyright (C) 2016  S.Körfgen

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//Configuration:

$account_number = "1000000"; //Hosteurope Account

//End configuration

require 'ACMECert.php';

function exception_handler($e){
	$err=error_get_last();
	echo 'Error: '.$e->getMessage()."\n".($err['message']?$err['message']."\n":'');
	exit(1);	
}

set_exception_handler('exception_handler');

if (!extension_loaded('openssl')) {
	throw new Exception('PHP OpenSSL Extension is required but not installed/loaded !');
}

function get_args($offset) {
	global $argv;
	
	$lists=array(array(),array());
	foreach(array_slice($argv,$offset) as $idx=>$item){
		$lists[$idx%2==0?0:1][]=$item;
	}
	$out=array();
	foreach($lists[0] as $k=>$v){
		$o=isset($lists[1][$k])?$lists[1][$k]:null;
		$out[]=array(ltrim($v,'-')=>$o);
	}
	return $out;
}

$my_args = get_args(4);
$args=get_args(4);
$opts=array();
$webroot=null;
$domains=array();

foreach($args as $item){
    $value=reset($item);
    $arg=key($item);
    switch($arg){
        case 'webroot':
        case 'w':
            $webroot=rtrim($value,'/').'/';
        break;
        case 'domain':
        case 'd':
            if ($webroot===null) {
                throw new Exception('-w, --webroot must be specified in front of -d, --domain !');
            }
            //challenge is always http-01
            $tmp['challenge'] = 'http-01';
            $tmp['docroot'] = $webroot;
            $domains[$value]=$tmp;
        break;
        case 'csr':
        case 'cert':
        case 'chain':
        case 'fullchain':
            $opts[$arg]=$value;
        break;
        default:
            throw new Exception('Unknown Parameter: '.$arg);
        break;
    }
}

$ac=new ACMECert();

//generate account key if it not exists and register it with LE
if (!file_exists($argv[2]))
{
    print("Generating account file\n");
    print("---------------------------\n");

    $key=$ac->generateRSAKey(2048);
    file_put_contents($argv[2],$key);

    print("\nRegistering with LetsEncrypt\n");
    print("----------------------------\n");

    $ac->loadAccountKey('file://'.$argv[2]);
    $ret=$ac->register(true, $argv[1]);
    print_r($ret);
}

//generate private key if if not exists
if (!file_exists($argv[3]))
{
    print("Generating private_key file\n");
    print("---------------------------\n");

    $key=$ac->generateRSAKey(2048);
    file_put_contents($argv[3],$key);
    echo $key . "\n";
    echo "Done.";
}


//this is where we call the main functionality and the certificate is generated
$ac->loadAccountKey('file://'.$argv[2]);
$domain_config=$domains;

$handler=function($opts){
  $fn=$opts['config']['docroot'].$opts['key'];
  @mkdir(dirname($fn),0777,true);
  file_put_contents($fn,$opts['value']);
  return function($opts){
    unlink($opts['config']['docroot'].$opts['key']);
  };
};

$fullchain=$ac->getCertificateChain('file://'.$argv[3],$domain_config,$handler);
file_put_contents('fullchain.pem',$fullchain);


###sending it as email:
##########################################################
# sendCertificate
##########################################################
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include('PHPMailer.php');

function sendCertificate($myemail) {
    global $argv;
    global $account_number;

    $email = new PHPMailer();
    $email->setFrom($myemail); //Name is optional
    $email->Subject   = 'ssl certificate files';
    $email->Body      = "
https://kis.hosteurope.de/administration/webhosting/admin.php?menu=6&mode=ssl_list&wp_id=$account_number
Zertifikat: fullchain.pem
Key: cert_private_key.pem
";

    $email->addAddress($myemail);
    
    $file_to_attach = __DIR__ . '/'.$argv[3];
    $email->addAttachment($file_to_attach , $argv[3]);
    $file_to_attach = __DIR__ . '/fullchain.pem';
    $email->addAttachment($file_to_attach , 'fullchain.pem');
    
    $sent = $email->send();
    if($sent) {
        echo "sending certificates by mail ... OK\n";
    }
    else {
        echo "sending certificates by mail ... ERROR\n";
        print_r( error_get_last() );
    }
}
sendCertificate($argv[1]);


?>
