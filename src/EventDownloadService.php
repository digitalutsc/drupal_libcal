<?php

namespace Drupal\libcal;

use GuzzleHttp\Exception\RequestException;
use Masterminds\HTML5\Exception;

/**
 * Class EventDownloadService.
 */
class EventDownloadService implements EventDownloadServiceInterface
{

  /**
   * Constructs a new EventDownloadService object.
   */
  public function __construct()
  {

  }

  public function postAccessToken()
  {
    $config = \Drupal::config('libcal.libcalapiconfig');

    try {
      $response = \Drupal::httpClient()->post("https://libcal.library.utoronto.ca/1.1/oauth/token", [
        'json' => [
          'client_id' =>$config->get("client_id"),
          'client_secret' => $config->get("client_secret"),
          'grant_type' => "client_credentials",
        ]
      ]);
      return json_decode((string)$response->getBody());
    }catch(RequestException $e) {
      print_r ($e->getMessage());
      return null;
    }
  }

  public function get($params) {
    if (empty($params)) {
      throw new \Exception("keyword must be valid");
    }
    $access_token = $this->postAccessToken()->access_token;
    try {
      $curl = curl_init();
      $defaultOptions = [
        CURLOPT_URL => "https://libcal.library.utoronto.ca/1.1/$params",
        CURLOPT_HTTPHEADER => array('Content-Type: application/json', "Authorization: Bearer " . $access_token),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER => true,
      ];
      //print_log($defaultOptions);
      curl_setopt_array($curl, $defaultOptions);
      $response = curl_exec($curl);
      //print_log($response);
      return json_decode($response);
    }catch (RequestException $e) {
      print_log ($e->getMessage());
      return null;
    }
  }

  public function eventToNode(object $event) {

  }



}
