<?php

require_once "class.phpmailer.php";
require_once "class.smtp.php";

require_once "config.inc.php";

$sessionId = session_id();

if(empty($sessionId)) {
    session_start();
    $sessionId = session_id();
}

$DB = OpenDB();
if($DB==false) {
    header('Location: /install/');
}

$mySession = new Session($sessionId);

if(isset($_GET["action"])) {
    $post_action = sanitize($_GET["action"]);
} else if(isset($_POST["action"])) {
    $post_action = sanitize($_POST["action"]);
}

if(!empty($post_action)) {
    if($post_action == "signin") {
	$auth_email = sanitize($_POST["email"]);
	$auth_password = sanitize($_POST["password"]);

	$result = doQuery("SELECT ID FROM Users WHERE userName='$auth_email' AND userPassword=PASSWORD('$auth_password');");
	if(mysqli_num_rows($result) > 0) {
	    $row = mysqli_fetch_array($result,MYSQLI_ASSOC);

	    $myUser = new User($row["ID"]);
	    $mySession->userId = $myUser->ID;

	    doQuery("UPDATE Sessions SET userId='$myUser->ID' WHERE ID='$mySession->ID';");
	    doQuery("UPDATE Users SET lastlogin=NOW() WHERE ID='$myUser->ID';");

	    $mySession->sendMessage("Welcome back ".$myUser->getName());

	    LOGWrite("User $myUser->Name logged in from IP ".getClientIP());

	    header("Location: /");
	    exit();
	} else {
	    $mySession->sendMessage("Invalid e-mail and/or password","error");
	}
    }
    if($post_action == "logout") {
	$myUser = new User($mySession->userId);
	if($myUser) {
	    $mySession->userId = false;
	    doQuery("UPDATE Sessions SET userId=NULL WHERE ID='$mySession->ID';");

	    $mySession->sendMessage("User ".$myUser->getName()." logged out");

	    LOGWrite("User $myUser->eMail logged out from IP ".getClientIP());
	    header("Location: /");
	    exit();
	}
    }
    // Posts acstions only for LOGGED users
    if($mySession->isLogged()) {

	//
	// Agent add or edit CB
	//
	if($post_action == "cb_agent_edit") {
	    $agent_id = intval(sanitize($_POST["agent_id"]));
	    $agent_name = mysqli_real_escape_string($DB,$_POST["agent_name"]);
	    $agent_apikey = mysqli_real_escape_string($DB,$_POST["agent_apikey"]);
	    $agent_desc = mysqli_real_escape_string($DB,$_POST["agent_desc"]);
	
	    $agent_isenable = 0;
	    if($_POST["is_enable"] == "on") {
		$agent_isenable = 1;
	    }

	    if($agent_id > 0) { // UPDATE
		doQuery("UPDATE Agents SET Name='$agent_name',Description='$agent_desc',apiKey='$agent_apikey',isEnable=$agent_isenable WHERE ID='$agent_id';");
		$mySession->sendMessage("Agent $agent_name updated successfully !");
	    } else { // Create NEW
		doQuery("INSERT INTO Agents (Name,apiKey,Description,isEnable,addDate) VALUES ('$agent_name','$agent_apikey','$agent_desc',$agent_isenable,NOW());");
		$mySession->sendMessage("Agent $agent_name added successfully !");
		LOGWrite("Agent $agent_name created",LOG_DEBUG);
	    }
	}

	//
	// Network add or edit CB
	//
	if($post_action == "cb_network_edit") {
	    $net_id = intval(sanitize($_POST["net_id"]));
	    $net_address = mysqli_real_escape_string($DB,$_POST["net_address"]);
	    $net_desc = mysqli_real_escape_string($DB,$_POST["net_desc"]);
	    $net_agentid = ($_POST["net_agentid"] ? intval($_POST["net_agentid"]):0);
	    $net_checkcycle = intval((empty($_POST["net_checkcycle"]) ? 10 : sanitize($_POST["net_checkcycle"])));
	
	    $net_isenable = 0;
	    if($_POST["is_enable"] == "on") {
		$net_isenable = 1;
	    }

	    if($net_id > 0) { // UPDATE
		doQuery("UPDATE Networks SET Network='$net_address',Description='$net_desc',agentId='$net_agentid',checkCycle='$net_checkcycle',isEnable=$net_isenable,chgDate=NOW() WHERE ID='$net_id';");
		$mySession->sendMessage("Network $net_address updated successfully !");
	    } else { // Create NEW
		doQuery("INSERT INTO Networks (Network,Description,agentId,isEnable,checkCycle,addDate) VALUES ('$net_address','$net_desc',$net_agentid,$net_isenable,'$net_checkcycle',NOW());");
		$mySession->sendMessage("New network $net_address created successfully !");
		LOGWrite("Network $net_address created",LOG_DEBUG);
	    }
	}
	//
	// Network remove CB
	//
	if($post_action == "cb_network_remove") {
	    $net_id = intval(sanitize($_POST["net_id"]));
	    // #TODO
	}
	//
	// Trigger edit CB
	//
	if($post_action == "cb_trigger_edit") {
	    $trigger_id = intval(sanitize($_POST["trigger_id"]));
	    $trigger_agentid = intval(sanitize($_POST["trigger_agentid"]));
	    $trigger_event = mysqli_real_escape_string($DB,sanitize($_POST["trigger_event"]));
	    $trigger_action = mysqli_real_escape_string($DB,sanitize($_POST["trigger_action"]));
	    $trigger_priority = mysqli_real_escape_string($DB,sanitize($_POST["trigger_priority"]));
	    $trigger_args = mysqli_real_escape_string($DB,sanitize($_POST["trigger_args"]));

	    if($_POST["is_enable"] == 'on') {
		$trigger_isenable = 1;
	    } else {
		$trigger_isenable = 0;
	    }

	    if($trigger_id > 0) { // Update trigger
		doQuery("UPDATE Triggers SET agentId='$trigger_agentid',Event='$trigger_event',Action='$trigger_action',Priority='$trigger_priority',Args='$trigger_args',isEnable=$trigger_isenable WHERE ID='$trigger_id';");
		$mySession->sendMessage("Trigger $trigger_event updated successfully !");
		LOGWrite("Trigger $trigger_id updated",LOG_DEBUG);
	    } else { // Add a trigger
		doQuery("INSERT INTO Triggers(agentId,Event,Action,Priority,Args,userId,isEnable,addDate) VALUES ('$trigger_agentid','$trigger_event','$trigger_action','$trigger_priority','$trigger_args','$mySession->userId',$trigger_isenable,NOW());");
		$trigger_id = mysqli_insert_id($DB);
		$mySession->sendMessage("New trigger $trigger_id on $trigger_event added successfully !");
		LOGWrite("Trigger $trigger_id on $trigger_event added",LOG_DEBUG);
	    }
	}
	//
	// Account CB
	//
	if($post_action == "cb_account_edit") {
	    $account_id = $_POST["user-id"];
	    $account_email = $_POST["user-email"];
	    $account_name =  $_POST["user-name"];
	    $account_password = $_POST["user-password"];
	    $account_password_val = $_POST["user-password-val"];
	    $account_alias =  $_POST["user-alias"];

	    if(strcmp($account_password,$account_password_val)==0) {
	        doQuery("UPDATE Users SET userName='$account_name',userPassword='$account_password',userEmail='$account_email',userAlias='$account_alias' WHERE ID='$account_id';");
		$mySession->sendMessage("Account $account_id updated successfully !");
		LOGWrite("Account $account_it updated",LOG_DEBUG);
	    } else {
		$mySession->sendMessage("Password don't match !","error");
	    }
	}
    }
}

function doQuery($query) {
    global $DB;
    $result = mysqli_query($DB,$query);
    if($result === false) {
	LOGWrite("Error while executing query '$query': ".mysqli_error($DB),LOG_ERROR);
	exit();
    }
    usleep(500);
    return $result;
}

function OpenDB() {
    global $CFG;
    $db = mysqli_connect($CFG["db_host"],$CFG["db_user"],$CFG["db_password"],$CFG["db_name"]);
    if($db == false) {
	LOGWrite("Cannot connect to DB ".$CFG["db_name"]."@".$CFG["db_host"]." !",LOG_ERROR);
	return false;
    }
    return $db;
}

function isSelected($value,$match) {
    if($value == $match) return "selected";
}

function isChecked($value) {
    if($value) return "checked";
}

function getPagination($cur_page,$total_items,$base_url,$items_per_page=10) {

    $num_pages = ceil($total_items/$items_per_page);

    echo "<!-- TOTAL_ITEMS=$total_items NUM_PAGES=$num_pages -->";

    if($cur_page <= 1) { // First page, no previous
	$prev_page = false;
    } else {
	$prev_page = $cur_page-1;
    }

    if($cur_page >= $num_pages) { // Last page, no next
	$next_page = false;
    } else {
	$next_page = $cur_page+1;
    }

    echo "<nav>
	    <ul class='pagination justify-content-center'>";

    if(($cur_page - 3) > 0) {
	$min = $cur_page-3;
    } else {
	$min=1;
    }
    if(($cur_page + 3) > $num_pages) {
	$max = $num_pages;
    } else {
	$max = $cur_page+3;
    }
    echo "	<li class='page-item ".($prev_page ? '':'disabled')."'>
		    <a class='page-link' href='".($base_url.'?p='.$prev_page)."' tabindex='-1'>Previous</a>
		</li>";

    if($min > 1) {
	echo "	<li class='page-item'>
		    <a class='page-link' href='".($base_url.'?p='.$prev_page)."'>...</a>
		</li>";
    }

    for($p=$min;$p<=$max;$p++) {
	echo "<li class='page-item ".(($p==$cur_page) ? 'active':'')."'>
	    <a class='page-link'  href='".($base_url.'?p='.$p)."'>$p</a>
	</li>";
    }
    if($p < $num_pages) {
	echo "	<li class='page-item'>
		    <a class='page-link' href='".($base_url.'?p='.$next_page)."'>...</a>
		</li>";
    }

    echo "	<li class='page-item ".($next_page ? '':'disabled')."'>
		    <a class='page-link' href='".($base_url.'?p='.$next_page)."'>Next</a>
		</li>
	    </ul>
	</nav>";
}


function getClientIP() {
    if(getenv('HTTP_X_FORWARDED_FOR')) {
	return getenv('REMOTE_ADDR')." (".getenv('HTTP_X_FORWARDED_FOR').")";
    } else {
	return getenv('REMOTE_ADDR');
    }
}

function sanitize($u_Input) {
    $banlist = array (
	" insert ", " select ", " update ", " delete ", " distinct ", " having ", " truncate ", " replace ",
	" handler ", " like ", " as ", " or ", " procedure ", " limit ", " order by ", " group by ", " asc ", " desc "
    );
    $replacelist = array (
	" ins3rt ", " s3lect ", " upd4te ", " d3lete ", " d1stinct ", " h4ving ", " trunc4te ", " r3place ",
	" h4ndler ", " l1ke ", " 4s ", " 0r ", " procedur3 ", " l1mit ", " 0rder by ", " gr0up by ", " 4sc ", " d3sc "
    );
    if(preg_match( "/([a-zA-Z0-9])/", $u_Input )) {
	$u_Input = trim(str_replace($banlist, $replacelist, $u_Input));
    } else {
	$u_Input = NULL;
    }
    return $u_Input;
}

function APG($nChar=5) {
    $salt = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ0123456789";
    srand((double)microtime()*1000000); 
    $i = 0;
    $pass = '';
    while ($i <= $nChar) {
	$num = rand() % strlen($salt);
        $tmp = substr($salt, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }
    return $pass;
}

function LOGWrite($message,$priority=LOG_DEBUG) {
    openlog("nidan", LOG_NDELAY, LOG_LOCAL2);
    syslog(intval($priority), $message);
    closelog();
}

function sendMail($toEmail, $toName, $subject, $message) {
    global $CFG;

    $mail = new PHPMailer(true);
    $mail->IsSMTP(); 
    try {
        $mail->Host       = $CFG["mailServerHost"]; 
        $mail->Port       = $CFG["mailServerPort"]; 
        $mail->AddAddress($toEmail, $toName);
        $mail->SetFrom($CFG["mailFromMail"], $CFG["mailFromName"]);

	$mail->Subject = $subject;

	$mailBody = str_replace(array("%body%"),array($message),$CFG["mailTemplate"]);

	$mail->MsgHTML($mailBody);
	$mail->AltBody = $mail->html2text($mailBody,true);
	$mail->Send();
        return true;
    } catch (phpmailerException $e) {
	LOGWrite("Error while sending e-mail with subject $subject to $toEmail ($toName): ".$e->errorMessage(),LOG_ERROR);
    } catch (Exception $e) {
	LOGWrite("Error while sending e-mail with subject $subject to $toEmail ($toName): ".$e->errorMessage(),LOG_ERROR);
    }
    return false;
}


class Session {
    var $ID;
    var $userId=false;
    
    function __construct($ID) {
	/* Cancella tutte le sessioni piu vecchie di 1 ora */
	doQuery("DELETE FROM Sessions WHERE HOUR(TIMEDIFF(NOW(),lastAction)) > 1;");
	/* Procedi... */
	doQuery("INSERT INTO Sessions (ID,IP) VALUES ('$ID','".getClientIP()."') ON DUPLICATE KEY UPDATE lastAction=NOW();");
	$this->ID = $ID;
	
	$result = doQuery("SELECT userId FROM Sessions WHERE ID='$ID';");
	if(mysqli_num_rows($result) > 0) {
	    $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
	    $this->userId = $row["userId"];
	} else {
	    $this->userId = false;
	}
    }   
    
    function __destruct() {
    }

    function sendMessage($message, $type='information') {
	global $DB;
	/* Types: 'alert', 'information', 'error', 'warning', 'notification', 'success' */
	doQuery("INSERT INTO SessionMessages (sessionId,Type,Message,addDate) VALUES ('$this->ID','$type','".mysqli_real_escape_string($DB,$message)."',NOW());");
    }

    function isLogged() {
	if($this->userId > 0) {
	    return true;
	} else {
	    return false;
	}
    }
}

class User {
    var $ID=false;
    var $Name;
    var $eMail;
    var $loginETA;
    var $Alias;

    function __construct($ID) {
	$result = doQuery("SELECT ID,userName,userAlias,DATEDIFF(NOW(),lastLogin) AS loginETA FROM Users WHERE ID='$ID';");
	if(mysqli_num_rows($result) > 0) {
	    $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
	    $this->ID = $row["ID"];
	    $this->Name = stripslashes($row["userName"]);
	    $this->Alias = stripslashes($row["userAlias"]);
	    $this->loginETA = $row["loginETA"];
	}
    }

    function getName() {
	if(isset($this->Alias)) {
	    return $this->Alias;
	} else {
	    return $this->eMail;
	}
    }
}

class Network {
    var $id;
    var $network;
    var $description;
    var $hostsCount;
    var $isEnable;
    var $agentId;
    var $scanTime;
    var $addDate;
    var $lastCheck;
    var $chgDate;
    var $checkCycle;

    function __construct($id=false) {
	if($id) {
	    $result = doQuery("SELECT ID,Network,(SELECT COUNT(ID) FROM Hosts WHERE netId=Networks.ID) AS HostsCount,Description,agentId,isEnable,addDate,lastCheck,scanTime,checkCycle FROM Networks WHERE ID='$id';");
	    if(mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
		$this->id = $row["ID"];
		$this->network = $row["Network"];
		$this->description = $row["Description"];
		$this->hostsCount = $row["HostsCount"];
		$this->agentid = $row["agentId"];
    		$this->addDate = new DateTime($row["addDate"]);
    		$this->lastCheck = new DateTime($row["lastCheck"]);
    		$this->checkCycle = $row["checkCycle"];
		$this->scanTime = $row["scanTime"];
		return $id;
	    } else {
		return false;
	    }
	} else {
	    return false;
	}
    }

    function getHosts() {
	$result = doQuery("SELECT IP,State,isOnline FROM Hosts WHERE netId='$this->id';");
	if(mysqli_num_rows($result) > 0) {
	    $net_array = array();
    	    /* Host already there: check for changes */
	    while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
		$IP = $row["IP"];
		$net_array[$IP] = array("state" => $row["State"],"is_online" => $row["isOnline"]);
	    }
	    return $net_array;
	}
	return False;
    }
}

class Host {
    var $id;
    var $netId;
    var $agentId;
    var $ip;
    var $mac;
    var $vendor;
    var $hostname;
    var $state;
    var $isOnline;
    var $lastCheck;
    var $scanTime;
    var $addDate;
    var $stateChange;
    var $checkCycle;
    
    function __construct($id=false) {
	if($id) {
	    $result = doQuery("SELECT ID,netId,agentId,IP,MAC,Vendor,Hostname,State,isOnline,lastCheck,scanTime,addDate,stateChange,checkCycle,chgDate FROM Hosts WHERE ID='$id';");
	    if(mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
		$this->id = $row["ID"];
		$this->netId = $row["netId"];
		$this->agentId = $row["agentId"];
		$this->ip = $row["IP"];
		$this->mac = $row["MAC"];
		$this->vendor = stripslashes($row["Vendor"]);
		$this->hostname = stripslashes($row["Hostname"]);
		$this->state = $row["State"];
		$this->isOnline = $row["isOnline"];
		$this->lastCheck = new DateTime($row["lastCheck"]);
		$this->scanTime = $row["scanTime"];
		$this->addDate = new DateTime($row["addDate"]);
		$this->stateChange = new DateTime($row["stateChange"]);
		$this->checkCycle = $row["checkCycle"];
	    }
	}
    }

    function getServices() {
	$result = doQuery("SELECT Port,Proto,State,Banner FROM Services WHERE hostId='$this->id';");
	if(mysqli_num_rows($result) > 0) {
	    $services_array = array();
	    /* Host already there: check for changes */
	    while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
		$port = $row["Port"]."/".$row["Proto"];
		$services_array[$port] = array("state" => $row["State"],"banner" => $row["Banner"]);
	    }
	    return $services_array;
	} else {
	    return false;
	}
    }
}

class Job {
    var $id;
    var $job;
    var $itemId;
    var $agentId=0;
    var $args; /* JSON array for job dispatch */
    var $addDate;
    var $startDate;
    var $endDate;

    function __construct($id=false) {
	if($id) {
	    $result = doQuery("SELECT ID,Job,itemId,Args,agentId,addDate,startDate,endDate FROM JobsQueue WHERE ID='$id';");
	    if(mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_array($result,MYSQLI_ASSOC);

		$this->id = $row["ID"];
		$this->job = $row["Job"];
		$this->itemId = $row["itemId"];
		$this->agentId = $row["agentId"];
		$this->args = json_decode($row["Args"]);
		    	    
		$this->addDate = new DateTime($row["addDate"]);
		$this->startDate = new DateTime($row["startDate"]);
		$this->endDate = new DateTime($row["endDate"]);
	    } else {
		return false;
	    }
	}
    }

    function schedule() {
	global $DB;

	/* Before, check of there's another active job like this in queue.... */
	$result = doQuery("SELECT ID FROM JobsQueue WHERE Job='$this->job' AND itemId='$this->itemId' AND agentId='$this->agentId' AND endDate IS NULL;");
	if(mysqli_num_rows($result) > 0) {
	    /* Job already in queue... do not add ! */
	    return false;
	} else {
	    doQuery("INSERT INTO JobsQueue(Job,itemId,agentId,Args,addDate) VALUES ('$this->job','$this->itemId','$this->agentId','$this->args',NOW());");
	    $this->id = mysqli_insert_id($DB);
	    return $this->id;
	}
    }

    function setEnd($agent_id=0,$eta=0) {
	// Mask this job as ended
	doQuery("UPDATE JobsQueue SET endDate=NOW(),timeElapsed='$eta' WHERE ID='$this->id' AND agentId='$agent_id';");
    }

    function setStart($agent_id=0) {
	// Mark this job as started
	doQuery("UPDATE JobsQueue SET startDate=NOW(),agentId='$agent_id' WHERE ID='$this->id';");
    }

    function setCache($cache_data=NULL) {
	global $DB;
	if(!empty($cache_data)) {
	    doQuery("UPDATE JobsQueue SET Cache='".mysqli_real_escape_string($DB,serialize($cache_data))."' WHERE ID='$this->id';");
	} else {
	    doQuery("UPDATE JobsQueue SET Cache=NULL WHERE ID='$this->id';");
	}
    }

    function getCache() {
	$result = doQuery("SELECT Cache FROM JobsQueue WHERE ID='$this->id';");
	if(mysqli_num_rows($result) > 0) {
	    $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
	    if(isset($row["Cache"])) {
		return unserialize(stripslashes($row["Cache"]));
	    } else {
		return false;
	    }
	}
	return false;
    }

}

class Agent {
    var $id;
    var $name;
    var $apiKey;
    var $description;
    var $isEnable;
    var $isOnline;
    var $addDate;
    var $lastSeen;

    function __construct($id=false) {
	if($id) {
	    $result = doQuery("SELECT ID,Name,apiKey,Description,isEnable,isOnline,addDate,lastSeen FROM Agents WHERE ID='$id';");
	    if(mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
		$this->id = $row["ID"];
		$this->name = stripslashes($row["Name"]);
		$this->apiKey = stripslashes($row["apiKey"]);
		$this->description = stripslashes($row["Description"]);
		$this->isEnable = $row["isEnable"];
		$this->isOnline = $row["isOnline"];
		$this->addDate = new DateTime($row["addDate"]);
		$this->lastSeen = false;
		if(!empty($row["lastSeen"])) {
	    	    $this->lastSeen = new DateTime($row["lastSeen"]);
		}
		return $this->id;
	    }
	    return false;
	}
    }

    function getNextJob() {
	/* First check if there are jobs for this agent... */
	$result = doQuery("SELECT ID FROM JobsQueue WHERE startDate IS NULL AND endDate IS NULL AND (agentId='$this->id' OR agentId=0) ORDER BY addDate LIMIT 1;");
	if(mysqli_num_rows($result) > 0) {
	    $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
	    $job_id = $row["ID"];
	    return $job_id;
	} else {
	    return false;
	}
    }

}

////////////////////////////////////////////////////////
//
// When something happens, this function weill be called...
//
function raiseEvent($agent_id,$event,$args) {
    LOGWrite("Event '$event' raised by agent $agent_id");

    // Add event to queue...
    doQuery("INSERT INTO EventsLog(addDate,agentId,Event,Args,isNew) VALUES (NOW(),'$agent_id','$event','".json_encode($args)."',1);");
}

$tcp_services = array(
    "1/tcp" => array("desc" => "TCP Multiplexor", "relevancy" => 1),
    "2/tcp" => array("desc" => "compressnet Management Utility", "relevancy" => 1),
    "3/tcp" => array("desc" => "compressnet Compression Process", "relevancy" => 1),
    "7/udp" => array("desc" => "Echo Protocol", "relevancy" => 1),
    "8/udp" => array("desc" => "Bif Protocol", "relevancy" => 1),
    "9/udp" => array("desc" => "Discard Protocol", "relevancy" => 1),
    "13/tcp" => array("desc" => "Daytime Protocol", "relevancy" => 1),
    "17/tcp" => array("desc" => "Quote of the Day", "relevancy" => 1),
    "19/udp" => array("desc" => "Chargen Protocol", "relevancy" => 1),
    "20/tcp" => array("desc" => "FTP Data", "relevancy" => 3),
    "21/tcp" => array("desc" => "FTP Control", "relevancy" => 3),
    "22/tcp" => array("desc" => "SSH", "relevancy" => 2),
    "23/tcp" => array("desc" => "Telnet insecure text communications", "relevancy" => 4),
    "25/tcp" => array("desc" => "SMTP", "relevancy" => 4),
    "53/tcp" => array("desc" => "DNS", "relevancy" => 3),
    "53/udp" => array("desc" => "DNS", "relevancy" => 3),
    "67/udp" => array("desc" => "BOOTP Bootstrap Protocol (Server)", "relevancy" => 3),
    "68/udp" => array("desc" => "BOOTP Bootstrap Protocol (Client)", "relevancy" => 3),
    "69/udp" => array("desc" => "TFTP Trivial File Transfer Protocol", "relevancy" => 4),
    "70/tcp" => array("desc" => "Gopher", "relevancy" => 1),
    "79/tcp" => array("desc" => "Finger", "relevancy" => 1),
    "80/tcp" => array("desc" => "HTTP HyperText Transfer Protocol (WWW)", "relevancy" => 2),
    "88/tcp" => array("desc" => "Kerberos Authenticating agent", "relevancy" => 1),
    "104/tcp" => array("desc" => "DICOM - Digital Imaging and Communications in Medicine", "relevancy" => 1),
    "110/tcp" => array("desc" => "POP3 Post Office Protocol (E-mail)", "relevancy" => 3),
    "111/tcp" => array("desc" => "SunRPC", "relevancy" => 2),
    "111/udp" => array("desc" => "SunRPC", "relevancy" => 2),
    "113/tcp" => array("desc" => "ident", "relevancy" => 1),
    "119/tcp" => array("desc" => "NNTP", "relevancy" => 1),
    "123/udp" => array("desc" => "NTP", "relevancy" => 2),
    "135/tcp" => array("desc" => "RPC", "relevancy" => 4),
    "135/udp" => array("desc" => "RPC", "relevancy" => 4),
    "137/udp" => array("desc" => "NetBIOS Name Service", "relevancy" => 4),
    "138/udp" => array("desc" => "NetBIOS Datagram Service", "relevancy" => 4),
    "139/tcp" => array("desc" => "NetBIOS Session Service", "relevancy" => 4),
    "143/tcp" => array("desc" => "IMAP4 Internet Message Access Protocol (E-mail)", "relevancy" => 2),
    "161/udp" => array("desc" => "SNMP Simple Network Management Protocol (Agent)", "relevancy" => 3),
    "162/udp" => array("desc" => "SNMP Simple Network Management Protocol (Manager)", "relevancy" => 3),
    "389/tcp" => array("desc" => "LDAP", "relevancy" => 1),
    "411/tcp" => array("desc" => "Direct Connect", "relevancy" => 1),
    "443/tcp" => array("desc" => "HTTPS", "relevancy" => 2),
    "445/tcp" => array("desc" => "Microsoft-DS (Active Directory)", "relevancy" => 4),
    "445/udp" => array("desc" => "Microsoft-DS SMB file sharing", "relevancy" => 4),
    "465/tcp" => array("desc" => "SMTP - Simple Mail Transfer Protocol SSL", "relevancy" => 1),
    "514/udp" => array("desc" => "SysLog", "relevancy" => 1),
    "563/tcp" => array("desc" => "NNTP Network News Transfer Protocol SSL", "relevancy" => 1),
    "587/tcp" => array("desc" => "SMTP TLS", "relevancy" => 1),
    "591/tcp" => array("desc" => "FileMaker 6.0 Web Sharing", "relevancy" => 1),
    "631/udp" => array("desc" => "IPP / CUPS Common Unix printing system", "relevancy" => 3),
    "636/tcp" => array("desc" => "LDAP SSL", "relevancy" => 1),
    "666/tcp" => array("desc" => "Doom TCP", "relevancy" => 1),
    "993/tcp" => array("desc" => "IMAP4 SSL", "relevancy" => 1),
    "995/tcp" => array("desc" => "POP3 SSL", "relevancy" => 1),
    "1080/tcp" => array("desc" => "SOCKS Proxy", "relevancy" => 5),
    "1194/udp" => array("desc" => "OpenVPN", "relevancy" => 3),
    "1433/tcp" => array("desc" => "Microsoft-SQL-Server", "relevancy" => 4),
    "1434/tcp" => array("desc" => "Microsoft-SQL-Monitor", "relevancy" => 4),
    "1434/udp" => array("desc" => "Microsoft-SQL-Monitor", "relevancy" => 4),
    "1984/tcp" => array("desc" => "Big Brother", "relevancy" => 1),
    "2049/udp" => array("desc" => "Network File System", "relevancy" => 4),
    "2101/tcp" => array("desc" => "rtcm-sc104", "relevancy" => 1),
    "2101/udp" => array("desc" => "rtcm-sc104", "relevancy" => 1),
    "3050/tcp" => array("desc" => "Firebird Database system", "relevancy" => 1),
    "3128/tcp" => array("desc" => "Proxy Squid cache", "relevancy" => 4),
    "3306/tcp" => array("desc" => "MySQL Database system", "relevancy" => 3),
    "3389/tcp" => array("desc" => "RDP", "relevancy" => 4),
    "3541/tcp" => array("desc" => "Voispeed", "relevancy" => 1),
    "3542/tcp" => array("desc" => "Voispeed", "relevancy" => 1),
    "3690/tcp" => array("desc" => "Subversion", "relevancy" => 1),
    "3690/udp" => array("desc" => "Subversion", "relevancy" => 1),
    "4662/tcp" => array("desc" => "eMule", "relevancy" => 1),
    "4672/udp" => array("desc" => "eMule", "relevancy" => 1),
    "4711/tcp" => array("desc" => "eMule Web Server", "relevancy" => 1),
    "4899/tcp" => array("desc" => "Radmin Connessione Remota", "relevancy" => 1),
    "5000/tcp" => array("desc" => "Sybase database server (default)", "relevancy" => 1),
    "5060/tcp" => array("desc" => "SIP", "relevancy" => 1),
    "5060/udp" => array("desc" => "SIP", "relevancy" => 1),
    "5084/tcp" => array("desc" => "EPCglobal Low-Level Reader Protocol (LLRP)", "relevancy" => 1),
    "5084/udp" => array("desc" => "EPCglobal Low-Level Reader Protocol (LLRP)", "relevancy" => 1),
    "5085/tcp" => array("desc" => "EPCglobal Low-Level Reader Protocol (LLRP)", "relevancy" => 1),
    "5085/udp" => array("desc" => "EPCglobal Low-Level Reader Protocol (LLRP)", "relevancy" => 1),
    "5190/tcp" => array("desc" => "AOL", "relevancy" => 1),
    "5222/tcp" => array("desc" => "XMPP Client Connection", "relevancy" => 1),
    "5269/tcp" => array("desc" => "XMPP Server Connection", "relevancy" => 1),
    "5432/tcp" => array("desc" => "PostgreSQL Database system", "relevancy" => 1),
    "5631/tcp" => array("desc" => "Symantec PcAnywhere", "relevancy" => 1),
    "5632/udp" => array("desc" => "Symantec PcAnywhere", "relevancy" => 1),
    "5800/tcp" => array("desc" => "Ultra VNC (http)", "relevancy" => 1),
    "5900/tcp" => array("desc" => "Ultra VNC (main)", "relevancy" => 1),
    "6000/tcp" => array("desc" => "X11", "relevancy" => 1),
    "6566/tcp" => array("desc" => "SANE", "relevancy" => 1),
    "6667/tcp" => array("desc" => "IRC, Internet Relay Chat", "relevancy" => 1),
    "8080/tcp" => array("desc" => "HTTP Alternate (http-alt)", "relevancy" => 4),
    "8118/tcp" => array("desc" => "privoxy http filtering proxy service", "relevancy" => 1),
    "41951/tcp" => array("desc" => "TVersity Media Server", "relevancy" => 1),
    "41951/udp" => array("desc" => "TVersity Media Server", "relevancy" => 1)
);

?>