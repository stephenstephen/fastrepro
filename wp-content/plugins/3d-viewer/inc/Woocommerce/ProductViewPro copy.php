<?php

namespace BP3D\Woocommerce;

class ProductViewPro{

    public function register(){
        add_action('woocommerce_loaded', [$this, 'woocommerce_loaded']);
        add_action('bp3d_product_model_before', [$this, 'model']);
        add_action('bp3d_product_model_after', [$this, 'model']);
    }

    public function woocommerce_loaded(){
        $settings = get_option( '_bp3d_settings_' );
        if(isset($settings['3d_woo_switcher']) && $settings['3d_woo_switcher'] !== '0'){
            remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
            add_action('woocommerce_before_single_product_summary',[$this, 'bp3d_product_models'], 20);
        }
    }

    public function bp3d_product_models(){
        if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
            return;
        }

        // Meta data of 3D Viewer
        $modeview_3d = get_post_meta( get_the_ID(), '_bp3d_product_', true );
        $viewer_position = isset($modeview_3d['viewer_position']) ? $modeview_3d['viewer_position'] : '';

        if(isset($modeview_3d['bp3d_models']) && !is_array($modeview_3d['bp3d_models'])){
            add_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 30);
            return;
        }

        global $product;
        wp_enqueue_style('bp3d-custom-style');
        wp_enqueue_script('bp3d-slick');
        wp_enqueue_script('bp3d-public');
        
        $columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
        $post_thumbnail_id = $product->get_image_id();
        $wrapper_classes   = apply_filters(
            'woocommerce_single_product_image_gallery_classes',
            array(
                'woocommerce-product-gallery',
                'woocommerce-product-gallery--' . ( $post_thumbnail_id ? 'with-images' : 'without-images' ),
                'woocommerce-product-gallery--columns-' . absint( $columns ),
                'images',
            )
        );
        
        ?>
        
        <div class="product-modal-wrap">
            <div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">
                <!-- Custom hook for 3d-viewer -->
                <?php  
                if($viewer_position === 'top') {
                    do_action( 'bp3d_product_model_before' ); ?>
                        <style>
                            .woocommerce div.product div.images .woocommerce-product-gallery__trigger {
                                position: absolute;
                                top: 385px;
                            }
                        </style>
                    <?php		
                }
        
                if($viewer_position === 'replace') {
                    add_filter( 'woocommerce_single_product_image_thumbnail_html',function($content){
                        return '';
                    }, 10, 2 );
                    do_action( 'bp3d_product_model_before' ); 	
                }
                ?>
        
                <figure class="woocommerce-product-gallery__wrapper">
                    <?php
        
                    if ( $post_thumbnail_id ) {
                        $html = wc_get_gallery_image_html( $post_thumbnail_id, true );
                    } else {
                        $html  = '<div class="woocommerce-product-gallery__image--placeholder">';
                        $html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
                        $html .= '</div>';
                    }
        
                    echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
                    do_action( 'woocommerce_product_thumbnails' );
                    ?>
                </figure>
            </div>
            <?php  
                if( $viewer_position === 'bottom') {
                    do_action( 'bp3d_product_model_after' ); 
                }
            ?>
        
        </div> <!-- End of Product modal wrap --> 
        <?php
    }

    public function model(){
        
    // Options Data
    $id = get_the_ID();
    $modeview_3d = false;
    $settings_opt = false;
    if($id){
        $modeview_3d  = get_post_meta( $id, '_bp3d_product_', true);
        $settings_opt = get_option('_bp3d_settings_');
    }else {
        $id = uniqid();
    }

    // Model Source
    $models = $modeview_3d['bp3d_models'] ? $modeview_3d['bp3d_models'] : [];
    $alt            = get_the_title();

    $camera_controls = $settings_opt['bp_camera_control'] !== '0' ? 'camera-controls' : '';
    $auto_rotate    = $settings_opt['bp_3d_rotate'] !== '0' ? 'auto-rotate' : '';
    $zooming_3d     = $settings_opt['bp_3d_zooming'] !== '0' ? '' : 'disable-zoom';

    // Preload
    $loading   = isset($settings_opt['bp_3d_loading']) ? $settings_opt['bpp_3d_loading'] : '';

    // AutoPlay and Shadow Intensity
    $model_autoplay = isset($settings_opt['bp_3d_autoplay']) && $settings_opt['bp_3d_autoplay'] !== '0' ? 'autoplay': '';
    $model_Shadow   = isset($settings_opt['3d_shadow_intensity']) ? $settings_opt['3d_shadow_intensity']: 0;

    // Auto Rotation
    if( $settings_opt['bp_3d_rotate'] === '1' && isset($settings_opt['3d_rotate_speed']) ) {
        $rotation_speed = 'rotation-per-second='.$settings_opt['3d_rotate_speed'].'deg';
    }else {
        $rotation_speed = null;
    }
    if( $settings_opt['bp_3d_rotate'] === '1' && isset($settings_opt['3d_rotate_delay']) ) {
        $rotation_delay = 'auto-rotate-delay='.$settings_opt['3d_rotate_delay'].'';
    }else {
        $rotation_delay = null;
    }
    // Angle
    $bp_model_angle     = isset($modeview_3d['bp_model_angle']) ? $modeview_3d['bp_model_angle']: '';
    $angle_property     = $modeview_3d['angle_property'];

    $x = $angle_property['top'];
    $y = $angle_property['right'];
    $z = $angle_property['bottom'];
    $camera_orbit = '';

    if($bp_model_angle === '1') {
        $camera_orbit = "camera-orbit='{$x}deg {$y}deg {$z}%'";
    }

    global $product;
    $attribute = apply_filters('bp3d_model_attribute', [], $product->get_id(), true);

?>

<!-- 3D Model html -->
<?php if( sizeof($models) > 1): ?>

    <div class="bp3dmodel-carousel" data-fullscreen='<?php echo esc_attr($settings_opt['bp_3d_fullscreen']); ?>'>
        <?php foreach( $models as $carousel_model ): ?>
        <div class="bp3dmodel-item">
        <div class="bp_model_gallery">
            <model-viewer class="model" id="bp_model_id_<?php echo esc_attr($id); ?>" <?php echo esc_attr($model_autoplay); ?> ar shadow-intensity="<?php echo esc_attr($model_Shadow); ?>" src="<?php echo esc_url($carousel_model['model_src']); ?>" alt="<?php echo esc_attr($alt); ?>" <?php echo esc_attr($camera_controls); ?> <?php echo $camera_orbit; ?> <?php echo esc_attr($zooming_3d); ?> loading="<?php  echo esc_attr($loading); ?>" <?php echo esc_attr($auto_rotate); ?> <?php echo esc_attr($rotation_speed); ?> <?php echo esc_attr($rotation_delay); ?> 
            <?php 
            if(is_array($attribute)){
                foreach($attribute as $key => $value){ 
                    echo "$key='$value'";
                }
            } ?>
            >
            <?php
                if($settings_opt['bp_3d_progressbar'] !== '1') { ?>
                    <style>
                        model-viewer<?php echo '#bp_model_id_'.esc_attr($id); ?>::part(default-progress-bar) {
                            display:none;
                        }
                    </style>
                <?php 
                } else {

                }
            ?>
            </model-viewer>

        </div>
        </div>
        <?php endforeach; ?>
    </div> <!-- End Of Carousel -->

<?php else: ?>

<div class="bp_grand">   
<div class="bp_model_parent">
<?php foreach( $models as $model ): ?>
<model-viewer class="model" id="bp_model_id_<?php echo esc_attr($id); ?>" <?php echo esc_attr($model_autoplay); ?> ar shadow-intensity="<?php echo esc_attr($model_Shadow); ?>" src="<?php echo esc_url($model['model_src']); ?>" alt="<?php echo esc_attr($alt); ?>" <?php echo esc_attr($camera_controls); ?> <?php echo $camera_orbit; ?> <?php echo esc_attr($zooming_3d); ?> loading="<?php  echo esc_attr($loading); ?>" <?php echo esc_attr($auto_rotate); ?> <?php echo esc_attr($rotation_speed); ?> <?php echo esc_attr($rotation_delay); ?>
<?php 
if(is_array($attribute)){
    foreach($attribute as $key => $value){ 
        echo "$key='$value'";
    }
} ?>
>
<?php
    if($settings_opt['bp_3d_progressbar'] !== '1') { ?>
        <style>
             model-viewer<?php echo '#bp_model_id_'.esc_attr($id); ?>::part(default-progress-bar) {
                display:none;
            }
        </style>
    <?php 
    } else { 

    }
?>
</model-viewer>
<?php endforeach; ?>
    <?php if( $settings_opt['bp_3d_fullscreen'] == 1): ?>
    <!-- Button -->
    <!-- <svg id="openBtn" width="24px" height="24px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="#f2f2f2" class="bi bi-arrows-fullscreen">
        <path fill-rule="evenodd" d="M5.828 10.172a.5.5 0 0 0-.707 0l-4.096 4.096V11.5a.5.5 0 0 0-1 0v3.975a.5.5 0 0 0 .5.5H4.5a.5.5 0 0 0 0-1H1.732l4.096-4.096a.5.5 0 0 0 0-.707zm4.344 0a.5.5 0 0 1 .707 0l4.096 4.096V11.5a.5.5 0 1 1 1 0v3.975a.5.5 0 0 1-.5.5H11.5a.5.5 0 0 1 0-1h2.768l-4.096-4.096a.5.5 0 0 1 0-.707zm0-4.344a.5.5 0 0 0 .707 0l4.096-4.096V4.5a.5.5 0 1 0 1 0V.525a.5.5 0 0 0-.5-.5H11.5a.5.5 0 0 0 0 1h2.768l-4.096 4.096a.5.5 0 0 0 0 .707zm-4.344 0a.5.5 0 0 1-.707 0L1.025 1.732V4.5a.5.5 0 0 1-1 0V.525a.5.5 0 0 1 .5-.5H4.5a.5.5 0 0 1 0 1H1.732l4.096 4.096a.5.5 0 0 1 0 .707z"/>
    </svg>

    <svg id="closeBtn" width="34px" height="34px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path fill="none" stroke="#f2f2f2" stroke-width="2" d="M7,7 L17,17 M7,17 L17,7"/>
    </svg> -->
    <?php require_once(BP3D_TEMPLATE_PATH.'fullscreen-buttons.php'); ?>
    <!-- ./Button -->
    <?php endif; ?>
</div>
</div>  <!-- End of Simple Model -->
<?php endif; ?> 
    <!-- Model Viewer Style -->
    <style>
    <?php echo '#bp_model_id_'.esc_attr($id); ?> {
        width: 100%;
        min-height: 340px;
        background-color: <?php echo esc_attr($modeview_3d['bp_model_bg']); ?>;
    }
    .fullscreen <?php echo "#bp_model_id_".esc_attr($id); ?>{
      height: 100%;
    }
    model-viewer.model {
        --poster-color: transparent;
    }
    </style>
    <?php  
    }
}