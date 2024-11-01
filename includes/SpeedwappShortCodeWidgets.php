<?php


class Short_Code_Widgets

{
    /**
     * get the widget html content
     *
     * @since    0.9.0
     */
    public function widget($atts)
    {
        global $wp_widget_factory;

        extract(shortcode_atts(array(
            'widget_name' => FALSE,
            'instance' => FALSE,
        ), $atts));
        
        $widget_name = esc_html($widget_name);
        $instance = esc_html($instance);
      

        if (!is_a($wp_widget_factory->widgets[$widget_name], 'WP_Widget')):
            $wp_class = 'WP_Widget_' . ucwords(strtolower($class));

            if (!is_a($wp_widget_factory->widgets[$wp_class], 'WP_Widget')):
                return '<p>' . sprintf(__("%s: Widget class not found. Make sure this widget exists and the class name is correct"), '<strong>' . $class . '</strong>') . '</p>';
            else:
                $class = $wp_class;
            endif;
        endif;

        ob_start();
        the_widget($widget_name,$instance,
            array(
                'before_widget' => '',
                'after_widget' => '',
                'before_title' => '<h2>',
                'after_title' => '</h2>'
            ));
        $output = ob_get_contents();
        ob_end_clean();
        return $output;

    }

    /**
     * add the shortcode and run the function
     *
     * @since    0.9.0
     */

    public function add_shortcode_for_widgets()
    {
        add_shortcode('add_widget', array( 'Short_Code_Widgets', 'widget' ));
    }

}

?>