<?php
/**
 * Plugin Name: Ads manager
 * Description: Simple Ads manager
 * Plugin URI:  https://github.com/lordealeister/ads-manager
 * Version:     0.0.1
 * Author:      Lorde Aleister
 * Author URI:  https://github.com/lordealeister
 * Text Domain: ads-manager
 */

if(!class_exists('AdsManager')):
    class AdsManager {

        public function __construct() {
            // Register post type
            add_action('init', array($this, 'registerPostType'));
            add_action('add_meta_boxes', array($this, 'adsCodeMetaBox'));
            add_action('save_post_ads', array($this, 'saveAdsContent'));

            add_action('category_add_form_fields', array($this, 'adsAddCategory'));
            add_action('category_edit_form', array($this, 'adsEditCategory'));
            add_action('created_category', array($this, 'saveCategoryAds'));
            add_action('edited_category', array($this, 'saveCategoryAds'));

            add_action('add_meta_boxes', array($this, 'adsMetaBox'));
            add_action('save_post', array($this, 'saveAdsPosition'));

            add_action('init', array($this, 'registerTaxonomy'));
            add_action('ads_position_edit_form_fields', array($this, 'positionEditAdsField'));
            add_action('ads_position_add_form_fields', array($this, 'positionAddAdsField'));
            add_action('edited_ads_position', array($this, 'savePositionAds'));  
            add_action('create_ads_position', array($this, 'savePositionAds'));  
            
            add_action('wp_ajax_ads_search', array($this, 'adsSearch'));

            add_action('admin_head', array($this, 'adsShortcodeTinymce'));    
            add_shortcode('ads', array($this, 'adsShortcodeOutput'));

            add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'), 100);
        }

        function enqueueAssets() {
            if(!wp_script_is('select2', 'enqueued')):
                wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
                wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery'));
            endif;
            
            wp_enqueue_script('ads_manager_script', plugins_url('/assets/ads-manager.js', __FILE__), array('jquery', 'select2')); 
        }
    
        function registerPostType() {
            $labels = array(
                'name'                  => 'Anúncios',
                'singular_name'         => 'Anúncio',
                'menu_name'             => 'Anúncios',
                'name_admin_bar'        => 'Anúncios',
                'archives'              => 'Arquivo de anúncios',
                'parent_item_colon'     => 'Anúncios',
                'all_items'             => 'Todos os anúncios',
                'add_new_item'          => 'Adicionar novo anúncio',
                'add_new'               => 'Adicionar novo',
                'new_item'              => 'Novo anúncio',
                'edit_item'             => 'Editar anúncio',
                'update_item'           => 'Atualizar anúncio',
                'view_item'             => 'Visualizar anúncio',
                'search_items'          => 'Procurar anúncios',
                'not_found'             => 'Anúncio não encontrado',
                'not_found_in_trash'    => 'Anúncio não encontrado na lixeira',
                'insert_into_item'      => 'Inserir no anúncio',
                'uploaded_to_this_item' => 'Carregado para esse anúncio',
                'items_list'            => 'Lista de anúncios',
                'items_list_navigation' => 'Navegação da lista de anúncios',
                'filter_items_list'     => 'Filtrar lista de anúncios',
            );
    
            $args = array(
                'label'                 => 'Anúncio',
                'description'           => 'Anúncio',
                'labels'                => $labels,
                'supports'              => array('title'),
                'hierarchical'          => false,
                'public'                => false,
                'show_ui'               => true,
                'show_in_menu'          => true,
                'menu_icon'				=> 'dashicons-money-alt',
                'menu_position'         => 80,
                'show_in_admin_bar'     => false,
                'can_export'            => true,
                'rewrite'               => false,
                'capability_type'       => 'page',
                'query_var'				=> true,
                'publicly_queryable'    => true,
            );
    
            register_post_type('ads', $args);
        }

        function registerTaxonomy() {
            $labels = array(
                'name'              => 'Posições',
                'singular_name'     => 'Posição',
                'search_items'      => 'Procurar posição',
                'all_items'         => 'Todas as posições',
                'edit_item'         => 'Editar posição',
                'update_item'       => 'Atualizar posição',
                'add_new_item'      => 'Adicionar posição',
                'new_item_name'     => 'Nova posição',
                'menu_name'         => 'Posições',
            );
    
            register_taxonomy('ads_position', array('ads'), array(
                'hierarchical'       => false,
                'labels'             => $labels,
                'show_ui'            => true,
                'show_admin_column'  => false,
                'show_in_nav_menus'  => false,
                'show_tagcloud'      => false,
                'show_in_quick_edit' => false,
                'meta_box_cb'        => false,
            ));
        }

        function adsCodeMetaBox() {
            add_meta_box( 
                'ads-code', // $id
                __('Código', 'ads-manager'), // $title
                array($this, 'adsCodeMetaBoxContent'), // $callback
                array('ads'),
                'normal', // $context
                'high' // $priority
            );
        }

        function adsCodeMetaBoxContent($post) { 
            $ads= get_post_meta($post->ID, 'ads_code', true);
            echo "<textarea style=\"width: 100%; min-height: 200px;\" type=\"textarea\" name=\"ads_code\">$ads</textarea>";
        }

        function saveAdsContent($post_id) {
            if(!isset($_POST['ads_code']))
                return;

            update_post_meta($post_id, 'ads_code', $_POST['ads_code']);
        }

        function adsMetaBox() {
            add_meta_box( 
                'ads', // $id
                __('Anúncios', 'ads-manager'), // $title
                array($this, 'adsMetaBoxContent'), // $callback
                array('post', 'page'),
                'side', // $context
                'low' // $priority
            );
        }

        function adsMetaBoxContent($post) { 
            $positions = get_terms(array('taxonomy' => 'ads_position', 'hide_empty' => false));

            foreach($positions as $position):
                $key = "ads_position_" . $position->term_id;
                $value = get_term_meta($position->term_id, "ads_code", true);

                if(metadata_exists('post', $post->ID, $key))
                    $value = get_post_meta($post->ID, $key, true);

                $data = $this->createSelect($key, $position->name, $value);

                echo "<p class=\"post-attributes-label-wrapper\">$data->label</p>";
                echo $data->select;    
            endforeach;
        }

        function adsEditCategory($term) { 
            $positions = get_terms(array('taxonomy' => 'ads_position', 'hide_empty' => false));

            echo "<p class=\"post-attributes-label-wrapper\">Anúncios</p>";

            foreach($positions as $position):
                $key = "ads_position_" . $position->term_id;
                $value = get_term_meta($position->term_id, "ads_code", true);

                if(metadata_exists('term', $term->term_id, $key))
                    $value = get_post_meta($term->term_id, $key, true);

                $data = $this->createSelect($key, $position->name, $value);

                echo "<p class=\"post-attributes-label-wrapper\">$data->label</p>";
                echo $data->select;    
            endforeach;
        }

        function adsAddCategory() {
            $positions = get_terms(array('taxonomy' => 'ads_position', 'hide_empty' => false));

            echo "<p class=\"post-attributes-label-wrapper\">Anúncios</p>";

            foreach($positions as $position):
                $key = "ads_position_" . $position->term_id;
                $value = get_term_meta($position->term_id, "ads_code", true);

                $data = $this->createSelect($key, $position->name, $value);

                $html = "<div class=\"form-field term-description-wrap\" style=\"width: 95%;\">";
                $html .= $data->label;
                $html .= $data->select;
                $html .= "</div>";
            
                echo $html;  
            endforeach;
        }  

        function saveCategoryAds($term_id) {
            $positions = array_filter(
                $_POST,
                function($key) {
                    return (substr($key, 0, strlen("ads_position_")) === "ads_position_");
                },
                ARRAY_FILTER_USE_KEY
            );

            if(empty($positions))
                return;

            foreach($positions as $key => $value)
                update_term_meta($term_id, $key, sanitize_text_field($value));
        }

        function saveAdsPosition($post_id) {
            $positions = array_filter(
                $_POST,
                function($key) {
                    return (substr($key, 0, strlen("ads_position_")) === "ads_position_");
                },
                ARRAY_FILTER_USE_KEY
            );

            if(empty($positions))
                return;

            foreach($positions as $key => $value)
                update_post_meta($post_id, $key, sanitize_text_field($value));
        }

        function createSelect($id, $title, $value = "") {
            $data = new stdClass();
            $data->label = "<label for=\"$id\" class=\"post-attributes-label\">$title</label>";
            $data->select = "<select id=\"$id\" class=\"ads-select\" name=\"$id\" style=\"width: 100%;\">";

            $data->select .= "<option value=\"\"" . (empty($value) ? " selected=\"selected\"" : "") . ">Vazio</option>";
            
            if(!empty($value))
                $data->select .= "<option value=\"$value\" selected=\"selected\">" . get_the_title($value) . "</option>";

            $data->select .= "</select>";

            return $data;
        }

        function positionEditAdsField($term) {  
            $key = "ads_code";
            $value = "";
            
            if(!is_string($term))
                $value = get_term_meta($term->term_id, $key, true);

            $data = $this->createSelect($key, 'Anúncio padrão', $value);

            $html = "<tr class=\"form-field term-name-wrap\">";
			$html .= "<th scope=\"row\">$data->label</th>";
			$html .= "<td>$data->select</td>";
            $html .= "</tr>";
         
            echo $html;
        }  

        function positionAddAdsField() {  
            $key = "ads_code";

            $data = $this->createSelect($key, 'Anúncio padrão', "");

            $html = "<div class=\"form-field term-description-wrap\" style=\"width: 95%;\">";
			$html .= $data->label;
			$html .= $data->select;
            $html .= "</div>";
         
            echo $html;
        }  

        function savePositionAds($term_id) {
            if(!isset($_POST['ads_code']))
                return;

            $ads = sanitize_text_field($_POST['ads_code']);

            update_term_meta($term_id, 'ads_code', $ads);
        }

        public function adsShortcodeTinymce() {
            add_filter('mce_external_plugins', array($this, 'adsShortcodeTinymcePlugin'));
            add_filter('mce_buttons', array($this, 'adsShortcodeTinymceButton'));
        }
        
        // inlcude the js for tinymce
        public function adsShortcodeTinymcePlugin($plugin_array) {
            $plugin_array['ads_shortcode_button'] = plugins_url('/assets/ads-shortcode.js', __FILE__);

            return $plugin_array;
        }

        // Add the button key for address via JS
        public function adsShortcodeTinymceButton($buttons) {
            array_push($buttons, 'ads_shortcode');

            return $buttons;
        }

        public function adsSearch() {
            $search = $_GET['search'];

            global $wpdb;
            $ads = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'ads' AND post_status = 'publish' AND post_title LIKE '%s'", '%' . $wpdb->esc_like($search) . '%'));

            echo wp_json_encode($ads);
            wp_die(); // this is required to terminate immediately and return a proper response
        }

        //function to output shortcode
        public function adsShortcodeOutput($atts) {
            if(!isset($atts['id']))
                return;

            return ads_code($atts['id']);
        }

    }

    function ads_position($position) {
        if(empty($position))
            return;

        $position = get_term_by('slug', $position, 'ads_position');
        if(empty($position))
            return;
        
        $is_category = is_category();

        if($is_category && !metadata_exists('term', get_query_var('cat'), "ads_position_" . $position->term_id) ||
            !$is_category && !metadata_exists('post', get_the_ID(), "ads_position_" . $position->term_id)):
            $ads = get_term_meta($position->term_id, "ads_code", true);

            if(!empty($ads))
                return get_post_meta($ads, "ads_code", true);

            return "";
        endif;

        $ads = $is_category ? get_term_meta(get_query_var('cat'), "ads_position_" . $position->term_id, true) : get_post_meta(get_the_ID(), "ads_position_" . $position->term_id, true);
        
        if(empty($ads))
            return;

        return get_post_meta($ads, "ads_code", true);
    }

    function ads_code($id = 0) {
        if(empty($id))
            $id = get_the_ID();
        if(empty($id))
            return;

        return get_post_meta($id, "ads_code", true);
    }

    new AdsManager();
endif;

?>
