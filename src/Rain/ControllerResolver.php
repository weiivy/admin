<?php
namespace Rain;

class ControllerResolver extends \Symfony\Component\HttpKernel\Controller\ControllerResolver
{
    protected function doGetArguments(\Symfony\Component\HttpFoundation\Request $request, $controller, array $parameters)
    {
        $class = [
            //'Rain\\Request' => $request,
            'Symfony\\Component\\HttpFoundation\\Request' => $request,
            'Rain\\Application' => Application::$app,
        ];

        $attributes = $request->attributes->all();
        $arguments = array();
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $attributes)) {
                $arguments[] = $attributes[$param->name];
            } elseif (($reflectionClass = $param->getClass()) !== null && array_key_exists($reflectionClass->getName(), $class)) {
                $arguments[] = $class[$reflectionClass->getName()];
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                if (is_array($controller)) {
                    $repr = sprintf('%s::%s()', get_class($controller[0]), $controller[1]);
                } elseif (is_object($controller)) {
                    $repr = get_class($controller);
                } else {
                    $repr = $controller;
                }

                throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
            }
        }

        return $arguments;
    }
}