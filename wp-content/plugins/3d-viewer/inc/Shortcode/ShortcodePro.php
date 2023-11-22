<?php
namespace BP3D\Shortcode;

use BP3D\Helper\Utils;
use BP3D\Helper\Block;
use BP3D\Template\ModelViewer;

class ShortcodePro{

    public function register(){
        add_shortcode('3d_viewer', [$this, 'bp3dviewer_cpt_content_func']);
        add_shortcode('3d_viewer_product', [$this, 'product_model_viewer']);
    }

    //Lets register our shortcode
    function bp3dviewer_cpt_content_func( $atts ){
        extract( shortcode_atts( array(
            'id' => '',
            'src' => '',
            'alt' => '',
            'width' => '100%',
            'height' => 'auto',
            'auto_rotate' => 'auto-rotate',
            'camera_controls' => 'camera-controls',
            'zooming_3d' => '',
            'loading' => '',
            'poster' => ''
        ), $atts ));
        
        if(!$id){
            return false;
        }

        
        $post_type = get_post_type($id);
        $isGutenberg = get_post_meta($id, 'isGutenberg', true);
        
        if(!in_array($post_type, ['bp3d-model-viewer', 'product'])){
            return false;
        }
        
        if($isGutenberg){
            $blocks =  Block::getBlock($id);
            return render_block($blocks);
        }
        ob_start();

        $data = wp_parse_args(  get_post_meta( $id, '_bp3dimages_', true ), $this->get3DViewerDefaultData());

        
        if($data['bp_3d_src_type'] == 'upload'){
            $modelSrc = $data['bp_3d_src']['url'];
        }else {
            $modelSrc = $data['bp_3d_src_link'];
        }

        
        $models = [];

        if(isset($data['bp_3d_models']) && is_array($data['bp_3d_models'])){
            foreach($data['bp_3d_models'] as $index => $model){
                $models[] = [
                    'modelUrl' => $model['model_link'],
                    "useDecoder" => "none",
                ];
    
                if(isset($data['bp_3d_posters'][$index]['poster_img'])){
                    $models[$index]['poster'] = $data['bp_3d_posters'][$index]['poster_img'];
                }
            }
        }

        $poster = $data['bp_3d_poster']['url'] ?? '';

        $finalData = [
            "align" => $data['bp_3d_align'],
            "uniqueId" => "model$id",
            "currentViewer" => $data['currentViewer'],
            "multiple" => $data['bp_3d_model_type'] !== 'msimple',
            "model" => [
                "modelUrl" => $modelSrc,
                "poster" => $poster
            ],
            "O3DVSettings" =>  [
				"isFullscreen" =>  $data['bp_3d_fullscreen'] == '1',
				"isPagination" => $this->isset($data, 'show_thumbs', 0) === '1',
				"isNavigation" =>  $this->isset($data, 'show_arrows', "1") === '1',
				"camera" =>  null,
				"mouseControl" =>  $data['bp_camera_control'] == '1',
			],
            "models" => $models,
            "lazyLoad" =>  $data['bp_3d_loading'] === 'lazy', // maybe not needed
            "loading" =>  $data['bp_3d_loading'],
            "autoplay" => (boolean) $data['bp_3d_autoplay'],
            "shadow" =>  $data['3d_shadow_intensity'] != 0,
            "autoRotate" => $data['bp_3d_rotate'] === '1',
            "zoom" => $data['bp_3d_zooming'] === '1',
            "isPagination" => $this->isset($data, 'show_thumbs', 0) === '1',
            "isNavigation" => $this->isset($data, 'show_arrows', "1") === '1',
            "preload" => 'auto', //$data['bp_3d_preloader'] == '1' ? 'auto' : $poster ? 'interaction' : 'auto',
            'rotationPerSecond' => $data['3d_rotate_speed'],
            "mouseControl" =>  $data['bp_camera_control'] == '1',
            "fullscreen" =>  $data['bp_3d_fullscreen'] == '1',
            "variant" => (boolean) false,
            "loadingPercentage" =>  $data['bp_model_progress_percent'] == '1',
            "progressBar" =>  $data['bp_3d_progressbar'] == '1',
            "rotate" =>  $data['bp_model_angle'] === '1',
            "rotateAlongX" => $data['angle_property']['top'],
            "rotateAlongY" => $data['angle_property']['right'],
            "exposure" => $data['3d_exposure'],
            "styles" => [
                "width" => $data['bp_3d_width']['width'].$data['bp_3d_width']['unit'],
                "height" =>  $data['bp_3d_height']['height'].$data['bp_3d_height']['unit'],
                "bgColor" => $data['bp_model_bg'],
                "progressBarColor" => $data['bp_model_progressbar_color'] ?? ''
            ],
            "stylesheet" => null,
            "additional" => [
                "ID" => "",
                "Class" => "",
                "CSS" => $data['css'] ?? '',
            ],
            "animation" => (boolean) false,
            "woo" => (boolean) false,
            "selectedAnimation" => ""
        ];

        ?>

        <div class="modelViewerBlock" data-attributes='<?php echo esc_attr(wp_json_encode($finalData)) ?>'></div>

        <?php

        wp_enqueue_script('bp3d-front-end');
        wp_enqueue_style('bp3d-custom-style');
        wp_enqueue_style('bp3d-public');
        
        return ob_get_clean();
    }

    /**
     * shortcode for product model viewer
     */
    public function product_model_viewer($attrs){
        extract( shortcode_atts( array(
            'id' => null,
            'width' => '100%'
        ), $attrs ));

        $post_type = get_post_type($id);
        if(!in_array($post_type, ['product'])){
            return false;
        }
        ob_start(); 
       
        require(__DIR__).'./../Woocommerce/config.php';
        
        if( !is_array($models) || sizeof($models) < 1){
            return ob_get_clean();
        }

        wp_enqueue_style('bp3d-custom-style');
        wp_enqueue_script('bp3d-slick');
        wp_enqueue_script('bp3d-public');

        ?>
        
        <!-- 3D Model html -->
        <?php if( sizeof($models) > 1 && $carousel_enabled){ 
            require(BP3D_TEMPLATE_PATH.'woocommerce_carousel.php');
        } else { 
            ?>
            
        <div class="bp_grand" style="width:<?php echo esc_attr($width) ?>">   
            <div class="bp_model_parent b3dviewer-wrapper">
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
                background-color: <?php echo esc_attr($modeview_3d['bp_model_bg'] ?? '#fff'); ?>;
            }
            .fullscreen <?php echo "#bp_model_id_".esc_attr($id); ?>{
            height: 100%;
            }
            model-viewer.model {
                --poster-color: transparent;
            }
            </style>
            <?php  

                return ob_get_clean(); 
    }

    public function get3DViewerDefaultData() {
        return  array(
            'bp_3d_model_type' => 'msimple', // done
            'bp_3d_src_type' => 'upload',  // done
            'currentViewer' => 'modelViewer',
            'bp_3d_src' => array(
                'url' => 'http://localhost/freemius/wp-content/uploads/2022/04/PEP-3D-Model_2.glb',
                'title' => ''
            ), // done
            "O3DVSettings" =>  [
				"isFullscreen" =>  true,
				"isPagination" =>  false,
				"isNavigation" =>  false,
				"camera" =>  null,
				"mouseControl" =>  true
			],
            'bp_3d_src_link' => 'i-do-not-exist.glb', // done
            'bp_3d_models' => array(
                array(
                    'model_link' => 'http://localhost/freemius/wp-content/uploads/2022/08/RobotExpressive.glb',
                ),
            ), // done
            'bp_model_anim_du' => 5000,
            'bp_3d_poster_type' => 'simple', // done
            'bp_3d_poster' => array('url' => ''), // done
            'bp_3d_posters' => '', // done
            'bp_3d_autoplay' => '', // done
            'bp_3d_preloader' => '',
            'bp_camera_control' => 1, // done
            'bp_3d_zooming' => 1,
            'bp_3d_loading' => 'auto', // done
            'bp_3d_rotate' => 1, // done
            '3d_rotate_speed' => 30, // done
            '3d_rotate_delay' => 3000,
            'bp_model_angle' => '', // done
            'angle_property' => array(
                'top' => 0,
                'right' => 75,
                'bottom' => 105,
            ), // done
            'bp_3d_fullscreen' => 1, // done
            'bp_3d_progressbar' => 1, // done
            'bp_model_progress_percent' => 1, // done
            '3d_shadow_intensity' => 1, // done
            '3d_exposure' => 1, // done
            'bp_3d_width' => array(
                'width' => 100,
                'unit' => '%',
            ), // done
            'bp_3d_height' => array(
                'height' => 500,
                'unit' => 'px',
            ), // done
            'bp_3d_align' => 'center', // done
            'bp_model_bg' => '#8224e3', // done
            'bp_model_progressbar_color' => '', // done
            'bp_model_icon_color' => '', // no need
            'css' => '',
        );
    }

    public function isset($array, $key, $default){
        if(isset($array[$key])){
            return $array[$key];
        }
        return $default;
    }
}
