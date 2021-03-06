<?php 

include_once "common.inc.php";

if(!$mySession->isLogged()) {
    header("Location: /signin.php");
    exit();
}

$pageTitle = "Jobs queue";

include "common_head.php"; 

include_once "common_sidebar.php";

?>
<main class="col-sm-9 offset-sm-3 col-md-10 offset-md-2 pt-3" id="contentDiv">
    <h2>Jobs queue</h2>
    <div class="table-responsive">
	<table id="table"
               data-toggle="table"
               data-url="/ajax/?action=table_get_jobs"
               data-height="100%"
               data-side-pagination="server"
               data-pagination="true"
               data-page-list="[5, 10, 20, 50, 100, 200]"
               data-search="true">
            <thead><tr>
		<th data-field="job">Job</th>
		<th data-field="id" data-sortable="true">ID</th>
		<th data-field="agent_id" data-sortable="true">Agent ID</th>
		<th data-field="schedule_date" data-sortable="true">Scheduled</th>
		<th data-field="start_date" data-sortable="true">Start Date</th>
		<th data-field="end_date" data-sortable="true">End Date</th>
		<th data-field="time_elapsed" data-sortable="true">Time Elapsed</th>
	    </tr></thead>
	</table>
    </div>
    <div class="clearfix">&nbsp;</div>
    <div class="btn-group" role="group" aria-label="Actions">
	<a class="btn btn-secondary ajaxCall" href="/ajax?action=job_clean"><i class="fa fa-recycle" aria-hidden="true"></i> Clean job </a>
    </div>
</main>

<?php

include "common_foot.php";

?>
