<?php
  /**
   * Bitflyer リアルタイム板情報取得
   */
require_once('vendor/autoload.php');

use PubNub\PubNub;
use PubNub\Enums\PNStatusCategory;
use PubNub\Callbacks\SubscribeCallback;
use PubNub\PNConfiguration;
 
class MySubscribeCallback extends SubscribeCallback {
    private $askBoardInfo = []; # 売り板
    private $bidBoardInfo = []; # 買い板

    function status($pubnub, $status) {
        if ($status->getCategory() === PNStatusCategory::PNUnexpectedDisconnectCategory) {
            // This event happens when radio / connectivity is lost
        } else if ($status->getCategory() === PNStatusCategory::PNConnectedCategory) {
            // Connect event. You can do stuff like publish, and know you'll get it
            // Or just use the connected event to confirm you are subscribed for
            // UI / internal notifications, etc
        } else if ($status->getCategory() === PNStatusCategory::PNDecryptionErrorCategory) {
            // Handle message decryption error. Probably client configured to
            // encrypt messages and on live data feed it received plain text.
        }
    }
 
    function message($pubnub, $message) {
      // Handle new message stored in message.message
      $msg = $message->getMessage();

      // 売り板
      if (!empty($msg["asks"])) {
        foreach ($msg["asks"] as $key => $value) {
          if ($value["size"] == 0) {
            // 板情報から削除
            foreach ($this->askBoardInfo as $akey => $avalue) {
              if ($avalue["price"] == $value["price"]) {
                unset($this->askBoardInfo[$akey]);
              }
            }
          } else {
            // 板情報に追加
            $this->askBoardInfo[] = ["price" => $value["price"], "amount" => $value["size"]];
          }
        }
      }

      // 買い板
      if (!empty($msg["bids"])) {
        foreach ($msg["bids"] as $key => $value) {
          if ($value["size"] == 0) {
            // 板情報から削除
            foreach ($this->bidBoardInfo as $bkey => $bvalue) {
              if ($bvalue["price"] == $value["price"]) {
                unset($this->bidBoardInfo[$bkey]);
              }
            }
          } else {
            // 板情報に追加
            $this->bidBoardInfo[] = ["price" => $value["price"], "amount" => $value["size"]];
          }
        }
      }
    }
 
    function presence($pubnub, $presence) {
        // handle incoming presence data
    }
}
 
$pnconf = new PNConfiguration();
$pubnub = new PubNub($pnconf);
 
$pnconf->setSubscribeKey("sub-c-52a9ab50-291b-11e5-baaa-0619f8945a4f");
$pnconf->setPublishKey("my_pub_key");

$subscribeCallback = new MySubscribeCallback();
$pubnub->addListener($subscribeCallback);
 
// Subscribe to a channel, this is not async.
$pubnub->subscribe()
    ->channels("lightning_board_BTC_JPY")
    ->execute();

