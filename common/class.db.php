<?php
define("MSB_NL", "\r\n");
define("SMS_CHARACTERS","ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789*()-+:.? ");
require_once("php.php");
class DB
{
    /* global variables */
    var $dbhost = DB_SERVER;              // default database host
    var $dblogin = DB_USER;               // database login name
    var $dbpass = DB_PASSWORD;            // database login password
    var $dbname = DB_NAME;                // database Name
    var $factory_code = FACTORY_CODE;     // database Name
    var $dblink;                    // database link identifier
    var $queryid;                         // database query identifier
    var $error = array();                 // storage for error messages
    var $record = array();                // database query record identifier
    var $totalrecords;                    // the total number of records received from a select statement
    var $last_insert_id;                  // last incremented value of the primary key
    var $previd = 0;                      // previus record id. [for navigating through the db]
    var $transactions_capable = true;     // does the server support transactions?
    var $begin_work = false;              // sentinel to keep track of active transactions

   function DB($dblogin, $dbpass, $dbname, $dbhost = null) {
        if ( strtolower($dbname)<>"mydb" && $dbname<>"" ) {
             $this->dbname= trim($dbname);
        }
        if ($dbhost != null) {
            $this->dbhost = trim($dbhost);
        }
    } // end function

    function connect() {
        $this->dblink = @mysql_connect($this->dbhost, $this->dblogin, $this->dbpass);
        if (!$this->dblink) {
            $this->return_error('Unable to connect to the database.');
        } else {
            $t = @mysql_select_db($this->dbname, $this->dblink);
            if (!$t) {
               $this->return_error('Unable to open databases.');
            }
        }
        /*if (CHINESE_DISPLAY=="1") {
             mysql_query("SET character_set_client=utf8", $this->dblink);
             mysql_query("SET character_set_connection=utf8", $this->dblink);
        }
        */

        return $this->dblink;
    } // end function

    function disconnect() {
        
        if (! empty($this->dblink) ) {
           
              $test = @mysql_close($this->dblink);
              if (!$test) {
                  $this->return_error('Unable to close the connection.');
              }
              unset($this->dblink);
           
        }
    } // end function

    function return_error($message) {
        return $this->error[] = $message.' '.mysql_error().'.';
    } // end function

    function showErrors() {
        $ret="";
        if ($this->hasErrors()) {
            reset($this->error);
            $errcount = count($this->error);    //count the number of error messages
            $ret="<p>Error(s) found: <b>'$errcount'</b></p>\n";
            // print all the error messages.
            while (list($key, $val) = each($this->error)) {
                $ret.= "+ $val<br>\n";
            }
            $this->resetErrors();
        }
        return $ret;
    } // end function

    function hasErrors() {
        if (count($this->error) > 0) {
            return true;
        } else {
            return false;
        }
    } // end function
    
    //Clears all the error messages.
    function resetErrors() {
        if ($this->hasErrors()) {
            unset($this->error);
            $this->error = array();
        }
    } // end function

    function query($sql) {
        if (empty($this->dblink)) {
            // check to see if there is an open connection. If not, create one.
            $this->connect();
        }
        $this->queryid = @mysql_query($sql, $this->dblink);
        if (!$this->queryid) {
            if ($this->begin_work) {
                $this->rollbackTransaction();
            }
            $this->return_error('Unable to perform the query <b>' . $sql . '</b>.');
        }
        $this->previd = 0;
        return $this->queryid;
    } // end function

    function fetchRow() {
        $this->previd++;
        return $this->record = @mysql_fetch_array($this->queryid);
    } // end function

    function moveFirst() {
        if (! empty($this->queryid)) {
            $t = @mysql_data_seek($this->queryid, 0);
            if ($t) {
                $this->previd = 0;
                return $this->fetchRow();
            } else {
                $this->return_error('Cant move to the first record.');
            }
        } else {
            $this->return_error('No query specified.');
        }
    } // end function

    function moveLast() {
        if (! empty($this->queryid)) {
            $this->previd = $this->resultCount()-1;
            $t = @mysql_data_seek($this->queryid, $this->previd);
            if ($t) {
                return $this->fetchRow();
            } else {
                $this->return_error('Cant move to the last record.');
            }
        } else {
            $this->return_error('No query specified.');
        }
    } // end function

    function moveNext() {
        return $this->fetchRow();
    } // end function

    function movePrev() {
        if (! empty($this->queryid)) {
            if ($this->previd > 1) {
                $this->previd--;
                $t = @mysql_data_seek($this->queryid, --$this->previd);
                if ($t) {
                    return $this->fetchRow();
                } else {
                    $this->return_error('Cant move to the previous record.');
                }
            } else {
                $this->return_error('BOF: First record has been reached.');
            }
        } else {
            $this->return_error('No query specified.');
        }
    } // end function
    function moveTo($id) {
        if (! empty($this->queryid)) {
            $t = @mysql_data_seek($this->queryid, $id);
	}
    } // end moveTo function

    function fetchLastInsertId() {
        $this->last_insert_id = @mysql_insert_id($this->dblink);
        if (!$this->last_insert_id) {
            return 0 ;   //$this->return_error('Unable to get the last inserted id');
        }
        return $this->last_insert_id;
    } // end function

    function resultCount() {
        $this->totalrecords=0;
        if (! empty($this->queryid)) {
           $this->totalrecords = @mysql_num_rows($this->queryid);
           if (!$this->totalrecords) {
               $this->return_error('Unable to count the number of rows returned');
           }
        }
        return $this->totalrecords;
    } // end function

   
    function resultExist() {
        if (! empty($this->queryid) && ($this->resultCount() > 0)) {
            return true;
        }
        return false;
    } // end function

    function clear($result=false) {
        if ($result) {
           
            $t = @mysql_free_result($result);
            
            if (!$t) {
                $this->return_error('Unable to free the results from memory.');
            }
        } else {
            if (! empty($this->queryid) ) {
                $t = @mysql_free_result($this->queryid);
                if (!$t) {
                    $this->return_error('Unable to free the results from memory.');
                }
            } else {
                $this->return_error('Nothing to clear.');
            }
        }
    } // end function

    function get_errors() {
        return $this->error;
    } // end function

    function beginTransaction() {
        if ($this->transactions_capable && $this->begin_work==false) {
            //$this->query("START TRANSACTION",$this->dblink);
            $this->query("BEGIN"); 
            $this->begin_work = true;
        }
    } // end function

    function commitTransaction() {
        if ($this->transactions_capable) {
            if ($this->begin_work) {
                $this->query('COMMIT');
                $this->begin_work = false;
            }
        }
    }

    function rollbackTransaction() {
        if ($this->transactions_capable) {
            if ($this->begin_work) {
                $this->query('ROLLBACK');
                $this->begin_work = false;
            }
        }
    } // end function

    // Build "Replace into" statement normally used to download the data to another site
    function getInserts($table,$s,$start,$end) {
        $value = '';
        if (strlen($s)==0) {
           $s="SELECT * FROM `".$table."`";
        }
        if ($end>0 && $start>0) {
            $s.= " LIMIT ". $start. "," .$end;
        } elseif  ($start>0 && $end==0) {
            $s.= " LIMIT ". $start;
        }
        
	if (!($this->queryid = $this->query($s))) {
            return '';
        }
        if ($this->resultCount() == 0) {
            return '';
        }
	while ($row = @mysql_fetch_row($this->queryid))
	{
		$values = "";
		foreach ($row as $data)
		{
		    $values .= "'" . addslashes($data) . "', ";
		}
		$values = substr($values, 0, -2); // Remove last 2 characters
                $values = str_replace( chr(13).chr(10),"\\r\\n",$values);
		$value .= "REPLACE INTO `".$table . "` VALUES (" . $values . ");" . MSB_NL;
	}
	return $value;
		
    } // getInserts    
    function getInserts2($table,$fields,$s) {
        $ret = '';
        if (!($this->queryid = $this->query($s))) {     
            return '';
        }
        if ($this->resultCount() == 0) {
            return '';
        }
	while ($row = @mysql_fetch_row($this->queryid))	{
 	   $row_val ="REPLACE INTO $table ($fields) VALUES (";
	   foreach ($row as $data) {
             $row_val .= "'" . addslashes($data) . "', ";
           }
	   $row_val = substr($row_val, 0, -2); // Remove last 2 characters
           $row_val = str_replace( chr(13).chr(10),"\\r\\n",$row_val);
	   $ret .= $row_val . ");" . MSB_NL;
	}
        
	return $ret;
    } // getInserts2    
    // FOR APP
    //=============
    function getUserByUsernameAndPassword($username, $password) {
        $username=trim(sanitize($username,false));
        $password=trim(sanitize($password,false));
        $this->query("SELECT * FROM myuser WHERE userName= '$username' AND userPass=md5('$password')");
        if ($this->resultCount() > 0) {
             return $this->fetchRow();
        } else {
            // user not found
            return false;
        }
    }
    // === For APP
} // --------------------   end of DB class -------------------------------------------

FUNCTION array_sort($arr) {
   $arr2 = $arr;
   $a_ret=array();
   asort($arr2);
   foreach($arr2 as $key => $val) {
        $a_ret[] = $arr[$key];
   }

   return $a_ret;
} 


FUNCTION to_upper($string)
{
  $new_string = "";
  while (eregi("^([^&]*)(&)(.)([a-z0-9]{2,9};|&)(.*)", $string, $regs))
  {
    $entity = $regs[2].strtoupper($regs[3]).$regs[4];
    if (html_entity_decode($entity) == $entity)
      $new_string .= strtoupper($regs[1]).$regs[2].$regs[3].$regs[4];
    else
      $new_string .= strtoupper($regs[1]).$entity;
    $string = $regs[5];
  }
  $new_string .= strtoupper($string);
  return $new_string;
}
function isnull($data)
{
  if ($data === NULL || $data === 'NULL' || $data === 'null') {
    return true;
  }
  return false;
}

function getIP() {                                                              
    if(getenv('HTTP_CLIENT_IP')) {                                              
       $ip = getenv('HTTP_CLIENT_IP');                                         
    } else if(getenv('HTTP_X_FORWARDED_FOR')) {                                 
        $ip = getenv('HTTP_X_FORWARDED_FOR');                                   
    } else {                                                                    
      $ip = getenv('REMOTE_ADDR');                                            
    }                                                                           
    return $ip;                                                                 
}                             



FUNCTION getuser_info2($db,$user_id) {
$data=array();
$data[0]="0";
$data[1]="";
$data[2]="" ;

if ( isset($user_id) && ! empty($user_id) ) {
  $s="SELECT u.real_name,u.email FROM myuser u WHERE u.username ='" . trim($user_id) . "'";
  $db->query($s); 
  if ($db->resultCount()>0) {
      $data[0]="1";
      $db->fetchRow();
      $data[1]= trim($db->record['real_name']);
      $data[2]= trim($db->record['email']);
      $db->clear(); 
  } else {
      $db->clear();
  }
}
return $data;
}  // getuser_info2;
FUNCTION save_sys_log($db, $prog, $func1, $func2,$min_rec_date_time,$rec_date_time,$rec_counter, 
                      $rec_rowid, $remarks="",$auto_purge="Y" ) {
   $prog=trim($prog);
   $func1=trim($func1);
   $func2=trim($func2);
   $rec_date = substr($rec_date_time,0,10);
   $remarks = addslashes($remarks); 

   $v_wh = " where prog = '$prog'  and func1 ='$func1' and func2 ='$func2'";

   $s = "select prog from sys_log " . $v_wh;
   $db->query($s);

   if ($db->resultCount()==0) {

     $s = "INSERT INTO sys_log (prog, func1, func2,min_rec_date_time,rec_date_time, rec_date, rec_counter, rec_rowid, sys_date,remarks,auto_purge) 
          VALUES ('$prog','$func1','$func2','$min_rec_date_time','$min_rec_date_time','$rec_date',$rec_counter,$rec_rowid,now(),'$remarks','$auto_purge')";
        
   } else {
     $s = "UPDATE sys_log SET min_rec_date_time = '$min_rec_date_time',rec_date_time ='$rec_date_time',rec_date='$rec_date',
          rec_counter=$rec_counter,rec_rowid=$rec_rowid,sys_date=now(),remarks='$remarks',auto_purge='$auto_purge'" . $v_wh;
   
   }
   $db->clear(); 
   $db->query($s);

} // save_sys_log

FUNCTION save_error($db, $prog, $func, $error ) {
   $error=sanitize($error,false);
   $error=addslashes($error);
   $db->query("SELECT count(*) as cnt from sys_error WHERE prog='$prog' AND func='$func' and date_time>date_sub(now(),interval 20 minute) and errors='$error'");
   $db->fetchRow();
   if ($db->record['cnt']==0) {
      $db->clear();
      $s="insert into sys_error (prog, func, date_time, errors) values ('$prog','$func',now(),'$error')";
      $db->query($s);
   } else {
      $db->clear();
   }
} // save_error


FUNCTION save_error2($db, $prog, $pc_name, $func, $error ) {
   $prog=trim($prog);
   $pc_name=trim($pc_name);
   $func=trim($func);
   $error=sanitize($error,false);
   $error=addslashes(trim($error));
   $db->query("SELECT count(*) as cnt from sys_error WHERE prog='$prog' AND pc_name='$pc_name' AND func='$func' and date_time>date_sub(now(),interval 20 minute) and errors='$error'");
   $db->fetchRow();
   if ($db->record['cnt']==0) {
      $db->clear();
      $db->query("insert into sys_error (prog, pc_name, func, date_time, errors) values ('$prog','$pc_name','$func',now(),'$error')");
   } else {
      $db->clear();
   }
} // save_error

FUNCTION is_marked($a_all_items,$item_to_check) {
  $xx=false;
  if (count($a_all_items)>0)   {
      for ($j = 0; $j < count($a_all_items) ; $j++) {
          if ( trim($a_all_items[$j]) === trim($item_to_check) ) {
               $xx=true;
               break;   
          }
      }
  }
  return $xx;  
}
// SAMPLE module='SCR_ENTRY' and code='SCRNO' and optional_code='SC'";
// Retuen "" means Error
FUNCTION get_new_key($db,$module,$code,$optional_code,$prefix,$suffix,$description,$length ) {

   $module=trim($module);
   $code=trim($code);
   $prefix=trim($prefix);
   $optional_code = trim($optional_code);
   $description = addslashes($description);
   $wh = " WHERE module='$module' and code='$code' and optional_code='$optional_code'";
   $db->query("SELECT key_value FROM m_system_key " . $wh );

   if ($db->resultCount() > 0) {
      $db->fetchRow();
      $new_value = trim(intval($db->record['key_value']) + 1);
      $db->clear();  
      if (ceil($new_value)<=0) {
           RETURN "";   // Considered as Error Flag
      }
      $db->resetErrors();
      $db->query("UPDATE m_system_key SET key_value = '$new_value' ". $wh );
      if ($db->hasErrors()==true) {
           RETURN "";
      }
      $db->query("SELECT key_value,prefix FROM m_system_key " . $wh );
     
      $db->fetchRow();
      $ret = trim($db->record['key_value']);
      
      if ($new_value<>$ret) {   // Something is wrong
          $db->clear();     
          return "";
      }   
      if (! is_null($db->record['prefix'])) {
          if ( strlen($db->record['prefix'])>0  && strlen($prefix)==0 ) {
               $prefix=trim($db->record['prefix']);
          }
      }
      $db->clear();     
      if ($length > 0 && $length > strlen(trim($ret))) {
          $ret = str_pad(trim($ret),$length, "0", STR_PAD_LEFT);
      }
      $s_upd = "UPDATE m_system_key SET key_value = '$ret' " . $wh ; 
   } else {
      $db->clear();  
      $ret='1';

      if ($length > 0 && $length > strlen(trim($ret))) {
         $ret = str_pad(trim($ret),$length, "0", STR_PAD_LEFT);
      }
      
      $s_upd= "INSERT INTO m_system_key (module, code, optional_code, prefix, suffix, key_value, description) VALUES " .
              "('$module','$code','$optional_code','$prefix','$suffix','$ret','$description')";
     
   }
   $db->resetErrors();
   $db->query($s_upd);

   if ($db->hasErrors()==true) {
       $ret="";
   } else {
       $db->query("SELECT key_value FROM m_system_key " . $wh );
       if ($db->resultCount() > 0) {
           $db->fetchRow();
           if ($ret<>trim($db->record['key_value'])) {
                $ret="";   // Something is wrong
           }
       } else {
           $ret="";
       }
       $db->clear();      
   }
   if ($ret<>"" && strlen(trim($prefix))>0) {
        $ret=$prefix.$ret;
   }
   return $ret;
}

FUNCTION check_master($db,$a_table,$a_table_name) {

 $msg="<FONT color=red>";
 $cnt = count($a_table);

  for ($i = 0; $i < $cnt; $i++) {
     $db->query("SELECT count(*) FROM ".$a_table[$i]);
     if ($db->resultCount()==0) {
        $msg .= $a_table_name[$i]." Not Set Up<BR>";
     } 
     $db->clear(); 
  }
  $msg .="</font>";
  RETURN $msg;
}
// splict stock code into 2 portion where the last digit is Ahpla Character
FUNCTION get_index($stock_code) {
     $data =array(0);
      $data[0]= trim($stock_code);
      $data[1]="";
      $x = strlen ($data[0]);

      $partindex=substr($data[0],$x-1,1);

      if (preg_match('/^[a-zA-Z]+$/', $partindex)) {
           $data[0]=substr($data[0],0, $x-1);
           $data[1]=$partindex;
      }
      return $data;
}


FUNCTION check_access($db,$user_id,$prog) {
   $prog=trim($prog);
   $access=array();
   $access[0]=false;
   $access[1]="N";
   $access[2]="N";
   $access[3]="N";

   $user_id=trim($user_id);
   if ($user_id=="admin") {
      $access[0]=true;
      $access[1]="Y";
      $access[2]="Y";
      $access[3]="Y";
      return $access;
   }
   $db->query("select l.add_rec,del_rec,l.write_access from myuser m ,m_access_list l where m.ID=l.id
               and m.userName='$user_id' and l.prog='$prog'");
   
   if ($db->resultCount()>0) {
     $access[0]=true;
     $db->fetchRow();
     if ( ! is_null($db->record['write_access']) ) {
        $access[1]=$db->record['write_access'];
        $access[2]=$db->record['add_rec'];
        $access[3]=$db->record['del_rec'];
     
     }
     
   }
   $db->clear();
   return $access;
}  // check_access


FUNCTION check_access2($db,$uid,$prog) {
   $prog=trim($prog);
   $access=array();
   $access[0]=false;
   $access[1]="N";
   $access[2]="N";
   $access[3]="N";
   $username="";
   $db->query("select userName FROM myuser where ID = $uid ");

   if ($db->resultCount()==0) {
        $db->clear();
       return $access;
   }
   $db->fetchRow();
   if ($db->record['userName']=="admin") {
        $db->clear();
        $access[0]=true;
        $access[1]="Y";
        $access[2]="Y";
        $access[3]="Y";
        return $access;
   }

   $db->clear();
   $db->query("select l.add_rec,del_rec,l.write_access 
               from m_access_list l where l.id= $uid and l.prog='$prog'");

   if ($db->resultCount()>0) {
     $access[0]=true;
     $db->fetchRow();
     
     if ( ! is_null($db->record['write_access']) ) {
        $access[1]=$db->record['write_access'];
        $access[2]=$db->record['add_rec'];
        $access[3]=$db->record['del_rec'];
     }
   }
   $db->clear();

   return $access;
}  // check_access2

// Return ('xxx','yyy','zzz')
// select distinsct store_cd as field_name from m_store_cd where store_type='PACK'"
FUNCTION get_all_keys($db,$s) {
   $s=trim($s);
   
   $reserve_words=array(' distinct ', ' DISTINCT ', ' Distinct', ' distinctrow ',' DISTINCTROW ');
   $sqlparse=substr($s,6);   // REMOVE SELECT
   if (strpos($sqlparse," from ")>0) { 
       $sqlparse=substr($sqlparse,0,strpos($sqlparse," from "));
   } elseif (strpos($sqlparse," FROM ")>0) {
       $sqlparse=substr($sqlparse,0,strpos($sqlparse," FROM "));
   }

   if (version_compare(PHP_VERSION, '5.0.0', '<')) {
       $sqlparse=trim(str_replace($reserve_words,'',$sqlparse)); 
   } else {
       $sqlparse=trim(str_ireplace($reserve_words,'',$sqlparse));   
   }
   $var=explode(" ",$sqlparse);
   $field_name=array();
   $j=count($var);
   if ( $j>0 ) {
        for ($i=0; $i<$j; $i++){
           
            if ( strtolower($var[$i])=="as" && $i > 0 ) {
                //Remove last 2 fields
                $var[$i]="";
                for ($k=$i-1; $k>=0; $k--){
                    if (strlen($var[$k])>0) {
                        $var[$k]="";
                        break;
                    }
                }

            } elseif (trim(strtolower($var[$i]))=="from") {  // In case of Mixed Smmal and Capital letter ,PHP4 Compatibility
               
                $var[$i]="";
            }
        }
	for ($i=0; $i<$j; $i++){
            if (strlen($var[$i])>0) {
               $field_name[] = $var[$i];
           
            }
        }
   }  // $i>0
   
   $ret ="";   
   $db->query($s);

   if ($db->resultCount()>0 && count($field_name)>0 ) {
      $f=trim($field_name[0]);

      $pos = strpos($f,".");
      if ($pos>0) {
	 $f=substr($f,$pos+1);
      }
     
      $ret ="(";
      while ($db->fetchRow()) {   
          $ret .= "'". trim($db->record["$f"]) . "',";
      }
      if ( strlen($ret)>=5) {
         $ret = substr($ret,0,-1) . ")";
      }
   
   }
   $db->clear();  
   return $ret;
}
function prepare_string($value) {
	if (preg_match("/^(.*)(##)(int|date)$/", $value, $parts)) {
                        $value = $parts[1];
                        $type = $parts[3];
                } else {
                        $type = "";
                }
                $value = (!get_magic_quotes_gpc()) ? addslashes($value) : $value;
                switch ($type) {
                        case "int":
                        $value = ($value != "") ? intval($value) : NULL;
                        break;
                        case "eu_date":
                        $date_parts = preg_split ("/[\-\/\.]/", $value);
                        $time = mktime(0, 0, 0, $date_parts[1], $date_parts[0], $date_parts[2]);
                        $value = strftime("'%Y-%m-%d'", $time);
                        break;
                        case "date":
                        $value = "'".preg_replace("/[\-\/\.]/", "-", $value)."'";
                        break;
                        default:
                        $value = ($value != "") ? "'" . $value . "'" : "''";
        }
        return $value;

}  // prepare_string

FUNCTION gen_user_key($db,$xusername,$xuserpass) {
         $key = array();
         WHILE (true) {
           $unique_login_key = md5($xusername.time());
           $key[0] = trim($unique_login_key);
           $db->query("select elogin from myuser where elogin='$unique_login_key'");
           if ( $db->resultCount()==0 ) { 
               $db->clear();
               BREAK;
           } else {
               $db->clear();
               sleep(1);
           }
         }

         WHILE ( true )  {
            $x1 = md5($xusername.time());
            $x2 = md5($xuserpass.time());
            $unique_key = $x1.$x2;
             if ( strlen($unique_key) > 100 ) {
                  $unique_key = substr($unique_key,1,100);
            }
            $key[1] =  $unique_key;
            $db->query("select access_key from myuser where access_key ='$unique_key'");
            
            if ( $db->resultCount()==0 ) { 
               $db->clear();
               BREAK;
            } else {
               $db->clear();
               sleep(1);
            }
         } 
         return $key;
}
FUNCTION check_regex($str) {
    $regex = "((https?|ftp)\:\/\/)?"; // SCHEME 
    $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass 
    $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP 
    $regex .= "(\:[0-9]{2,5})?"; // Port 
    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path 
    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query 
    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor 

    if(preg_match("/^$regex$/", $str))  { 
        return true; 
    } else {
        return false;
    }
}
function sanitize($str,$sql=true) {
    $forbidden=array("<",">","'","\"","%","`","=",";");
    for ($i=0; $i<count($forbidden); $i++) {
        $repl=$forbidden[$i];
        $str=str_replace("$repl","",$str);
    }
    if ( $sql==true) {
       $str=sanitize2($str);
    }
    return ($str);
}
function sanitize2($str) {

  if (version_compare(PHP_VERSION, '5.0.0', '<')) {
     $bad_str = array("DROP","INSERT","UPDATE","ALTER","MODIFY","REPLACE","SELECT","DELETE","NULL","drop","insert","update","alter","modify","replace","select","delete","null");
     $newphrase = str_replace($bad_str, "", $str);
  } else {

     $bad_str = array("DROP","INSERT","UPDATE","ALTER","MODIFY","REPLACE","SELECT","DELETE","NULL");
     $newphrase = str_ireplace($bad_str, "", $str);
  }
  return ($newphrase);
}

/*function sanitize_sms($str,$remove_comma=false) {
    $forbidden=array("'","\"","~","`","#","^","%","_","[","]","{","}","&","$","!");
    for ($i=0; $i<count($forbidden); $i++) {
        $repl=$forbidden[$i];
        $str=str_replace("$repl","",$str);
    }
    if ( $remove_comma==true) {
        $str=str_replace(",","",$str);
    }
    return ($str);
}
*/
function sanitize_sms($str,$remove_comma=false) {
    $forbidden=array();
 
    $sms="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789*()-+=:,.?/";
    $len=strlen($str);
    for ($i=0; $i < $len; $i++) {
        if (in_array(substr($str,$i,1), $forbidden) || substr($str,$i,1)=="A" || substr($str,$i,1)=="a" || substr($str,$i,1)==" ") {
             continue;
        }
        $pos = stripos($sms, substr($str,$i,1));
        if ($pos==false) {
            $forbidden[]=substr($str,$i,1);
        }
    }
    for ($i=0; $i<count($forbidden); $i++) {
        $repl=$forbidden[$i];
        $str=str_ireplace("$repl","",$str);
    }
    if ( $remove_comma==true) {
        $str=str_ireplace(",","",$str);
    }
    return ($str);
}
FUNCTION is_sms_char($str) {
    $str=trim($str);
    $len= strlen($str);
    $ok=true;
    for ($i=0; $i < $len; $i++) {
        $pos = stripos(SMS_CHARACTERS, substr($str,$i,1));
        if ($pos==false) {
            $ok=false;
            break;
        }
    }
    return $ok;
}
function os_glove_stock_code($latex_type, $powder_free,$ptype, $plr, $size_desc) {
  $latex_type=trim($latex_type);
  $powder_free=trim($powder_free);
  $ptype=trim($ptype);
  $plr=trim($plr);
  $size_desc=trim($size_desc);
  if (strlen($latex_type)>0 && strlen($powder_free)>0 && strlen($ptype)>0 && strlen($plr)>0 && strlen($size_desc)>0) { 
       $pdf = ($powder_free=="Y" ? "F":"P");
       return "GL"."-".$latex_type."-".$pdf."-".$ptype."-".$plr."-".$size_desc;
  } else {
    return "";  // ERROR indicator
  }
 
}
FUNCTION get_ptypes($db,$productcode) {
  $data=array();
  $productcode=trim($productcode);

  $db->query("select ptype from m_product_child where productcode='$productcode'");
  
  while ($db->fetchRow()) {
     $data[]=trim($db->record['ptype']);
  }
  $db->clear();
  return $data;
} // end of get_ptypes Function
FUNCTION check_fty_access($db,$ftycode,$uid) {
      $ret = "N";
      $ftycode=trim($ftycode);
      $s="SELECT * from factory_access_list WHERE uid = $uid AND ftycode ='$ftycode'";
      $db->query($s);
      if ($db->resultCount()>0) {
          $ret = "Y";
      }
      $db->clear();
      return $ret;
}  // cehck access
FUNCTION check_access_by_scr($db,$scrno,$uid) {
  if ( strlen($uid)==0 || strlen($scrno)==0) { return false; }
  $scrno=trim($scrno);
  $s = "select '1' from salesorder s, myuser m where m.id=$uid and m.ftycode=s.produceat and s.scrno='$scrno'";

  $db->query($s);
  if ($db->resultCount()>0) {
      $db->clear();
      return true;
  } else {
      $db->clear();
      $s = "select '1' from salesorder s, factory_access_list m where m.uid=$uid and m.ftycode=s.produceat and s.scrno='$scrno'";
      $db->query($s);
      if ($db->resultCount()>0) {
         $db->clear();
         return true;
      } else {
	 $db->clear();
         return false;
      }   
  }
 
}

FUNCTION load_branches($db,$uid,$form_name,$name,$curr_item,$fty_type,$mfg_only,$no_restrict,$auto_submit=false) {
   $fty_type=trim($fty_type);
   $opt='';
   if ( $auto_submit==true) {
      $opt="<select name='$name' onchange='document.".$form_name. ".submit();'>" ;
   } else {
      $opt="<select name='$name'>";
   }
   if ($no_restrict==true) {
      $s = "select company_code as ftycode,company_name from m_company_master ";
      $whe=false;
      if ($mfg_only==true) {
         $s.=" WHERE is_mfg='Y'";
         $whe=true; 
      } 
      if ( strlen($fty_type)>0) {
         if ($whe==true) {
            $s.=" AND factory_type='$fty_type'";
         } else {
            $s.=" WHERE factory_type='$fty_type'";
            $whe=true;
         }
      }

   } else {
      $filter = get_all_keys($db,"select ftycode from myuser where id=".$uid." union select ftycode from factory_access_list where uid=".$uid);
      $s = "select company_code as ftycode,company_name from m_company_master WHERE company_code IN ".$filter." AND active='Y'";
      if ($mfg_only==true) {
         $s.=" AND is_mfg='Y'";
      }
      if ( strlen($fty_type)>0) {
         $s.=" AND factory_type='$fty_type'";
      }
   }
   
   $db->query($s); 

   if ($db->resultCount()>0) {

      if ( $auto_submit==true) {
         $opt="<select name='$name' onchange='document.".$form_name. ".submit();'>" ;
      } else {
         $opt="<select name='$name'>";
      }
      while ($db->fetchRow()) { 
         if ($curr_item=='') {
            $curr_item=trim($db->record['ftycode']);
         }
         $opt .= '<option value="'.$db->record['ftycode'] .'"' ;
	 if ( trim($curr_item) == trim($db->record['ftycode']) ) {
             $opt .= ' selected="selected"';
         }
         $opt .= '>'.$db->record['company_name'] . '</option>' ;
      }  // While Loop

      $db->clear(); 
      $opt .='</select>';               
   } else {  // Row Counts
      $db->clear(); 
   } // Row Counts
   return $opt;
}  // End load_factory

FUNCTION encode_str($x) {
    // MUST BE SAME AS ENC_PASSWORD
    return md5($x."9bf8286bd71d366c0c8b20815d94737!");
}

FUNCTION decode_str($encode,$x) {

   if (md5($x."9bf8286bd71d366c0c8b20815d94737!")==$encode) {
     return true;
   } else {
     return false;
   }
}  // decode
function generate_pwd()
{
	// Create the meta-password
	$sMetaPassword = "";
//	   "S" => array('characters' => "!@-_=+*", 'minimum' => 1, 'maximum' => 2),
	
	//global $CONFIG;
        $CONFIG['security']['password_generator'] = array(
	   "C" => array('characters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 'minimum' => 5, 'maximum' => 6),
	   "N" => array('characters' => '1234567890', 'minimum' => 3, 'maximum' => 4)
        );
	$ahPasswordGenerator = $CONFIG['security']['password_generator'];
	foreach ($ahPasswordGenerator as $cToken => $ahPasswordSeed)
		$sMetaPassword .= str_repeat($cToken, rand($ahPasswordSeed['minimum'], $ahPasswordSeed['maximum']));
		
	$sMetaPassword = str_shuffle($sMetaPassword);
	
	// Create the real password
	$arBuffer = array();
	for ($i = 0; $i < strlen($sMetaPassword); $i ++)
		$arBuffer[] = $ahPasswordGenerator[(string)$sMetaPassword[$i]]['characters'][rand(0,strlen($ahPasswordGenerator[$sMetaPassword[$i]]['characters']) - 1)];

	return implode("", $arBuffer);
}
// Function to calculate script execution time. 
function microtime_float () 
{ 
    list ($msec, $sec) = explode(' ', microtime()); 
    $microtime = (float)$msec + (float)$sec; 
    return $microtime; 
} 

function is_leap_year($year) { 
  return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year %400) == 0)));
}
// Might not be true
FUNCTION server_os() {
  $software = strtoupper($_SERVER['SERVER_SOFTWARE']); 
  if( stristr($software,'WIN')){ 
     return 'Windows'; 
  } else { 
     return 'Unix'; 
  } 
} // server_os

FUNCTION save_sys_params($db, $module, $field, $variable, $prefix , $suffix, $desc ) {
$db->query( "INSERT INTO sys_params (field_name, module_nm, variable, prefix, suffix, description) VALUES (
     '". trim($field) . "','" . trim($module) . "','" . trim($variable) . "','" . trim($prefix) .
     "','" . trim($suffix) . "','" . trim($desc) . "')");

}
//
FUNCTION save_m_prog($db,$prog,$prog_name,$access_level,$customer_access,$prog_cat) {

   $db->query("SELECT '1' FROM m_prog WHERE prog ='$prog'");
   $cnt = $db->resultCount();
   $db->clear();

   if ($cnt==0) {
      $db->query("INSERT INTO m_prog (prog, prog_name,access_level, customer_access, prog_cat) VALUES (
           '$prog','$prog_name',$access_level,'$customer_access','$prog_cat')");
   }
}
// Load OSTRIO Database name
FUNCTION load_target_company($db,$form_name,$name,$curr_item,$add_blank=true,$auto_submit=true) {
   $opt='';
   $name=trim($name);
   $curr_item=trim($curr_item);
   $db->query("select variable as ftycode from sys_params where module_nm='OSTRIO_DB' order by variable");
  
   if ($db->resultCount()>0 ) {
       if ($auto_submit==true) {
          $opt="<select name='$name' onchange='document.".$form_name. ".submit();'>" ;
       } else {
          $opt="<select name='$name'>" ;
       }
       if ($add_blank==true) {
          $opt.= "<option value=''>&nbsp;</option>";
       }
       while ($db->fetchRow()) { 
         $opt .= "<option value='".$db->record['ftycode'] . "'" ;
 	 if ( $curr_item == trim($db->record['ftycode'])) {
             $opt .= " selected='selected'";
         }
         $opt .= ">".$db->record['ftycode']."</option>" ;
      }  // While Loop
      $opt .= "</select>";               
   }  // Row Counts
   $db->clear();
 
   return $opt;
}  // load_target co
// Sample v_str = xx=11|   Field delimter=,(Comma) Record Delimiter=|
function get_log($v_str) { 
   $a_var=explode('|',$v_str);
   $j=count($a_var);
   if ( $j==0 ) {
      return "";
   }
   $header="<table width='100%'><tr bgcolor='#99CCFF'><td>Field Name</td><td>Value</td></tr>";
   $detail="";
   $color_cnt=0;
   for ($i=0; $i<$j; $i++){
   	for ($i=0; $i<$j; $i++){
            if (strlen($a_var[$i])>1) {
               $a_field=explode('=',$a_var[$i]);
               $z=count($a_field);
               if (count($a_field)>=2) {
                 $bgc=++$color_cnt % 2==0 ? "#DFDFDF" : "#F0FFFF";
                 $detail .= "<tr bgcolor='$bgc'><td>".$a_field[0]."</td><td>".$a_field[1];
                 for ($k=2; $k<$z; $k++){
                    $detail .=",".$a_field[$k];
                 }
                 $detail .= "</td></tr>";
               }
            }
        }
   }  // $i>0
   if (strlen($detail)>0) {
       return $header.$detail."</table>";
   }
      
} // Get Log

FUNCTION save_log_bom_error($db, $scrno, $productcode, $stock_code, $rec_type ) {
   $scrno=trim($scrno);
   $productcode=trim($productcode);
   $stock_code=trim($stock_code);
   $rec_type=trim($rec_type);
   $db->query("REPLACE INTO log_bom_error (scrno,productcode,stock_code,rec_type,create_date) VALUES 
              ('$scrno','$productcode','$stock_code','$rec_type',now())");

} // save_error
?>
