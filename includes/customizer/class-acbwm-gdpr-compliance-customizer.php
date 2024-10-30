<?php
defined('ABSPATH') || exit;

/**
 * Acbwm_Gdpr_Compliance_Customizer class.
 */
class Acbwm_Gdpr_Compliance_Customizer
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('customize_register', array($this, 'add_sections'));
        add_action('wp_footer', array($this, 'include_gdpr_compliance'), 98);
        add_action('wp_footer', array($this, 'print_live_preview_scripts'), 99);
    }

    /**
     * preview for gdpr compliance customizer
     */
    public static function include_gdpr_compliance()
    {
        if (acbwm_is_customizer_preview()) {
            $settings = self::get_settings();
            $mappings = self::style_mappings();
            include_once ACBWM_ABSPATH . 'templates/common/gdpr-compliance.php';
        }
    }

    /**
     * Add settings to the customizer.
     *
     * @param WP_Customize_Manager $wp_customize Theme Customizer object.
     */
    public function add_sections($wp_customize)
    {
        $wp_customize->add_section(
            'acbwm_gdpr_compliance',
            array(
                'title' => __('GDPR Compliance ', ACBWM_TEXT_DOMAIN),
                'priority' => 10
            )
        );
        self::add_setting($wp_customize, 'enable_compliance');
        $wp_customize->add_control(
            'acbwm_gdpr_compliance[enable_compliance]',
            array(
                'label' => __('Enable GDPR Compliance?', ACBWM_TEXT_DOMAIN),
                'description' => __('If enabled, We would not track visitors until they accepted tracking.', ACBWM_TEXT_DOMAIN),
                'section' => 'acbwm_gdpr_compliance',
                'settings' => 'acbwm_gdpr_compliance[enable_compliance]',
                'type' => 'radio',
                'choices' => array(
                    'yes' => __('Yes', ACBWM_TEXT_DOMAIN),
                    'no' => __('No', ACBWM_TEXT_DOMAIN)
                )
            )
        );
        self::add_setting($wp_customize, 'gdpr_message');
        $wp_customize->add_control(
            'acbwm_gdpr_compliance[gdpr_message]',
            array(
                'label' => __('GDPR Message', ACBWM_TEXT_DOMAIN),
                'description' => __('Message to show for visitors and customers about tracking. use "acbwm_accept_tracking" as ID for opt out link. ', ACBWM_TEXT_DOMAIN),
                'section' => 'acbwm_gdpr_compliance',
                'settings' => 'acbwm_gdpr_compliance[gdpr_message]',
                'type' => 'textarea'
            )
        );
        self::add_setting($wp_customize, 'bg_color');
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'acbwm_gdpr_compliance[bg_color]', array(
            'label' => __('Background color', ACBWM_TEXT_DOMAIN),
            'section' => 'acbwm_gdpr_compliance',
            'settings' => 'acbwm_gdpr_compliance[bg_color]'
        )));
        self::add_setting($wp_customize, 'text_color');
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'acbwm_gdpr_compliance[text_color]', array(
            'label' => __('Text color', ACBWM_TEXT_DOMAIN),
            'section' => 'acbwm_gdpr_compliance',
            'settings' => 'acbwm_gdpr_compliance[text_color]'
        )));
        self::add_setting($wp_customize, 'custom_css');
        $wp_customize->add_control(
            'acbwm_gdpr_compliance[custom_css]',
            array(
                'label' => __('Custom css?', ACBWM_TEXT_DOMAIN),
                'description' => __('Custom css for GDPR compliance message', ACBWM_TEXT_DOMAIN),
                'section' => 'acbwm_gdpr_compliance',
                'settings' => 'acbwm_gdpr_compliance[custom_css]',
                'type' => 'textarea'
            )
        );
    }

    /**
     * add setting
     *
     * @param WP_Customize_Manager $wp_customize Theme Customizer object.
     * @param $key string
     */
    public static function add_setting($wp_customize, $key)
    {
        $wp_customize->add_setting(
            "acbwm_gdpr_compliance[{$key}]",
            array(
                'default' => self::get_default_value($key),
                'type' => 'option',
                'transport' => 'postMessage',
                'capability' => 'manage_woocommerce'
            )
        );
    }

    /**
     * get the default value of the settings
     * @param $key
     * @param string $default_val
     * @return mixed|string
     */
    public static function get_default_value($key, $default_val = "")
    {
        $default_values = self::get_default_values();
        if (isset($default_values[$key])) {
            return $default_values[$key];
        }
        return $default_val;
    }

    /**
     * get all the settings
     * @return array
     */
    public static function get_settings()
    {
        $default_params = self::get_default_values();
        $params = get_option('acbwm_gdpr_compliance', array());
        return wp_parse_args($params, $default_params);
    }

    /**
     * get the settings
     * @param $key
     * @param $default
     * @return mixed
     */
    public static function get_setting($key, $default)
    {
        $settings = self::get_settings();
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        return $default;
    }

    /**
     * All default values
     * @return array
     */
    public static function get_default_values()
    {
        return array(
            'enable_compliance' => 'no',
            'custom_css' => '',
            'text_color' => '#ffffff',
            'bg_color' => '#2aa189',
            'gdpr_message' => 'Please check our Privacy Policy to see how we use your personal data. <a href="#" id="acbwm_accept_tracking" class="acbwm_accept_tracking">Click here</a> to opt out of cart tracking.',
        );
    }

    /**
     * text mappings
     * @return array
     */
    public function text_mappings()
    {
        return array(
            'gdpr_message' => '.acbwm-gdpr-compliance .acbwm-gdpr-compliance-message',
            'custom_css' => '#acbwm_gdpr_compliance_custom_style'
        );
    }

    /**
     * style mappings
     * @return array
     */
    public static function style_mappings()
    {
        return array(
            'bg_color' => array('.acbwm-gdpr-compliance', 'background-color'),
            'text_color' => array('.acbwm-gdpr-compliance', 'color')
        );
    }

    /**
     * Live editing scripts
     */
    function print_live_preview_scripts()
    {
        if (!acbwm_is_customizer_preview()) {
            return;
        }
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                wp.customize('acbwm_gdpr_compliance[enable_compliance]', function (value) {
                    value.bind(function (to) {
                        if (to === "yes") {
                            $(".acbwm-gdpr-compliance").show();
                        } else {
                            $(".acbwm-gdpr-compliance").hide();
                        }
                    });
                });
                <?php
                $text_mappings = $this->text_mappings();
                foreach ($text_mappings as $key => $val) {
                    echo "
                        wp.customize('acbwm_gdpr_compliance[{$key}]', function (value) {
                            value.bind(function (to) {
                                $('{$val}').html(to);
                            });
                        });
                    ";
                }
                $style_mappings = $this->style_mappings();
                foreach ($style_mappings as $key => $val) {
                    $css_property = $val[1];
                    $css_selector = $val[0];
                    echo "
                        wp.customize('acbwm_gdpr_compliance[{$key}]', function (value) {
                            value.bind(function (to) {
                                $('{$css_selector}').css('{$css_property}', to);
                            });
                        });
                    ";
                }
                ?>
            });
        </script>
        <?php
    }
}

new Acbwm_Gdpr_Compliance_Customizer();