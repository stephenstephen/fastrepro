<?php

namespace BP3D\Woocommerce;

class ProductView{

    public function register(){
        add_action('woocommerce_loaded', [$this, 'woocommerce_loaded']);
        add_action('bp3d_product_model_before', [$this, 'model']);
        add_action('bp3d_product_model_after', [$this, 'model']);
        add_action('wp_footer', [$this, 'wp_footer']);
    }


    public function wp_footer(){
        global $product;
        if(!$product || !method_exists( $product, 'get_id')){
            return;
        }
        $modelData = get_post_meta( $product->get_id(), '_bp3d_product_', true );
        $viewer_position = isset($modelData['viewer_position']) ? $modelData['viewer_position'] : '';

        if($viewer_position === 'custom_selector'){
            $finalData = $this->getProductAttributes($modelData); ?>

            <div data-selector='<?php echo esc_attr($modelData['custom-selector'] ?? '') ?>' class="modelViewerBlock wooCustomSelector" data-attributes='<?php echo esc_attr(wp_json_encode($finalData)); ?>'></div>

            <?php
             wp_enqueue_script('bp3d-front-end');
             wp_enqueue_style('bp3d-custom-style');
             wp_enqueue_style('bp3d-public');
        }

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
        $viewer_position = isset($modeview_3d['viewer_position']) ? $modeview_3d['viewer_position'] : 'top';
        $models = $modeview_3d['bp3d_models'] ?? [];

        if((isset($modeview_3d['bp3d_models']) && !is_array($modeview_3d['bp3d_models'])) || $viewer_position === 'none' || $viewer_position === 'custom_selector' || sizeof($models) < 1){
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

    /**
     * Model
     */

    public function model(){
        global $product;
        $modelData = get_post_meta( $product->get_id(), '_bp3d_product_', true );
        $finalData = $this->getProductAttributes($modelData); 
    
        ?>
            
        <div class="modelViewerBlock wooCustomSelector" data-attributes='<?php echo esc_attr(wp_json_encode($finalData)); ?>'></div>

        <?php
            wp_enqueue_script('bp3d-front-end');
            wp_enqueue_style('bp3d-custom-style');
            wp_enqueue_style('bp3d-public');

        return;
        // Options Data
        require(__DIR__).'/config.php';

        ?>

        <!-- 3D Model html -->
        <?php if( sizeof($models) > 1 && $carousel_enabled){ 
            require_once(BP3D_TEMPLATE_PATH.'woocommerce_carousel.php');
        } else { ?>

            <div class="bp_grand">   
                <div class="bp_model_parent">
                    <model-viewer data-items="<?php echo esc_attr(wp_json_encode($models)) ?>" class="model" id="bp_model_id_<?php echo esc_attr($id); ?>" <?php echo esc_attr($model_autoplay); ?> ar shadow-intensity="<?php echo esc_attr($model_Shadow); ?>" src="<?php echo esc_url($models[0]['model_src'] ?? ''); ?>" alt="<?php echo esc_attr($alt); ?>" <?php echo esc_attr($camera_controls); ?> <?php echo $camera_orbit; ?> <?php echo esc_attr($zooming_3d); ?> loading="<?php  echo esc_attr($loading); ?>" <?php echo esc_attr($auto_rotate); ?> <?php echo esc_attr($rotation_speed); ?> <?php echo esc_attr($rotation_delay); ?>
                        <?php 
                        if(is_array($attribute)){
                            foreach($attribute as $key => $value){
                                echo "$key='$value'";
                            }
                        } ?>
                        >
                        <?php
                            if($settings_opt['bp_3d_progressbar'] !== '1') { ?>
                                <style> model-viewer<?php echo '#bp_model_id_'.esc_attr($id); ?>::part(default-progress-bar) {display:none;}</style>
                            <?php 
                            }
                        ?>
                    </model-viewer>

                    <?php foreach( $models as $model ){ ?>
                        
                <?php } ?>
                    <!-- Button -->
                    <?php if( $settings_opt['bp_3d_fullscreen'] == 1){ ?>
                        <?php require(BP3D_TEMPLATE_PATH.'fullscreen_buttons.php'); ?>
                    <?php } ?>
                </div>
            </div>  <!-- End of Simple Model -->
        <?php } ?> 

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

    public function getProductAttributes($modelData){
        $options = get_option('_bp3d_settings_');
        $models = [];
        foreach($modelData['bp3d_models'] as $index => $model){
            $models[] = [
                'modelUrl' => $model['model_src'],
                "useDecoder" => "none",
                'poster' => $model['poster_src'] ?? '',
                'product_variant' => $model['product_variant'] ?? ''
            ];
    
        }

        $finalData = [
            "align" => 'center',
            "uniqueId" => "model".get_the_ID(),
            "multiple" => true,
            "O3DVSettings" => ['currentViewer' => 'modelViewer'],
            "model" => [
                "modelUrl" => '',
                "poster" =>  ''
            ],
            "models" => $models,
            "lazyLoad" => $options['bp_3d_loading'] === 'lazy', // done
            "autoplay" => (boolean) $options['bp_3d_autoplay'], // done
            "shadow" =>  $options['3d_shadow_intensity'] != 0, //done
            "autoRotate" => $options['bp_3d_rotate'] === '1', // done
            "zoom" => $options['bp_3d_zooming'] === '1',
            "isPagination" => $this->isset($modelData, 'show_thumbs', 0) === '1',
            "isNavigation" => $this->isset($modelData, 'show_arrows', 0) === '1',
            "preload" => 'auto', //$options['bp_3d_preloader'] == '1' ? 'auto' : 'interaction',
            'rotationPerSecond' => $options['3d_rotate_speed'], // done
            "mouseControl" =>  $options['bp_camera_control'] == '1',
            "fullscreen" =>  $options['bp_3d_fullscreen'] == '1', // done
            "variant" => (boolean) false,
            "loadingPercentage" =>  false, //$options['bp_model_progress_percent'] == '1',
            "progressBar" =>  false, //$options['bp_3d_progressbar'] == '1',
            "rotate" =>  false, //$options['bp_model_angle'] === '1',
            "rotateAlongX" => 0, //$options['angle_property']['top'],
            "rotateAlongY" => 75, //$options['angle_property']['right'],
            "exposure" => 1, //$options['3d_exposure'],
            "styles" => [
                "width" => '100%', //$options['bp_3d_width']['width'].$options['bp_3d_width']['unit'],
                "height" => isset($options['bp_3d_height']) ? $options['bp_3d_height']['height'].$options['bp_3d_height']['unit'] : '350px',
                "bgColor" => $modelData['bp_model_bg'] ?? '', // done
                "progressBarColor" => '#666', //$options['bp_model_progressbar_color'] ?? ''
            ],
            "stylesheet" => null,
            "additional" => [
                "ID" => "",
                "Class" => "",
                "CSS" => '',//$options['css'] ?? '',
            ],
            "animation" => false,
            "woo" =>  true,
            "selectedAnimation" => ""
        ];

        return $finalData;
    }

    public function isset($array, $key, $default){
        if(isset($array[$key])){
            return $array[$key];
        }
        return $default;
    }
}
