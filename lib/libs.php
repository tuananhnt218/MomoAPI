<?php
function get_microtime()
{
	return floor(microtime(true) * 1000);
}
function GET_rkey($length)
{
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$size = strlen($chars);
	$str = '';
	for ($i = 0; $i < $length; $i++) {
		$str .= $chars[rand(0, $size - 1)];
	}
	return $str;
}
function GET_imei()
{
	$time = md5(get_microtime());
	$text = substr($time, 0, 8) . '-';
	$text .= substr($time, 8, 4) . '-';
	$text .= substr($time, 12, 4) . '-';
	$text .= substr($time, 16, 4) . '-';
	$text .= substr($time, 17, 12);
	//$text = strtoupper($text);
	return $text;
}
function GET_secureID($length = 17)
{
    $characters = '0123456789abcde';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function encryptDecrypt($data, $key, $mode = 'ENCRYPT')
{
	if (strlen($key) < 32) {
		$key = str_pad($key, 32, 'x');
	}
	$key = substr($key, 0, 32);
	$iv = pack('C*', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	if ($mode === 'ENCRYPT') {
		return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv));
	} else {
		return openssl_decrypt(base64_decode($data), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
	}
}
function get_pHash($data)
{
	$pHashSyntax = $data['imei'] . '|' . $data['pwd'];
	return encryptDecrypt($pHashSyntax, encryptDecrypt($data['setupkey'], $data['ohash'], 'DECRYPT'));
}
function encodeRSA($content, $key)
{
	require_once('Crypt/RSA.php');
	$rsa = new Crypt_RSA();
	$rsa->loadKey($key);
	$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
	return base64_encode($rsa->encrypt($content));
}
function get_checksum($data, $type)
{
	$checkSumSyntax = $data['phone'] . get_microtime() . '000000' . $type . (get_microtime() / 1000000000000.0) . 'E12';
	return encryptDecrypt($checkSumSyntax, encryptDecrypt($data['setupkey'],  $data['ohash'], 'DECRYPT'));
}

function CurlMomo($url, $headers, $data)
{
	if (is_array($data)){
		$data = json_encode($data);
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function GET_OTP($data)
{
	$url = "https://api.momo.vn/backend/otp-app/public/SEND_OTP_MSG";
	$data_body = [
		'user' => $data['phone'],
		'msgType' => 'SEND_OTP_MSG',
		'cmdId' => get_microtime() . '000000',
		'lang' => "vi",
		'time' => get_microtime(),
		'channel' => "APP",
		'appVer' => 31090,
		'appCode' => "3.1.9",
		'deviceOS' => "ANDROID",
		"buildNumber" => 0,
		"appId" => "vn.momo.platform",
		'result' => true,
		'errorCode' => 0,
		'errorDesc' => '',
		'momoMsg' => [
			'_class' => 'mservice.backend.entity.msg.RegDeviceMsg',
			'number' => $data['phone'],
			'imei' => $data['imei'],
			'cname' => 'Vietnam',
			'ccode' => '084',
			'device' => 'G011A',
			'firmware' => '22',
			'hardware' => 'intel',
			'manufacture' => 'google',
			'csp' => 'Vinaphone',
			'icc' => '',
			'mcc' => '452',
			'device_os' => 'Android',
			'secure_id' => $data['secure_id'],
		],
		'extra' => [
			'action' => 'SEND',
			'rkey' => $data['rkey'],
			'AAID' => '',
			'IDFA' => '',
			'TOKEN' => '',
			'SIMULATOR' => 'true',
			'SECUREID'=> $data['secure_id'],
			'MODELID'=> 'google g011aintel41338011',
			'isVoice' => 'true',
			'REQUIRE_HASH_STRING_OTP' => true,
			'checkSum' => '',
		],
	];
	$header = array(
		'host' => 'api.momo.vn',
		'accept' => 'application/json',
		'app_version' => '31090',
		'app_code' => '3.1.9',
		'device_os' => 'ANDROID',
		'agent_id' => 'undefined',
		'sessionkey' => '',
		'sessionkey_v2' => '',
		'user_phone' => 'undefined',
		'lang' => 'vi',
		'authorization' => 'Bearer undefined',
		'x-firebase-appcheck' => 'error getAppCheckToken failed in last 5m',
		'msgtype' => 'SEND_OTP_MSG',
		'content-type' => 'application/json',
		'content-length' => '1055',
		'accept-encoding' => 'gzip',
		'user-agent' => 'okhttp/4.9.0',
	);
	$response = CurlMomo($url, $header, $data_body);
	return $response;
}

function CHECK_OTP($data)
{
	$data_body = [
		'user' => $data['phone'],
		'msgType' => "REG_DEVICE_MSG",
		'cmdId' => get_microtime() . '000000',
		'lang' => "vi",
		'time' => get_microtime(),
		'channel' => "APP",
		'appVer' => 31090,
		'appCode' => "3.1.9",
		'deviceOS' => "ANDROID",
		"buildNumber" => 0,
		"appId" => "vn.momo.platform",
		'result' => true,
		'errorCode' => 0,
		'errorDesc' => '',
		'momoMsg' => [
			'_class' => 'mservice.backend.entity.msg.RegDeviceMsg',
			'number' => $data['phone'],
			'imei' => $data['imei'],
			'cname' => 'Vietnam',
			'ccode' => '084',
			'device' => 'G011A',
			'firmware' => '22',
			'hardware' => 'intel',
			'manufacture' => 'google',
			'csp' => 'Vinaphone',
			'icc' => '',
			'mcc' => '452',
			'device_os' => 'Android',
			'secure_id' => $data['secure_id'],
		],
		'extra' => [
			'ohash' => hash('sha256', $data['phone'] . $data['rkey'] . $data['otp']),
			'AAID' => '',
			'IDFA' => '',
			'TOKEN' => '',
			'SIMULATOR' => 'false',
			'SECUREID' => $data['secure_id'],
			'MODELID' => 'google g011aintel41338011',
			'checkSum' => '',
		],
	];
	$url = "https://api.momo.vn/backend/otp-app/public/REG_DEVICE_MSG";
	$header = array(
		'host' => 'api.momo.vn',
		'accept' => 'application/json',
		'app_version' => '31090',
		'app_code' => '3.1.9',
		'device_os' => 'ANDROID',
		'agent_id' => 'undefined',
		'sessionkey' => '',
		'sessionkey_v2' => '',
		'user_phone' => 'undefined',
		'lang' => 'vi',
		'authorization' => 'Bearer undefined',
		'x-firebase-appcheck' => 'error getAppCheckToken failed in last 5m',
		'msgtype' => 'REG_DEVICE_MSG',
		'content-type' => 'application/json',
		'content-length' => '1014',
		'accept-encoding' => 'gzip',
		'user-agent' => 'okhttp/4.9.0',
	);
	$response = CurlMomo($url, $header, $data_body);
	return $response;
}

function LoginMomo($data)
{
	$data_body = [
		'user' => $data['phone'],
		'msgType' => 'USER_LOGIN_MSG',
		'pass' => $data['pwd'],
		'cmdId' => get_microtime() . '000000',
		'lang' => "vi",
		'time' => get_microtime(),
		'channel' => "APP",
		'appVer' => 31090,
		'appCode' => "3.1.9",
		'deviceOS' => "ANDROID",
		'buildNumber' => 0,
		'appId' => 'vn.momo.platform',
		'result' => true,
		'errorCode' => 0,
		'errorDesc' => '',
		'momoMsg' => [
			'_class' => 'mservice.backend.entity.msg.LoginMsg', 'isSetup' => false,
		],
		'extra' => [
			'pHash' => get_pHash($data),
			'AAID' => '',
			'IDFA' => '',
			'TOKEN' => '',
			'SIMULATOR' => 'true',
			'SECUREID' => $data['secure_id'],
			'MODELID' => 'google g011aintel41338011',
		],
	];
	$url = "https://owa.momo.vn/public/login";
	$header = array(
		'host' => 'api.momo.vn',
		'accept' => 'application/json',
		'app_version' => '31090',
		'app_code' => '3.1.9',
		'device_os' => 'ANDROID',
		'agent_id' => 'undefined',
		'sessionkey' => '',
		'sessionkey_v2' => '',
		'user_phone' => 'undefined',
		'lang' => 'vi',
		'authorization' => 'Bearer undefined',
		'x-firebase-appcheck' => 'error getAppCheckToken failed in last 5m',
		'msgtype' => 'REG_DEVICE_MSG',
		'content-type' => 'application/json',
		'content-length' => '1014',
		'accept-encoding' => 'gzip',
		'user-agent' => 'okhttp/4.9.0',
	);
	$response = CurlMomo($url, $header, $data_body);
	if (empty($response)) {
		return false;
	}
	return $response;
}

function HistoryMomo($data, $hours = 24)
{
	$requestkeyRaw = get_rkey(32);
	$requestkey = encodeRSA($requestkeyRaw, $data["encrypt_key"]);
	$data_post = [
		'user' => $data['phone'],
		'msgType' => 'QUERY_TRAN_HIS_MSG',
		'cmdId' => get_microtime() . '000000',
		'lang' => "vi",
		'channel' => "APP",
		'time' => get_microtime(),
		'appVer' => 31090,
		'appCode' => '3.1.9',
		'deviceOS' => "ANDROID",
		'result' => true,
		'errorCode' => 0,
		'errorDesc' => '',
		'extra' => [
			'checkSum' => get_checksum($data, "QUERY_TRAN_HIS_MSG"),
		],
		'momoMsg' => [
			'_class' => 'mservice.backend.entity.msg.QueryTranhisMsg',
			'begin' => (time() - (3600 * $hours)) * 1000,
			'end' => get_microtime(),
		],
	];
	$url = "https://owa.momo.vn/api/sync/QUERY_TRAN_HIS_MSG";
	$header = array(
		'Msgtype' => "QUERY_TRAN_HIS_MSG",
		'Accept' => 'application/json',
		'Content-Type' => 'application/json',
		'requestkey: ' . $requestkey,
		'userid: ' . $data['phone'],
		'Authorization: Bearer ' . trim($data['auth_token']),
	);
	$result = CurlMomo($url, $header, encryptDecrypt(json_encode($data_post), $requestkeyRaw), "POST");
	if (empty($result)) {
		return false;
	}
	$result = encryptDecrypt($result, $requestkeyRaw, 'DECRYPT');
	return $result;
}