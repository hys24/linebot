<?php

require_once __DIR__ . '/vendor/autoload.php';

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

$signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
try {
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log("parseEventRequest failed. InvalidSignatureException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log("parseEventRequest failed. UnknownEventTypeException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log("parseEventRequest failed. UnknownMessageTypeException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log("parseEventRequest failed. InvalidEventRequestException => ".var_export($e, true));
}

foreach ($events as $event) {
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    continue;
  }
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non text message has come');
    continue;
  }

  $hash = array("やあやあ", "ほう…",  "えっ？", "あーーーーー！", "むむっ！","これは…！","なんと…","ハン！","ドゥーン！","ぱーどぅん？","ほう...","ナンだと？","ヒーハー！","笑","ありがとぅーす！");
  $key = array_rand($hash);
  error_log($hash[$key]);

  //replyTextMessage($bot, $event->getReplyToken(),$hash[$key]);//$event->getText());
  $pdo = connectDb();
  $sql = 'select * from tanilun';
  try{
    foreach ($pdo->query($sql) as $row) {
        error_log($row['id']);
        error_log($row['url'].'<br>');
    }
  }catch (PDOException $e){
    error_log('Error:'.$e->getMessage());
    die();
  }
  // replyImageMessage($bot, $event->getReplyToken(),'https://lh3.googleusercontent.com/ujwUalQqlHoYJSdf1280s49R2HhIMUPxBPAX7aB65BE81d9ewDmwIU2Q0QIHIUZS2y6fGOrBNMhLZP1rY83pSq0BiQJ7EIKpn9q9c-URDn0gI5_BRgKq2W8iE68xp3jxDm4FPg_1P-F-AEdLPyA8FJR6yEd7dmER-WJCKOyeyO4v0d-8-9PC70KoTWf6T_RRJXp7D_KTEcRP4l4ktTHRXN0dniSma0PV4sCUuBmUklDJ9-O3y21SE_x3-6hiIW8ckIwExmzjXzpqle_Ix2iZm1mr_oQZ3dKjHDN8n-KU5ugdJZqvRx1IncLt69NFAVkVYPArDGAmqAUSnq5cSvqlCRpzXbfx1nsJMWf7Ka0bO379K5z09mAlRFCTBiwj3G_ecQMJOwzLNCLvL5PV0tKCS70EGo5RPhkJxXMnrKqdibg1i4uN13aYCynYszlboW3GCHAxLbpeyROiekOgbw8drVhPzu3TsbAR8VWwDyHNggUpa1GlTnNweGt0FJafB0H5Yx_Vf60_1vcysVmr1WoqkHUriJqm1GfCkLnTa7Ta3Olp6tUxqAzqo1LwgtEPXcAsmZSzKVx1MW7IolnagITBXFKn5ZJfCX1EyR-brkAGc2gg9LRnfXZ2yvDzrT7XpJakVbHhWidqnK823MnJR-pueD8MGLC3kjwN3CNiN3EHFEmbhg=w1254-h836-no'
  // ,'https://lh3.googleusercontent.com/ujwUalQqlHoYJSdf1280s49R2HhIMUPxBPAX7aB65BE81d9ewDmwIU2Q0QIHIUZS2y6fGOrBNMhLZP1rY83pSq0BiQJ7EIKpn9q9c-URDn0gI5_BRgKq2W8iE68xp3jxDm4FPg_1P-F-AEdLPyA8FJR6yEd7dmER-WJCKOyeyO4v0d-8-9PC70KoTWf6T_RRJXp7D_KTEcRP4l4ktTHRXN0dniSma0PV4sCUuBmUklDJ9-O3y21SE_x3-6hiIW8ckIwExmzjXzpqle_Ix2iZm1mr_oQZ3dKjHDN8n-KU5ugdJZqvRx1IncLt69NFAVkVYPArDGAmqAUSnq5cSvqlCRpzXbfx1nsJMWf7Ka0bO379K5z09mAlRFCTBiwj3G_ecQMJOwzLNCLvL5PV0tKCS70EGo5RPhkJxXMnrKqdibg1i4uN13aYCynYszlboW3GCHAxLbpeyROiekOgbw8drVhPzu3TsbAR8VWwDyHNggUpa1GlTnNweGt0FJafB0H5Yx_Vf60_1vcysVmr1WoqkHUriJqm1GfCkLnTa7Ta3Olp6tUxqAzqo1LwgtEPXcAsmZSzKVx1MW7IolnagITBXFKn5ZJfCX1EyR-brkAGc2gg9LRnfXZ2yvDzrT7XpJakVbHhWidqnK823MnJR-pueD8MGLC3kjwN3CNiN3EHFEmbhg=w1254-h836-no');//$event->getText());

}

function connectDb(){
  $url = parse_url(getenv('DATABASE_URL'));
  $dsn = sprintf('pgsql:host=%s;dbname=%s', $url['host'], substr($url['path'], 1));
  try{
    $pdo = new PDO($dsn, $url['user'], $url['pass']);
  }catch (PDOException $e){
    print('Error:'.$e->getMessage());
    die();
  }
  return $pdo;
}

function replyTextMessage($bot, $replyToken, $text){
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

function replyImageMessage($bot, $replyToken, $originalImageUrl, $previewImageUrl){
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($originalImageUrl, $previewImageUrl));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}



 ?>
