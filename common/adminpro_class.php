<?php
/*
  Image Files: inc_top_htmlimage.gif,inc_top_htmlimage1.jpg
  defined("DB_NAME") or die( '# Access denied.');
*/
define("STAFF_USER_GROUP_ADMIN", 200);       // Staff usergroup as defined in myuser
define("CUSTOMER_USER_GROUP_ADMIN", 100);  // Customer usergroup as defined in myuser 
class protect {
var $errorMsg="";
var $showPage=false;
var $user_ftycode="";
var $user_fty_type="-1";
var $user_login_id;
var $usergroup = STAFF_USER_GROUP_ADMIN;   // Normal Staff User group=1, 100 = e.Order if 100 customer id will be loaded from myuser
                    // If staff ( group=1, check host name (eorder.adventa-health.com) and online_order access flag, if ok load customer id
var $elogin="_|]kjjs01928833sjhxv._hsk";
var $access_key="GG_|]8s99skjjs01928833sjhxvikkshsk";
var $system_workno="";
var $real_name="";
var $user_email="";
var $isAdmin=-1;
var $internal_user='N';
var $is_mfg="";
var $access_level=0;
var $fty_access_level=0;
var $default_ftycode='';
var $customer_id='';
var $parent_id='';          // customer parent id (addressbook)
var $lab_rowid=0;
var $center_rowid=0;
var $hospital_id=0;
var $host = "";
var $is_hq="N";
var $staff_access_eorder = false;
var $prg_group = "";
var $error_point = 0;
var $is_app=false;

// Must check users.php sys_prog.php if passed-in attributes change   
function protect($login_url='',$isAdmin=false,$userGroupXX=false,$prg_group=''){
   $this->host=$_SERVER['HTTP_HOST'];
   if (strlen($_SERVER['REQUEST_URI']) > 255 || 
	strpos($_SERVER['REQUEST_URI'], "eval(") || 
        strpos($_SERVER['REQUEST_URI'], "md5(") || 
	strpos($_SERVER['REQUEST_URI'], "base64")) {
		@header("HTTP/1.1 414 Request-URI Too Long");
		@header("Status: 414 Request-URI Too Long");
		@header("Connection: Close");
		@exit;
   } 

   include("adminpro_config.php");
   $this->accNoCookies=$globalConfig['acceptNoCookies'];
   $this->tbl=$globalConfig['tbl'];
   $this->tblID=$globalConfig['tblID'];
   $this->tblUserName=$globalConfig['tblUserName'];
   $this->tblUserPass=$globalConfig['tblUserPass'];
   $this->tblIsAdmin=$globalConfig['tblIsAdmin'];
   $this->tblUserGroup=$globalConfig['tblUserGroup'];
   $this->tblSessionID=$globalConfig['tblSessionID'];
   $this->tblLastLog=$globalConfig['tblLastLog'];
   $this->tblUserRemark=$globalConfig['tblUserRemark'];
   $this->inactiveMin=$globalConfig['inactiveMin'];  // Max Inactivtity in Minute
   $this->prg_group=$prg_group;
   if ($this->prg_group=="iphone" || $this->prg_group=="android") {
       $this->is_app=true;
   }
   if ( $login_url=='' || strlen($login_url) < 5) {
       $this->loginUrl=$globalConfig['loginUrl'];
   } else {
       $this->loginUrl=trim($login_url);
   }
   $this->enblRemember=$globalConfig['enblRemember'];
   $this->cookieRemName=$globalConfig['cookieRemName'];
   $this->cookieRemPass=$globalConfig['cookieRemPass'];
   $this->cookieExpDays=$globalConfig['cookieExpDays'];
   $this->isMd5=$globalConfig['isMd5'];
   $this->errorPageTitle=$globalConfig['errorPageTitle'];
   $this->errorPageH1=$globalConfig['errorPageH1'];
   $this->errorPageLink=$globalConfig['errorPageLink'];
   $this->errorNoCookies=$globalConfig['errorNoCookies'];
   $this->errorNoLogin=$globalConfig['errorNoLogin'];
   $this->errorInvalid=$globalConfig['errorInvalid'];
   $this->errorDelay=$globalConfig['errorDelay'];
   $this->errorNoAdmin=$globalConfig['errorNoAdmin'];
   $this->errorNoGroup=$globalConfig['errorNoGroup'];
   $this->errorCssUrl=$globalConfig['errorCssUrl'];
   $this->errorCharset=$globalConfig['errorCharset'];
   $this->block_access=$globalConfig['block_access'];
   $this->ip_addr=$globalConfig['ip_addr'];
   @session_start();
   $db = new DB("XXUser","password",DB_NAME);
   $db->connect();
   $this->checkSession($db);    // --> checkRemember  -> checkPost -> checkLogin
   $db->disconnect();
}
/*
**** @function: checkSession(called by class constructor or by checkLogin)
**** calls hasCookie() and checks if the $globalConfig['acceptNoCookies'] is true;
**** if no cookie was set and we do not accept that -> makes an error message; else:
**** checks if a session is active: if not -> checkPost() (checks if some post was sent);
**** if session exists, it checks if some $_POST['action']==logout -> makeLogout();
**** if not -> checkTime();
*/
function checkSession($db){
   if (!$this->hasCookie() && $this->accNoCookies && (@$_POST['action']!="login" || @$_GET)) {
	$this->errorMsg=$this->errorNoCookies;
	$this->error_point=1;
	$this->prompt_error($db,false);
        return;    
   } else {
        if (! (isset($_SESSION['userID']) && isset($_SESSION['sessionID'])) ) {
                       
            $this->checkPost($db);  // Disabled, Original  $this->checkRemember($db);  // Function will call $this->checkPost()
           
        } elseif (isset($_SESSION['userID']) && isset($_SESSION['sessionID'])) {
 
           if (@$_POST['action']=="logout") {
               $this->makeLogout($db);
           } else { 
               $this->checkTime($db);
           }
        } else {
           $this->makeLogout($db);
        }
   }  // has cookie checking
}
/*
**** @function: hasCookie(called by checkSession())
**** checks if the client's browser has accepted the cookie of the active session;
**** if yes, it returns true;
**** if not -> it returns false;
*/
function hasCookie(){
   if ( isset($_COOKIE[session_name()])) {
      return true;
   } else {
      return false;
   }
}
/*
**** @function: makeLogout(called by checkSession())
**** sets MySQL Time Field=0 and SessionID Field='';
**** closes the session and goes to logout page, if some $_POST['action']="logout" was sent;
*/
function makeLogout($db){
   if (isset($_SESSION['userID'])) {
      $uid=ceil($_SESSION['userID']);
      if ($uid>0) {
         
         $SQL="UPDATE ".$this->tbl." SET ";
         $SQL.=$this->tblLastLog."= '1900-01-01 00:00:00', ";
         $SQL.=$this->tblSessionID."='',otp=''  ";
         $SQL.=" WHERE ".$this->tblID."=".$uid;
         $db->query($SQL);
      }
      unset($_SESSION['userID']);
      unset($_SESSION['sessionID']);
      unset($_SESSION['otp']); 
      @session_unset(); 
      @session_destroy();
   }
   //----if ($this->enblRemember && isset($_COOKIE[$this->cookieRemName]) && isset($_COOKIE[$this->cookieRemPass])) {
   if ($this->enblRemember && isset($_COOKIE[$this->cookieRemName]) ) {
       setcookie($this->cookieRemName,$_COOKIE[$this->cookieRemName],time());
   }


   $db->disconnect();
   if ($this->is_app) {
       header("Location: ".$this->loginUrl."?is_app=1");
   } else {
       header("Location: ".$this->loginUrl);   
   }
   exit();
}
/*
**** @function: checkTime(called by checkSession())
**** gets the time of the last page access from the database;
**** compares this time with the time now. If the elapsed minutes>inactiveMin (configuration);
**** or the session ID has changed (by some second login) -> it creates an error page
*/
function checkTime($db){
   if (!(isset($_SESSION['sessionID']) && isset($_SESSION['userID']) && isset($_SESSION['otp']) )) {
      $db->disconnect();
      if ($this->is_app) {
           header("Location: ".$this->loginUrl."?is_app=1");
      } else {
           header("Location: ".$this->loginUrl);
      }
      exit();
   }
   if (floatval($_SESSION['userID'])<=0 || $_SESSION['sessionID']=="") {
       $db->disconnect();
       if ($this->is_app) {
           header("Location: ".$this->loginUrl."?is_app=1");
       } else {
           header("Location: ".$this->loginUrl);
       }
       exit();
   }
   
   $uid = ceil($_SESSION['userID']);
   $SQL = "SELECT last_access,now() as cur_time FROM ".$this->tbl;
   $SQL.= " WHERE ".$this->tblID."=".$uid." AND active='Y' AND blocked='N' AND ".$this->tblSessionID."='".$_SESSION['sessionID']."' AND otp='".$_SESSION['otp']."'";
   $db->query($SQL);
   
   if ($db->resultCount()==0) {
       $db->clear();
       $db->disconnect();
       if (isset($_SESSION['sessionID'])) { 
          unset($_SESSION['sessionID']);
          unset($_SESSION['userID']); 
          unset($_SESSION['otp']); 
          session_unset();  
          session_destroy(); 
       }
       if ($this->is_app) {
           header("Location: ".$this->loginUrl."?is_app=1");
       } else {
           header("Location: ".$this->loginUrl);
       }
        exit();
   }
 
   $db->fetchRow();
   if ( $db->record['last_access']>'2013-10-01 00:00:00' ) { 
        $inactive_minute =   (strtotime($db->record['cur_time']) - strtotime($db->record['last_access'])) / 60 ;
        if ( $inactive_minute > $this->inactiveMin && $this->inactiveMin>0) {
           $db->clear();
           $db->disconnect();
           if ($this->is_app) {
               header("Location: ".$this->loginUrl."?is_app=1");
           } else {
               header("Location: ".$this->loginUrl);
           }
           exit();
       } 

       $db->clear();
       
       if ($inactive_minute>2) {
           $SQL = "UPDATE myuser SET lastLog=now(),last_access=now() WHERE ID=". $_SESSION['userID'];
           $db->query($SQL); 
       }
       $this->showPage($db);

   } else {
       $db->clear();
       $this->showPage($db);       
   }
}
/*  NOT USED
**** @function: checkRemember (called by checkSession() if no session is active)
**** checks if some username + password cookies were set and if we have this function enabled;
**** If not -> checkPost()
**** if yes -> it updates the MySQL table, registers the Session vars -> checkSession()

function checkRemember($db){
   // Currently not Relevant as Remember Me and  password cookies ($_COOKIE[$this->cookieRemPass]) not set. 
   $sql_cmd="";
   if ($this->enblRemember && isset($_COOKIE[$this->cookieRemName])) {  //  && isset($_COOKIE[$this->cookieRemPass])
      $SQL = "SELECT ".$this->tblID." as ID, now() as cur_time, ";
      $SQL.= $this->tblUserName." as userName,".$this->tblUserPass." as userPass,last_access";
      $SQL.= " FROM ".$this->tbl;
      $SQL.= " WHERE ".$this->tblUserName."='".$_COOKIE[$this->cookieRemName]."' AND active='Y' ";

      $db->query($SQL);
      if ($db->resultCount()>0) {
         $db->fetchRow();
         
         if ($_COOKIE[$this->cookieRemName]==$db->record['userName']) {

           if ( $db->record['last_access']>'2013-10-01 00:00:00' ) {
               $inactive_minute = (strtotime($db->record['cur_time']) - strtotime($db->record['last_access'])) / 60 ;
               if ( $inactive_minute <= $this->inactiveMin && $this->inactiveMin>0) {
                   
                   @session_regenerate_id(true);
                   $_SESSION['sessionID']= session_id();
                   $_SESSION['userID']   =  $db->record['ID']; 

                   $auth_key = md5(time().$_SESSION['userID']);
                   $_SESSION['otp'] = $auth_key;
                   //setcookie("auth_key", $auth_key, time() + 60 * 60 * 24 * 7, "/", "tn.adventa.com", false, true);
                   $SQL = "UPDATE myuser SET ". $this->tblLastLog."= now(),last_access=now(), ";
                   $SQL.= $this->tblSessionID."='".session_id()."',";
                   $SQL.= "ip_addr='".$this->ip_addr."',fail_login_count=0,last_fail_login_time='1900-01-01 00:00:00',otp='$auth_key'";
                   $SQL.= " WHERE ID =".$_SESSION['userID'];


               } else {
      
                   if ($inactive_minute>2) {      
                       $sql_cmd = "UPDATE ".$this->tbl." SET ".$this->tblLastLog."=now(),last_access=now(),".$this->tblSessionID."='".session_id()."' ".
                                  " WHERE (".$this->tblID."=".$db->record['ID'].")";
                   }
              }

           } 
           $db->clear();
           if ($sql_cmd<>"") {
               $db->query($SQL); 
           }
           setcookie($this->cookieRemName,$_COOKIE[$this->cookieRemName],time()+(60*60*24*$this->cookieExpDays));
          
           $this->checkSession($db);
           
         } else { // cookie=userName and ...
            $db->clear();  
            $this->checkPost($db);
         }
         
      } else {
         $db->clear();
         $this->checkPost($db);
      } // count
   } else {  // check enable remember and cookie
      $this->checkPost($db);
   }  // check enabed remember or cookie
} // End of Check Remember
*/

/*
**** @function: checkPost (called by checkRemember())
**** checks if some $_POST was sent. If not -> it creates an error page
**** if yes -> checkLogin()
*/
function checkPost($db){
   if (! isset($_POST['userName'])) {
      $this->errorMsg=$this->errorNoLogin;
      $this->error_point=2;
      $this->prompt_error($db,false);
      return;
   }  else  { 
      if (strlen($_POST['userName'])>0 &&  strlen( $_POST['userPass'])>0) {
         $this->checkLogin($db);
      } else {
var_dump($_POST['userPass']);		  
die('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');		  
         $this->error_point=3;
         $this->prompt_error($db,true);
         return;
      }
   }
}  // End of check Post Values
/*
**** @function: checkLogin (called by checkPost())
**** checks if $_POST['userName'] and $_POST['userPass'] and $_POST['action']="login" was sent;
**** If not ->  set $action = "" and  creates an error page;
**** if yes -> it compares the $_POST with the username and password on database;
**** if all ok -> showPage() else -> Set action ="" and creates an error page;
*/
function checkLogin($db){
   $this->errorMsg=$this->errorInvalid; 
   $need_to_clear = false;

   $tbl_password="";	 
   $tbl_uid=-1;	 
   $valid_user=false;
   $auto_reset = "N";
   $internal_web='N';
   $external_web='N';
   $action=sanitize(@$_POST['action'],false);
   $got_error=false;  // Must not continue if TRUE
   $userName = sanitize(trim($_POST['userName']),true);
   if (strlen($userName)>25) {
        return;
   }
   //$userName = mysql_real_escape_string($userName);
   $userPass = sanitize(trim(@$_POST['userPass']),false);
   
   //if (strlen($userPass)<32) {
   //   $userPass = md5($userPass);
   //}

   $s="SELECT ID,userName,userPass,userGroup,customerid,isAdmin,active,internal_web,external_web, 
       fail_login_count,auto_reset,last_fail_login_time,now() as cur_time,blocked FROM myuser 
       WHERE userName='$userName' AND active ='Y'";
   $db->query($s);

   if ($db->resultCount()>0) {
      $db->fetchRow();
      $tbl_uid = $db->record['ID']; 
      $this->isAdmin=$db->record['isAdmin'];
      $this->usergroup=$db->record['userGroup'];

      $internal_web=trim($db->record['internal_web']);
      $external_web=trim($db->record['external_web']);
      $tbl_password=trim($db->record['userPass']);
      $this->customer_id=trim($db->record['customerid']);
      $auto_reset=trim($db->record['auto_reset']);

      if ( $this->usergroup==1 && $external_web=="N") {  
         if (remote_access($this->host,$this->ip_addr)==true) {  // From adminpro_config - Factory dependednt function
            $db->clear();
            $this->errorMsg="Not allowed to access  " . $this->host ;
            save_error($db, "login", "access_intranet", $userName. " Not allowed to access to Internet, Host: ".$this->host ." From ".$this->ip_addr );  
            $this->error_point=4;
            $this->prompt_error($db,true);    
            return;
         }
      } 
      
   } else {
      $got_error=true;
      $db->clear();
      if ( $this->usergroup == CUSTOMER_USER_GROUP_ADMIN && (strlen($this->customer_id)==0 ) ) { 
          $got_error=true;
          $this->error_point=10;
          $this->errorMsg="Sorry, your Online ID has not been approved by Adventa-Health.";
          save_error($db, "login", "e_order_inactive", "User ID: ".$userName. ", IP=".$this->ip_addr.", Customer Account ID Not activated / disabled." );  
      } else {
          $this->errorMsg="Authentication Failed.";
      }
      $this->error_point=5;
      $this->prompt_error($db,true);
      return;
   }
   $reset_to_active=false;
   $need_to_clear = true;
               
   //Reactive Account after certain period provided 'auto_reset=Y 
   if ($db->record['fail_login_count'] >= 3 ) {  //000
        
      if ($db->record['blocked']=='Y' ) {   // Auto Reset to Active if auto-reset is enabled
         
          if ( $auto_reset=='Y') {
              $need_to_clear=false;
              if (strtotime($db->record['last_fail_login_time']) > strtotime('1980-01-01 00:00:00') && (strtotime($db->record['cur_time']) - strtotime($db->record['last_fail_login_time']))>1800) {
                     // Auto Reset to Active 
                     $db->clear();
                     $db->query("UPDATE myuser SET blocked='N',fail_login_count=0,last_fail_login_time='1900-01-01 00:00:00' WHERE userName='$userName'");
                     $reset_to_active=true; 
              } else {
                     $db->clear();
                     $this->error_point=6;
                     $got_error=true;
                     $this->errorMsg="Your Login Account has been disabled due to too many failed login attempts.";
                
              }
          } else {
              $db->clear();
              $need_to_clear=false;
              $got_error=true;
              $this->error_point=7;
              $this->errorMsg="Your Login Account Is Not Active or has been disabled.";
          
          }
                   
      } else {  // Below : fail count >=3 , account is disabled here
          if ($this->block_access=='Y')  {
              $db->clear();
              $got_error=true;
              $this->error_point=8;
              $need_to_clear=false;
              $db->query("UPDATE myuser SET blocked='Y', last_fail_login_time=now() WHERE username='$userName'");
              $this->errorMsg = "Too many failed attempts to login from " . $this->ip_addr . ", Account Disabled";
              save_error($db, "login", "Login_Error", "User ID: ".$userName. ", ".$this->errorMsg );  
          }  // lock Access
      }
   } elseif ($this->block_access=='Y' && $userPass<>$tbl_password ) {
      // Active but wrong password, increase fail_login counter - Might cause proble if someone uses this ID to disable the account
      $db->clear();
      $got_error=true;
      $this->error_point=9;
      $db->query("UPDATE myuser SET fail_login_count=(fail_login_count+1) WHERE userName='$userName'");
      $need_to_clear=false;                      

   }    //000
   
   
   if ($need_to_clear==true) {
       $db->clear();
       $need_to_clear=false;                      
   } 
   if ($got_error==true) {
      $this->prompt_error($db,true);    
      return;
   }
   // Both UserName and password are correct, Login Success 
   if ($action=="login" && $userPass==$tbl_password && $tbl_uid>0) { 

       @session_regenerate_id(true);
       $_SESSION['sessionID']= session_id();
       $_SESSION['userID']   = $tbl_uid;

       $auth_key = md5(time().$_SESSION['userID']);
       $_SESSION['otp'] = $auth_key;
       //setcookie("auth_key", $auth_key, time() + 60 * 60 * 24 * 7, "/", "tn.adventa.com", false, true);
       $SQL = "UPDATE myuser SET ". $this->tblLastLog."= now(),last_access=now(), ";
       $SQL.= $this->tblSessionID."='".session_id()."',otp='$auth_key',";
       $SQL.= "ip_addr='".$this->ip_addr."',fail_login_count=0,last_fail_login_time='1900-01-01 00:00:00' ";
       $SQL.= " WHERE ID =".$tbl_uid;

       $db->query($SQL);
       
       if ($this->enblRemember){
	   setcookie($this->cookieRemName,$userName,time()+(60*60*24*$this->cookieExpDays));
       }
               
       $this->checkSession($db);

  } else {  // user name & Password are correct
      // Below : password not correct
      if ($this->block_access=='Y' && strlen($userName)>0) {
           $db->query("UPDATE myuser SET ip_addr='".$this->ip_addr."',fail_login_count=(fail_login_count+1) WHERE userName='$userName'");                
      }  
      $this->error_point=11;
      $this->prompt_error($db,true);
      return;
  }  // user name & Password are correct
         
}  // End of checklogin

/*
**** sets MySQL Time Field=0 and SessionID Field='' and closes the session;
*/
function prompt_error($db,$show_error_msg=false) {   // Not Used
   if (isset($_SESSION['userID'])) {
      if ($_SESSION['userID']>0) {
         $SQL ="UPDATE ".$this->tbl." SET ". $this->tblLastLog."='1900-01-01 00:00:00',".$this->tblSessionID."='',otp='' ";
	 $SQL.=" WHERE ".$this->tblID."=".$_SESSION['userID'];
	 $db->query($SQL);
	 if ($this->enblRemember && isset($_COOKIE[$this->cookieRemName]) && isset($_COOKIE[$this->cookieRemPass])){
	     setcookie($this->cookieRemName,$_COOKIE[$this->cookieRemName],time());
	 }
      }
   }  
   if ( isset($_SESSION['sessionID'])) { 
        unset($_SESSION['sessionID']); 
        unset($_SESSION['userID']); 
        unset($_SESSION['otp']); 
        session_unset(); 
        session_destroy();
   }
   $db->disconnect();
   if ($show_error_msg==true) {
      if ($this->is_app) {
         header("Location: ".$this->loginUrl."?is_app=1");
      } else {
          // This will be used by the calling program to display login error due to illegal uid or password
          header("Location: ".$this->loginUrl."?a=b24a6ab23d67f96f6a159&e=".$this->error_point);
      }
   } else {
      if ($this->is_app) {
          header("Location: ".$this->loginUrl."?is_app=1");
      } else {
          header("Location: ".$this->loginUrl."?e=".$this->error_point);
      }
   }
   exit();
} // end of make Error Page

function user_info($db) {
  
  $customer_mapping="";             // Handle staff_access_order issue
    
  if ( isset($_SESSION['userID']) ) {
     
     $s="select m.ftycode,m.userName,m.isAdmin,m.usergroup,m.real_name,m.email,m.internal_user,
         m.workno,m.customerid,m.elogin,m.access_key,
         m.lab_rowid, m.center_rowid, m.hospital_id, m.access_level, c.is_mfg,
         c.access_level as fty_access_level,c.is_hq
         from myuser m left join m_company_master c on (m.ftycode=c.company_code) WHERE m.ID=".$_SESSION['userID'] ;
 
     $db->query($s);
     
     if ($db->resultCount()==0) {
        $db->clear();
        return false;
     }
     $db->fetchRow();
     $this->user_login_id=trim($db->record['userName']);
     $this->elogin = trim($db->record['elogin']);
     $this->access_key = trim($db->record['access_key']);
     $this->usergroup=$db->record['usergroup'];
     $this->isAdmin=$db->record['isAdmin'];     
     if ( ! is_null($db->record['workno']) ) {
        $this->system_workno=trim($db->record['workno']);
     }
     $this->real_name=trim($db->record['real_name']);
     if (! preg_match("/dummy/i",$db->record['email']) && strlen($db->record['email'])>0) {
         $this->user_email=trim($db->record['email']);
     }
     
     $this->internal_user=trim($db->record['internal_user']);    
     $this->access_level=trim($db->record['access_level']);
     
     $this->lab_rowid = $db->record['lab_rowid'];
     $this->center_rowid = $db->record['center_rowid'];
     $this->hospital_id = $db->record['hospital_id'];
     
     
     if (strtoupper($this->internal_user)=="Y") {
         if ( ! is_null($db->record['is_hq']) ) {
            $this->fty_access_level=$db->record['fty_access_level'];    
            $this->is_mfg=$db->record['is_mfg'];
            if ($db->record['is_hq']=='Y' && $db->record['is_mfg']=='Y') {  // allow user belonging to this company code to access other factory
                $this->user_fty_type='000';     // Should only be applied to HQ / TN Only
            }
         } 
         
         $this->user_ftycode=trim($db->record['ftycode']);
         $this->customer_id="";  // Blank  this to prevent access to E.Order (Customer data)
         // Temporary variable For E. Order Access By Staff Only
         $customer_mapping = trim($db->record['customerid']);
         
     } else {
         $this->customer_id=trim($db->record['customerid']);
         $this->parent_id=$this->customer_id;
         $this->user_ftycode="";
         $this->user_fty_type="-1";
     }
     $db->clear();
     if (strtoupper($this->internal_user)=="Y" && $this->usergroup == STAFF_USER_GROUP_ADMIN ) {
        $db->query("select company_code,is_hq from m_company_master WHERE is_default='Y'");
        if ($db->resultCount()>0) {
           $db->fetchRow();
           if ( ! is_null($db->record['company_code']) ) {
              $this->default_ftycode=trim($db->record['company_code']);
              $this->is_hq=trim($db->record['is_hq']);
           }
           $db->clear();
           if ($this->prg_group=="online_order") {  // E.Order Module
              $access = check_access2($db,$_SESSION['userID'],'online_order'); 
              $this->staff_access_eorder = $access[0];
              if ($this->staff_access_eorder==true && strlen($customer_mapping)>0) {
                 $this->customer_id = $customer_mapping;
                 $this->parent_id   = $customer_mapping;
           
              }
           }
        } else {
           $db->clear();
        }
     }
     if ( $this->usergroup == CUSTOMER_USER_GROUP_ADMIN || $this->usergroup == STAFF_USER_GROUP_ADMIN ) {
        if (strlen($this->customer_id)>0) {
            $this->get_parent_id($db) ;
        }
     }
     return true;
  }
  
  return false;
}  // End og user_info
/*
 **** @function: showPage
 **** makes the public var $showPage true, if everything was ok;
*/
 function showPage($db) {
    $this->user_info($db);
    $this->log_visitor($db,$_SERVER['PHP_SELF'],0);
    $this->showPage=true;
 }
 // --- login_type 0 = means Login, 1 means logout
 FUNCTION log_visitor($db,$prog,$login_type=0) {
     // Note: base on pre-defined inactivity timeout
     $prog=sanitize($prog,true);
     $prog=addslashes($prog);
     if ($prog=="/tree.php" || $prog=="/index.php" ||  $prog=="/hrm/index.php" || $prog=="/login.php" || $prog=="/index.html" || $prog=="/index.htm") {
        return true;
     }
     $this->ip_addr= sanitize($this->ip_addr,true);
     $cutoff  = date("Y-m-d H:i:s",time() - 1800); 
     if (! @$_SESSION['userID']) {return true; }  
     $uid=@$_SESSION['userID'];
     $uid=ceil($uid);
     if ($uid==0) { return true; }

     $db_login_type=-1;
     $db_visit_date="1970-01-01 00:00:00";
     $db_prog="";
     $wh=" WHERE uid = ". $uid ." AND sessionid = '".session_id()."' AND ip_addr='".$this->ip_addr."' AND visit_date >='$cutoff' and login_type=0 order by visit_date desc limit 1";
     $s="SELECT login_type,visit_date,prog from log_online_visitor ".$wh;
     
     $db->query($s);
     if ($db->resultCount()>0) {
         $db->fetchRow();
         $db_login_type=$db->record['login_type'];
         $db_visit_date=$db->record['visit_date'];
         $db_prog=$db->record['prog'];

     }
     $db->clear();

     if ($db_visit_date=="1970-01-01 00:00:00" ) {
        
        $s="INSERT INTO log_online_visitor (uid,ip_addr,sessionid,location,visit_date,longitude,latitude,login_type,prog) VALUES (";
        $s.= $uid .",'".$this->ip_addr."','". session_id() ."','',now(),0,0,".$login_type.",'$prog')";
        $db->query( $s );
        
     } else {
           
        if ($prog<>$db_prog && $db_login_type==0) {
           $wh=" WHERE uid = ". $uid ." AND sessionid = '".session_id()."' AND ip_addr='".$this->ip_addr."' AND visit_date >='$cutoff' and login_type=0 LIMIT 1";
           $s="UPDATE log_online_visitor SET logout_time = now(),login_type=1 ".$wh;
      
           $db->query($s);
        }
        $wh = " WHERE uid = ". $uid ." AND sessionid = '".session_id()."' AND ip_addr='".$this->ip_addr."' AND visit_date='$db_visit_date'";
        if ($db_login_type==0 && $login_type==1) {
           $s="UPDATE log_online_visitor SET logout_time = now(),login_type=1 ".$wh;
           $db->query($s);
        }
     }
  }  // end of log visitor
     //
  FUNCTION logout_btn() {
     $ret_html = "<form name='formLoGOut' action='".$this->loginUrl."' method='POST'>";
     $ret_html.= "<input type='hidden' name='action' value='logout'>";
     $ret_html.= "<input type='submit' name='logout' value='Logout'>"; 
     $ret_html.= "</form>\n";
     return $ret_html;
  }
  // NOTE: Does not include self id, normally need to be initiazed as own customer id
  FUNCTION get_parent_id($db) {
     if (strlen(trim($this->customer_id))==0) {
        return "";
     }
     $s = "select a.parent_id from addressbook a where a.customerid='". $this->customer_id. "'";
     $s.= " and exists (select customerid from addressbook b where b.customerid=a.parent_id and b.active='Y')";

     $db->query($s);
     if ($db->resultCount()>0) {
        $db->fetchRow();
        if ( ! is_null( $db->record['parent_id'] ) ) {
            if ( strlen( trim( $db->record['parent_id'] ) )>0 ) {
                $this->parent_id=trim($db->record['parent_id']);
            }
        }
     }
     $db->clear();
	 
    }

  }  // End of protect class

?>
