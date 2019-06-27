<?php
namespace Model;
class Success{

    public static function setSuccess($successMsg){
        //Success::$success=$successMsg;
        $_SESSION["success"]=$successMsg;
    } 
    
    public static function getSuccess(){
        if(!isset($_SESSION["success"])){
            $_SESSION["success"]=null;
        }       
        $return =$_SESSION["success"];
        Success::clearSuccess();
        return $return;
    } 
    
    private static function clearSuccess(){
        $_SESSION["success"]=null;
    } 
    
}

?>