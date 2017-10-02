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

  $messageText = $event->getText();

  switch ($messageText) {
    case "本日のおすすめ":
      replyRecommend($bot, $event);break;
    case "コスパ":
      break;
    case "スタンプ":
      replyStickerMessage($bot, $event->getReplyToken(), 1, 1);break;
    default :
  }

  $result = getResult($messageText);
  if($result == "")replyStickerMessage($bot, $event->getReplyToken(), 1, 1);
  replyMultiMessage($bot, $event->getReplyToken(),
    new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($result['food_image'],$result['food_image']),
    new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result['food']." ".$result['price']),
    new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result['food_description']),
    new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($result['shop'],$result['address'],$result['lat'],$result['lon'])
  );
}
  //replyTextMessage($bot, $event->getReplyToken(),$hash[$key]);//$event->getText());

  function getResult($messageText){
    $pdo = connectDb();
    $sql = 'select * from tanilun';
    $array = array();
    try{
      $stmt = $pdo->query($sql);
      while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
        if(preg_grep('/'.$messageText.'/', $result))array_push($array, $result);
        //if(in_array($messageText, $result))error_log(print_r($result,true));//array_push($array, $result);
      }
    }catch (PDOException $e){
      error_log('Error:'.$e->getMessage());
      die();
    }
    if(count($array) == 0)return "";
    $key = array_rand($array);
    return $array[$key];
  }


  function replyRecommend($bot, $event){
    $pdo = connectDb();
    $sql = 'select * from tanilun';
    try{
      $stmt = $pdo->query($sql);
      $allResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $key = array_rand($allResult);
      $result = $allResult[$key];
      replyMultiMessage($bot, $event->getReplyToken(),
        new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($result['food_image'],$result['food_image']),
        new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result['food']." ".$result['price']),
        new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result['food_description']),
        new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($result['shop'],$result['address'],$result['lat'],$result['lon'])
      );
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

//テキストを返信
function replyTextMessage($bot, $replyToken, $text){
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//画像を返信
function replyImageMessage($bot, $replyToken, $originalImageUrl, $previewImageUrl){
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($originalImageUrl, $previewImageUrl));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//位置情報を返信
function replyLocationMessage($bot, $replyToken, $title, $address, $lat, $lon){
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($title, $address, $lat, $lon));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//スタンプを返信
function replyStickerMessage($bot, $replyToken, $packageId, $stickerId){
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder($packageId, $stickerId));
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

//Buttonsテンプレート
function replyButtonsTemplate($bot, $replyToken, $alternativeText, $title, $text, $imageUrl, ...$actions){
  $actionArray = array();
  foreach ($actions as $action) {
    array_push($actionArray, $action);
  }
  $msgBuilder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder($title, $text, $imageUrl, $actionArray)
  );
  $response = $bot->replyMessage($replyToken, $msgBuilder);
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}


//複数のメッセージを返信
function replyMultiMessage($bot, $replyToken, ...$msgs){
  $msgBuilder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
  foreach ($msgs as $msg) {
    $msgBuilder->add($msg);
  }
  $response = $bot->replyMessage($replyToken, $msgBuilder);
  if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' '.$response->getRawBody());
  }
}

 ?>
