<?php

namespace michnaadam33\rbacConsole;

use yii\base\BootstrapInterface;
use yii\base\Application;

class RbacConsoleBootstrap implements BootstrapInterface
{
    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        $app->controllerMap = array_merge($app->controllerMap, $this->controllerMap());
    }

    protected function controllerMap()
    {
        return [
            'rbac' => [
                'class' => 'michnaadam33\rbacConsole\RbacController',
            ]
        ];
    }
}