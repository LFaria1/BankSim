<?php

use Slim\App;
use Model\Page;

return function (App $app) {
   // $app->add(new \Slim\Csrf\Guard);

   /**
    * Middleware that verify which page the user is trying to get, and if they are not logged in
    * or page is not allowed, will redirect to login page
    */
    $app->add(function($request, $response, $next){
        $requestPath=$request->getUri()->getPath();
        $pathArray=["/login","/register"];
        $redirect=true;
        foreach($pathArray as $path){
            if($path==$requestPath){
                
                $redirect=false;
            }
        }

        if(!isset($_SESSION["id"]) && $redirect){      
            $response=$response->withRedirect("/login",302);            
        }
        
        return $next($request,$response);
        

    });
};
