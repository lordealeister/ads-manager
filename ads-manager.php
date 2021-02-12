<?php
/**
 * Plugin Name: Ads manager
 * Description: Adds a button to the editor for inserting the article shortcode
 * Plugin URI:  https://github.com/zero4281/tinymce-post-dropdown
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

            add_action('init', array($this, 'registerTaxonomy'));
            add_action('ads_position_edit_form_fields', array($this, 'positionAdsField'));
            // add_action('ads_position_add_form_fields', array($this, 'positionAdsField'));
            
            add_action('admin_head', array($this, 'articleShortcodeTinymce'));    
            add_action('wp_ajax_ads_search', array($this, 'adsShortcodeSearch'));
            add_shortcode('ads', array($this, 'adsShortcodeOutput'));

            add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));
        }

        function enqueueAssets() {
            // wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
            // wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery'));
         
            
            wp_enqueue_script('ads_manager_script', plugins_url('/assets/ads-manager.js', __FILE__), array('jquery', 'select2')); 
         
        }
    
        public function registerPostType() {
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
                'query_var'				=> false,
            );
    
            register_post_type('ads', $args);
        }

        public function registerTaxonomy() {
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

            $ads = sanitize_text_field($_POST['ads_code']);

            update_post_meta($post_id, 'ads_code', $ads);
        }

        function adsMetaBox() {
            add_meta_box( 
                'ads', // $id
                __('Anúncio', 'ads-manager'), // $title
                array($this, 'adsMetaBoxContent'), // $callback
                array('post', 'page'),
                'normal', // $context
                'high' // $priority
            );
        }

        function adsMetaBoxContent($post) { 
            $ads= get_post_meta($post->ID, 'ads_code', true);
            echo "<textarea style=\"width: 100%; min-height: 200px;\" type=\"textarea\" name=\"ads_code\">$ads</textarea>";
        }

        function positionAdsField($tag) {  
            $html = '<p style="margin-bottom: 1rem;"><label for="rudr_select2_posts">Anúncio</label><select id="rudr_select2_posts" class="ads-select" name="rudr_select2_posts" style="width: 95%;">';
            $html .= '<option>Teste</option>';
            
            $html .= '</select></p>';
         
            echo $html;
         }  

        public function articleShortcodeTinymce() {
            add_filter('mce_external_plugins', array($this, 'adsShortcodeTinymcePlugin'));
            add_filter('mce_buttons', array($this, 'adsShortcodeTinymceButton'));
        }
        
        // inlcude the js for tinymce
        public function adsShortcodeTinymcePlugin($plugin_array) {
            $plugin_array['ads_shortcode_button'] = plugins_url('/ads-shortcode.js', __FILE__);

            return $plugin_array;
        }

        // Add the button key for address via JS
        public function adsShortcodeTinymceButton($buttons) {
            array_push($buttons, 'ads_shortcode');

            return $buttons;
        }

        public function adsShortcodeSearch() {
            $search = $_GET['search'];

            $query = new WP_Query(array(
                'post_type' => 'ads', 
                'post_status' => 'publish',
                'posts_per_page' => 50,
                // 's' => $search,
            ));

            echo wp_json_encode($query->posts);

            wp_die(); // this is required to terminate immediately and return a proper response
        }

        /*
        TMCEPD_URL_dropdown_key
        */
        //function to output shortcode
        public function adsShortcodeOutput($atts) {
            return apply_filters('article_shortcode_output', $atts);
        }

    }

    new AdsManager();
endif;

?>
