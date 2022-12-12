
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบเติมเงิน</title>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>

    <link rel="stylesheet"><script src="dist/sweetalert2.min.js"></script><link rel="stylesheet" href="dist/sweetalert2.min.css">
    <script src="dist/sweetalert2.min.js"></script><link rel="stylesheet" href="dist/sweetalert2.min.css">

    <style>
    body {
		background: #1c1c1c;
        font-family: 'Kanit', sans-serif;
        font-style: normal;
        font-weight: 300;
        padding-top: 70px;
        overflow-x: hidden;
    }
    </style>
</head>

<?php
$phone_number = "0987273349"; // หมายเลขโทรศัพท์
$table_name = "users"; // ชื่อ ตารางที่เก็บข้อมูล
$row_name = "username"; // ชื่อ row อ้างอิงชื่อ
$row_point = "point"; // ชื่อ row อ้างอิงพ้อย
$mutiple = 1; // โบนัส

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "myshop"; // ชื่อตาราง DB

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
session_start();

function buildHeaders ($array) {
    $headers = array();
    foreach ($array as $key => $value) {
        $headers[] = $key.": ".$value;
    }
    return $headers;
}

if (isset($_POST["topup"])) {
    $_POST["ref_id"] = explode("https://gift.truemoney.com/campaign/?v=", $_POST["ref_id"])[1];
    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, "https://gift.truemoney.com/campaign/vouchers/$_POST[ref_id]/verify?mobile=$phone_number");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

    $phoneList = curl_exec($cURLConnection);
    curl_close($cURLConnection);

    $json = json_decode($phoneList);
    if ($json->status->code == "VOUCHER_NOT_FOUND") {
        $_SESSION["status"] = false;
        //$_SESSION["msg"] = "ไม่พบซองอั่งเปานี้!";
        echo '</body><script> swal("ผิดพลาด","ไม่พบซองอั่งเปานี้!!","error").then(function() {window.location = "";});</script>';
    } else if($json->status->code == "TARGET_USER_REDEEMED" || $json->status->code == "VOUCHER_OUT_OF_STOCK") {
        $_SESSION["status"] = false;
        //$_SESSION["msg"] = "ซองอั่งเปานี้ถูกใช้ไปแล้ว!";
        echo '</body><script> swal("ผิดพลาด","ซองอั่งเปานี้ถูกใช้ไปแล้ว!!","error").then(function() {window.location = "";});</script>';
    } else if($json->status->code == "SUCCESS") {
        $postRequest = array(
            "mobile" => "$phone_number",
            "voucher_hash" => "$_POST[ref_id]"
        );
        $cURLConnection2 = curl_init('https://gift.truemoney.com/campaign/vouchers/'. $_POST["ref_id"] .'/redeem');
        curl_setopt($cURLConnection2, CURLOPT_POSTFIELDS, json_encode($postRequest));
        curl_setopt_array($cURLConnection2, array(
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_PROXY => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER => buildHeaders(array("Content-Type" => "application/json"))
		));
        $apiResponse2 = curl_exec($cURLConnection2);
        curl_close($cURLConnection2);
        $json2 = json_decode($apiResponse2);
        if($json2->status->code != "SUCCESS") {
            $_SESSION["status"] = false;
            //$_SESSION["msg"] = "ไม่สามารถเติมได้!";
            echo '</body><script> swal("ผิดพลาด","ไม่สามารถเติมเงินได้กรุณาติดต่อแอดมิน!!","error").then(function() {window.location = "";});</script>';
        } else {
            $value = intval($json2->data->voucher->amount_baht);
            $valueEx = $value*$mutiple;
            $_SESSION["status"] = true;
            //$_SESSION["msg"] = "เติมเงินสำเร็จจำนวน $value!";
            echo '</body><script> swal("เติมเงินเรียบร้อย","[ '.$_POST['uname'].' ] จำนวน '.$value.' ได้รับ '.$valueEx.' พ้อย","success").then(function() {window.location = "";});</script>';
            $conn->query("UPDATE `$table_name` SET `$row_point`=`$row_point`+$valueEx WHERE `$row_name`='$_POST[uname]'");
            //$con->query("INSERT INTO log_giftwallet (playerName, Link, amount, amountEx) VALUES ('$_POST[uname]', '$_POST[ref_id]', '$value', '$valueEx'); "); //save log
        }
    }else{
        echo '</body><script> swal("ผิดพลาด","ลิงค์อังเปาไม่ถูกหรือถูกใช้งานไปแล้ว !!","error").then(function() {window.location = "";});</script>';
        exit;
    }
}

?>

<body>
    <div class="col-sm-5 mx-auto mt-3">
        <div class="card shadow">
  <h5 class="card-header">💰 ระบบเติมเงิน TrueWallet (ซองของขวัญ)</h5>
            <div class="card-body text-center mx-auto">
                <?php
                /*if (isset($_SESSION["status"])) {
                    if ($_SESSION["status"] == false) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION["msg"] . '</div>';
                    } else {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION["msg"] . '</div>';
                    }
                    unset($_SESSION["status"]);
                }*/
                ?>
                <h5>เติมเงินด้วยซองอั่งเปา</h5>
                <img src="img/wl.png" width="250" class="mt-1" alt="">
                <hr>
                <form action="#" method="POST">
                    <div class="form-group">
					<div class="text-left mt-1"><i class="fas fa-angle-right"></i>ชื่อผู้ใช้</div>
                        <input type="text" name="uname" class="form-control form-control-lg" style="border-radius: 150px;" placeholder="ชื่อผู้ใช้ในเกม" required>
                    </div>
                    <div class="form-group">
					<div class="text-left mt-1"><i class="fas fa-angle-right"></i>ลิ้งซองของขวัญ</div>
                        <input type="text" name="ref_id" class="form-control form-control-lg" style="border-radius: 150px;" placeholder="https://gift.truemoney.com/campaign/?v=..." required>
                    </div>
                    <hr>
                            <span style="color:red"><b>*** ออกเกมก่อนเติมเงิน ***</b></span>
					<button class="mt-4 btn btn-primary btn-lg col-12" style="border-radius: 50px;" type="submit" name="topup">เติมพ้อย</button>
                </form>
            </div>
        </div>
    </div>
<br />
<br />
<br />
<br />
    
</body>

</html>