<?php

require __DIR__ . '/vendor/autoload.php';

use \Curl\Curl;
use League\Csv\Writer;

$found = TRUE;
$page = 1;

//csv file name
$csvFile = __DIR__ . '/csv/dialogflow-training-export-' . date('YmdHis') . '.csv';
$stream = fopen($csvFile, 'w');

//load the CSV document from a stream
$csv = Writer::createFromStream($stream);

//insert the header
$header = ['SessionId', 'Conversation', 'Request', 'No Match', 'Date'];
$csv->insertOne($header);

do  {

    echo "Get data from page {$page}\n";

    // configure this wisely, because google api has API throttle configuration, watch out!
    // you will get empty data if too brute force, and the worse is get blocked!
    usleep(mt_rand(500, 3000));

    $curl = new Curl();

    // preparing the headers
    $authToken = '5e66f218-3e84-48a5-93b9-895ec16c69cb';
    $cookie = '_ga=GA1.2.804191977.1525141257; _gid=GA1.2.1195045110.1530267381; zUserAccessToken=c5545304-a170-4891-a2f7-f339dae1ef48';
    $xsrfToken = '466b30bc-0da7-4fd6-b6f1-5588d8e5142c';

    $curl->setHeader('Authorization', 'Bearer ' . $authToken);
    $curl->setHeader('Cookie', $cookie);
    $curl->setHeader('accept-language', 'en-US,en;q=0.9,id;q=0.8');
    $curl->setHeader('x-xsrf-token', $xsrfToken);
    $curl->setHeader('user-agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36');
    $curl->setHeader('accept', 'application/json');
    $curl->setHeader('authority', 'console.dialogflow.com');
    $curl->setHeader('accept-encoding', 'gzip, deflate, br');

    // send request
    $curl->get('https://console.dialogflow.com/api/interactions/conversations?page='.$page.'&perPage=50&lang=id');

    // if something goes wrong
    if ($curl->error) {
        echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
        $found = FALSE;
    } 

    // if response found and the result is not empty
    if ($found && ! empty($result = $curl->response)) {

        // processing result into CSV file
        foreach ($result as $item) {

            $sessionId = $item->sessionId;
            $interactions = $item->interactions;
            $firstInteraction = $interactions[0];
            $intentName = $firstInteraction->intentName;
            $markedQuery = $firstInteraction->markedQuery;
            $requestCounter = count($interactions);

            $firstConversation = $firstInteraction->query;

            $noMatch = 0;
            foreach ($interactions as $interaction) {
                if ($interaction->intentName == 'Default Fallback Intent') {
                    $noMatch++;
                }
            }

            $dateTime = $firstInteraction->timestamp;

            $records = [
                $sessionId,
                $firstConversation,
                $requestCounter,
                $noMatch,
                $dateTime
            ];

            //insert records
            $csv->insertOne($records);
        }

    } else {

        $found = FALSE;

    }

    $page++;

} while ($found);

