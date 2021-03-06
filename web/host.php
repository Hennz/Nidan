<?php 

include_once "common.inc.php";

if(!$mySession->isLogged()) {
    header("Location: /signin.php");
    exit();
}

if(!empty($_GET["id"])) {
    $host_id = intval($_GET["id"]);
    
    $result = doQuery("SELECT ID,IP,MAC,Hostname,isOnline,addDate,stateChange,lastCheck,checkCycle FROM Hosts WHERE ID='$host_id';");
    if(mysqli_num_rows($result) > 0) {
	$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
	$host_id = $row["ID"];
	$host_ip = $row["IP"];
	$host_mac = $row["MAC"];
	$host_name = stripslashes($row["Hostname"]);
    	$host_adddate = new DateTime($row["addDate"]);
    	$host_lastCheck = new DateTime($row["lastCheck"]);
    	$host_stateChange = new DateTime($row["stateChange"]);
	$host_checkcycle = $row["checkCycle"];

	$pageTitle = "Host $host_ip details";
    }
}

include "common_head.php"; 

include_once "common_sidebar.php";
?>
<main class="col-sm-9 offset-sm-3 col-md-10 offset-md-2 pt-3" id="contentDiv">
<?php

///////////////////////////////////////////////////////////////
// HOST DETAILS
//
//

if(isset($_GET["id"])) {
?>
    <div class="panel panel-default">
	<div class="panel-heading"><h2><i class="fa fa-laptop" aria-hidden="true"></i> Host <?php echo "$host_name ($host_ip)"; ?> details</h2></div>
	<div class="panel-body">
	    <p>
		Added on <?php echo $host_adddate->format("H:i:s d-M-Y"); ?>
	    </p><p>
		Last state change on <?php echo ($host_stateChange ? $host_stateChange->format("H:i:s d-M-Y"):"Never"); ?>
	    </p><p>
		Last check on <?php echo ($host_lastCheck ? $host_lastCheck->format("H:i:s d-M-Y"):"planned"); ?>
	    </p>
	</div>
	<table class="table table-striped">
	    <thead>
		<th>State</th>
		<th>Port</th>
		<th>Description</th>
		<th>Banner</th>
		<th>Last seen</th>
	    </thead>
	    <tbody>
<?php
    // Get all services found on this host
    $result = doQuery("SELECT ID,Port,Proto,State,Banner,lastSeen FROM Services WHERE hostId='$host_id';");
    if(mysqli_num_rows($result) > 0) {
	while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
	    $service_id = $row["ID"];
	    $service_port = $row["Port"];
	    $service_proto = $row["Proto"];
	    $service_banner = $row["Banner"];
	    $service_lastseen = new DateTime($row["lastSeen"]);

	    switch($row["State"]) {
		case 'open':
		    $service_status = "fa-circle-o text-success";
		    break;
		case 'closed':
		    $service_status = "fa-times text-danger";
		    break;
		case 'filtered':
		    $service_status = "fa-filter text-info";
		    break;
		default:
		    $service_status = "fa-question-circle";
		    break;
	    }   

	    echo "<tr>
		<td><i class='fa $service_status' aria-hidden='true'></i></td>
		<td>$service_port/$service_proto <a href='https://www.speedguide.net/port.php?port=$service_port' target=new'>&nbsp;<i class='fa fa-question-circle' aria-hidden='true'></i></a></td>
		<td>".(array_key_exists("$service_port/$service_proto",$tcp_services) ? $tcp_services["$service_port/$service_proto"]["desc"]:"No desc")."</td>
		<td>$service_banner</td>
		<td>".$service_lastseen->format("H:i:s d-M-Y")."</td>
	    </tr>";
	}
    } else {
        echo "<tr><td colspan=10>Host not scanned yet...</td></tr>";
    }
?>
	    </tbody>
	</table>
    </div>
<?php
} else {
///////////////////////////////////////////////////////////////
// NET OR FULL HOSTS LIST
//
//
    if(isset($_GET["p"])) {
	$page_num = abs(intval(sanitize($_GET["p"])));
    } else {
	$page_num = 1;
    }

    $order_by = "INET_ATON(IP)";

    if(isset($_GET["o"])) {
	switch($_GET["o"]) {
	    case 'mac':
		$order_by = "MAC";
		break;
	    case 'ip':
		$order_by = "INET_ATON(IP)";
		break;
	    case 'host':
		$order_by = "Hostname";
		break;
	    case 'adddate':
		$order_by = "addDate";
		break;
	    case 'chgdate':
		$order_by = "stateChange";
		break;
	    case 'lastcheck':
		$order_by = "lastCheck";
		break;
	    case 'ip':
	    default:
		$order_by = "INET_ATON(IP)";
		break;
	}
    }
    
    if(isset($_GET["d"])) {
	if($_GET["d"]==1) {
	    $order_by .= " DESC";
	}
    }

    $row_offs = ($page_num-1) * 10;

    if(!empty($_GET["net"])) {
	$net_id = intval(sanitize($_GET["net"]));

	$result = doQuery("SELECT ID FROM Hosts WHERE netId=$net_id;");
	$total_rows = mysqli_num_rows($result);

	$tmp_net = new Network($net_id);

	// Show ALL HOSTS order by lastSeen first in network
	$result = doQuery("SELECT ID,IP,MAC,Hostname,isOnline,addDate,TIMESTAMPDIFF(MINUTE,lastCheck,NOW()) AS lastCheck,stateChange FROM Hosts WHERE netId=$net_id ORDER BY $order_by LIMIT 10 OFFSET $row_offs;");
	echo "<h2>All hosts in $tmp_net->description ($tmp_net->network)</h2>";
    } else {
	$result = doQuery("SELECT ID FROM Hosts;");
	$total_rows = mysqli_num_rows($result);

	// Show ALL HOSTS order by lastSeen first
	$result = doQuery("SELECT ID,IP,MAC,Hostname,isOnline,addDate,TIMESTAMPDIFF(MINUTE,lastCheck,NOW()) AS lastCheck,stateChange FROM Hosts ORDER BY $order_by LIMIT 10 OFFSET $row_offs;");
	echo "<h2>All hosts in all net(s)</h2>";
    }

?>
    <div class="table-responsive">
	<table class="table table-striped"><thead>
	    <tr>
		<th>State</th>
		<th>MAC</i></th>
		<th>IP <a href="<?php echo keepGet('o','ip'); ?>" class="fa fa-fw fa-sort"></a></th>
    		<th>Hostname <a href="<?php echo keepGet('o','host'); ?>" class="fa fa-fw fa-sort"></a></th>
		<th>Services</th>
		<th>Last check <a href="<?php echo keepGet('o','lastcheck'); ?>" class="fa fa-fw fa-sort"></a></th>
	    </tr>
	</thead><tbody>
<?php
    if(mysqli_num_rows($result) > 0) {
	while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
	    $host_id = $row["ID"];
	    $host_ip = $row["IP"];
	    $host_mac = $row["MAC"];
	    $host_name = stripslashes($row["Hostname"]);

	    $hostname = ($host_name ? $host_name:$host_ip);

	    $host_status = "fa-times text-danger";
	    if($row["isOnline"]) {
		$host_status = "fa-circle-o text-success";
	    }
	    $host_adddate = new DateTime($row["addDate"]);

	    $host_lastcheck = false;
	    if(!empty($row["lastCheck"])) {
	        $host_lastcheck = $row["lastCheck"];
	    }

	    $host_statechange = false;
	    if(!empty($row["stateChange"])) {
	        $host_statechange = new DateTime($row["stateChange"]);
	    }

	    echo "<tr>
		<td><i class='fa $host_status' aria-hidden='true'></i></td>
		<td>$host_mac</td>
		<td><a href='/host?id=$host_id'>$host_ip</a></td>
		<td>$host_name</td>
		<td>";

	    $res = doQuery("SELECT Port,Proto FROM Services WHERE hostId='$host_id' AND State='open';");
	    if(mysqli_num_rows($res) > 0) {
		while($row = mysqli_fetch_array($res,MYSQLI_ASSOC)) {
		    $service_port = $row["Port"];
		    $service_proto = $row["Proto"];
		    
		    if(isset($tcp_services["$service_port/$service_proto"])) {
			switch($tcp_services["$service_port/$service_proto"]["relevancy"]) {
			    default:
			    case 1:
				$service_relevancy = "default";
				break;
			    case 2:
				$service_relevancy = "primary";
				break;
			    case 3:
				$service_relevancy = "info";
				break;
			    case 4:
				$service_relevancy = "warning";
				break;
			    case 5:
				$service_relevancy = "danger";
				break;
			}
		    } else {
			$service_relevancy = "default";
		    }

		    echo "<span class='badge badge-pill badge-".$service_relevancy."'>$service_port/$service_proto</span> ";
		}
	    }
	    echo "</td><td>
		    ".($host_lastcheck ? getHumanETA($host_lastcheck) : "'ll start soon...")."
		</td><td>
		    <a class='nav-link ajaxCall' title='Refresh' href='/ajax?action=refreshHost&id=$host_id'><i class='fa fa-refresh' aria-hidden='true'></i></a>
		    <a href='http://$hostname' target=_new><i class='fa fa-external-link w-25' aria-hidden='true'></i></a>
		</td>
	    </tr>";
	}
    } else {
	echo "<tr><td colspan=10>No hosts ...yet !</td></tr>";
    }
?>
	<tr>
	    <td colspan=10>
		<?php getPagination($page_num,$total_rows,'/host',10); ?>
	    </td>
	</tr></tbody></table>
    </div>
<?php
}
?>
</main>

<?php 

include "common_foot.php"; 

?>
