<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MongoDB\Client as Mongo;

class read2send extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send JSON beacon data to server.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      // $client = new \GuzzleHttp\Client();
      $client = new \GuzzleHttp\Client([
        'headers' => ['Content-Type' => 'application/json']
      ]);

      $beacJSON = [];
      $jsonObj = new \stdClass();
      $jsonObj2 = new \stdClass();
      $masterJSON;
      $hexdata;
      $collection = (new Mongo)->local->beacons;
      $query = array("sent"=> 0);
      $documents = $collection->find($query)->toArray();

      for($i = 0; $i < sizeOf($documents); $i++){
        // echo json_encode($documents[$i]);
        // echo "\n\n";


      $hexdata = $documents[$i]["data"];
      $timestamp = $documents[$i]["timestamp"];

      // $nr = self::occurrences($hexdata, "0200", 0);


      // echo $nr;

      $isFrom = self::whereFrom($hexdata);


      if($isFrom == "Gateway"){

    		$gatewayID = hexdec(substr($hexdata, 6, 2));
    		$macPosition = stripos($hexdata, "ff0006");
    		$lengthPosition = $macPosition + 20;
    		$beacMAC = substr($hexdata, $macPosition + 6, 12);
    		$dataLength = hexdec(substr($hexdata, $lengthPosition, 2));
    		$beaconData = substr($hexdata, $lengthPosition + 4, $dataLength * 2);
    		$dataLeft = substr($hexdata, $lengthPosition + 4 + $dataLength * 2);
    		$dataLeftLength = strlen($dataLeft)/2;
    		$beacSignalStrength = hexdec(substr($dataLeft, 24, 2)) - 256;
        $ebMAC = null;
        $ebSignalStrength = null;
        $source = 1;


	}
	else if($isFrom == "EchoBeacon"){

        $gatewayID = hexdec(substr($hexdata, 6, 2));
        $macPosition = stripos($hexdata, "ff0006");
        $lengthPosition = $macPosition + 20;
        $ebMAC = substr($hexdata, $macPosition + 6, 12);
        $dataLength = hexdec(substr($hexdata, $lengthPosition, 2));
        $ebData = substr($hexdata, $lengthPosition + 4, $dataLength * 2);
        $beacMAC = substr($ebData, 24, 12);
        $beacSignalStrength = hexdec(substr($ebData, 36, 2)) - 256;
        $dataLeft = substr($hexdata, $lengthPosition + 4 + $dataLength * 2);
        $dataLeftLength = strlen($dataLeft)/2;
        $ebSignalStrength = hexdec(substr($dataLeft, 24, 2)) - 256;
        $source = 2;


	}


    $jsonObj->source = $source;
    $jsonObj->gateway_id = $gatewayID;
    $jsonObj->beacon_sn = $beacMAC;
    $jsonObj->echoBeacon_sn = $ebMAC;
    $jsonObj->beacon_rssi = $beacSignalStrength;
    $jsonObj->echoBeacon_rssi = $ebSignalStrength;
    $jsonObj->timestamp = $timestamp;
    $jsonObj->hexdata = $hexdata;



    $beacJSON[$i] = $jsonObj;






      }


        // if(sizeOf($documents) > 0){

        try {
          $response = $client->post('httpbin.org/post', [
            \GuzzleHttp\RequestOptions::JSON => ["beacon"=>$beacJSON]
        ]);
          echo '<pre>' . var_export($response->getStatusCode(), true) . '</pre>';
          echo '<pre>' . var_export($response->getBody()->getContents(), true) . '</pre>';

          self::updateDocuments($collection, $documents);

        }


          catch (RequestException $e) {

            // If there are network errors, we need to ensure the application doesn't crash.
            // if $e->hasResponse is not null we can attempt to get the message
            // Otherwise, we'll just pass a network unavailable message.
            if ($e->hasResponse()) {
              $exception = (string) $e->getResponse()->getBody();
              $exception = json_decode($exception);
              return new JsonResponse($exception, $e->getCode());
            } else {
              return new JsonResponse($e->getMessage(), 503);
            }

          }
        // }else{
        //   echo "No new beacons.\n";
        // }






    }



    public function whereFrom($string){

      if(substr_count ($string , '0201061bffbe05' ) > 0)
        return "EchoBeacon";
      else {
        return "Gateway";
      }


    }

    public function updateDocuments($col,$documents){

    for($i = 0; $i < sizeOf($documents); $i++){
      $col->updateOne(['_id' => $documents[$i]["_id"]], ['$set' => ['sent' => 1]]);
    }
  }

}
