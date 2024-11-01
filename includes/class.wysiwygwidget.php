<?php

    // @see http://wordpress.stackexchange.com/questions/82670/why-cant-wp-editor-be-used-in-a-custom-widget

    add_action( 'widgets_init', create_function( '', 'return register_widget("ABY_Wysiwig_Widget");' ) );

    class ABY_Wysiwig_Widget extends WP_Widget {
        public function __construct() {
            parent::__construct( 'aby_wysiwyg_widget', 'Wysiwyg &mdash; Simple Widgets', array( 'description' => 'Edit using the native Wordpress wywsiwyg.' ) ) ;
        }

        public function widget( $args, $instance ) {
        }

        public function form( $instance ) {
            ?>
                <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
                    <input 
                        class="widefat" 
                        id="<?php echo $this->get_field_id( 'title' ); ?>" 
                        name="<?php echo $this->get_field_name( 'title' ); ?>" 
                        type="text" 
                        value="<?php echo esc_attr( $instance[ 'title' ] ); ?>" 
                    /> 
                </p>
                <?php 
                    wp_editor( 
                        $instance[ 'content' ], 
                        $this->get_field_id( 'content' ), 
                        array(
                            'textarea_name' => $this->get_field_name( 'content' )
                        ) 
                    ); 
                ?>
            <?php 
        }

        public function update( $new_instance, $old_instance ) {
            $instance = array();
            
            $instance['title'] = ( ! empty( $new_instance['title'] ) ) 
                ? strip_tags( $new_instance['title'] ) 
                : '';
            
            $instance['content'] = ( ! empty( $new_instance['content'] ) ) 
                ? $new_instance['content'] 
                : '';
                
            return $instance;
        }
    }