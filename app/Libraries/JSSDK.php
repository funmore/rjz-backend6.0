<?php
namespace App\Libraries;
class JSSDK {
  private $appId;
  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getAccessToken() {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode(file_get_contents("access_token.json"));
    if ($data->expire_time < time()) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));
      $access_token = $res->access_token;
      if ($access_token) {
        $data->expire_time = time() + 7000;
        $data->access_token = $access_token;
        $fp = fopen("access_token.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      }
    } else {
      $access_token = $data->access_token;
    }
    return $access_token;
  }

  public function wxLogin($code) {
      $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$this->appId&secret=$this->appSecret&js_code=$code&grant_type=authorization_code";
      $res = json_decode($this->httpGet($url), TRUE);
      return $res;
  }

  public function sendWxMsg($data) {
      $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$this->getAccessToken();
      $res = json_decode($this->httpPost($url, $data),TRUE);
      return $res;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }

  private function httpPost($url, $post_data) {
      $data_string = json_encode($post_data);
      $ch = curl_init($url);

//      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//      curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
//      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
           'Content-Length: ' . strlen($data_string))
      );
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5000);


//      curl_setopt($ch, CURLOPT_URL, $url);
//      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
//      curl_setopt($ch, CURLOPT_POST, 1);
//      curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
//      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//      curl_setopt($ch, CURLOPT_URL, $url);
//      curl_setopt($ch, CURLOPT_POST, 1);
//      curl_setopt ( $ch, CURLOPT_HEADER, 0 );
//      curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
//      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

      $fp = fopen("post_log_url.json", "w");
      fwrite($fp, json_encode ($url));
      fclose($fp);

      $fp = fopen("post_log_data.json", "w");
      fwrite($fp, json_encode($post_data));
      fclose($fp);

      $res = curl_exec($ch);

      $curl_error_msg=curl_error($ch);
      $fp = fopen("curl_error.json", "w");
      fwrite($fp, json_encode($curl_error_msg));
      fclose($fp);


      curl_close($ch);

      $fp = fopen("post_log.json", "w");
      fwrite($fp, json_encode($res));
      fclose($fp);

      return $res;
  }
}

