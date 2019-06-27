<?php
namespace Model;

use Model\SQLConn;

class Account
{

    private $user;
    private $balance;
    private $loans;

    /**
     * Creating an account for the new user
     */
    public function createAccount($userId)
    {
        $sql = new SQLConn();
        $query = "INSERT INTO tb_accounts (user_id) VALUES (:userId)";
        $params = [":userId" => $userId];
        $sql->query($query, $params);
    }

    /**
     * Get values to plot graphic
     * @return array
     */
    public static function getHomeInfo()
    {
        $result["graphData"] = Account::getMonthlyFluctuation();
        $result["accountBalance"] = Functions::formatReal(Account::getBalance()["balance"]);

        $monthIncome=Account::getTransactions($_SESSION["account"], 1, 30);
        $result["monthIncome"] = Functions::formatReal($monthIncome);

        $monthOutcome = Account::getTransactions($_SESSION["account"], -1, 30);
        $result["monthOutcome"] =  Functions::formatReal($monthOutcome);
        $result["monthBalance"] = Functions::formatReal($monthOutcome+$monthIncome);

        $result["loans"] = Loan::getLoanProgress();
        return $result;
    }

    /**
     * Return all transactions associated with this account
     * $type = 0 will return all transactions. $type = 1 will return only positive transactions. $type = -1 will return negative transactions
     * if interval is defined, return transactions from the lasts $interval days
     * @return array
     */
    public static function getTransactions($account_id, $type = 0, $interval = 0)
    {
        $sql = new SQLConn();
        $query = "SELECT * FROM tb_transaction_history WHERE account_id=:account_id";
        $params = [":account_id" => $account_id];
        if ($interval > 0) {
            $query = $query . " AND created_at between curdate() - interval :interval day and curdate()";
            $params[":interval"] = $interval;
        }

        $result = $sql->selectQuery($query, $params);
        $return = 0;

        if ($type == 1 || $type == -1) {
            foreach ($result as $row) {
                if ((float)$row["transaction_value"] * $type > 0) {
                    $return += (float)$row["transaction_value"];
                }
            }
        } else {
            $return = $result;
        }
        return $return;
    }

    /**
     * Return all transactions associated with this account based on actual page and $limit parameter
     * @return array 
     */
    public static function getTransactionPage($pageNumber=1,$limit=8){
        $init=($pageNumber*$limit)-$limit;

        $sql = new SQLConn();
        $query = "SELECT * FROM tb_transaction_history WHERE account_id=:account_id order by created_at desc limit :init,:end";
        $params = [":account_id"=>$_SESSION["account"],":init"=>$init,":end"=>$limit];
        $result = $sql->selectQuery($query,$params);
        $return["transactions"]=[];
        foreach ($result as $key=>$transaction){
            $return["transactions"][$key]=$result[$key];
            $return["transactions"][$key]["date"]=Functions::formatDate($transaction["created_at"],"d/m/Y");
            $return["transactions"][$key]["displayColor"]=(float)$transaction["transaction_value"]>0?"green":"red";
            $return["transactions"][$key]["value"]=Functions::formatReal($transaction["transaction_value"]);            
            $return["transactions"][$key]["type"]=Account::getTransactionType($result[$key]);            
        }
        return $return;
    }

    /**
     * Return the start page number and end page number
     * Every class has its own Pagination function because PDO doesn't allow table name binding
     * @return array
     */
    public static function Pagination($actualPage,$pageLimit=5){
        $sql = new SQLConn();
        $query = "select count(*) from tb_transaction_history where account_id=:account_id";
        $params = [":account_id"=>$_SESSION["account"]];
        $result = $sql->selectQuery($query,$params);
        //Rounding up
        $nOfPages=ceil((int)$result[0]["count(*)"]/8);
        $return=[];
        
        if($actualPage==1 || $actualPage<=5){
            $return["initPage"]=1;            
        }else{
            $return["initPage"]=$actualPage-5;
        }

        if($nOfPages<=5){
            $return["endPage"]=$nOfPages;
        }else{
            $return["endPage"]=$actualPage+5;
        }
        
        return $return;
    }

    /**
     * Return transaction type (loan,deposit,withdraw)
     * @return string
     */
    public static function getTransactionType($transaction){
        $value=(float)$transaction["transaction_value"];
        if($transaction["loan_id"]!=null){
            return "Empréstimo";
        }else if($value>0){
            return "Depósito";
        }else{
            return "Despesa";
        }

    }

    /**
     * Calculate I/O of this account from the last 6 months.
     * return an array with two arrays
     */
    public static function getMonthlyFluctuation($range = 6)
    {
        //implement 6 months limit
        $in = array();
        $out = array();
        $transactions =  Account::getTransactions($_SESSION["account"]);
        $time = new \DateTime("", new \DateTimeZone('America/Sao_Paulo'));
        for ($i = 1; $i <= $range; $i++) {
            $month = $time->format("m");
            $in[$month] = 0;
            $out[$month] = 0;
            $time->modify('-1 month');
        }

        foreach ($transactions as $transaction) {
            //converting to UNIX timestamp
            $timestamp = strtotime($transaction["created_at"]);
            $value = (float)$transaction["transaction_value"];

            //Getting transaction month and using as a key for the associative array
            $key = date("m", $timestamp);
            if ($value > 0) {
                $in[$key] += $value;
            } else {
                $out[$key] += abs($value);
            }
        }
        //Sorting array keys in ascending order
        ksort($in);
        ksort($out);

        return ["in" => $in, "out" => $out];
    }
    /**
     * Make a deposit/withdraw. If value is negative, is a payment or withdraw
     * @param float
     * @param int
     */
    public static function makeTransaction($value,$loanId=0)
    {
        $value = sprintf('%01.2f', $value);

        $sql = new SQLConn();
        
        if($loanId!==0){
        $query = "INSERT INTO tb_transaction_history (account_id,transaction_value,loan_id) VALUES(:account_id,:value,:loan_id)";
        $params = [":account_id" => $_SESSION["account"], ":value" => $value,":loan_id"=>$loanId];
        }else{
        $query = "INSERT INTO tb_transaction_history (account_id,transaction_value) VALUES(:account_id,:value)";
        $params = [":account_id" => $_SESSION["account"], ":value" => $value];
        }
        $result = $sql->query($query, $params);
        if (isset($result["error"])) {
            Error::setError("Não foi possível efetuar a transferência.");
        } else {
            Success::setSuccess("Transação efetuada com sucesso.");
        }

        Account::updateBalance($sql, $value);
    }
    
    /**
     * Get account balance 
     */
    public static function getBalance()
    {
        $sql = new SQLConn();
        $query = "SELECT balance FROM tb_accounts WHERE id=:id";
        $params = [":id" => $_SESSION["account"]];

        $return = $sql->selectQuery($query, $params);
        return $return[0];
    }

    /**
     * Update account balance
     * If value is negative, debit from account;
     */
    public static function updateBalance($sql, $value)
    {
        $query = "UPDATE tb_accounts set balance=balance+:balance where user_id=:id ";
        $params = [":balance" => $value, ":id" => $_SESSION["id"]];
        $sql->query($query, $params);
    }

}
