<?php
$globalConfig['tbl']="myuser"; //  The name of the MySQL table to store the data required
$globalConfig['tblID']="ID"; // The name of the ID field of the MySQL table
$globalConfig['tblUserName']="userName"; // The name of the Username field of the MySQL table
$globalConfig['tblUserPass']="userPass"; // The name of the Userpassword field of the MySQL table
$globalConfig['tblIsAdmin']="isAdmin"; // The name of the Administator field of the MySQL table
$globalConfig['tblUserGroup']="userGroup"; // The name of the User Group field of the MySQL table
$globalConfig['tblSessionID']="sessionID"; // The name of the ID field of the MySQL table
$globalConfig['tblLastLog']="lastLog"; // The name of the Time field of the MySQL table
$globalConfig['tblUserRemark']="userRemark"; // The name of the Remarks field of the MySQL table
$globalConfig['block_access']="Y"; 
$globalConfig['ip_addr']=get_ip(); 
/*
$globalConfig['acceptNoCookies']
true = display an error message if the user has deactivated cookies
false = no error message; you should though pass somehow (POST/GET) the session ID on each link!!
e.g. your_next_page.php?PHPSESSID=".session_id(); etc.
**********************************************************************************
*/
$globalConfig['acceptNoCookies']=false;
$globalConfig['inactiveMin']=180; // The time in minutes to force new login, if account has been inactive
$globalConfig['loginUrl']="../login.php"; // The URL of the login page
$globalConfig['logoutUrl']="../logout.html"; // The URL of the logout page NOT USED
/*
**REMEMBER LOGIN CONFIGURATION****************************************************
*/
$globalConfig['enblRemember']=false; // set true to enable Remember Me function ( DO NOT SET TO true)
$globalConfig['cookieRemName']="acde93381f309a7aeefaf17316e7b518"; // name of username cookie
$globalConfig['cookieRemPass']="_99jkhshhskskss0eff973ac85052c07d0ca1cc30079fe1"; // name of password
$globalConfig['cookieExpDays']="1"; // num of days, when remember me cookies expire 30
/*
**END REMEMBER LOGIN CONFIGURATION************************************************
*/
/*
**HASH CONFIGURATION**************************************************************
$globalConfig['isMd5']
1 = passwords will be stored md5 encrypted on database
other number = passwords will be stored as is on database
**********************************************************************************
*/
$globalConfig['isMd5']="1";
/*
**END HASH CONFIGURATION**********************************************************
*/
/*
**ERROR PAGE CONFIGURATION********************************************************
*/
/*
$globalConfig['errorCssUrl']
the url of the external stylesheet file for the error pages
please leave it blank: $globalConfig['errorCssUrl']=""; if you do not want to use one
**********************************************************************************
*/
$globalConfig['errorCssUrl']="adminpro.css";
/*
**********************************************************************************
*/
/*
$globalConfig['errorCharset']
the Charset for the error pages, default: iso-8859-1
please leave it blank: $globalConfig['errorCharset']=""; if you do not want to use one
**********************************************************************************
*/
$globalConfig['errorCharset']="iso-8859-1";
/*
**********************************************************************************
*/
$globalConfig['errorPageTitle']="Access Denied!";
$globalConfig['errorPageH1']="<font color=red>Access Denied!</font>";
$globalConfig['errorPageLink']="Click to login";
$globalConfig['errorNoCookies']="YOU MUST ACCEPT COOKIES TO PROCEED!";
$globalConfig['errorNoLogin']="Please Login First To Access This Web Page!";
$globalConfig['errorInvalid']="Authentication Failed";
$globalConfig['errorDelay']="Your Account Has Been Inactive For too long <br>";
$globalConfig['errorDelay'].="Or You Have used The Login ID More Than Once!<br>";
$globalConfig['errorDelay'].="This Session Is No Longer Active!";
$globalConfig['errorNoAdmin']="YOU NEED ADMINISTRATOR RIGHTS TO VIEW THIS PAGE!";
$globalConfig['errorNoGroup']="YOU DO NOT BELONG TO THE USER GROUP REQUIRED TO VIEW THIS PAGE!";
/*
**END ERROR PAGE CONFIGURATION****************************************************
*/
function get_ip() {                                                              
    if(getenv('HTTP_CLIENT_IP')) {                                              
       $ip = getenv('HTTP_CLIENT_IP');                                         
    } else if(getenv('HTTP_X_FORWARDED_FOR')) {                                 
        $ip = getenv('HTTP_X_FORWARDED_FOR');                                   
    } else {                                                                    
      $ip = getenv('REMOTE_ADDR');                                            
    }                                                                           
    return $ip;                                                                 
}             

FUNCTION remote_access($host,$ip) {
  $ret = false;
  //if ( $host=="tn.terangnusa.com" 
  //   && $ip<>"localhost" && $ip<>"175.142.36.118" && $ip<>"tnliux2" && $ip<>"131.107.2.10" 
  //   && $ip<>"131.107.2.16" && $ip<>"200.40.111.6" && $ip<>"218.111.200.149" 
  //   && $ip<>"218.111.160.133") {
  //   $ret=true;
  //}
  return $ret;
}       
?>
