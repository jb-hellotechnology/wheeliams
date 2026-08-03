<?php

    echo $HTML->title_panel([
        'heading' => $Lang->get('Listing members'),
        'button'  => [
            'text' => $Lang->get('Add member'),
            'link' => $API->app_nav().'/edit/',
            'icon' => 'core/plus',
            ],
    ], $CurrentUser);

    if (isset($message)) echo $message;


    /* ----------------------------------------- SMART BAR ----------------------------------------- */
    


        $Smartbar = new PerchSmartbar($CurrentUser, $HTML, $Lang);

        $Smartbar->add_item([
            'active' => ($status=='all'),
            'title' => $Lang->get('All'),
            'link'  => $API->app_nav().'/?status=all',
        ]);

        $Smartbar->add_item([
            'active' => ($status=='pending'),
            'title' => $Lang->get('Pending'),
            'link'  => $API->app_nav().'/?status=pending',
        ]);


        $tag_options = [];
        if (PerchUtil::count($tags)) {
            foreach($tags as $Tag) {
                $tag_options[] = [
                    'value' => $Tag->tag(),
                    'title' => $Tag->tagDisplay(),
                ];
            }
        }

        $Smartbar->add_item([
            'id'      => 'cf',
            'title'   => 'By Tag',
            'icon'    => 'core/tag',
            'active'  => PerchRequest::get('tag'),
            'type'    => 'filter',
            'arg'     => 'tag',
            //'persist' => ['view'],
            'options' => $tag_options,
            'actions' => [
                        [
                            'title'  => 'Clear',
                            'remove' => ['tag', 'show-filter'],
                            'icon'   => 'core/cancel',
                        ]
                    ],
        ]);

        echo $Smartbar->render();


        echo $Alert->output(); 

    /* ----------------------------------------- /SMART BAR ----------------------------------------- */
     
    if (PerchUtil::count($members)) {

        $cols = $Members->get_edit_columns();

        $Listing = new PerchAdminListing($CurrentUser, $HTML, $Lang, $Paging);
        $first = true;
        $i = 0;

        foreach($cols as $col) {
            $Listing->add_col([
                    'title'     => $col['title'],
                    'sort'      => (in_array($col['id'], $Members->static_fields) ? $col['id'] : false),
                    'value'     => function($item) use ($col, $first, &$i) {
                        $item = $item->to_array();
                        if ($col['id']=='_title') {
                            if (isset($item['_title'])) {
                                $title = $item['_title'];
                            }else{
                                $i++;
                                $title = PerchLang::get('Item').' '.$i;
                            }
                        }else{
                            if (isset($item[$col['id']])) {
                                $title = $item[$col['id']];
                            }else{
                                if ($first) {
                                    if (isset($item['_title'])) {
                                        $title = $item['_title'];
                                    }else{
                                        $i++;
                                        $title = PerchLang::get('Item').' '.$i;
                                    }
                                }else{
                                    $title = '-';
                                }
                            }

                    }

                        if ($col['Tag']) {

                            $FieldType = PerchFieldTypes::get($col['Tag']->type(), false, $col['Tag']);

                            $title = $FieldType->render_admin_listing($title);

                            if ($col['Tag']->format()) {
                                $Template = new PerchTemplate;
                                $title = $Template->format_value($col['Tag'], $title);
                            }
                        }

                        if ($first && trim($title)=='') {
                            $title = '#'.$item['_id'];
                        }

                        return $title;

                    },
                    'edit_link' => ($first ? 'edit' : false),
                ]);

            $first = false;
        }

        $Listing->add_delete_action([
                    'inline' => true,
                    'path'   => 'delete',
                ]);
        
            
        echo $Listing->render($members);

/*
        echo '<div class="inner"><table class="d itemlist">';
            echo '<thead>';
                echo '<tr>';
                    foreach($cols as $col) {
                        echo '<th>'.PerchUtil::html($col['title']).'</th>';
                    }
                    echo '<th class="last action"></th>';
                echo '</tr>';
            echo '</thead>';
        
            echo '<tbody>';
            $Template = new PerchTemplate;
            $i = 1;
            foreach($members as $Member) {
                $item = $Member->to_array();
                echo '<tr>';
                    $first = true;
                    foreach($cols as $col) {

                        if ($first) { 
                            echo '<td >';
                            echo '<a class="primary" href="'.$HTML->encode($API->app_path()).'/edit/?id='.$HTML->encode(urlencode($item['memberID'])).'">';
                        }else{
                            echo '<td>';
                        }

                        if ($col['id']=='_title') {
                            if (isset($item['_title'])) {
                                $title = $item['_title'];
                            }else{
                                $title = PerchLang::get('Item').' '.$i;
                            }
                        }else{
                            if (isset($item[$col['id']])) {
                                $title = $item[$col['id']];    
                            }else{
                                if ($first) {
                                    if (isset($item['_title'])) {
                                        $title = $item['_title'];
                                    }else{
                                        $title = PerchLang::get('Item').' '.$i;
                                    }
                                }else{
                                    $title = '-';
                                }
                            }
                            
                        }

                        if ($col['Tag']) {

                            $FieldType = PerchFieldTypes::get($col['Tag']->type(), false, $col['Tag']);

                            $title = $FieldType->render_admin_listing($title);

                            if ($col['Tag']->format()) {
                                $title = $Template->format_value($col['Tag'], $title);
                            }
                        }
                        
                        if ($first && trim($title)=='') $title = '#'.$item['_id'];

                        echo $title;

                        if ($first) echo '</a>';
                         
                        echo '</td>';

                        $first = false;
                    }
                    echo '<td>';
                        echo '<a href="'.$HTML->encode($API->app_path()).'/delete/?id='.$HTML->encode(urlencode($item['memberID'])).'" class="delete inline-delete">'.PerchLang::get('Delete').'</a>';
                    echo '</td>';
                echo '</tr>';
                $i++;
            }
            echo '</tbody>';
        
        
        echo '</table></div>';
        
    

  
        if ($Paging->enabled()) {
            echo $HTML->paging($Paging);
        }
    */

    } // if pages
    