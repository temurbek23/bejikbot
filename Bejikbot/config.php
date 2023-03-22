<?php
require_once "db.php";
require('fpdf.php');
function send($method, $data){
    $bot_api = "6072999514:AAGbOsg9jHuCb4O5NtX0OwLLmqQwbO4AtjU";
    $url = "https://api.telegram.org/bot{$bot_api}/{$method}";
    if(!$curl = curl_init()){
        exit();
    }

    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($curl);
    curl_close($curl);
    return $result;

}

function query($array){
    $sql = "INSERT INTO `users` (first_name, last_name, username, chat_id) VALUES ('{$array['first_name']}', '{$array['last_name']}', '{$array['username']}', '{$array['chat_id']}')";
    global $conn;
    if(mysqli_query($conn, $sql)) {
    } else {
        send("sendMessage",[
            'chat_id' => 1991666833,
            'text' => "Error: " . $sql . "<br>" . mysqli_error($conn)
        ]);
    }
}

function getDatabaze($username){
    global $conn;
    $sql = "SELECT username FROM `users`";

    $result = mysqli_query($conn, $sql);

    $n = true;
    while($row = mysqli_fetch_assoc($result)){
        if($row['username'] == $username){
            $n = false;
        }
    }
    return $n;
}

function getPhoto($picture,$chat_id, $name){
    global $conn;
    $sql = "SELECT Bejik.id FROM Bejik INNER JOIN users ON users.chat_id = '{$chat_id}' AND Bejik.user_id = users.id";
    $row = mysqli_fetch_assoc(mysqli_query($conn , $sql));
    $sql = "INSERT INTO `photos`(`bejik_id`, `name`, `picture`, `status`) VALUES ('{$row['id']}', '{$name}', '{$picture}', 'new')";
    if(!mysqli_query($conn, $sql)) {
        send("sendMessage",[
            'chat_id' => 1991666833,
            'text' => "Error: " . $sql . "\n" . mysqli_error($conn)
        ]);
    }
}

function sendMessage($chat_id, $text){
    send("sendMessage", [
        'chat_id' => $chat_id,
        'text' => $text
    ]);
}


function getInfo($chat_id){
    global $conn;

    $sql = "SELECT Bejik.id  FROM users INNER JOIN Bejik ON Bejik.user_id=users.id AND users.chat_id = '{$chat_id}'";
    $id = mysqli_fetch_assoc(mysqli_query($conn, $sql));

    $sql = "SELECT `name` , `picture` FROM `photos` WHERE `bejik_id` = '{$id['id']}' AND `status` = 'new'";
    $result = mysqli_query($conn, $sql);

    $sql = "SELECT Bejik.card_type FROM users INNER JOIN Bejik ON Bejik.user_id=users.id AND users.chat_id = '{$chat_id}'";
    $row1 = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    while ($row = mysqli_fetch_assoc($result)){
        Bejik($row['picture'] , $row['name'], $chat_id, $row1['card_type']);
    }



}
function getImg($chat_id, $img){
    global $conn;

    $sql = "SELECT Bejik.id, Bejik.user_id, Bejik.card_type FROM Bejik INNER JOIN users ON users.chat_id = '{$chat_id}' AND Bejik.user_id = users.id";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $sql));

    $sql_user = "SELECT `id`  FROM `users` WHERE `chat_id` = '$chat_id'";
    $user = mysqli_fetch_assoc(mysqli_query($conn, $sql_user))['id'];
    if(isset($result)){
        if($result['card_type'] != $img){
            $sql_user = "UPDATE Bejik SET `card_type` = $img WHERE `user_id` = '{$user}'";
            mysqli_query($conn, $sql_user);
        }
    }else{

        $sql = "INSERT INTO `Bejik` (`user_id`, `card_type`) VALUES ('{$user}','{$img}')";
        mysqli_query($conn,$sql);
    }
}

function Bejik($img, $text, $chat_id,$default){

    $im = imagecreatefromjpeg("uploads/".$img);

    $def = imagecreatefromjpeg($default);
    $d_width = imagesx($def);
    $d_height = imagesy($def);

    $width1 = imagesx($im);
    $height1 = imagesy($im);

    $x1 = $d_width/2;
    $y1 = $d_height;
    
    $min = min(imagesx($im),imagesy($im));
    $max = max(imagesx($im),imagesy($im));

    $n = $min / $x1;

    if($y1 * $n <= $max){
        $y = $max - $y1 * $n;
    }
    else{
        sendMessage($chat_id, "Bu rasmning o'lchamlari to'g'ri kelmaydi!!");
        setAction($chat_id, null);
        setStatus($chat_id);
        exit();
    }

    if($max == $width1){
        $width1 = $max - $y;
    }
    elseif($max == $height1){
        $height1 = $max - $y;
    }

    $im2 = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => $width1, 'height' => $height1]);
    if ($im2 !== FALSE) {
        imagejpeg($im2, 'example-cropped.jpg');
    }

    $filename = 'example-cropped.jpg';

    list($width, $height) = getimagesize($filename);
    $new_width = $x1;
    $new_height = $y1;

    $image_p = imagecreatetruecolor($new_width, $new_height);
    $image = imagecreatefromjpeg($filename);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    imagejpeg($image_p, "rasm.jpg", 100);



    $dest = imagecreatefromjpeg($default);
    $src = imagecreatefromjpeg("rasm.jpg");

    imagecopymerge($dest, $src, 0, 0, 0, 0, $x1, $y1, 100);

    imagejpeg($dest,"photo.jpg");

    $img = imagecreatefromjpeg("photo.jpg");
    $white = imagecolorallocate($img, 0,0,0);
    $font = "C:\OSPanel\domains\Bejikbot\arial.ttf";

    imagettftext($img, 36, 0, 5*$d_width/8, 5*$d_height/16, $white, $font, $text);

    imagejpeg($img,"save.jpg",100);
    getPdf("save.jpg");

    send("sendDocument", [
        'chat_id' => $chat_id,
        'document' => new CURLFile("result.pdf")
    ]);
    getPrint("save.jpg");
    getPdf("save1.jpg");
    send("sendDocument", [
        'chat_id' => $chat_id,
        'document' => new CURLFile("result.pdf")
    ]);
    imagedestroy($dest);
    imagedestroy($src);
    imagedestroy($im);
    imagedestroy($im2);
    unlink("example-cropped.jpg");
    unlink("rasm.jpg");
    unlink("photo.jpg");
    unlink("save.jpg");
    unlink("save1.jpg");
    unlink("result.pdf");
}

function setAction($chat_id, $action){
    global  $conn;
    $sql = "UPDATE `users` SET `action` = '{$action}' WHERE  `chat_id` = '{$chat_id}'";
    mysqli_query($conn,$sql);
}

function getAction($chat_id){
    global  $conn;
    $sql = "SELECT `action` FROM `users`  WHERE  `chat_id` = '{$chat_id}'";

    return mysqli_fetch_assoc(mysqli_query($conn,$sql))['action'] ;

}
function setSave($file_id, $chat_id, $name){
    global  $conn;
    $f = send("getFile",[
        'file_id' => $file_id,
    ]);

    $path = json_decode($f,true)['result']['file_path'];
    $file = "https://api.telegram.org/file/bot6072999514:AAGbOsg9jHuCb4O5NtX0OwLLmqQwbO4AtjU/$path";

    $file_name = time() . "_" . basename($file);
    $ch = curl_init($file);
    $fp = fopen('uploads/' . $file_name, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    $sql = "SELECT Bejik.card_type FROM Bejik INNER JOIN users ON users.chat_id = '{$chat_id}' AND Bejik.user_id = users.id";
    $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    setImg($file_name, $row['card_type']);

    getPhoto($file_name, $chat_id, $name);


}

function setStatus($chat_id){
    global $conn;
    $sql = "SELECT Bejik.id FROM users INNER JOIN Bejik ON Bejik.user_id=users.id AND users.chat_id = '{$chat_id}'";
    $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    $sql = "UPDATE `photos` SET `status` = 'old' WHERE `bejik_id`='{$row['id']}'";
    mysqli_query($conn, $sql);
}


function getPrint($img){
    $im = imagecreate(3508, 2480);
    imagecolorallocate($im, 255, 255, 255);

    imagejpeg($im,"picture.jpg");
    $dest = imagecreatefromjpeg("picture.jpg");
    $src = imagecreatefromjpeg($img);
    $s = 190;
    for($j = 1; $j < 4; $j++){
        $k = 202;
        for($i = 1; $i < 4; $i++){
            imagecopymerge($dest, $src, $k, $s, 0, 0, 960, 564, 100);
            imagejpeg($dest, "save1.jpg");
            $dest = imagecreatefromjpeg("save1.jpg");
            $k = $k + 900 + 202;
        }
        $s = $s + 564 + 200;
    }


    unlink("picture.jpg");
}

function getPdf($img){
    $pdf = new FPDF();
    $pdf->AddPage();
    $image = $img;
    list($width, $height) = getimagesize($image);
    $ratio = $width / $height;

    $max_width = 200;
    $max_height = 200;

    if ($width > $height) {
        $new_width = $max_width;
        $new_height = $max_width / $ratio;
    } else {
        $new_width = $max_height * $ratio;
        $new_height = $max_height;
    }

    $pdf->Image($image, 5, 10, $new_width, $new_height);
//  $pdf->Image($image, 5, $new_height + 10, $new_width, $new_height);
    $pdf->Output('f', 'result.pdf');


}
function setCheck($chat_id){
    global $conn;
    $sql = "SELECT Bejik.id FROM users INNER JOIN Bejik ON Bejik.user_id=users.id AND users.chat_id = '{$chat_id}'";
    $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));

    $sql = "SELECT COUNT(*) FROM `photos` WHERE `bejik_id` = '{$row['id']}' AND `status` = 'new'";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    return $result['COUNT(*)'];
}


function setImg($img, $default){
    $im = imagecreatefromjpeg("uploads/".$img);

    $def = imagecreatefromjpeg($default);
    $d_width = imagesx($def);
    $d_height = imagesy($def);


    $x1 = $d_width/2;
    $y1 = $d_height;

    $min = min(imagesx($im),imagesy($im));
    $max = max(imagesx($im),imagesy($im));

    $n = $min / $x1;

    if($y1 * $n > $max){
        global $chat_id;
        sendMessage($chat_id, "Bu rasmning o'lchamlari to'g'ri kelmaydi!!");
        send("sendMessage", [
            'chat_id' => $chat_id,
            'text' => "Quyda rasmlar soni va saqlash tugmasi⬇️",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => setCheck($chat_id), 'callback_data' => 'number'],
                        ['text' => 'save', 'callback_data' => '/save'],
                    ]
                ]
            ])
        ]);
        exit();
    }
}