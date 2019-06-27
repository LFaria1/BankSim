<?php
namespace Model;

use Model\SQLConn;
use Model\Functions;

class Loan{
    /**
     * Get all loans from logged account based on actual page and $limit parameter
     * 
     * @return array
     */
    public static function getLoansPage($pageNumber=1,$limit=10){
        $init=($pageNumber*$limit)-$limit;

        $sql = new SQLConn();
        $query = "SELECT * FROM tb_loans where account_id=:account_id order by created_at desc limit :init,:end";
        $params = [":account_id"=>$_SESSION["account"],":init"=>$init,":end"=>$limit];
        $result=$sql->selectQuery($query,$params);

        //var_dump($result);
        //exit;
        return Loan::parseLoanData($result);    

    }

    public static function getLoan($id){
        $sql = new SQLConn();
        $query = "SELECT * FROM tb_loans where id=:id";
        $params = [":id"=>$id];
        $result= $sql->selectQuery($query,$params);
        $result=Loan::parseLoanData($result,true);     
        return $result["loans"][0];
    }

    /**
     * Receive sql response and parse the information to the template format
     * @return array
     */
    public static function parseLoanData($sqlResult){
        
        foreach($sqlResult as $key=>$loan){
            $response["loans"][$key]["id"]=$loan["id"];
            $response["loans"][$key]["startDate"]=Functions::formatDate($loan["created_at"],"d/m/Y");
            $response["loans"][$key]["endDate"]=Loan::getEndDate($loan);
            $response["loans"][$key]["monthlyInstallment"]=Loan::getMonthlyInstallment($loan);
            $response["loans"][$key]["totalValue"]=Functions::formatReal($loan["loan_amount"]);
            $response["loans"][$key]["remainingValue"]=Functions::formatReal($loan["amount_left"]);
            $response["loans"][$key]["monthlyInterest"]=$loan["interest"];
            $response["loans"][$key]["months"]=$loan["months"];
            $response["loans"][$key]["estimatedRemainingValue"]=Functions::formatReal(Loan::getEstimatedValue($loan));
        }

        return $response;
    }

    public static function getLoanProgress(){
        $sql = new SQLConn();
        $query = "SELECT * FROM tb_loans where account_id=:account_id and amount_left > 0 order by amount_left asc limit 6";
        $params = [":account_id"=>$_SESSION["account"]];
        $result =  $sql->selectQuery($query,$params);
        $return= [];
        foreach($result as $key=>$loan){
            $return[$key]["progress"]=(100*($loan["loan_amount"]-$loan["amount_left"])/$loan["loan_amount"]);
            $return[$key]["totalValue"]=Functions::formatReal($loan["loan_amount"]);
            $return[$key]["remainingValue"]=Functions::formatReal($loan["loan_amount"]-$loan["amount_left"]);
            $return[$key]["id"]=$loan["id"];
        }
        return $return;
    }

    /**
     * Returns loan end date
     * @return string d/m/Y format
     */
    public static function getEndDate($loan){
        $date = \DateTime::createFromFormat("Y-m-d H:i:s",$loan["created_at"]);
        $period = $loan["months"];
        return $date->modify("+".$period." month")->format("d/m/Y");

    }

    /**
     * Calculate monthly installment based on the amount left and months left
     */
    public static function getMonthlyInstallment($loan){
        $rValue= Loan::getEstimatedValue($loan);
        $rMonths=Loan::getRemainingMonths($loan);
        $monthlyInstallment = $rValue/$rMonths;
        
        return Functions::formatReal($monthlyInstallment);
    }
    /**
     * Calculate total estimated value based on monthly interest, remaining value and remaining months
     * @return float
     */
    public static function getEstimatedValue($loan){
        $remainingValue=(float)$loan["amount_left"];
        $remainingMonths=Loan::getRemainingMonths($loan);
        $interest=(float)$loan["interest"];
        $estimatedValue = $remainingValue*((1+$interest/100)**$remainingMonths);
        return $estimatedValue;
    }

    /**
     * Calculate loan remaining months
     * @return int
     */
    public static function getRemainingMonths($loan){
        $dateEnd=\DateTime::createFromFormat("Y-m-d H:i:s",$loan["created_at"]);
        $dateNow= new \Datetime();
        $remainingMonths= $dateEnd->diff($dateNow);
        $remainingMonths=$loan["months"]-(int)$remainingMonths->format("M");
        return  $remainingMonths;
    }

    /**
     * Create a loan associated with the user account and update the balance
     * Get loan values from $_SESSION
     */
    public static function makeLoan($userCredentials)
    {
        $loan=$_SESSION["newLoan"];

        foreach($userCredentials as $arg){
            if($arg==""){
                Error::setError("Preencha todos os campos abaixo");
                return;
            }
        }
        if(!User::checkCredentials($userCredentials)){
            Error::setError("Os dados não estão corretos");
            return;
        }
        $sql = new SQLConn();
        $query = "INSERT INTO tb_loans(loan_amount,interest,months,amount_left,account_id) values(
            :loan_amount,:interest,:months,:amount_left,:account_id)";
        $loanValue=Functions::moneyToFloat($loan["value"]);
        $params = [
            ":loan_amount" => $loanValue,
            ":interest" => $loan["monthlyInterest"],
            ":months" => $loan["months"],
            ":amount_left" => $loanValue,
            ":account_id" => $_SESSION["account"]
        ];

        $result=$sql->query($query, $params);
        if(isset($result["error"])){
            error::setError($result["error"]);
            return;
        }else{
            Account::updateBalance($sql, $loanValue);
            Success::setSuccess("Empréstimo efetuado com sucesso");
        }
        
    }

    /**
     * Update loan amount left
     * @param float
     */
    public static function updateLoan($value,$id){
        $sql = new SQLConn();
        $query = "UPDATE tb_loans set amount_left =amount_left + :value where id=:id";
        $params = [":value"=>$value,":id"=>$id];
        $sql->query($query,$params);
    }

    /**
     * Return the start page number and end page number
     * Every class has its own Pagination function because PDO doesn't allow table name binding
     * @return array
     */
    public static function Pagination($actualPage,$pageLimit=8){
        $sql = new SQLConn();
        $query = "select count(*) from tb_loans where account_id=:account_id";
        $params = [":account_id"=>$_SESSION["account"]];
        $result = $sql->selectQuery($query,$params);
        //Rounding up
        $nOfPages=ceil((int)$result[0]["count(*)"]/$pageLimit);
        $return=[];
        
        if($actualPage==1 || $actualPage<=$pageLimit){
            $return["initPage"]=1;            
        }else{
            $return["initPage"]=$actualPage-$pageLimit;
        }

        if($nOfPages<=$pageLimit){
            $return["endPage"]=$nOfPages;
        }else{
            $return["endPage"]=$actualPage+$pageLimit;
        }
        
        return $return;
    }
    /**
     * Calculate loan variables for the user to check before making a loan;
     * @return array
     */

    public static function calculateNewLoan($value,$months){
        $loan["value"]=Functions::formatReal($value);
        $loan["months"]=(int)$months;
        $loan["monthlyInterest"] = 2+((3/36)*$loan["months"]);

        //format to Real
        $totalValue=$value*((1+$loan["monthlyInterest"]/100)**$loan["months"]);

        $loan["totalValue"]=Functions::formatReal($totalValue);
        $now = new \DateTime;
        $loan["startDate"]=$now->format("d/m/y");
        $loan["endDate"]=$now->modify("+".$loan["months"]." month")->format("d/m/Y");

        //format to Real
        $loan["monthlyInstallment"]=Functions::formatReal($totalValue/$loan["months"]);
        return $loan;
    }

    /**
     * Verify if the input credentials and logged user credentials match,
     * and update both account balance and loan amount left
     */
    public static function repayLoan($args,$id){
        
        foreach($args as $arg){
            if($arg==""){
                Error::setError("Preencha todos os campos abaixo");
                return;
            }
        }
        if(!(int)$args["value"]){
            Error::setError("Preencha o campo valor corretamente");
            return;
        }
        if(!User::checkCredentials($args)){
            Error::setError("Os dados não estão corretos");
            return;
        }
        $sql = new SQLConn();
        $query = "SELECT balance FROM tb_accounts WHERE id=:account_id";
        $params = [":account_id"=>$_SESSION["account"]];
        $result = $sql->selectQuery($query,$params);
        $balance = (float)$result[0]["balance"];
        $value = (float)$args["value"]*-1;

        //if balance is not enough
        if($value*-1>$balance){
            Error::setError("Saldo insuficiente");
            return;
        }
        //discount value from loan
        $result = Loan::updateLoan($value,$id);
        if(isset($result["error"])){
            Error::setError("Ocorreu um Erro: ".$result["error"]);
            return;
        }
        //makeTransaction updates account balance
        Account::makeTransaction($value,$id);
        Success::setSuccess("Pagamento Efetuado com sucesso");
        return;

    }
    
}
