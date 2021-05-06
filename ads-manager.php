<?php
/**
 * Plugin Name: Ads manager
 * Description: Simple Ads manager
 * Plugin URI:  https://github.com/lordealeister/ads-manager
 * Version:     1.1.2
 * Author:      Lorde Aleister
 * Author URI:  https://github.com/lordealeister
 * Text Domain: ads-manager
 */

if(!class_exists('AdsManager')):

    if(file_exists(dirname(__FILE__) . '/vendor/autoload.php'))
        require_once dirname(__FILE__) . '/vendor/autoload.php';

    class AdsManager {

        public function __construct() {
            // Register post type
            add_action('init', array($this, 'registerPostType'));
            add_action('init', array($this, 'registerTaxonomy'));

            add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'), 100);
            
            add_action('admin_menu', array($this, 'addMenuSettings'));
            add_action('admin_init', array($this, 'updateSettings'));

            add_action('cmb2_admin_init', array($this, 'adsMetaBox'));
            add_action('cmb2_admin_init', array($this, 'adsPositionMetaBox'));
            add_action('cmb2_admin_init', array($this, 'adsCodeMetaBox'));

            add_action('shortcode_button_load', array($this, 'adsShortcodeTinymce'), 9993);
            add_shortcode('ads', array($this, 'adsShortcodeOutput'));

            if(!empty(get_option('ads_google')))
                add_action('wp_enqueue_scripts', array($this, 'enqueueAssetsFront'));
        }

        function enqueueAssets() {            
            wp_enqueue_script('ads-manager-script', plugins_url('/assets/ads-manager.js', __FILE__), array('jquery')); 
            wp_enqueue_style('ads-manager-style', plugins_url('/assets/ads-manager.css', __FILE__), false, null);
            wp_enqueue_script('cmb2_conditional_logic', plugins_url('/assets/cmb2-conditional-logic.min.js', __FILE__), array('jquery'), false, true);
        }

        function enqueueAssetsFront() {
            wp_enqueue_script('google-tag', 'https://securepubads.g.doubleclick.net/tag/js/gpt.js', [], null, false);
            wp_add_inline_script('google-tag', $this->adsScripts());
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

        function adsMetaBox() {
            $options = array();
            $terms = get_terms(array('taxonomy' => 'ads_position', 'hide_empty' => false));

            foreach($terms as $term)
                $options[$term->term_id] = $term->name;

            $metaBox = new_cmb2_box(array(
                'id'           => 'ads_positions_metabox',
                'title'        => __('Anúncios', 'ads-manager'),
                'object_types' => array('page', 'post', 'term'),
                'taxonomies'   => array('category'),
                'context'      => 'side',
                'priority'     => 'low',
            ));

            $metaBox->add_field(array(
                'name' => '',
                'desc' => __('Marque para gerenciar os anúncios nessa página'),
                'id'   => 'ads_manage',
                'type' => 'switch',
            ));

            $positions = $metaBox->add_field(array(
                'id'          => 'ads_positions',
                'type'        => 'group',
                'options'     => array(
                    'group_title'    => __('Posição {#}', 'ads-manager'), // {#} gets replaced by row number
                    'add_button'     => __('Gerenciar nova posição', 'ads-manager'),
                    'remove_button'  => __('Remover posição', 'ads-manager'),
                    'sortable'       => false,
                    'closed'         => true, // true to have the groups closed by default
                ),
            ));

            $metaBox->add_group_field($positions, array(
                'name'           => 'Posição',
                'id'             => 'ads_position',
                'taxonomy'       => 'ads_position', //Enter Taxonomy Slug
                'type'           => 'select',
                'show_option_none' => false,
                'options'          => $options,
            ));

            $metaBox->add_group_field($positions, array(
                'name'             => __('Ativo', 'ads-manager'),
                'id'               => 'ads_position_active',
                'type'             => 'switch',
                'default'          => true, //If it's checked by default 
                'active_value'     => true,
                'inactive_value'   => false
            ));

            $metaBox->add_group_field($positions, array(
                'name'       => __('Anúncio mobile', 'ads-manager'),
                'id'         => 'ads_mobile',
                'type'       => 'post_search_ajax',
                'desc'			=> __('Comece a digitar o título do anúncio', 'ads-manager'),
                'query_args'	=> array(
                    'post_type'			=> array('ads'),
                    'post_status'		=> array('publish'),
                    'posts_per_page'	=> -1,
                ),
                'attributes'    => array(
                    'data-conditional-id'     => 'ads_position_active',
                    'data-conditional-value'  => true,
                ),
            ));

            $metaBox->add_group_field($positions, array(
                'name'       => __('Anúncio desktop', 'ads-manager'),
                'id'         => 'ads_desktop',
                'type'       => 'post_search_ajax',
                'desc'			=> __('Comece a digitar o título do anúncio', 'ads-manager'),
                'query_args'	=> array(
                    'post_type'			=> array('ads'),
                    'post_status'		=> array('publish'),
                    'posts_per_page'	=> -1,
                ),
                'attributes'    => array(
                    'data-conditional-id'     => 'ads_position_active',
                    'data-conditional-value'  => true,
                ),
            ));
        }

        function adsPositionMetaBox() {
            $metaBox = new_cmb2_box(array(
                'id'           => 'ads_position_metabox',
                'title'        => __('Anúncios', 'ads-manager'),
                'object_types' => array('term'),
                'taxonomies'   => array('ads_position'),
                'context'      => 'normal',
                'priority'     => 'low',
            ));

            $metaBox->add_field(array(
                'name'       => __('Anúncio padrão mobile', 'ads-manager'),
                'id'         => 'ads_mobile',
                'type'       => 'post_search_ajax',
                'desc'			=> __('Comece a digitar o título do anúncio', 'ads-manager'),
                'query_args'	=> array(
                    'post_type'			=> array('ads'),
                    'post_status'		=> array('publish'),
                    'posts_per_page'	=> -1,
                )
            ));

            $metaBox->add_field(array(
                'name'       => __('Anúncio padrão desktop', 'ads-manager'),
                'id'         => 'ads_desktop',
                'type'       => 'post_search_ajax',
                'desc'			=> __('Comece a digitar o título do anúncio', 'ads-manager'),
                'query_args'	=> array(
                    'post_type'			=> array('ads'),
                    'post_status'		=> array('publish'),
                    'posts_per_page'	=> -1,
                )
            ));
        }

        function adsCodeMetaBox() {
            $metaBox = new_cmb2_box(array(
                'id'           => 'ads_metabox',
                'title'        => __('Definições', 'ads-manager'),
                'object_types' => array('ads'),
                'context'      => 'normal',
            ));

            $metaBox->add_field(array(
                'name'       => __('Conteúdo do anúncio', 'ads-manager'),
                'id'         => 'ads_code',
                'type'       => 'textarea_code',
            ));

            $metaBox->add_field(array(
                'name'       => __('Definição do slot', 'ads-manager'),
                'desc'       => __("Insira somente a definição do slot, exemplo: <br> googletag.defineSlot('/12345678/eC_HomeM_Square1', [300, 250], 'div-gpt-ad-123456789-0').addService(googletag.pubads());", 'ads-manager'),
                'id'         => 'ads_slot',
                'type'       => 'textarea_code',
                'attributes' => array(
                    'style'  => empty(get_option('ads_google')) ? 'display: none;' : '',
                )
            ));
        }

        function adsShortcodeTinymce() {
            // the button slug should be your shortcodes name.
            // The same value you would use in `add_shortcode`
            // Only numbers, letters and underscores are allowed.
            $button_slug = 'ads';
        
            // Set up the button data that will be passed to the javascript files
            $js_button_data = array(
                // Actual quicktag button text (on the text edit tab)
                'qt_button_text' => __('Anúncio', 'ads-manager'),
                // Tinymce button hover tooltip (on the html edit tab)
                'button_tooltip' => __('Inserir anúncio', 'ads-manager'),
                // Tinymce button icon. Use a dashicon class or a 20x20 image url
                'icon'           => 'dashicons-money-alt',
        
                // Optional parameters
                'author'         => 'Lorde Aleister',
                'authorurl'      => 'https://github.com/lordealeister',
                'infourl'        => 'https://github.com/lordealeister/ads-manager',
                'version'        => '0.0.1',
                'include_close'  => true, // Will wrap your selection in the shortcode
                'mceView'        => false, // Live preview of shortcode in editor. YMMV.
        
                // Use your own textdomain
                'l10ncancel'     => __('Cancel', 'shortcode-button'),
                'l10ninsert'     => __('Insert Shortcode', 'shortcode-button'),
            );
        
            // Optional additional parameters
            $additional_args = array(
                // Can be a callback or metabox config array
                'cmb_metabox_config'   => array($this, 'adsShortcodeConfig'),
            );
        
            new Shortcode_Button($button_slug, $js_button_data, $additional_args);
        }

        function adsShortcodeConfig($button_data) {
            return array(
                'id'     => 'shortcode_'. $button_data['slug'],
                'fields' => array(
                    array(
                        'name'    => __('Anúncio', 'ads-manager'),
                        'desc'    => __('Comece a digitar o título do anúncio', 'ads-manager'),
                        'id'      => 'id',
                        'type'    => 'post_search_ajax',
                        'query_args'	=> array(
                            'post_type'			=> array('ads'),
                            'post_status'		=> array('publish'),
                            'posts_per_page'	=> -1,
                        )
                    ),
                    array(
                        'name'    => __('Exibir em', 'ads-manager'),
                        'id'      => 'display',
                        'type'    => 'radio_inline',
                        'options' => array(
                            'mobile' => __('Mobile', 'ads-manager'),
                            'desktop'   => __('Desktop', 'ads-manager'),
                        ),
                        'default' => 'mobile',
                    )
                ),
                // keep this w/ a key of 'options-page' and use the button slug as the value
                'show_on' => array('key' => 'options-page', 'value' => $button_data['slug']),
            );
        }

        //function to output shortcode
        public function adsShortcodeOutput($atts) {
            if(!isset($atts['id']))
                return;

            return ads_code($atts['id'], isset($atts['display']) ? $atts['display'] : 'mobile');
        }

        public function addMenuSettings() {
            add_submenu_page(
                'edit.php?post_type=ads',
                'Configurações',
                'Configurações',                   
                'manage_options',                   
                'ads-settings',                   
                array($this, 'addPageSettings')               
            ); 
        }

        public function addPageSettings() {
            ?>
            <h1>Configurações de ads</h1>

            <form method="post" action="options.php">
                <?php settings_fields('ads-settings'); ?>
                <?php do_settings_sections('ads-settings'); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="ads_google">Habilitar anúncios do Google:</label></th>
                        <td><input type="checkbox" id="ads_google" name="ads_google" value="enabled" <?php echo(!empty(get_option('ads_google')) ? " checked" : ""); ?>/></td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
            <?php
        }

        function updateSettings() {
            register_setting('ads-settings', 'ads_google'); 
        }

        function adsScripts() {
            global $wpdb;
            $data = "window.googletag = window.googletag || {cmd: []}; googletag.cmd.push(function() {";

            $results = $wpdb->get_results( 
                "SELECT pm.meta_value AS slot
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                AND pm.meta_key = 'ads_slot'
                WHERE p.post_type = 'ads'
                AND p.post_status NOT IN ('draft','auto-draft')"
            );

            if(!empty($results)):
                foreach($results as $ads):
                    if(empty($ads->slot))
                        continue;

                    $data .= $ads->slot;
                endforeach;
            endif;

            $data .= "googletag.pubads().enableSingleRequest(); googletag.enableServices(); });";

            return $data;
        }

    }

    function ads_position($position) {
        if(empty($position))
            return;
        
        $position = get_term_by('slug', $position, 'ads_position');
        if(empty($position))
            return;

        $ads_mobile = 0;
        $ads_desktop = 0;
        $is_category = is_category();

        if(!$is_category && !empty(get_post_meta(get_the_ID(), 'ads_manage', true)) || 
            $is_category && !empty(get_term_meta(get_query_var('cat'), 'ads_manage', true))):
            $entries = $is_category ? get_term_meta(get_query_var('cat'), 'ads_positions', true) : get_post_meta(get_the_ID(), 'ads_positions', true);    
            
            foreach($entries as $entry):
                if($entry['ads_position'] != $position->term_id)
                    continue;

                if(empty($entry['ads_position_active']))
                    return '';

                $ads_mobile = isset($entry['ads_mobile']) ? $entry['ads_mobile'] : -1;
                $ads_desktop = isset($entry['ads_desktop']) ? $entry['ads_desktop'] : -1;
            endforeach;
        endif;

        $html = "";

        if(!empty($ads_mobile) && $ads_mobile != -1):
            $html .= "<div class=\"ads-manager ads-position ads-mobile d-block d-lg-none\">" . get_post_meta($ads_mobile, 'ads_code', true) . "</div>";
        elseif(empty($ads_mobile)):
            $ads_mobile = get_term_meta($position->term_id, 'ads_mobile', true);

            if(!empty($ads_mobile))
                $ads_mobile = get_post_meta($ads_mobile, 'ads_code', true);

            if(!empty($ads_mobile))
                $html .= "<div class=\"ads-manager ads-position ads-mobile d-block d-lg-none\">$ads_mobile</div>";
        endif;

        if(!empty($ads_desktop) && $ads_desktop != -1):
            $html .= "<div class=\"ads-manager ads-position ads-desktop d-none d-lg-block\">" . get_post_meta($ads_desktop, 'ads_code', true) . "</div>";
        elseif(empty($ads_desktop)):
            $ads_desktop = get_term_meta($position->term_id, 'ads_desktop', true);

            if(!empty($ads_mobile))
                $ads_desktop = get_post_meta($ads_desktop, 'ads_code', true);

            if(!empty($ads_desktop))
                $html .= "<div class=\"ads-manager ads-position ads-desktop d-none d-lg-block\">$ads_desktop</div>";
        endif;

        return $html;
    }

    function ads_code($id = 0, $display = null) {
        if(empty($id))
            $id = get_the_ID();
        if(empty($id))
            return;

        return "<div class=\"" . ($display == 'mobile' ? 'ads-manager ads-code ads-mobile d-block d-lg-none' : ($display == 'desktop' ? 'ads-manager ads-code ads-desktop d-none d-lg-block' : 'ads-manager ads-code d-block')) . "\">" . get_post_meta($id, 'ads_code', true) . "</div>";
    }

    new AdsManager();
endif;

?>
