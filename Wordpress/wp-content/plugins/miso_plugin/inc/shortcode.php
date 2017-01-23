<?php
// This is the shortcode function for the miso plugin.$_COOKIE
function miso_plugin_shortcode( $atts, $content = null ){
    extract( shortcode_atts( array(
                'type' => '',
                'exclude' => '',
                'columns' => 'col-sm-12'
            ), $atts )
    );

    $html_list = ''; // variable to store the list output
    $html_nav = ''; // variable to store the nav output

    $modal_id = 0; // Id for the modals

    $html_output    = ''; // Main output
    $modal_output   = ''; // Modal output
    $count          = 0;

    $bootstrap_columns = strlen($columns)>0 ? $columns: 'col-sm-12';

    $active_nav_item = true;

    $show_nav = false;

    // Is get_pages to get posts with the correct type (only those that are published though).
    $pages_args = array(
        'exclude' => $exclude,
        'post_type' => $type,
        'post_status' => 'publish'
    );

    // loop through all of the post categories. Look for top level. Look for second level
    $tax_name = $type;
    $tax_name = str_replace('miso_', '', $tax_name);
    $tax_name .= '_category';
    
    $taxonomy = $tax_name;

    $current_blog = get_current_blog_id();

    // Array of top level terms
    $top_level_terms = get_terms( $taxonomy, 
        array(
            'hide_empty' => false,
            'parent' => 0
        )
    );


    if (count($top_level_terms) > 0 ){
        // This is the menu loop. Items with a hierarchy to the categories
        $show_nav = true;
        $html_list .= '<div class="tab-content">';
        foreach ($top_level_terms as $top_term){ // For each term add the title to the nav then loop through all the children terms
            if ($top_term->count>0){
                if ($active_nav_item){
                    $html_nav .= '<li class="active"><a data-toggle="tab" href="#'.$top_term->slug.'">'.$top_term->name.'</a></li>';
                    $html_list .= '<div id="'.$top_term->slug.'" class="tab-pane fade in active">';
                    $active_nav_item = false;
                } else {
                    $html_nav .= '<li class=""><a data-toggle="tab" href="#'.$top_term->slug.'">'.$top_term->name.'</a></li>';
                    $html_list .= '<div id="'.$top_term->slug.'" class="tab-pane fade">';
                }
                
                $sub_terms = get_terms( $taxonomy, 
                    array(
                        'hide_empty' => false,
                        'parent' => $top_term->term_id
                    )
                );
                if (count($sub_terms) > 0){
                    foreach ($sub_terms as $term){ // Loop through each of the children terms and output all posts with that category
                        if ($term->count>0){
                            $html_list .= '<h3>'.$term->name.'</h3>';
                            $html_list .= '<div class="row">';

                            $posts = get_posts( array(
                                'tax_query' => array(
                                    array(
                                    'taxonomy' => $taxonomy,
                                    'field' => 'id',
                                    'terms' => $term->term_id,
                                    'include_children' => false
                                    )
                                ),
                                'numberposts' => -1,
                                'exclude' => $exclude,
                                'post_type' => $type,
                                'post_status' => 'publish',
                                'orderby' => 'menu_order', 
                                'order' => 'ASC'
                            ) );
                            
                            foreach ($posts as $item){

                                $variations = get_post_meta($item->ID, '_variations', true);
                                $display_price = get_post_meta($item->ID, '_display_price', true);
                                $show_image = get_post_meta($item->ID, '_show_image', true);

                                if ($current_blog !== 1){
                                    $site_1_id = intval(mlp_get_linked_elements($item->ID, '', $current_blog )[1]);
                                    switch_to_blog( 1 );
                                    if (strlen($variations) < 1){
                                        $variations = get_post_meta($site_1_id, '_variations', true);
                                    }
                                    if (strlen($display_price) < 1){
                                        $display_price = get_post_meta($site_1_id, '_display_price', true);
                                    }
                                    if (strlen($show_image) < 1){
                                        $show_image = get_post_meta($site_1_id, '_show_image', true);
                                    }
                                    restore_current_blog();
                                }

                                $html_list    .= createItemHtml($item, $bootstrap_columns, $variations ,$modal_id, $display_price);
                                if (strlen($item->post_content)>0 || strlen($variations)>0){
                                    $modal_output .= createItemModal($item, $modal_id, $variations, $display_price, $show_image );
                                    $modal_id++;
                                }
                            }
                            $html_list .= '</div>';
                        }
                    }
                } else {
                    $posts = get_posts( array(
                        'tax_query' => array(
                                array(
                                'numberposts' => -1,
                                'taxonomy' => $taxonomy,
                                'field' => 'id',
                                'terms' => $top_term->term_id,
                                'include_children' => false
                                )
                            ),
                        'numberposts' => -1,
                        'exclude' => $exclude,
                        'post_type' => $type,
                        'post_status' => 'publish',
                        'orderby' => 'menu_order', 
                        'order' => 'ASC'
                    ) );
                    foreach ($posts as $item){
                        $variations = get_post_meta($item->ID, '_variations', true);
                        $display_price = get_post_meta($item->ID, '_display_price', true);
                        $show_image = get_post_meta($item->ID, '_show_image', true);
                        if ($current_blog !== 1){
                            $site_1_id = intval(mlp_get_linked_elements($item->ID, '', $current_blog )[1]);
                            switch_to_blog( 1 );
                            if (strlen($variations) < 1){
                                $variations = get_post_meta($site_1_id, '_variations', true);
                            }
                            if (strlen($display_price) < 1){
                                $display_price = get_post_meta($site_1_id, '_display_price', true);
                            }
                            if (strlen($show_image) < 1){
                                $show_image = get_post_meta($site_1_id, '_show_image', true);
                            }
                            restore_current_blog();
                        }
                        $html_list    .= createItemHtml($item, $bootstrap_columns,$variations, $modal_id, $display_price);
                        if (strlen($item->post_content)>0 || strlen($variations)>0){
                            $modal_output .= createItemModal($item, $modal_id, $variations, $display_price, $show_image );
                            $modal_id++;
                        }
                    }
                }
                $html_list .= '</div>';
            }
        }
        $html_list .= '</div>';
    } else {
        // this is the offers loop
        $html_list .= '<div class="row">';
        $show_nav = false;
        $posts = get_posts( array(
            'numberposts' => -1,
            'exclude' => $exclude,
            'post_type' => $type,
            'post_status' => 'publish',
            'orderby' => 'menu_order', 
            'order' => 'ASC'
        ) );
        foreach ($posts as $item){
            $variations = get_post_meta($item->ID, '_variations', true);
            $display_price = get_post_meta($item->ID, '_display_price', true);
            $show_image = get_post_meta($item->ID, '_show_image', true);
            if ($current_blog !== 1){
                $site_1_id = intval(mlp_get_linked_elements($item->ID, '', $current_blog )[1]);
                switch_to_blog( 1 );
                if (strlen($variations) < 1){
                    $variations = get_post_meta($site_1_id, '_variations', true);
                }
                if (strlen($display_price) < 1){
                    $display_price = get_post_meta($site_1_id, '_display_price', true);
                }
                if (strlen($show_image) < 1){
                    $show_image = get_post_meta($site_1_id, '_show_image', true);
                }
                restore_current_blog();
            }
            $html_list    .= createItemHtml($item, $bootstrap_columns, $variations, $modal_id, $display_price);
            if (strlen($item->post_content)>0 || strlen($variations)>0){
                $modal_output .= createItemModal($item, $modal_id, $variations, $display_price, $show_image );
                $modal_id++;
            }
        };
        $html_list .= '</div>';
    }


    $html_output .= '<div class="miso-wrap">';
    $html_output .= '<div class="miso-inner">';

    if ($show_nav){
        $html_output .= '<ul class="nav nav-tabs no-bullets">';
        $html_output .= $html_nav;
        $html_output .= '</ul>';
    }

    $html_output .= $html_list;

    $html_output .= '</div>';
    $html_output .= '</div>';

    $html_output .= $modal_output;

    // $GLOBALS['modal_output'] .= $modal_output; // push the modal output into the global modal_output array.
    return $html_output; // Return the html output
}





function createItemHtml( $item, $classes, $variations, $modal_number, $display_price ){
    /**
     * Accepts a post object and a class value and returns the html for the post
     *
     * @param $item = wordpress post object
     * @param $classes = bootstrap classes
     * @param $modal_number
     * @param $display_price
     * @return the html output
     */
    // Gets the more info text from the blog settings
    $more_info_text = get_option('more-info-text');

    if ($modal_number == 0){
        $modal_number = '0';
    }
    $return = '';
    $return .= '<div class="box '.$classes.' col-xs-24">';
    $return .= get_the_post_thumbnail( $item->ID, 'full' );
    $return .= '<div class="box-tag">'; 
    $return .= '<div class="product-name">'.$item->post_title.'</div>';
    if (strlen($item->post_content)>0 || strlen($variations)>0){
        $return .= '<a data-toggle="modal" href="#/" data-target="#modal_'.$modal_number.'" class="open-modal">'.$more_info_text.'</a>';
    }
    if (strlen($display_price)>0){
        $return .= '<div class="price">'.$display_price.'</div>';
    }
    $return .= '</div></div>';
    return $return;
}
function createItemModal( $item, $modal_number, $variations, $display_price, $show_image ){
    /**
     * Accepts a post object and which modal this is.
     *
     * @post = wordpress post object
     * @modal_number = int
     *
     * returns the modal html
     */

    $return = '';
    $return .= '<div class="modal" id="modal_'.$modal_number.'" tabindex="-1" role="dialog" aria-labelledby="Product info for '.$item->title.'">';
    $return .= '<div class="vertical-alignment-helper">';
    $return .= '<div class="modal-dialog vertical-align-center" role="document">';
    $return .= '<div class="modal-content">';
    $return .= '<div class="modal-header">';
    $return .= '<a id="close_'.$modal_number.'" class="close" data-dismiss="modal" aria-label="Close" aria-hidden="true" href="#">close</a>';

    if ($show_image === 'true'){
        $return .= '<div class="pop-up-img">';
        $image_url = wp_get_attachment_image_src(get_post_thumbnail_id($item->ID), 'full');
        $image_url = gettype($image_url) ==='array'? $image_url[0] : $image_url;
        $return .= '<img src="'.$image_url.'" class="no-margin" alt="Product Image">';
        $return .= '</div>';
        $return .= '<div class="pop-up-right">';

    } else {
        $return .= '<div class="pop-up-full">';
    }
    $return .= '<h3 class="modal-title">'.$item->post_title.'</h3>';
    $return .= apply_filters('the_content', $item->post_content);
    $return .= '</div>';

    $variationsList = json_decode($variations);
    if (count($variationsList) > 0){
        $return .= '<div class="modal-body">';
        foreach ($variationsList as $variation){
            $return .= '<div class="original"><h3>'.$variation->variation_name.'</h3>';
            if (isset($variation->sizes) && count($variation->sizes) > 0){
                foreach ($variation->sizes as $size){
                    $return .= '<p class="choice-price">'.$size->name.' - <span>'.$size->value.'</span></p>';
                }
            }
            $return .= '</div>';
        }
        $return .= '</div>';
    }
    $return .= '</div></div></div></div></div>';
    return $return;

}
