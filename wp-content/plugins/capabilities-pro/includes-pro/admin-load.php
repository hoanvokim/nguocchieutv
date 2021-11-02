<?php
namespace PublishPress\Capabilities;

/*
 * PublishPress Capabilities Pro
 * 
 * Admin execution controller: menu registration and other filters and actions that need to be loaded for every wp-admin URL
 * 
 * This module should not include full functions related to our own plugin screens.  
 * Instead, use these filter and action handlers to load other classes when needed.
 * 
 */
class AdminFiltersPro {
    function __construct() {
        add_action('init', [$this, 'versionInfoRedirect'], 1);
        add_action('admin_init', [$this, 'loadUpdater']);

        add_action('admin_enqueue_scripts', [$this, 'adminScripts']);

        // Editor Features: Custom Items
        add_action('admin_init', [$this, 'initPostFeatureCustom']);

        $this->initPostFeatureCustom();

        add_action('pp_capabilities_editor_features', [$this, 'editorFeaturesUI']);
        add_action('wp_ajax_ppc_submit_feature_gutenberg_by_ajax', [$this, 'ajaxFeaturesRestrictCustomItem']);
        add_action('wp_ajax_ppc_submit_feature_classic_by_ajax', [$this, 'ajaxFeaturesRestrictCustomItem']);
        add_action('wp_ajax_ppc_delete_custom_post_features_by_ajax', [$this, 'ajaxFeaturesClearCustomItem']);

        // Editor Features: Metaboxes
        add_action('admin_head', [$this, 'initPostFeatureMetaboxes'], 999);

        add_action('publishpress-caps_manager-load', [$this, 'CapsManagerLoad']);
        add_action('admin_print_styles', array($this, 'adminStyles'));

        add_action('pp-capabilities-settings-ui', [$this, 'settingsUI']);
        add_action('pp-capabilities-update-settings', [$this, 'updateSettings']);

        add_action('publishpress-caps_manager_postcaps_section', [$this, 'capsManagerUI']);

        //add_action('publishpress-caps_sidebar_bottom', [$this, 'sidebarUI']);

        add_action('publishpress-caps_process_update', [$this, 'updateOptions']);

        add_action('pp-capabilities-admin-submenus', [$this, 'actCapabilitiesSubmenus']);
        
        if (!empty($_REQUEST['page']) && ('pp-capabilities' == $_REQUEST['page']) 
        && !empty($_POST) && !empty($_POST['action']) && ('update' == $_POST['action'])
        ) {
            add_action('init', [$this, 'updateCapabilitiesOptions']);
        }
    }

    public function adminScripts() {
        global $capsman;

        $url = plugins_url( '', CME_FILE );

        wp_register_style('pp_capabilities_pro_admin', $url . '/includes-pro/common/css/admin.css', false, PUBLISHPRESS_CAPS_VERSION);
        wp_enqueue_style('pp_capabilities_pro_admin');

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        $url .= "/includes-pro/common/js/admin{$suffix}.js";
        wp_enqueue_script( 'pp_capabilities_pro_admin', $url, array('jquery'), PUBLISHPRESS_CAPS_VERSION, true );
    }

    public function settingsUI() {
        require_once(dirname(__FILE__).'/settings-ui.php');
        new Pro_Settings_UI();
    }

    public function updateSettings() {
        require_once(dirname(__FILE__).'/settings-handler.php');
        new Pro_Settings_Handler();
    }

    function actCapabilitiesSubmenus() {
        $cap_name = (is_multisite() && is_super_admin()) ? 'read' : 'manage_capabilities';
        
        add_submenu_page('pp-capabilities',  __('Admin Menus', 'capsman-enhanced'), __('Admin Menus', 'capsman-enhanced'), $cap_name, 'pp-capabilities-admin-menus', [$this, 'ManageAdminMenus']);
        add_submenu_page('pp-capabilities',  __('Nav Menus', 'capsman-enhanced'), __('Nav Menus', 'capsman-enhanced'), $cap_name, 'pp-capabilities-nav-menus', [$this, 'ManageNavMenus']);
    }

    /**
	 * Manages admin menu permission
	 *
	 * @hook add_management_page
	 * @return void
	 */
	function ManageAdminMenus ()
	{
        global $capsman;

		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' .__('You do not have permission to manage menu restrictions.', 'capabilities-pro') . '</strong>');
		}

		$capsman->generateNames();
		$roles = array_keys($capsman->roles);

		if ( ! isset($capsman->current) ) {
			if (empty($_POST) && !empty($_REQUEST['role'])) {
                $capsman->set_current_role($_REQUEST['role']);
			}
		}

		if (!isset($capsman->current) || !get_role($capsman->current)) {
			$capsman->current = $capsman->get_last_role();
		}

		if ( ! in_array($capsman->current, $roles) ) {
			$capsman->current = array_shift($roles);
		}

		$ppc_admin_menu_reload = '0';

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['ppc-admin-menu-role']) ) {
			$capsman->set_current_role($_POST['ppc-admin-menu-role']);

			//set role admin menu
			$admin_menu_option = !empty(get_option('capsman_admin_menus')) ? get_option('capsman_admin_menus') : [];
			$admin_menu_option[$_POST['ppc-admin-menu-role']] = isset($_POST['pp_cababilities_disabled_menu']) ? $_POST['pp_cababilities_disabled_menu'] : '';

			//set role admin child menu
			$admin_child_menu_option = !empty(get_option('capsman_admin_child_menus')) ? get_option('capsman_admin_child_menus') : [];
			$admin_child_menu_option[$_POST['ppc-admin-menu-role']] = isset($_POST['pp_cababilities_disabled_child_menu']) ? $_POST['pp_cababilities_disabled_child_menu'] : '';

			update_option('capsman_admin_menus', $admin_menu_option, false);
			update_option('capsman_admin_child_menus', $admin_child_menu_option, false);

			//set reload option for menu reflection if user is updating own role
			if(in_array($_POST['ppc-admin-menu-role'], wp_get_current_user()->roles)){
			$ppc_admin_menu_reload = '1';
			}

            ak_admin_notify(__('Settings updated.', 'capabilities-pro'));
		}

		include ( dirname(__FILE__) . '/admin-menus.php' );
	}

    /**
     * Manages navigation menu permissions
     *
     * @hook add_management_page
     * @return void
     */
    function ManageNavMenus()
    {
        global $capsman;

        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities')) {
            // TODO: Implement exceptions.
            wp_die('<strong>' . __('You do not have permission to manage navigation menus.', 'capabilities-pro') . '</strong>');
        }

        $capsman->generateNames();
        $roles = array_keys($capsman->roles);

        if (!isset($capsman->current)) {
            if (empty($_POST) && !empty($_REQUEST['role'])) {
                $capsman->set_current_role($_REQUEST['role']);
            }
        }

        if (!isset($capsman->current) || !get_role($capsman->current)) {
            $capsman->current = $capsman->get_last_role();
        }

        if (!in_array($capsman->current, $roles)) {
            $capsman->current = array_shift($roles);
        }


        if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['ppc-nav-menu-role'])) {
            $capsman->set_current_role($_POST['ppc-nav-menu-role']);

            //set role nav child menu
            $nav_item_menu_option = !empty(get_option('capsman_nav_item_menus')) ? get_option('capsman_nav_item_menus') : [];
            $nav_item_menu_option[$_POST['ppc-nav-menu-role']] = isset($_POST['pp_cababilities_restricted_items']) ? $_POST['pp_cababilities_restricted_items'] : '';


            update_option('capsman_nav_item_menus', $nav_item_menu_option, false);


            ak_admin_notify(__('Settings updated.', 'capabilities-pro'));
        }

        include(dirname(__FILE__) . '/nav-menus.php');
    }

    function versionInfoRedirect() {
        if (!empty($_REQUEST['publishpress_caps_refresh_updates'])) {
            publishpress_caps_pro()->keyStatus(true);
            set_transient('publishpress-caps-refresh-update-info', true, 86400);

            delete_site_transient('update_plugins');
            delete_option('_site_transient_update_plugins');

            $opt_val = get_option('cme_edd_key');
            if (is_array($opt_val) && !empty($opt_val['license_key'])) {
                $plugin_slug = basename(CME_FILE, '.php'); // 'capabilities-pro';
                $plugin_relpath = basename(dirname(CME_FILE)) . '/' . basename(CME_FILE);  // $_REQUEST['plugin']
                $license_key = $opt_val['license_key'];
                $beta = false;

                delete_option(md5(serialize($plugin_slug . $license_key . $beta)));
                delete_option('edd_api_request_' . md5(serialize($plugin_slug . $license_key . $beta)));
                delete_option(md5('edd_plugin_' . sanitize_key($plugin_relpath) . '_' . $beta . '_version_info'));
            }

            wp_update_plugins();
            //wp_version_check(array(), true);

            if (current_user_can('update_plugins')) {
                $url = remove_query_arg('publishpress_caps_refresh_updates', $_SERVER['REQUEST_URI']);
                $url = add_query_arg('publishpress_caps_refresh_done', 1, $url);
                $url = "//" . $_SERVER['HTTP_HOST'] . $url;
                wp_redirect($url);
                exit;
            }
        }

        if (!empty($_REQUEST['publishpress_caps_refresh_done']) && empty($_POST)) {
            if (current_user_can('activate_plugins')) {
                $url = admin_url('update-core.php');
                wp_redirect($url);
            }
        }
    }

    function CapsManagerLoad() {
        require_once(dirname(__FILE__).'/manager-ui.php');
        new ManagerUI();
    }

    function loadUpdater() {
        require_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes-pro/library/Factory.php');
        $container = \PublishPress\Capabilities\Factory::get_container();
        return $container['edd_container']['update_manager'];
    }

    function adminStyles() {
        global $plugin_page;

        if (!empty($plugin_page) && (0 == strpos('pp-capabilities', $plugin_page))) {
            wp_enqueue_style('publishpress-caps-pro', plugins_url( '', CME_FILE ) . '/includes-pro/pro.css', [], PUBLISHPRESS_CAPS_VERSION);
            wp_enqueue_style('publishpress-caps-status-caps', plugins_url( '', CME_FILE ) . '/includes-pro/status-caps.css', [], PUBLISHPRESS_CAPS_VERSION);

            add_thickbox();
        }
    }

    function capsManagerUI($args) {
        if (Pro::customStatusPermissionsAvailable() && get_option('cme_custom_status_control')) {
            require_once(dirname(__FILE__).'/admin.php');
            $ui = new CustomStatusCapsUI();
            $ui ->drawUI($args);
        }
    }

    function updateCapabilitiesOptions() {
        if (defined('PRESSPERMIT_ACTIVE')) {
            update_option('cme_custom_status_control', (int) !empty($_REQUEST['cme_custom_status_control']));
        }
    }

    function updateOptions() {
        $this->updateCapabilitiesOptions();

        update_option('cme_display_branding', (int) !empty($_REQUEST['cme_display_branding']));
    }

    function editorFeaturesUI() {
        require_once (dirname(__FILE__) . '/features/config/metaboxes-config.php');
        new EditorFeaturesMetaboxesConfig();

        require_once (dirname(__FILE__) . '/features/config/custom-config.php');
        EditorFeaturesCustomConfig::instance();

        ?>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {

                $('.editor-features-tab').click(function (e) {
                    e.preventDefault();

                    $('.editor-features-custom').hide();
                    var elem = $(this).attr('data-tab') + '-custom';
                    $(elem).show();
                });
            });
            /* ]]> */
        </script>
        <?php
    }

    /**
     * Capture metaboxes for post features
     *
     * @param array $post_types Post type.
     * @param array $elements All elements.
     * @param array $post_disabled All disabled post type element.
     *
     * @since 2.1.1
     */
    function initPostFeatureMetaboxes()
    {
        $screen = get_current_screen();

        if ($screen && !empty($screen->base) && ($screen->base == 'post')) {
            require_once (dirname(__FILE__) . '/features/config/metaboxes-config.php');
            $features_metaboxes = new EditorFeaturesMetaboxesConfig();
            $features_metaboxes->capturePostFeatureMetaboxes($screen->post_type);
        }
    }

    function initPostFeatureCustom() {
        require_once (dirname(__FILE__) . '/features/config/custom-config.php');
        EditorFeaturesCustomConfig::instance();
    }

    /**
     * Ajax callback to add restriction for a custom editor features item.
     *
     * @since 2.1.1
     */
    function ajaxFeaturesRestrictCustomItem()
    {
        require_once (dirname(__FILE__) . '/features/config/custom-config.php');
        EditorFeaturesCustomConfig::addByAjax();
    }

    /**
     * Ajax callback to delete custom-added editor features item restriction.
     *
     * @since 2.1.1
     */
    function ajaxFeaturesClearCustomItem()
    {
        require_once (dirname(__FILE__) . '/features/config/custom-config.php');
        EditorFeaturesCustomConfig::deleteByAjax();
    }
}
