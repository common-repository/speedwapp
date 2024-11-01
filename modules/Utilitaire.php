<?php

/**
 * Created by PhpStorm.
 * User: akambi
 * Date: 11/06/2016
 * Time: 16:48.
 */
class Widget
{
    /**
     * Get all wordpress available widgets.
     *
     * @return array
     */
    public function get_widgets()
    {
        global $wp_widget_factory;

        $widgets = array();
        foreach ($wp_widget_factory->widgets as $widgetClass => $widgetConfig) {
            $widgets[$widgetClass] = array(
                'class' => $widgetClass,
                'title' => !empty($widgetConfig->name) ? $widgetConfig->name : __('Untitled Widget', 'speedwapp'),
                'description' => !empty($widgetConfig->widget_options['description']) ? $widgetConfig->widget_options['description'] : '',
                'installed' => true,
                'groups' => array(),
            );
        }

        $widgets = apply_filters('speedwapp_get_widgets', $widgets);

        // Sort the widgets alphabetically
        uasort($widgets, array($this, 'widgets_sorter'));

        return $widgets;
    }

    /** Sort widgets
     * @param $a
     * @param $b
     *
     * @return int
     */
    private function widgets_sorter($a, $b)
    {
        if ($a['title'] == $b['title']) {
            return 0;
        }

        return ($a['title'] < $b['title']) ? -1 : 1;
    }

}
