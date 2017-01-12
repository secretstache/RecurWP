<?php

class GF_Simple_AddOn_Bootstrap {
    public static function load() {
        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }
        require_once( 'class-addon.php' );
        GFAddOn::register( 'RecurWPGFAddOn' );
    }
}
function gf_simple_addon() {
    return RecurWPGFAddOn::get_instance();
}
add_action( 'gform_loaded', array( 'GF_Simple_AddOn_Bootstrap', 'load' ), 5 );
