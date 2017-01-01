<?php

class UpgradeToOmekaS_Controller_Plugin_Down extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $user = current_user();

        // Allow access only to the super user and log out other ones.
        if ($user) {
            if ($user->role == 'super') {
                return;
            }
            $request->setModuleName('default');
            $request->setControllerName('users');
            $request->setActionName('logout');
            return;
        }

        // Allow access to the login page.
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        if ($controller == 'users' && $action == 'login') {
            return;
        }

        $request->setModuleName('down');
        $request->setControllerName('index');
        $request->setActionName('index');
    }
}
