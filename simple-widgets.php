<?php

    /**
    * Plugin Name: Simple Widgets
    * Version: 1.1.0
    * Description: Provides extra widget management. Hide the widget title, add extra css classes, conditional widget visibility. 
    * Author: ApocalypseBoy
    * Author URI: http://apocalypseboy.com/
    * Plugin URI: http://apocalypseboy.com/simple-slides/
    * License: GPLv2 or later
    */

    /*
        This program is free software; you can redistribute it and/or
        modify it under the terms of the GNU General Public License
        as published by the Free Software Foundation; either version 2
        of the License, or (at your option) any later version.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
    */    
    
    define( 'ABY_SW_BASENAME', plugin_basename( __FILE__ ) );
    define( 'ABY_SW_FOLDER', pathinfo( ABY_SW_BASENAME, PATHINFO_DIRNAME ) ); 
    define( 'ABY_SW_DIR', WP_PLUGIN_DIR . '/' . ABY_SW_FOLDER );
    define( 'ABY_SW_URL', WP_PLUGIN_URL . '/' . ABY_SW_FOLDER );  
    
    class ABY_SimpleWidgets {
        public function __construct() {
            $this->init();
        }
        
        public function init() {
            $settings = (array) get_option( 'aby_sw_settings' );
            
            require_once( ABY_SW_DIR . '/includes/class.menumanager.php' );
//            require_once( ABY_SW_DIR . '/includes/class.wysiwygwidget.php' );
            
            add_action( 'wp_ajax_sw_save_settings', array( $this, 'cb_sw_save_settings' ) );
            add_filter( 'dynamic_sidebar_params', array( $this, 'cb_dynamic_sidebar_params' ), 10, 1 );
            add_filter( 'widget_display_callback', array( $this, 'cb_widget_display_callback' ), 1, 3 );
            
            if ( $settings['manage_link_show'] ) {
                add_action( 'sidebar_admin_setup', array( $this, 'cb_sidebar_admin_setup' ) );    
            }
        }  
        
        public function cb_widget_display_callback( $instance, $widget, $args ) {
            global $template;
            
            $simple_widgets_data = get_option( 'simple_widgets_data' );
            $simple_widgets_data = array_map( 'stripslashes_deep', (array) $simple_widgets_data );
            
            $simple_widgets_data = $simple_widgets_data ? $simple_widgets_data : array();
            $widget_settings = @$simple_widgets_data[$widget->id];              
                        
            if ( '1' != @$widget_settings['is_conditional'] )
                return $instance;
                
            $template_basename = pathinfo( $template, PATHINFO_BASENAME );
            $template_basename = str_replace( '.php', '', $template_basename );
                
            if ( '1' == @$widget_settings['show_template_' . $template_basename] )
                return $instance;
                
            global $post;     
            
            if ( is_page() && '1' == @$widget_settings['show_page_' . $post->ID] )
                return $instance;
                
            if ( '1' == @$widget_settings['use_eval_code'] )
                if ( $this->cb_eval( $widget_settings['eval_code'] ? $widget_settings['eval_code'] : '1' ) )
                    return $instance;
            
            return false;
        } 
        
        public function cb_eval( $code ) {    
            $value = false;
            @eval( '$value = ' . $code . ';' );
            return $value;
        }
        
        public function cb_dynamic_sidebar_params( $params ) { 
            $simple_widgets_data = get_option( 'simple_widgets_data' );
            $simple_widgets_data = array_map( 'stripslashes_deep', (array) $simple_widgets_data );
            $simple_widgets_data = $simple_widgets_data ? $simple_widgets_data : array();
            $widget_settings = @$simple_widgets_data[$params[0]['widget_id']];  
            
            /**
            * Hide widget title.   
            */
            
            remove_filter( 'widget_title', array( $this, 'cb_widget_title' ) );
            if ( '1' == @$widget_settings['hide_title'] )
                add_filter( 'widget_title', array( $this, 'cb_widget_title' ) );
            
            /**
            * Add extra classes.
            */
            
            $widget = $params[0]['widget_id'];
            $widget_type = trim( substr( $widget, 0, strrpos( $widget, '-' ) ) );
            $widget_type_index = trim( substr( $widget, strrpos( $widget, '-' ) + 1 ) );
            $widget_type_data = get_option(  'widget_' . $widget_type );
            $widget_type_data = array_map( 'stripslashes_deep', (array) $widget_type_data );
            $widget_data = $widget_type_data[$widget_type_index];
            $title_class = preg_replace( '/[^\da-z ]/i', '', $widget_data['title'] );
            $title_class = str_replace( ' ', '-', $title_class );
            $title_class = strtolower( $title_class );
            
            $extra_classes = array();
            
            if ( $widget_settings['extra_css_classes'] )
                $extra_classes[] = $widget_settings['extra_css_classes'];
            
            if ( $title_class )    
                $extra_classes[] = $title_class;
            
            $params[0]['before_widget'] = preg_replace( 
                '/class="/', 
                sprintf( 'class="%s ', implode( ' ', $extra_classes ) ), $params[0]['before_widget'], 
                1 
            );
            
            return $params;
        }
        
        public function cb_widget_title( $title ) {
            return '';
        }
        
        public function cb_sw_save_settings() {
            $data = $_POST['data'];
            update_option( 'simple_widgets_data', $data );
            die( json_encode( array( 'res' => 1, 'redirect' => admin_url( 'admin.php?page=sw_main&updated=1' ) ) ) );
        }
        
        public function cb_load_sw_main() {
            wp_enqueue_style( 'sw.main', ABY_SW_URL . '/styles/main.css' );
            wp_enqueue_script( 'sw.jquery.easing', ABY_SW_URL . '/scripts/jquery.easing.js', array( 'jquery' ) );
            wp_enqueue_script( 'sw.main', ABY_SW_URL . '/scripts/main.js', array( 'jquery', 'sw.jquery.easing' ) );
        }
        
        public function page_main() {
            global $wp_registered_sidebars, $sidebars_widgets;
            $simple_widgets_data = get_option( 'simple_widgets_data' );
            $simple_widgets_data = array_map( 'stripslashes_deep', (array) $simple_widgets_data );
            $simple_widgets_data = $simple_widgets_data ? $simple_widgets_data : array();
            $widgets = wp_get_sidebars_widgets();
            
            $templates = array( 
                'index' => 'Index Template (index.php)',
                'front-page' => 'Front Page Template (front-page.php)',
                'home' => 'Home Template (home.php)',
                'archive' => 'Archive Template (archive.php)',
                'search' => 'Search Template (search.php)',
                'single' => 'Single Template (single.php)',
                'page' => 'Page Template (page.php)',
                '404' => '404 Template (404.php)',
                'taxonomy' => 'Taxonomy Template (taxonomy.php)',
                'category' => 'Category Template (category.php)',
                'tag' => 'Tag Template (tag.php)',
                'author' => 'Author Template (author.php)',
                'data' => 'Date Template (date.php)',
                'paged' => 'Paged Template (paged.php)',
                'attachment' => 'Attachment Template (attachment.php)',
                'single-post' => 'Single Post Template (single-post.php)',
            );
            
            $pages = array();
            
            $query_pages = new WP_Query( array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'nopaging' => true,          
            ) );
            
            foreach ( $query_pages->posts as $page ) 
                $pages[$page->ID] = $page->post_title;
            
            ?>
                <div class="wrap wrap-simple-widgets">
                    <div class="icon32" id="icon-options-general"><br /></div>
                    <h2>Simple Widgets</h2>
                    <form class="sw_form" method="post"><div class="sw_controls">
                <?php
                
                foreach ( $wp_registered_sidebars as $registered_sidebar ) {
                    $sidebar_widgets_specific = $sidebars_widgets[$registered_sidebar['id']];
                    
                    ?>
                        <div class="sidebar-entry">
                            <div class="sidebar-data">
                                <?php echo $registered_sidebar['name']; ?> (<?php echo count( $sidebar_widgets_specific ); ?>)
                            </div>
                            <div class="sidebar-widgets">
                    <?php
                    
                    foreach ( $sidebar_widgets_specific as $widget ) {
                        $widget_type = trim( substr( $widget, 0, strrpos( $widget, '-' ) ) );
                        $widget_type_index = trim( substr( $widget, strrpos( $widget, '-' ) + 1 ) );
                        
                        $widget_type_data = get_option(  'widget_' . $widget_type );
                        $widget_type_data = array_map( 'stripslashes_deep', (array) $widget_type_data );
                        
                        $widget_data = $widget_type_data[$widget_type_index];
                        ?>
                            <div class="widget-entry">
                                <div class="widget-data" id="<?php echo $widget; ?>_aby_sw">
                                    <?php echo sprintf( '<strong>[%s]</strong> %s', $widget_type, $widget_data['title'] ? $widget_data['title'] : '<em>No Title</em>' ); ?>        
                                </div>
                                <div class="widget-settings">
                                    <table class="form-table">
                                        <tbody>
                                            <tr valign="top">
                                                <th scope="row">
                                                    <label>Hide Title</label>
                                                    <p class="description">Optionally hide the widget's title.</p>
                                                </th>                     
                                                <td>
                                                    <label>
                                                        <input type="checkbox" name="simple_widgets_data[<?php echo $widget; ?>][hide_title]" value="1" <?php __checked_selected_helper( '1', @$simple_widgets_data[$widget]['hide_title'], true, 'checked' ); ?>  />
                                                        Force hide the widget's title.
                                                    </label>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row">
                                                    <label>Extra CSS Classes</label>
                                                    <p class="description">Add extra css classes to the widget's code for added styling handles.</p>
                                                </th>                     
                                                <td>
                                                    <input class="regular-text" type="text" name="simple_widgets_data[<?php echo $widget; ?>][extra_css_classes]" value="<?php echo @$simple_widgets_data[$widget]['extra_css_classes']; ?>"  />
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row">
                                                    <label>Conditional Visibility</label>
                                                    <p class="description">Conditionally hide the widget from certain parts of your site.</p>
                                                </th>                     
                                                <td>
                                                    <label>
                                                        <input type="checkbox" name="simple_widgets_data[<?php echo $widget; ?>][is_conditional]" value="1" <?php __checked_selected_helper( '1', @$simple_widgets_data[$widget]['is_conditional'], true, 'checked' ); ?>  />
                                                        Enable conditional visibility.
                                                    </label>
                                                    <h3>Show in Templates</h3>
                                                    <p><a href="http://codex.wordpress.org/Template_Hierarchy">Hint: Learn about Templates and Template Heirarchy.</a></p>
                                                    <ul>
                                                        <?php foreach ( $templates as $template_key => $template_name ) : ?>
                                                            <li>
                                                                <label>
                                                                    <input type="checkbox" name="simple_widgets_data[<?php echo $widget; ?>][show_template_<?php echo $template_key; ?>]" value="1" <?php __checked_selected_helper( '1', @$simple_widgets_data[$widget]['show_template_' . $template_key], true, 'checked' ); ?>  />
                                                                    <?php echo $template_name; ?>
                                                                </label>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                    <h3>Show in Pages</h3>
                                                    <ul>
                                                        <?php foreach ( $pages as $page_id => $page_title ) : ?>
                                                            <li>
                                                                <label>
                                                                    <input type="checkbox" name="simple_widgets_data[<?php echo $widget; ?>][show_page_<?php echo $page_id; ?>]" value="1" <?php __checked_selected_helper( '1', @$simple_widgets_data[$widget]['show_page_' . $page_id], true, 'checked' ); ?>  />
                                                                    <?php echo $page_title ? $page_title : '<em>No Title</em>'; ?>
                                                                </label> 
                                                                <a target="_blank" href="<?php echo site_url( '?p=' . $page_id ); ?>">View Page</a>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul> 
                                                    <h3>Use PHP Code</h3>   
                                                    <p><a href="http://codex.wordpress.org/Conditional_Tags">Hint: Learn about Conditional Tags.</a></p>         
                                                    <label>
                                                        <input type="checkbox" name="simple_widgets_data[<?php echo $widget; ?>][use_eval_code]" value="1" <?php __checked_selected_helper( '1', @$simple_widgets_data[$widget]['use_eval_code'], true, 'checked' ); ?>  />
                                                        Enable php code condition.
                                                    </label><br />
                                                    <textarea class="code regular-text" rows="3" name="simple_widgets_data[<?php echo $widget; ?>][eval_code]"><?php echo @$simple_widgets_data[$widget]['eval_code']; ?></textarea>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php           
                    }
                    ?>
                        </div><!-- .sidebar-widgets -->
                        </div>
                    <?php             
                }      
                ?>
                    </div>
                    <div class="sw_buttons">
                        <input class="sw_submit button button-primary" type="button" value="Save Settings" />
                    </div>
                    </form>
                    
                    <div class="sw_contact">
                        <h3>Support</h3>
                        <p>
                            <a target="_blank" href="http://apocalypseboy.com/wp-plugins/simple-widgets?ref=wp_sw">To report bugs, request a feature, or get support contact the author</a>. 
                        </p>
                        <p>
                            Any feedback is welcome as it will help in developing this plugin. Thanks!
                        </p>                        
                    </div>
                </div>
            <?php
        }
        
        /**
        * Manage Settings
        */
        
        public function cb_load_sw_settings() {
            $settings_default = array(
                'manage_link_show' => '1',
                'manage_link_newwindow' => '1'
            );
            
            $settings = array_merge(
                $settings_default,
                (array) @$_POST['aby_sw_settings']
            );
            
            update_option( 'aby_sw_settings', $settings );
        }
        
        public function page_settings() {
            $settings = (array) get_option( 'aby_sw_settings' );
            ?>
                <div class="wrap wrap-simple-widgets">
                    <div class="icon32" id="icon-options-general"></div>
                    <h2>Settings &mdash; Simple Widgets</h2>
                    <form method="post">
                        <table class="form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row">
                                        <label>Manage Link</label>
                                    </th>                     
                                    <td>
                                        <input type="hidden" value="0" name="aby_sw_settings[manage_link_show]" />
                                        <label>
                                            <input type="checkbox" name="aby_sw_settings[manage_link_show]" value="1" <?php __checked_selected_helper( '1', $settings['manage_link_show'] , true, 'checked' ); ?>  />
                                            Add a manage link at the bottom of the widget editor?
                                        </label>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <label>Manage Link Window</label>
                                    </th>                     
                                    <td>
                                        <input type="hidden" value="0" name="aby_sw_settings[manage_link_newwindow]" />
                                        <label>
                                            <input type="checkbox" name="aby_sw_settings[manage_link_newwindow]" value="1" <?php __checked_selected_helper( '1', $settings['manage_link_newwindow'] , true, 'checked' ); ?>  />
                                            Open the manage link in a new window?
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button button-primary" type="submit" value="Save Settings" />
                        </p>
                    </form>
                </div>
            <?php
        }
        
        public function cb_sidebar_admin_setup() {
            global $wp_registered_widgets, $wp_registered_widget_controls;
            
            foreach ( $wp_registered_widgets as $id => $wp_registered_widget ) {
                $wp_registered_widget_controls[$id]['callback_orig']=$wp_registered_widget_controls[$id]['callback'];
                $wp_registered_widget_controls[$id]['callback'] = array( $this, 'cb_wp_registered_widget_control' );
                array_push( $wp_registered_widget_controls[$id]['params'], $id );    
            }
        }
        
        public function cb_wp_registered_widget_control() {
            global $wp_registered_widget_controls;
            
            $settings = (array) get_option( 'aby_sw_settings' );
            
            $params = func_get_args();
            $id = array_pop( $params );
            
            $callback = $wp_registered_widget_controls[$id]['callback_orig'];
            
            if ( is_callable( $callback ) ) {
                call_user_func_array( $callback, $params );        
            }
            
            echo sprintf( 
                '<p class="%s" style="text-align: center;" title="Manage Widget &mdash; Simple Widgets"><a href="%s" target="%s">%s</a></p>',
                'aby_sw_manage_link',
                admin_url( 'admin.php?page=sw_main#' . $id ),
                '1' == $settings['manage_link_newwindow'] ? '_blank' : '_self',
                'Manage Widget'
            );
        }
    }
    
    $ABY_SimpleWidgets = new ABY_SimpleWidgets();