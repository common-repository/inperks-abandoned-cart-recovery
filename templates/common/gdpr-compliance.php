<?php
/**
 * @var $preview bool
 * @var $settings array
 */
?>
<style>
    .acbwm-gdpr-compliance {
        padding: 10px;
        width: 100%;
        left: 0;
        bottom: 0;
        position: fixed;
        text-align: center;
    }

    <?php
        if(!empty($mappings)){
            foreach ($mappings as $key => $map ) {
                $selector =  $map[0];
                $property =  $map[1];
                $value = '';
                if ( isset($settings[ $key ] ) ) {
                    $value = $settings[ $key ];
                }
                if(!empty($value)){
                    if ( !empty( $value ) ) {
                        echo "$map[0] { $map[1]: $value; }\n";
                    }
                }
            }
        }
    ?>
</style>
<style id="acbwm_gdpr_compliance_custom_style">
    <?php
        echo $settings['custom_css'];
    ?>
</style>
<div class="acbwm-gdpr-compliance" style="<?php if ($settings['enable_compliance'] == "no") {
    echo "display:none;";
} ?>">
    <div class="acbwm-gdpr-compliance-message">
        <?php _e($settings['gdpr_message'], ACBWM_TEXT_DOMAIN) ?>
    </div>
</div>