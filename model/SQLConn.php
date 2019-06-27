<?php

namespace Model;

use phpDocumentor\Reflection\Types\String_;

class SQLConn{

    const HOSTNAME = "127.0.0.1";
	const USERNAME = "root";
	const PASSWORD = "";
    const DBNAME = "bank";
    
    private $conn;

    public function __construct()
    {
        $this->conn = new \PDO(
			"mysql:dbname=".SqlConn::DBNAME.";host=".SqlConn::HOSTNAME, 
			SqlConn::USERNAME,
			SqlConn::PASSWORD
        ) or die("Sem acesso ao DB");

        //Will throw exception if anything fail
        $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        //there is bug in PDO that doesn't let you bind integers to LIMIT in sql
		$this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES,false);
    }

    public function returnLastId(){
        return $this->conn->lastInsertId();
    }

    /**
     * Bind all parameters at once
     */
    private function setParameters($statement,$params=array()){        
        foreach($params as $key => $value){
            $this->bindParam($statement,$key,$value);
        }
    }

    /**
     * Bind one parameters
     */
    private function bindParam($statement, $key, $value){
		$statement->bindParam($key, $value);

	}

    /**
     * Used when query is not a select query. Return the id(primary key)
     * of the inserted/updated/deleted element
     * @param string $rawQuery
     * @param array $params
     */
    public function query($rawQuery,$params = array()){
        $statement = $this->conn->prepare($rawQuery);
        $this->setParameters($statement,$params);
        try{
            $statement->execute();
            return $this->returnLastId();
            }catch(\PDOException $e){
                return array("error"=>$e->getMessage());
            }
    }

    /**
     * Used when getting data from SQL database.
     * @param string $rawQuery
     * @param array $params
     */
    public function selectQuery($rawQuery,$params = array()){
        $statement = $this->conn->prepare($rawQuery);
        $this->setParameters($statement,$params);        

        try{
            $statement->execute();
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
            }catch(\PDOException $e){
                return array("error"=>$e->getMessage());
            }
    }



}



?>