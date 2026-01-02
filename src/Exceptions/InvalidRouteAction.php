<?php

declare(strict_types=1);

namespace HybridGram\Exceptions;

final class InvalidRouteAction extends \Exception
{
    public function __construct(mixed $action)
    {
        $actionType = gettype($action);
        
        if (is_array($action) && count($action) < 2) {
            $message = "Invalid route action: array must contain exactly 2 elements [class, method], got " . count($action) . " element(s).";
        } else {
            $message = "Invalid route action type: {$actionType}. Expected callable, string in format 'class@method', or array [class, method].";
        }

        parent::__construct($message);
    }
}
