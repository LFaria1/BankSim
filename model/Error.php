<?php
namespace Model;
class Error{
    /**
     * Set error message
     */
    public static function setError($errorMsg){
        $_SESSION["error"]=$errorMsg;
    }
    
    /**
     * Return error message and clear error 
     */    
    public static function getError(){
        if(!isset($_SESSION["error"])){
            $_SESSION["error"]=null;
        }    
        $return = $_SESSION["error"];
        Error::clearError();
        return $return;
    } 
    
    private static function clearError(){
        $_SESSION["error"]=null;
    } 
    
}

?>