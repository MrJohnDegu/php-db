<?php

/*
  * Made by John Carlo De Guzman | jcdeguzman88@gmail.com | 
  * db-class 1.0
  * 
  * made with mysqli
  *

* These two classes are needed to let this db class work

* First is you need to setup the connection located at the bottom of this php file.

* Require this file on top of every php page that you will need the class
  
  require_once'path/to/your/db/class/db.php';

* To use the class you will have to make a instance of this class

  $query = new db($c);

    $c can be changed, depends on the setup of the connection on the bottom of this php file.
    but as a standard, don't change the $c variable and just use it as $c;

* Available methods/variables for the instance of this class are

  1.) query() - this will make a new query 

      $query->query('select * from table_name'); 

  2.) params() - this will make a where query and inside the params method is the value of the ? 
                - It will also clean (SQL Injection) all the variables passed with params parameter.

      $query->query('select * from table_name where column_name = ?')->params('1'); 
        - the first question mark will get the first value of the params method.
        - the second question mark will get the second valud of the params method.
        - .....

  3.) exec() - this will execute the query given.

      $query->exec(); 

   *TIP* You can do a combination of all in one line.

    $query->query('select * from table_name where column_name = ?')->params('1')->exec(); 

  4.) rowItem[][] - this will be the array variable that will store the retrieved data.

      $query->rowItem[0]['column_name'];

      the first [] will be the position 
      the second [] will be the column name

        $query->rowItem[0]['account_id'];
          - this will get the first retrieved account_id

  5.) numElements - this is a public variable that will return the number of elements retrieved.

    $query->numElements;

  6.) numItems - this is a public variable that will return the number of items retrieved.

    $query->numItems;

  7.) query - this will return the query that you have thrown.

    $query->query;

  8.) error - this will return the error of the query you have thrown.

    $query->error;

  9.) clear() - this will clear all the data inside the instance of the class

    $query->clear();

  10.) clean() - this will clean the data sent from the parameter to refrain things from SQL Injection

    $query->clean(variableName);
    $query->clean("o'neal");

  11.) escape() - this will escape all the html entities.

    $query->escape('<pre> work work work work. </pre>');

}

*/



class connection{
   public $mysql_host;
   public $mysql_user;
   public $mysql_pass;
   public $mysql_database_name ;
   public $conn;

   public function __construct($_host,$_user,$_pass,$db_name){
       $this->mysql_host = $_host;
       $this->mysql_user = $_user;
       $this->mysql_pass = $_pass;
       $this->mysql_database_name =$db_name;
   }

   public function connect(){
      $this->conn =  mysqli_connect($this->mysql_host,$this->mysql_user,$this->mysql_pass,$this->mysql_database_name);

      if(!$this->conn){
            die('Sorry, we are having some connection problems.');
        }

   }

}

class db{

    //--------------------------------------------------------VARIABLES-----------------------------------------------------------
    private $connection;    //  establishing the connection
    private $tablename;     //  specific table name
    public $query;          //  sets the query
    private $result;        //  for mysqli result
    public $error;          //  throws error
    public $numElements;    //  returns number of elements in the rowItem array (starts with 0)
    public $numItems;       //  returns number of items in the rowItem array (starts with 1)
    public $rowItem = [];   //  array that will hold the results
    //----------------------------------------------------------------------------------------------------------------------------


    //--------------------------------------------------------CONSTRUCTOR-----------------------------------------------------------

    public function __construct(connection $_conn){
      $this->connection = $_conn;
    }

    public function query($_query){
      $this->query = $_query;
      return $this;
    }

    private function str_replace_first($_haystack, $_needle, $_replace)
    {
      $pos = strpos($_haystack, $_needle);
      if ($pos !== false) {
        return substr_replace($_haystack, $_replace, $pos, strlen($_needle));
      }
    }


    public function params($_args){
      $this->params = func_get_args();
      $total = count($this->params);
      for($i = 0; $i < $total; $i++){
        if(is_int($this->params[$i]) || is_float($this->params[$i])){
          $this->query = $this->str_replace_first($this->query,'?',$this->CLEAN($this->params[$i]));
        }else if(is_array($this->params[$i])){
          array_walk_recursive($this->params[$i], function (&$data){
            $data = "'" . $this->connection->conn->real_escape_string(trim($data)) . "'";
          });
          $this->query = $this->str_replace_first($this->query,'?',implode(",",$this->params[$i]));
        }else{
          $this->query = $this->str_replace_first($this->query,'?',"'".$this->CLEAN($this->params[$i])."'");
        }
      }
      return $this;
    }

    public function exec(){
      $this->CLEAR();
      $this->numItems = 0;
      $this->connection->conn->set_charset("utf8");
      if($this->result = $this->connection->conn->query($this->query)){
        if(strtolower(substr($this->query,0,6)) == 'select' || strtolower(substr($this->query,0,4)) == 'show'){
          if($this->result->num_rows){
            while ($row = $this->result->fetch_assoc()) {
              $this->rowItem[] = $row;
            }
              array_walk_recursive($this->rowItem, 'db::escape');
              $this->count($this->result->num_rows);
              $this->result->free();
          }
        }
         return 1;
      }else{
        $this->set_error($this->connection->conn->error);
        return 0;
      }
    return $this;
    }

    private function set_error($err){
      $this->error =  $err;
    }

    public function clear(){
      $this->rowItem = [];
      $this->numItems = 0;
      $this->numElements = 0;
    }

    private function count($items){
      $this->numElements = $items - 1;
      $this->numItems = $items;
    }

    public function clean($data){
      return $this->connection->conn->real_escape_string(trim($data));
    }

    public static function escape(&$data){
      $data = htmlspecialchars($data,ENT_QUOTES,'UTF-8');
      return $data;
    }
}



// IMPORTANT TO SETUP
    
    // (Server Name, Username, Password, Database Name)
    $c = new connection('','','',''); 
    $c->connect(); // to connect to database  -- This will die if the connections is not right
