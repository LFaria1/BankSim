<?php
namespace Model;

use Model\SQLConn;
use Model\Error;
use Model\Success;
use Model\Account;

class User
{

    static private $name;
    static private $email;
    static private $id;
    static private $account;
    static private $creationDate;

    /**
     * Setting session for user from database
     * Receives an array 
     */
    public static function setSession($userData)
    {
        self::$name = $userData["name"];
        self::$email = $userData["email"];
        self::$id = $userData["id"];
        self::$account = $userData["account"];
        self::$creationDate = date("d/m/Y",strtotime($userData["created_at"]));

        //Setting class properties to $_SESSION
        $user = new User;
        if (!isset($_SESSION["id"])) {
            foreach (get_class_vars(get_class($user)) as $key => $value) {
                $_SESSION[$key] = $value;
            }
        }
        //somehow account is the only value that doesnt get set strtotime
        $_SESSION["account"]=self::$account;

    }

    public static function unsetSession(){
        foreach($_SESSION as $key=>$value){
            unset($_SESSION[$key]);
        }
    }

    /**
     * If User session is not set, will redirect the user to the login page
     * 
     */
    /*
    public static function verifyLogin()
    {
        if (!isset($_SESSION["id"])) {
            header("Location: /login");
            exit;
        }
    }*/

    /**
     * Return all users registered on database 
     */

    public static function getAllUsers()
    {
        $sql = new SQLConn();
        $query = "SELECT * FROM tb_users";
        //Returns an array with the values inside the first element
        $result = $sql->selectQuery($query);
        return $result[0];
    }

    /**
     * Getters and Setters
     */

    public function setName($param)
    {
        $this->name = $param;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setEmail($param)
    {
        $this->email = $param;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function setId($param)
    {
        $this->id = $param;
    }
    public function getId()
    {
        return $this->id;
    }
    public function setAccount($param)
    {
        $this->account = $param;
    }
    public function getAccount()
    {
        return $this->account;
    }
    /**
     * End Getters and Setters
     */


    /**
     * Verify user credentials.
     * Will return an array with the error if not found in database
     * Receives email and password 
     */
    public static function login($queryParams)
    {
        $sql = new SQLConn();
        $query = "SELECT * FROM tb_users WHERE email=:email";
        $result = $sql->selectQuery($query, [":email" => $queryParams["email"]]);

        if (count($result) == 0) {
            Error::setError("Usuário não encontrado ou senha incorreta");
            Page::renderPage("pages/login.html");
            //PASSWORD IS CLEAR TEXT FOR NOW
            if ($result["password"] !== $queryParams["password"]) {
                Error::setError("Usuário não encontrado ou senha incorreta");
                Page::renderPage("pages/login.html");
            }
        }

        $params = [":password" => $queryParams["password"], ":email" => $queryParams["email"]];
        $query = "SELECT name,email,a.id,b.id as account,a.created_at FROM tb_users a join tb_accounts b on(a.id=b.user_id) WHERE a.email=:email AND a.password=:password";
        $result = $sql->selectQuery($query, $params);
        User::setSession($result[0]);
    }

    /**
     * Check if sent credentials match the current user credentials
     * @return boolean
     */

    public static function checkCredentials($queryParams){
        if($queryParams["email"]!==$_SESSION["email"]){
            return false;
        }
        $sql = new SQLConn();
        $query = "SELECT * FROM tb_users WHERE email=:email AND password =:password";
        $result = $sql->selectQuery($query, [":email" => $queryParams["email"],":password"=>$queryParams["password"]]);
        if(count($result)==0){
            return false;
        }else{
            return true;
        }

    }
    /**
     * Return error if password doesnt match or if user already exists;
     * Receives name, email, password, password-confirm
     * If user created with success, also creates an account for the user
     */
    public static function registerUser($queryParams)
    {
        if (!$queryParams["password"] == $queryParams["password-confirm"]) {
            Error::setError("As senhas devem ser iguais");
            return;
        }
        $sql = new SQLConn();
        $query = "INSERT INTO tb_users (name,email,password) VALUES (:name,:email,:password);";
        $parameters = [
            ":name" => $queryParams["name"],
            ":email" => $queryParams["email"],
            ":password" => $queryParams["password"]
        ];

        //will return an error or the id of the created user
        $result = $sql->query($query, $parameters);
        if (isset($result["error"])) {
            Error::setError("Usuário já existe");
            return ($result["error"]);
        } else {
            Account::createAccount($result);
            Success::setSuccess("Usuário criado com sucesso");
        }
    }
}
