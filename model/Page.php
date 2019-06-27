<?php
use Model\Error;
use Model\Success;
use Twig\TwigLoader;
use Model\Functions;

namespace Model;

class Page
{
    /**
     * Receives a path and render the template. If renderPage doens't receive success 
     * or error as parameter, will send error and sucess as null to the template
     * Receites template parameters and merge it with $_SESSION variables containing user data
     */
    public static function renderPage($pagePath, ...$args){
        /*
        * Loading Twig
        */
        $loader = new \Twig\Loader\FilesystemLoader('./templates');
        $twig = new \Twig\Environment($loader, [
            //'cache' => '../templates/cache',
            'cache' => false
        ]);

        $templateArgs = Functions::setArgs($_SESSION,...$args);
       
        
        if(!isset($args["success"])){
        $args["success"]=Success::getSuccess();

        }
        if(!isset($args["error"])){
        $args["error"]=Error::getError();
        }
        echo $twig->render($pagePath, $templateArgs);
    }
}
