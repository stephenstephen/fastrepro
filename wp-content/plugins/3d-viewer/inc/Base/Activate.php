<?php 
namespace BP3D\Base;

class Activate{

    public function register(){
        register_activation_hook( __FILE__, [$this, 'bp3d_plugin_activate'] );
        add_action( 'admin_init', [$this, 'bp3d_plugin_redirect'] );
        add_filter( 'admin_footer_text', [$this, 'bp3d_admin_footer'] );
    }

    public function bp3d_plugin_activate(){
        add_option( 'bp3d_plugin_do_activation_redirect', true );
    }
    
    public function bp3d_plugin_redirect() {
        if ( get_option( 'bp3d_plugin_do_activation_redirect', false ) ) {
            delete_option( 'bp3d_plugin_do_activation_redirect' );
            //wp_redirect('edit.php?post_type=bp3d-model-viewer&page=bp3d-support');
        }
    
    }

    public function bp3d_admin_footer( $text ){
        if ( 'bp3d-model-viewer' == get_post_type() ) {
            $url = 'https://wordpress.org/plugins/3d-viewer/reviews/?filter=5#new-post';
            $text = sprintf( __( 'If you like <strong> 3D Viewer </strong> please leave us a <a href="%s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating. Your Review is very important to us as it helps us to grow more. ', 'model-viewer' ), $url );
        }
        
        return $text;
    }
}