<?php   

    /**
    * Class is under development.
    */
    
    class aby_MenuManager {
        public function __construct() {
            $this->init();
        }
        
        public function init() {
            add_action( 'admin_menu', array( $this, 'callback' ) );
        }
        
        public function callback() {
        }
    }

    class aby_sw_MenuManager extends aby_MenuManager {
        public function callback() {
            global $ABY_SimpleWidgets;
            
            $hook = add_menu_page( 'Simple Widgets', 'Simple Widgets', 'manage_options', 'sw_main', array( $ABY_SimpleWidgets, 'page_main' ) );
            add_action( 'load-' . $hook, array( $ABY_SimpleWidgets, 'cb_load_sw_main' ) );
            
            $hook = add_submenu_page( 'sw_main', 'Manage', 'Manage', 'manage_options', 'sw_main', array( $ABY_SimpleWidgets, 'page_main' ) );
            add_action( 'load-' . $hook, array( $ABY_SimpleWidgets, 'cb_load_sw_main' ) );
            
            $hook = add_submenu_page( 'sw_main', 'Settings', 'Settings', 'manage_options', 'sw_settings', array( $ABY_SimpleWidgets, 'page_settings' ) );
            add_action( 'load-' . $hook, array( $ABY_SimpleWidgets, 'cb_load_sw_settings' ) );
        }
    }
    
    $aby_sw_MenuManager = new aby_sw_MenuManager();