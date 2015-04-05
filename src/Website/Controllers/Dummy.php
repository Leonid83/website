<?php
namespace Freefeed\Website\Controllers;


use Freefeed\Website\Application;

class Dummy
{
    public function landingAction(Application $app)
    {
        return $app->render('landing.twig');
    }

    public function refuseAction(Application $app)
    {
        return $app->render('refuse.twig');
    }
}
