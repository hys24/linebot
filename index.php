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

  //replyTextMessage($bot, $event->getReplyToken(),$hash[$key]);//$event->getText());
  $pdo = connectDb();
  $sql = 'select * from tanilun';
  try{
    $stmt = $pdo->query($sql);
    while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
        error_log($result['url']);
        replyMultiMessage($bot, $event->getReplyToken(),
        new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result['food']),
        new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result['price']),
        new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result['food_description']),
        new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($result['food_image'],$result['food_image'])
      );
    }

  }catch (PDOException $e){
    error_log('Error:'.$e->getMessage());
    die();
  }
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

function replyMultiMessage($bot, $replyToken, ...$msgs){
  $sendMessage = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
  foreach ($msgs as $msg) {
    $sendMessage->add($msg);
  }
  $response = $bot->replyMessage($replyToken, $sendMessage);
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

 ?>
