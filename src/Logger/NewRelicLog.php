<?php
namespace Drupal\newrelic_log\Logger;

use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Redirects logging messages to syslog.
 */
class NewRelicLog implements LoggerInterface {
  use RfcLoggerTrait;

  protected $parser;

  public function __construct(LogMessageParserInterface $parser) {
    $this->parser = $parser;
  }

  public function sendData($data) {
    $url='https://log-api.eu.newrelic.com/log/v1';
    $apikey=ini_get('newrelic.license');
    $curl=curl_init();
    $headers=["Content-Type: application/json","Api-Key: ".$apikey];
    $data['hostname']=gethostname();
    $data['application']=ini_get('newrelic.appname');
    curl_setopt($curl,CURLOPT_URL,$url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl,CURLOPT_POST,1);
    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($data));
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
    $r=curl_exec($curl);
    $http_status=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);
  }

  public function log($level, $message, array $context = array()) {
    $placeholders=$this->parser->parseMessagePlaceholders($message,$context);
    $message=empty($placeholders)?$message:strtr($message,$placeholders);

    $data=newrelic_get_linking_metadata();
    $data['message']=$message;
    $data['context']=$context;
    $this->sendData($data);

    newrelic_notice_error($message,$context['exception']??null);
	}

}
