<?php
namespace Freefeed\Website\Controllers;


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
        $data = $user_model->getAccountFieldsByUsername($username);

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
}
