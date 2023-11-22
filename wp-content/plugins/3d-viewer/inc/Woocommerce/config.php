<?php 
$id = $id ?? get_the_ID();
$modeview_3d = false;
$settings_opt = false;
if($id){
    $modeview_3d  = get_post_meta( $id, '_bp3d_product_', true);
    $settings_opt = get_option('_bp3d_settings_');
}else {
    $id = uniqid();
}

// Model Source
$models = $modeview_3d['bp3d_models'] ?? [];
$carousel_enabled = $modeview_3d['carousel_enabled'] ?? true;
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
$angle_property     = $modeview_3d['angle_property'] ?? ['top' => 0, 'right' => 0, 'bottom' => '100'];

$x = $angle_property['top'];
$y = $angle_property['right'];
$z = $angle_property['bottom'];
$camera_orbit = '';

if($bp_model_angle === '1') {
    $camera_orbit = "camera-orbit='{$x}deg {$y}deg {$z}%'";
}

global $product;
$attribute = apply_filters('bp3d_woocommerce_model_attribute', [], $product->get_id(), true);
?>