<?php
namespace Freefeed\Website\Controllers;


use Freefeed\Website\Application;
use Freefeed\Website\Models\User;

class Dashboard
{
    private $app;
    private $model;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->model = new User($app);
    }

    public function inAction()
    {
        return $this->app->render(
            'dashboard.twig',
            [
                'title' => "Хотят во FreeFeed",
                'users' => $this->model->listOptinAccounts(),
            ]
        );
    }

    public function outAction()
    {
        return $this->app->render(
            'dashboard.twig',
            [
                'title' => "Не хотят во FreeFeed",
                'users' => $this->model->listOptoutAccounts(),
            ]
        );
    }

    public function unvalidatedAction()
    {
        return $this->app->render(
            'dashboard.twig',
            [
                'title' => "Неподтверждённые аккаунты",
                'users' => $this->model->listUnvalidatedAccountsWithEmails(),
                'show_emails' => true,
            ]
        );
    }

    public function unconfirmedAction()
    {
        return $this->app->render(
            'dashboard.twig',
            [
                'title' => "Неподтверждённые емейлы",
                'users' => $this->model->listUnconfirmedAccounts(),
                'show_emails' => true,
            ]
        );
    }

    public function undecidedAction()
    {
        return $this->app->render(
            'dashboard.twig',
            [
                'title' => "Сами не знают чего хотят",
                'users' => $this->model->listUndecidedAccounts(),
                'show_emails' => true,
            ]
        );
    }
}
