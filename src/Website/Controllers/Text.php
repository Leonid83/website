<?php
namespace Freefeed\Website\Controllers;


use Freefeed\Website\Application;

class Text
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function indexAction()
    {
        return $this->app->render('index.twig');
    }

    public function movingAction()
    {
        return $this->app->render('moving.twig');
    }

    public function archiveAction()
    {
        return $this->app->render('archive.twig');
    }

    public function tosAction()
    {
        return $this->app->render('tos.twig');
    }

    public function moneyAction()
    {
        return $this->app->render('money.twig');
    }

    public function restoreAction()
    {
        return $this->app->render('restore.twig');
    }
}
