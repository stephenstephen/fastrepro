<?php
    /** @var \SW_WAPF_PRO\Includes\Models\FieldGroup $field_group */
    /** @var array $cart_item_fields */
    /** @var WC_Product $product */
    use \SW_WAPF_PRO\Includes\Classes\Html;
    use \SW_WAPF_PRO\Includes\Classes\Helper;
    $label_position = isset($field_group->layout['labels_position']) ? $field_group->layout['labels_position'] : 'above';
    $instructions_position = isset($field_group->layout['instructions_position']) ? $field_group->layout['instructions_position'] : 'field';

    $mark_required = isset($field_group->layout['mark_required']) && $field_group->layout['mark_required'];
    $open_sections = 0;
    $section_has_buttons = false;
    $section_field_for_buttons = null;
    $clones_in_section = [];
?>

<div
    class="wapf-field-group label-<?php echo $label_position ?>"
    data-group="<?php echo $field_group->id; ?>"
    data-variables="<?php echo Helper::thing_to_html_attribute_string($field_group->variables); ?>"
    <?php if($field_group->has_gallery_image_rules()) { ?>
        data-wapf-st="<?php echo isset($field_group->layout['swap_type']) ? esc_attr($field_group->layout['swap_type']) : 'rules';?>"
        data-wapf-gi="<?php echo Helper::thing_to_html_attribute_string($field_group->get_gallery_image_rules()); ?>"
    <?php } ?>
>
    <?php

    foreach( $field_group->fields as $field ) {

        $cart_item_field = isset( $cart_item_fields[$field->id] ) ? $cart_item_fields[$field->id] : [ 'value' => [], 'clones' => [] ];
	    $width = empty( $field->width ) ? 100 : floatval( $field->width );
	    $has_width = $width !== 100;

	    if($field->type === 'section') {

		    $open_sections++;
	        if( $field->get_clone_type() === 'button') {
	            $section_has_buttons = $open_sections;
	            $section_field_for_buttons = $field;
	        }

	        echo '<div data-field-id="' . $field->id . '" class="'.Html::section_container_classes($field).'" style="width: '.$width.'%;" '.(!empty($field->conditionals) ? 'data-wapf-d="'.Helper::thing_to_html_attribute_string($field->conditionals).'"' : '').' '.Html::field_container_attributes($field).'>';

	        continue;
	    }

	    if($field->type === 'sectionend') {

	        if( $section_has_buttons === $open_sections) {
		        Html::partial('frontend/repeater-button', [
			        'field'             => $section_field_for_buttons,
			        'edit_cart_clones'  => Helper::edit_cart_clones( $clones_in_section, 2 )
		        ] );
		        $section_field_for_buttons = null;
		        $section_has_buttons = false;
		        $clones_in_section = [];
            }

		    $open_sections--;

		    echo '</div>';

		    continue;
	    }

	    if( $section_has_buttons === $open_sections) {
	        foreach ($cart_item_field['clones'] as $key => $clone) {
	            if(! isset($clones_in_section[ $key ])) $clones_in_section[ $key ] = [];
	            $clones_in_section[ $key ][] = $clone;
            }
	        //$clones_in_section = array_merge( $clones_in_section, $cart_item_field['clones']);
        }

	    echo '<div class="'. Html::field_container_classes($field,$product) . ($has_width ? ' has-width' : '') . '" style="width:'.$width.'%;" ' . Html::field_container_attributes($field).' >';

	    if( ! empty( $field->label ) && ( $label_position === 'above' || $label_position === 'left' ) ) {
		    echo sprintf(
			    '<div class="wapf-field-label"><label>%s</label>%s</div>%s',
			    Html::field_label($field,$product,$mark_required),
                $instructions_position === 'tooltip' ? Html::field_description_tooltip( $field ) : '',
			    $instructions_position === 'label' ? Html::field_description( $field ) : ''
		    );
	    }

	    echo '<div class="wapf-field-input">'. Html::field( $product, $field, $field_group->id, $cart_item_field[ 'value' ] ) .'</div>';

	    if( $instructions_position === 'field' )
		    echo Html::field_description( $field );

	    if( ! empty( $field->label ) && ( $label_position === 'below' || $label_position === 'right' ) ) {
		    echo sprintf(
			    '<div class="wapf-field-label"><label>%s</label>%s</div>%s',
			    Html::field_label($field,$product,$mark_required),
                $instructions_position === 'tooltip' ? Html::field_description_tooltip( $field ) : '',
                $instructions_position === 'label' ? Html::field_description($field) : ''
		    );
	    }

	    // We add the cloner here, right underneath the 'wapf-field-input' but still inside wapf-field-container so that it
        // also benefits from conditional settings.
	    if( $field->get_clone_type() === 'button' ) {
		    Html::partial('frontend/repeater-button', [
			    'field'             => $field,
			    'edit_cart_clones'  => Helper::edit_cart_clones( $cart_item_field['clones'] )
		    ] );
	    }

	    echo '</div>'; // Closing the "wapf-field-container"

    }

    for( $i=0; $i < $open_sections; $i++ ) {
	    if( $section_has_buttons !== false) {
		    Html::partial('frontend/repeater-button', [
			    'field'             => $section_field_for_buttons,
			    'edit_cart_clones'  => Helper::edit_cart_clones( $clones_in_section, 2 )
		    ] );
	    }
        echo '</div>'; // closing sections that don't have an "section end" set on the backend.
    }
    ?>

</div>