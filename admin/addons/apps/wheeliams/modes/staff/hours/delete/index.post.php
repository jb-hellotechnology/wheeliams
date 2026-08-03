 <?php
    echo $HTML->side_panel_start();
    
    echo $HTML->side_panel_end();
    
    echo $HTML->title_panel([
    'heading' => 'Delete Staff Hours'
    ], $CurrentUser);

    $Smartbar = new PerchSmartbar($CurrentUser, $HTML, $Lang);
    
    $staffID = (int) $_GET['staffID'];

	$Smartbar->add_item([
	    'active' => false,
	    'title' => 'Profile',
	    'link'  => $API->app_nav().'/staff/edit/?id='.$staffID,
	]);

	$Smartbar->add_item([
	    'active' => true,
	    'title' => 'Hours',
	    'link'  => $API->app_nav().'/staff/hours/?id='.$staffID,
	]);
	
	$Smartbar->add_item([
	    'active' => false,
	    'title' => 'Holidays',
	    'link'  => $API->app_nav().'/staff/holidays/?id='.$staffID,
	]);
	
	$Smartbar->add_item([
	    'active' => false,
	    'title' => 'Sick Days',
	    'link'  => $API->app_nav().'/staff/sick-days/?id='.$staffID,
	]);
	
	echo $Smartbar->render();

    echo $HTML->main_panel_start(); 
    
    if (isset($message)){ 
	    
	    echo $message;
	    
	}
	
	if(!$deleted){
		
		echo $Form->form_start();
		    
		echo $Form->submit_field('btnSubmit', 'Delete Staff Hours', $API->app_path());
		
		echo $Form->form_end();
	
	}

    echo $HTML->main_panel_end();