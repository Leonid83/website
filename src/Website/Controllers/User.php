<?php
namespace Freefeed\Website\Controllers;


use Freefeed\Clio\Api;
use Freefeed\Website\Application;
use Freefeed\Website\Models\EmailValidation;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class User
{
    public function loginAction(Application $app)
    {
        return $app->render('login.twig');
    }

    public function loginPostAction(Application $app, Request $request)
    {
        $post = $request->request;

        $username = $post->get('username', '');
        $password = $post->get('password', '');
        $remember = $post->getBoolean('remember');

        if (strlen($username) === 0 or strlen($password) === 0) {
            return $app->render('login.twig', ['error' => 'credentials should not be empty']);
        }

        $user_model = new \Freefeed\Website\Models\User($app);

        if (strpos($username, '@') !== false) {
            $data = $user_model->getAccountFieldsByEmail($username);
        } else {
            $data = $user_model->getAccountFieldsByUsername($username);
        }

        if (null === $data) {
            return $app->render('login.twig', ['error' => 'bad credentials']);
        }

        if (!password_verify($password, $data['password'])) {
            return $app->render('login.twig', ['error' => 'bad credentials']);
        }

        if (password_needs_rehash($data['password'], PASSWORD_DEFAULT)) {
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $user_model->setPassword($data['id'], $new_hash);
            $data['password'] = $new_hash;
        }

        /** @var SessionInterface $session */
        $session = $app['session'];

        if (!$session->isStarted()) {
            $session->start();
        }

        if (!$remember) {
            $session->invalidate(0);
        }

        $session->set('logged_in', true);
        $session->set('user', $data['id']);
        $session->set('password', $data['password']); // stored, so we can remove old sessions

        return $app->redirect($app->path('index'), Response::HTTP_SEE_OTHER);
    }

    public function registerAction(Application $app)
    {
        return $app->render('register.twig', ['errors' => []]);
    }

    public function registerPostAction(Application $app, Request $request)
    {
        $post = $request->request;

        $errors = [];
        $user_model = new \Freefeed\Website\Models\User($app);

        $email = trim($post->get('email', ''));

        if (strlen($email) === 0) {
            $errors[] = ['field' => 'email', 'message' => 'email is required'];
        } else {
            if ($user_model->emailIsTaken($email)) {
                $errors[] = ['field' => 'email' , 'message' => 'this email is already used'];
            }
        }

        $friendfeed_username = trim($post->get('friendfeed_username', ''));

        $api_key = trim($post->get('api_key', ''));
        $backup_me = $post->getBoolean('backup_me');
        $restore_me = $post->getBoolean('restore_me');

        if (strlen($friendfeed_username) === 0) {
            $errors[] = ['field' => 'friendfeed_username', 'message' => 'not given'];
        } elseif ($user_model->friendfeedNameIsTaken($friendfeed_username)) {
            $errors[] = ['field' => 'friendfeed_username', 'message' => 'username is already claimed'];
        }

        if (strlen($api_key) === 0 and $backup_me) {
            $errors[] = ['field' => 'backup_me', 'message' => 'we can not manage your backup, unless you provide API key'];
        }

        if (count($errors) > 0) {
            $data = [
                'email' => $email,
                'friendfeed_username' => $friendfeed_username,
                'errors' => $errors
            ];

            return $app->render('register.twig', $data);
        }

        $clio_api_token = null;

        if (strlen($api_key) > 0) {
            $api = new Api($app->getSettings()['clio_api']);
            $response = $api->auth($friendfeed_username, $api_key);

            if ($response['auth'] === true) {
                $clio_api_token = $response['token'];
            } else {
                $data = [
                    'email' => $email,
                    'friendfeed_username' => $friendfeed_username,
                    'errors' => [['field' => 'remote_key', 'message' => 'remote key verification failed']],
                ];

                return $app->render('register.twig', $data);
            }
        }

        $uid = $user_model->register($friendfeed_username, $email, $clio_api_token, $backup_me, $restore_me);

        $validation_model = new EmailValidation($app);
        $activation_secret = $validation_model->create($uid);

        $body = $app->renderView('email/email_validation.twig', [
            'username' => $friendfeed_username,
            'activation_link' => $app->url('validate_email', ['secret' => $activation_secret]),
        ]);

        $message = new \Swift_Message('freefeed.net: email validation', $body, 'text/plain', 'utf-8');
        $message->setFrom('freefeed.net@gmail.com');
        $message->setTo($email);

        $email_count = $app->mail($message);

        if ($email_count === 0) {
            $user_model->deleteById($uid);
            return $app->abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'can not send email');
        }

        return $app->redirect($app->path('registration_success'), Response::HTTP_SEE_OTHER);
    }

    public function registrationSuccessAction(Application $app)
    {
        return $app->render('registration_success.twig');
    }

    public function validateEmailAction(Application $app, $secret)
    {
        $validation_model = new EmailValidation($app);
        $uid = $validation_model->validate($secret);

        if (false === $uid) {
            $app->abort(Response::HTTP_NOT_FOUND);
        }

        $generator = new ComputerPasswordGenerator();
        $generator
            ->setLength(12)
            ->setUppercase()->setLowercase()->setNumbers()
            ->setSymbols(false);

        $password = $generator->generatePassword();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $user_model = new \Freefeed\Website\Models\User($app);
        $user_model->setEmailValidatedAndPassword($uid, $hash);

        $user = $user_model->getAccountFields($uid);

        if ($user['account_validated'] == 0) {
            $api = new Api($app->getSettings()['clio_api']);
            $match = $api->userEmailMatches($user['friendfeed_username'], $user['email']);

            if ($match) {
                $user_model->validateAccount($uid);
                $user['account_validated'] = true;
            }
        }

        $body = $app->renderView('email/account_created.twig', [
            'username' => $user['friendfeed_username'],
            'password' => $password,
            'login_link' => $app->url('login'),
        ]);
        $message = new \Swift_Message('feeefeed.net: account created', $body, 'text/plain', 'utf-8');
        $message->setFrom('freefeed.net@gmail.com');
        $message->setTo($user['email']);
        $email_count = $app->mail($message);

        $data = [
            'username' => $user['friendfeed_username'],
            'password' => $email_count > 0 ? null : $password,
            'login_link' => $app->url('index'),
        ];

        return $app->render('account_created.twig', $data);
    }
}
