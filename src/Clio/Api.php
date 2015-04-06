<?php
namespace Freefeed\Clio;


use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Stream\Stream;

class Api
{
    private $endpoint = null;
    private $clio_token = null;
    private $client = null;

    public function __construct($endpoint, $clio_token = null)
    {
        $this->endpoint = $endpoint;
        $this->clio_token = $clio_token;

        $this->client = new Client(['base_url' => $this->endpoint]);
    }

    /**
     * @param string $username
     * @return \GuzzleHttp\Message\FutureResponse|ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     */
    public function userInfo($username)
    {
        $url = "{$username}.json";

        if (null !== $this->clio_token) {
            $url .= '?clio_token='.urlencode($this->clio_token);
        }

        $request = $this->client->createRequest('GET', $url, ['future' => true]);
        $request->addHeader('Accept', 'application/json');

        try {
            $response = $this->client->send($request);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return false;
        }
    }

    /**
     * @param string $username
     * @return \GuzzleHttp\Message\FutureResponse|ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     */
    public function userSubscriptions($username)
    {
        $url = "{$username}/subscriptions.json";

        if (null !== $this->clio_token) {
            $url .= '?clio_token='.urlencode($this->clio_token);
        }

        $request = $this->client->createRequest('GET', $url);
        $request->addHeader('Accept', 'application/json');

        try {
            $response = $this->client->send($request);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return false;
        }
    }

    /**
     * @param string $username
     * @param string $remote_key
     * @return \GuzzleHttp\Message\FutureResponse|ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     */
    public function auth($username, $remote_key)
    {
        $url = "auth.json";
        $payload = ['user' => $username, 'key' => $remote_key];

        $request = $this->client->createRequest('POST', $url, ['future' => true]);
        $request->addHeader('Accept', 'application/json');
        $request->addHeader('Content-type', 'application/x-www-form-urlencoded');
        $request->setBody(Stream::factory(http_build_query($payload)));

        try {
            $response = $this->client->send($request);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return ['auth' => false];
        }
    }
}
