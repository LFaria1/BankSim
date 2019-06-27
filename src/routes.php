<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Model\User;
use Model\Error;
use Model\Success;
use Model\Loan;
use Model\Page;
use Model\Functions;
use Model\Account;

return function (App $app) {

    $container = $app->getContainer();

    $app->get('/', function (Request $request, Response $response, array $args) {
        // echo $args['name'];
        $users = User::getAllUsers();
        Page::renderPage("index.html", ['users' => $users]);
    });

    /**
     * Login pages routes
     */
    $app->get('/login', function (Request $request, Response $response, array $args) {
        if (isset($_SESSION["id"])) {
            return $response->withRedirect("/home", 301);
        }
        Page::renderPage("pages/login.html");
    });

    $app->post('/login', function (Request $request, Response $response, array $args) {
        User::login($request->getParams());
        return $response->withRedirect("/home", 301);
    });

    /**
     * Register pages routes
     */
    $app->get('/register', function (Request $request, Response $response, array $args) {
        Page::renderPage("pages/register.html");
    });


    $app->post('/register', function (Request $request, Response $response, array $args) {
        $result = User::registerUser($request->getParams());
        if (isset($result["error"])) {
            Page::renderPage("pages/register.html");
        } else {
            Page::renderPage("pages/login.html");
        }
    });

    /**
     * Home pages routes
     */
    $app->get('/home', function (Request $request, Response $response, array $args) {
        //$args=Functions::setArgs($_SESSION,Account::getGraphInfo());

        Page::renderPage("pages/home-old.html", Account::getHomeInfo());
    });

    /**
     * Transaction pages routes
     */
    $app->get("/transactions", function (Request $request, Response $response, array $args) {
        return $response->withRedirect("/transactions/1", 301);
    });

    $app->get("/transactions/{page}", function (Request $request, Response $response, array $args) {
        $transactions = Account::getTransactionPage($args["page"]);
        $pages = Account::Pagination((int)$args["page"]);
        $pages["actualPage"] = $args["page"];
        Page::renderPage("pages/transaction.html", $transactions, $pages);
    });

    $app->post("/transactions/deposit", function (Request $request, Response $response, array $args) {
        $params = $request->getParams();
        $value = Functions::moneyToFloat($params["value"]);

        Account::makeTransaction($value);
        return $response->withRedirect("/transactions", 301);
    });
    $app->post("/transactions/withdraw", function (Request $request, Response $response, array $args) {
        $params = $request->getParams();
        $value = Functions::moneyToFloat($params["value"]);
        //get actual balance;
        $balance = Account::getBalance()["balance"];

        if ((float)$balance <= $value) {
            Error::setError("Saldo insuficiente");
        } else {
            Account::makeTransaction(-1 * $value);
        }
        return $response->withRedirect("/transactions", 301);
    });


    /**
     * Logout Route
     * Clear $_SESSION cointaining user data and redirect the user to login page
     */
    $app->get("/logout", function (Request $request, Response $response, array $args) {
        User::unsetSession();
        return $response->withRedirect("/login", 301);
    });

    /**
     * Loans pages routes
     */
    $app->get("/loans", function (Request $request, Response $response, array $args) {
        return $response->withRedirect("/loans/page/1");
    });

    $app->post("/loans/new-loan", function (Request $request, Response $response, array $args) {
        $params=$request->getParams();
        $value=Functions::moneyToFloat($params["value"]);
        //explode to remove the "months" string 
        $months=explode(" ",$params["months"]);        
        $loan=Loan::calculateNewLoan($value,(int)$months[0]);
        $_SESSION["newLoan"]=$loan;

        return $response->withRedirect("/loans/new-loan");
    });

    $app->get("/loans/new-loan", function (Request $request, Response $response, array $args) {
     
        Page::renderPage("pages/new-loan.html",$_SESSION["newLoan"]);
    });

    $app->post("/loans/new-loan/register-loan", function (Request $request, Response $response, array $args) {
        $userCredentials = $request->getParams();
        Loan::makeLoan($userCredentials);

        if(isset($_SESSION["error"])){
           $response= $response->withRedirect("/loans/new-loan",301);
        }else{           
           $response= $response->withRedirect("/loans",301);
        }
        return $response;

    });
/*
    $app->get("/loans/new-loan", function (Request $request, Response $response, array $args) {
        Page::renderPage("pages/new-loan.html",$params);
    });
*/
    $app->get("/loans/page", function (Request $request, Response $response, array $args) {
        return $response->withRedirect("/loans/page/1");
    });
    $app->get("/loans/page/{page}", function (Request $request, Response $response, array $args) {
        $loans = Loan::getLoansPage($args["page"]);
        $pages = Loan::Pagination($args["page"]);
        $pages["actualPage"] = $args["page"];
        Page::renderPage("pages/loans.html", $loans, $pages);
    });

    $app->get("/loans/{id}", function (Request $request, Response $response, array $args) {
        Page::renderPage("pages/loan.html", Loan::getLoan($args["id"]));
    });
    $app->post("/loans/{id}", function (Request $request, Response $response, array $args) {
        Loan::repayLoan($request->getParams(), $args["id"]);
        return $response->withRedirect("/loans/" . $args["id"]);
    });
    /*
    $app->post("/transactions/deposit", function (Request $request, Response $response, array $args) {
        $params = $request->getParams();
        Account::makeTransaction($params["value"]);
        return $response->withRedirect("/transaction", 301);;
    });
    $app->post("/transactions/withdraw", function (Request $request, Response $response, array $args) {
        $params = $request->getParams();
        Account::makeTransaction($params["value"], false);
        Page::renderPage("pages/transaction.html");
    });*/
};
