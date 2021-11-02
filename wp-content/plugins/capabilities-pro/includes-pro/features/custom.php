<?php
namespace PublishPress\Capabilities;

class EditorFeaturesCustom {
    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new EditorFeaturesCustom();
        }

        return self::$instance;
    }

    function __construct() {
        // late registration to push Custom Items to bottom, just above entry form
        add_filter('pp_capabilities_post_feature_elements', [$this, 'fltCustomElements'], 50);
        add_filter('pp_capabilities_post_feature_elements_classic', [$this, 'fltCustomElementsClassic'], 50);
    }

    /**
     * Fetch our customs post feature gutenberg options.
     *
     * @return mixed
     *
     * @since 2.1.1
     */
    public static function getData()
    {
        $data = (array)get_option('ppc_feature_post_gutenberg_custom_data');
        $data = array_filter($data);

        return $data;
    }

    /**
     * Fetch our customs post feature classic options.
     *
     * @return mixed
     *
     * @since 2.1.1
     */
    public static function getClassicData()
    {
        $data = (array)get_option('ppc_feature_post_classic_custom_data');
        $data = array_filter($data);

        return $data;
    }

    function fltCustomElements($elements) {
        $data = self::getData();
        $added_element = [];

        if (count($data) > 0) {
            foreach ($data as $name => $restrict_data) {
                $delete_button        = '<span class="ppc-custom-features-delete" data-id="' . $name . '" data-parent="gutenberg"><small>(' . __('Delete',
                        'capsman-enhanced') . ')</small></span>';
                $added_element[$name] = [
                    'label'    => $restrict_data['label'] . ' <small>(' . $restrict_data['elements'] . ')</small> &nbsp; ' . $delete_button . '',
                    'elements' => $restrict_data['elements'],
                ];
            }
        }

        $elements[__('Custom Gutenberg Items', 'capsman-enhanced')] = $added_element;

        return $elements;
    }

    function fltCustomElementsClassic($elements) {
        $data = self::getClassicData();
        $added_element = [];

        if (count($data) > 0) {
            foreach ($data as $name => $restrict_data) {
                $delete_button        = '<span class="ppc-custom-features-delete" data-id="' . $name . '" data-parent="classic"><small>(' . __('Delete',
                        'capsman-enhanced') . ')</small></span>';
                $added_element[$name] = [
                    'label'    => $restrict_data['label'] . ' <small>(' . $restrict_data['elements'] . ')</small> &nbsp; ' . $delete_button . '',
                    'elements' => $restrict_data['elements'],
                ];
            }
        }

        $elements[__('Custom Classic Editor Items', 'capsman-enhanced')] = $added_element;

        return $elements;
    }
}
