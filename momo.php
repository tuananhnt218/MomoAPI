<?php
require_once('lib/libs.php');
date_default_timezone_set('Asia/Ho_Chi_Minh');

$html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Momo</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            form {
                display: flex;
                justify-content: flex-start;
                flex-direction: column;
                --tw-space-y-reverse: 0;
                margin-top: calc(1.5rem * calc(1 - var(--tw-space-y-reverse)));
                margin-bottom: calc(1.5rem * var(--tw-space-y-reverse));
            }
            input {
                margin: 10px;
                padding: 5px;
            }
        </style>
    </head>
    </html>
';
$data = array(
	"phone" => "",
    "rkey" => "",
    "imei" => "",
    "secure_id" => "",
    "setupkey" => "",
    "ohash" => "",
    "pwd" => ""
);
$flag = true;
foreach (array_keys($data) as $key) {
    if(empty($data[$key])){
        $flag = false;
        echo $html;
        break;
    }
}

if ($flag) {
    $result = LoginMomo($data);
    header('Content-Type: application/json; charset=utf-8');
    if (json_decode($result)->result){
        $data["auth_token"] = json_decode($result)->extra->AUTH_TOKEN;
        $data["encrypt_key"] = json_decode($result)->extra->REQUEST_ENCRYPT_KEY;
        $result = HistoryMomo($data, 100);
    }
    print_r($result);
    exit();
}

if (isset($_POST['stepOne'])) {
    $data['phone'] = $_POST['phone'];
    $data['rkey'] = $_POST['rkey'];
    $data['imei'] = $_POST['imei'];
    $data['secure_id'] = $_POST['secure_id'];
    $result = GET_OTP($data);
    if (json_decode($result)->result){
        echo '
            <form action="" method="post">
                <label style="padding: 10px;">SĐT: '.$data['phone'].'</label>
                <input type="hidden" name="phone" value="'.$data['phone'].'" />
                <input type="text" name="otp" placeholder="Nhập mã OTP" autocomplete="off" />
                <input type="text" name="pwd" placeholder="Nhập mật khẩu ví Momo" autocomplete="off" />
                <input type="hidden" name="rkey" value="'.$data['rkey'].'" />
                <input type="hidden" name="imei" value="'.$data['imei'].'" />
                <input type="hidden" name="secure_id" value="'.$data['secure_id'].'" />
                <input type="submit" name="stepTwo" value="Đăng nhập" />
            </form>
        ';
    } else {
        print_r($result);
    }
}
if (isset($_POST['stepTwo'])) {
    $data['phone'] = $_POST['phone'];
    $data['rkey'] = $_POST['rkey'];
    $data['imei'] = $_POST['imei'];
    $data['secure_id'] = $_POST['secure_id'];
    $data['otp'] = $_POST['otp'];
    $data['pwd'] = $_POST['pwd'];
    $checkOTP = CHECK_OTP($data);
    if (json_decode($checkOTP)->result){
        echo '
            "phone" => "'.$data['phone'].'", <br />
            "rkey" => "'.$data['rkey'].'", <br />
            "imei" => "'.$data['imei'].'", <br />
            "secure_id" => "'.$data['secure_id'].'", <br />
            "setupkey" => "'.json_decode($checkOTP)->extra->setupKey.'", <br />
            "ohash" => "'.json_decode($checkOTP)->extra->ohash.'", <br />
            "pwd" => "'.$data['pwd'].'"<br />
        ';
        echo '<h3>Copy thông tin trên bỏ vào $data trong momo.php, sau đó load lại trang</h3>';
    } else {
        print_r($result);
    }
}
if(empty($data['phone'])){
    echo '
        <form action="" method="post">
            <input type="text" name="phone" placeholder="Nhập SĐT" autocomplete="off" />
            <input type="hidden" name="rkey" value="'.GET_rkey(20).'" />
            <input type="hidden" name="imei" value="'.GET_imei().'" />
            <input type="hidden" name="secure_id" value="'.GET_secureID().'" />
            <input type="submit" name="stepOne" value="Gửi OTP" />
        </form>
    ';
}
?>