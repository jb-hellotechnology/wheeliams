<?php
	echo $HTML->side_panel_start();
    
    echo $HTML->side_panel_end();
    
    echo $HTML->title_panel([
    'heading' => 'Dashboard',
    ], $CurrentUser);
?>
<div id="dashboard" class="dashboard">
	<div class="widget" data-app="content">
		<div class="dash-content">
			<header>Signed In Staff</header>
			<div class="body">
				<ul class="dash-list">
				<?php
			    foreach($staff as $Staff) {
				    echo "<li>$Staff</li>";
				}
				?>
				</ul>
			</div>
		</div>
	</div>
</div>