<?php

require_once "config.php";
$update = file_get_contents('php://input');
$update = json_decode($update, true);
$message = $update['message'] ?? null;
$chat_id = $message['chat']['id'];
$text = $message['text'] ?? null;
$callback_id = $update['callback_query']['from']['id'];
$photo = $message['photo'];
$file_id = $photo[count($photo) - 1]['file_id'];
$width = $photo[count($photo) - 1]['width'];
$height = $photo[count($photo) - 1]['height'];
$data = $update['callback_query']['data'];
$caption = $message['caption'];

try{
    if($message) {
        if($text == "/start"){
            sendMessage($chat_id, "Botga xush kelibsiz😊!!!\nQuydagi shablaonlardan birni tanlang⏬");
            send('sendPhoto',
                [
                    'chat_id' => $chat_id,
                    'photo' => new CURLFile('img.jpg'),
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => "    1   ", 'callback_data' => 'default1.jpg'],
                                ['text' => "    2   ", 'callback_data' => 'default2.jpg'],
                            ],
                            [
                                ['text' => "    3   ", 'callback_data' => 'default3.jpg'],
                                ['text' => "    4   ", 'callback_data' => 'default4.jpg'],
                            ]
                        ]
                    ])
                ]);
            $first_name = $message['from']['first_name'];
            $last_name = $message['from']['last_name'];
            $username = $message['from']['username'];
            if (getDatabaze($username)) {
                query([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'username' => $username,
                    'chat_id' => $chat_id,
                ]);
            }
        }
        $action = getAction($chat_id);
        if(isset($action)){
            if($action == 'getSave' && $text == "/download") {
                getInfo($chat_id);
                setAction($chat_id, null);
                setStatus($chat_id);
                send("sendMessage", [
                    'chat_id' => $chat_id,
                    'text' => "Botdan yana foydalanish uchun quydagini bosing⬇️",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => "restart", 'callback_data' => '/restart'],
                            ]
                        ]
                    ])
                ]);
            }elseif ($action == "getImg"){
                if($photo){
                    if($caption == ""){
                        sendMessage($chat_id, "Quydagicha rasm bilan caption qismiga ismingizni yuboring👇");
                        send('sendPhoto',
                            [
                                'chat_id' => $chat_id,
                                'photo' => new CURLFile("img1.jpg")
                            ]);
                    }
                    else{
                        setSave($file_id, $chat_id, $caption);

                        send("sendMessage", [
                            'chat_id' => $chat_id,
                            'text' => "Rasmlar soni ".setCheck($chat_id)." ta \nRasimlarni tashlab bo'lgach saqlash tugmasini bosing⬇️",
                            'reply_markup' => json_encode([
                                'inline_keyboard' => [
                                    [
                                        ['text' => 'save', 'callback_data' => '/save'],
                                    ]
                                ]
                            ])
                        ]);
                    }

                }
                else{
                    sendMessage($chat_id, "Rasim yuboring");
                }
            }
        }

    }
    elseif($update['callback_query']){
        switch ($data){
            case "default1.jpg" :
                send('sendMessage' , [
                    'chat_id' => $callback_id,
                    'text' => "Bu shablon hali o'rnatilmagan!"
                ]);
                break;
            case "default2.jpg":
                send('sendMessage',[
                    'chat_id' => $callback_id,
                    'text' => "Bu shablon hali o'rnatilmagan❌"
                ]);
                break;
            case "default3.jpg":
                send('sendMessage' , [
                    'chat_id' => $callback_id,
                    'text' => "Siz quydagi shablonni tanladingiz🔻"
                ]);
                send('sendPhoto',
                    [
                        'chat_id' => $callback_id,
                        'photo' => new CURLFile("default3.jpg")
                    ]);
                setAction($callback_id,'getImg');
                getImg($callback_id, $data);
                send('sendMessage', [
                    'chat_id' => $callback_id,
                    'text' => "Intalgancha rasmlaringizni quydagicha yuboring va ismingizni rasmning caption qismiga yozing"
                ]);

                send('sendPhoto',
                    [
                        'chat_id' => $callback_id,
                        'photo' => new CURLFile("img1.jpg")
                    ]);
                break;
            case "default4.jpg":
                send('sendMessage' , [
                    'chat_id' => $callback_id,
                    'text' => "Bu shablon hali o'rnatilmagan!!!"
                ]);
                break;
            case "/save":
                setAction($callback_id, "getSave");
                sendMessage($callback_id, "Buyurtma tayor olish uchun quyadagni bosing👇\n/download");
                break;
            case "/restart":
                sendMessage($callback_id, "Quydagi shablonlardan birni tanlang⏬");
                send('sendPhoto',
                    [
                        'chat_id' => $callback_id,
                        'photo' => new CURLFile('img.jpg'),
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    ['text' => "    1   ", 'callback_data' => 'default1.jpg'],
                                    ['text' => "    2   ", 'callback_data' => 'default2.jpg'],
                                ],
                                [
                                    ['text' => "    3   ", 'callback_data' => 'default3.jpg'],
                                    ['text' => "    4   ", 'callback_data' => 'default4.jpg'],
                                ]
                            ]
                        ])
                    ]);
                break;
        }
    }
}
catch (Exception $exception){
    send("sendMessage",[
        'chat_id' => 1991666833,
        'text' => json_encode($exception->getMessage() . $exception->getLine(),JSON_PRETTY_PRINT)
    ]);
}





