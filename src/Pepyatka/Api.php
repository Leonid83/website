<?php
namespace Freefeed\Pepyatka;

use Freefeed\Website\Application;
use GuzzleHttp\Client;

class Api
{
    private $endpoint;

    public function __construct(Application $app)
    {
        $this->endpoint = new Client([
            'base_url' => [
                $app->getSettings()['pepyatka_server'].'/{version}/',
                ['version' => 'v1']
            ],
        ]);
    }

    public function createUserWithHash($login, $bcrypt_hash, $email)
    {
        if (strlen($bcrypt_hash) !== 60) {
            throw new \UnexpectedValueException('hash does not look like a valid bcrypt-hash');
        }

        if (strpos($bcrypt_hash, '$2y$') === 0) {
            // converting to node-compatible mode
            $bcrypt_hash = '$2a'.substr($bcrypt_hash, 3);
        }

        if (strpos($bcrypt_hash, '$2a$') !== 0) {
            throw new \UnexpectedValueException('hash does not look like a valid bcrypt-hash');
        }

        $data = [
            'username' => $login,
            'email' => $email,
            'password_hash' => $bcrypt_hash,
        ];

        try {
            $this->endpoint->post('/users', ['body' => $data]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 422) {
                // user already exists
                return;
            }

            throw $e;
        }
    }
}
