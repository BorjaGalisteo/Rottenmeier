<?php

/**
 * Sample PHP code for youtube.commentThreads.list
 * See instructions for running these code samples locally:
 * https://developers.google.com/explorer-help/guides/code_samples#php
 */

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    throw new Exception(sprintf('Please run "composer require google/apiclient:~2.0" in "%s"', __DIR__));
}
require_once __DIR__ . '/vendor/autoload.php';


$channel_id = 'UCWhKWA_zR6c-H1c4kcGj6KQ';


function getClient()
{
    require_once 'config.php';
    $client = new Google_Client();
    $client->setApplicationName('rottenmeier');
    $client->setDeveloperKey($developer_key);
    // Define service object for making API requests.
    $client->setScopes([
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.force-ssl',
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtubepartner',
    ]);

    // TODO: For this request to work, you must replace
    //       "YOUR_secrets.json" with a pointer to your
    //       client_secret.json file. For more information, see
    //       https://cloud.google.com/iam/docs/creating-managing-service-account-keys
    $client->setAuthConfig('secrets.json');
    $client->setAccessType('offline');
    return $client;
}

function generateAccessTokenByURL()
{
    $client = getClient();

    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open this link in your browser:\n%s\n", $authUrl);
    print('Enter verification code: ');
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
    file_put_contents("./token.json", json_encode($accessToken));
    return $accessToken;
}

function refreshAccessTokenIfExpired($accessToken)
{
    $client = getClient();
    $client->setAccessToken($accessToken);

    if (!$client->isAccessTokenExpired()) {
        return $accessToken;
    }

    $newAccessToken = $client->fetchAccessTokenWithRefreshToken($accessToken['refresh_token']);
    if (isset($newAccessToken['error'])) {
        echo "Error when retrieving new access token using a Refresh Token " . json_encode($newAccessToken) . PHP_EOL;
        exit(1);
    }

    $accessToken = array_merge($accessToken, $newAccessToken);
    file_put_contents("./token.json", json_encode($accessToken));
    return $accessToken;

}

function getAccessToken()
{
    if (!file_exists("./token.json")) {
        $accessToken = generateAccessTokenByURL();
        return $accessToken;
    }

    $accessToken = json_decode(file_get_contents("./token.json"), true);
    $accessToken = refreshAccessTokenIfExpired($accessToken);
    return $accessToken;
}


$accessToken = getAccessToken();


$client = new Google_Client();
$client->setAccessToken($accessToken);
// Define service object for making API requests.
$service = new Google_Service_YouTube($client);


$queryParams = [
    'channelId'  => $channel_id,
    'maxResults' => 50,
];
$pages = [];
$response = $service->search->listSearch('id', $queryParams);
$nextPage = $response['nextPageToken'];
$pages[] = $response;
while (null != $nextPage){
    $queryParams['pageToken'] = $nextPage;
    $response = $service->search->listSearch('id', $queryParams);
    $nextPage = $response['nextPageToken'];
    $pages[] = $response;
}
$videos_count = 0;
$comments_count = 0;
foreach ($pages as $page){
    foreach ($page['items'] as $videos) {
        $videos_count++;
        $video_id = $videos['id']['videoId'];
        if (null != $video_id) {
            $queryParams = [
                'videoId'    => $video_id,
                'maxResults' => 100,
            ];
            $response    = $service->commentThreads->listCommentThreads('snippet,replies', $queryParams);
            $comments_pages = [];
            $comments_pages[] = $response['items'];
            $nextPageToken_comments = $response['nextPageToken'];

            while(null !== $nextPageToken_comments) {
                $queryParams['pageToken'] = $nextPageToken_comments;
                $response    = $service->commentThreads->listCommentThreads('snippet,replies', $queryParams);
                $comments_pages[] = $response['items'];
                $nextPageToken_comments = $response['nextPageToken'];
            }
            foreach ($comments_pages as $items) {
                foreach ($items as $item) {
                    $comments_count++;
                    $pos = strpos($item['snippet']['topLevelComment']['snippet']['textOriginal'], 'mierda');
                    if ($pos !== false) {
                        echo $item['snippet']['topLevelComment']['id'] . ' ' . $item['snippet']['topLevelComment']['snippet']['authorDisplayName'] . ' DIJO: ' . $item['snippet']['topLevelComment']['snippet']['textOriginal'] . PHP_EOL;
                    }
                }
            }

        }
    }
}
echo $comments_count . " comments analyzed in " . $videos_count . " videos.". PHP_EOL;
