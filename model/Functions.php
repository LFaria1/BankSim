<?php
namespace Model;

use PHPUnit\Framework\Error\Notice;

class Functions
{

    /**
     * Receives a number of arrays and merge into one array
     * If receive an array with arrays inside, each inside array will be an element of the return array;
     * @return array
     */

    public function setArgs(...$params)
    {

        $returnArray = array();
        foreach ($params as $param) {

            if (is_array($param)) {
                foreach ($param as $key => $value) {
                    $returnArray[$key] = $value;
                }
            }
        }
        return $returnArray;
    }

    /**
     * format float value to R$
     * @return string
     */
    public function formatReal($value)
    {
        return number_format($value, 2, ",", ".");
    }

    /**
     * Format date
     * @return string
     */
    public static function formatDate($date, $format = null)
    {
        $time =  \DateTime::createFromFormat("Y-m-d H:i:s", $date);
        if ($format == null) {
            return $time;
        } else {
            return $time->format($format);
        }
    }

    /**
     * Format money input to SQL float format
     */
    public static function moneyToFloat($input)
    {   //Triggering custom error when input can't be converted to float (contains special characters or letters)
        /*
        set_error_handler(function () {
            Error::setError("Valor inválido. Use apenas números e vírgula para centavos");
            return;
        }, E_NOTICE | E_USER_NOTICE);
        */
        $value = str_replace(".", "", $input);
        if (strpos($input,",")!==false) {
            $value=str_replace(",",".",$value);                      
        }else{
            $value = number_format((float)$value,2,".","");  
        }

        return (float)$value;
    }

}
