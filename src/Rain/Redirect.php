<?php

namespace Rain;

use Symfony\Component\HttpFoundation\RedirectResponse;

class Redirect
{
    public static function to($name, $params = [])
    {
        return new RedirectResponse(Application::$app->urlGenerator->generate($name, $params));
    }
}