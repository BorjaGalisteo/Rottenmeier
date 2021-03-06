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
require_once 'config.php';

$delete = false;
$banAuthor = false;
if (isset($argv[1]) and "delete" == $argv[1]) {
    $delete = true;
    $message_confirm = 'All comments that contains the word ' . $curse . '. Are you sure? [yes/no]: ';
    if (isset($argv[2]) and "ban" == $argv[2]) {
        $banAuthor = true;
        $message_confirm = 'All comments that contains the word ' . $curse . ', and the author will be BANNED. Are you sure? [yes/no]: ';
    }
    print($message_confirm);
    $confirmation = trim(fgets(STDIN));
    if ("yes" != $confirmation && "no" != $confirmation) {
        echo "not valid option.";
        exit;
    }
    if ("no" == $confirmation) {
        echo "Nothing to do... exit.";
        exit;
    }
}

function getClient($developer_key)
{
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

function generateAccessTokenByURL($developer_key)
{
    $client = getClient($developer_key);

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

function refreshAccessTokenIfExpired($accessToken, $developer_key)
{
    $client = getClient($developer_key);
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

function getAccessToken($developer_key)
{
    if (!file_exists("./token.json")) {
        $accessToken = generateAccessTokenByURL($developer_key);
        return $accessToken;
    }

    $accessToken = json_decode(file_get_contents("./token.json"), true);
    $accessToken = refreshAccessTokenIfExpired($accessToken, $developer_key);
    return $accessToken;
}

function getVideosPages($queryParams, $service, $pages)
{
    $response = $service->search->listSearch('id', $queryParams);
    $queryParams['pageToken'] = $response['nextPageToken'];
    $pages[] = $response;
    if (null == $queryParams['pageToken']) {
        return $pages;
    }
    return getVideosPages($queryParams, $service, $pages);
}

function getCommentsPages($queryParams, $service, $pages)
{
    $response = $service->commentThreads->listCommentThreads('snippet,replies', $queryParams);
    $queryParams['pageToken'] = $response['nextPageToken'];
    $pages[] = $response['items'];
    if (null == $queryParams['pageToken']) {
        return $pages;
    }
    return getCommentsPages($queryParams, $service, $pages);
}

function contains($str, array $arr)
{
    foreach($arr as $a) {
        if (stripos($str,$a) !== false) return true;
    }
    return false;
}

$accessToken = getAccessToken($developer_key);

$client = new Google_Client();
$client->setAccessToken($accessToken);

// Define service object for making API requests.
$service = new Google_Service_YouTube($client);
$queryParams = [
    'channelId' => $channel_id,
    'maxResults' => 50,
];
$pages_with_videos = getVideosPages($queryParams, $service, []);

$videos_count = 0;
$comments_count = 0;
$comments_to_delete = [];
foreach ($pages_with_videos as $page) {
    foreach ($page['items'] as $videos) {
        $videos_count++;
        $video_id = $videos['id']['videoId'];
        if (null != $video_id) {
            $queryParams = [
                'videoId' => $video_id,
                'maxResults' => 100,
            ];
            $comments_pages = getCommentsPages($queryParams, $service, []);
            foreach ($comments_pages as $items) {
                foreach ($items as $item) {
                    $comments_count++;
                    $match = contains($item['snippet']['topLevelComment']['snippet']['textOriginal'],$curse);
                    if ($match !== false) {
                        $comments_to_delete[] = $item['snippet']['topLevelComment']['id'];
                        echo $item['snippet']['topLevelComment']['id'] . ' ' . $item['snippet']['topLevelComment']['snippet']['authorDisplayName'] . ' SAID: ' . $item['snippet']['topLevelComment']['snippet']['textOriginal'] . PHP_EOL;
                    }
                }
            }

        }
    }
}
echo $comments_count . " comments analyzed in " . $videos_count . " videos (" . count($comments_to_delete) . " MATCHS)" . PHP_EOL;
if ($delete) {
    $comments_deleted = 0;
    foreach ($comments_to_delete as $comment_id) {
        $comments_deleted++;
        $service->comments->setModerationStatus($comment_id, 'rejected', ['banAuthor' => $banAuthor]);
    }
    echo $comments_deleted . " COMMENTS DELETED " . PHP_EOL;
}

