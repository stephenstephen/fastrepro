<?php
/* @var $model array */
use SW_WAPF_PRO\Includes\Classes\Helper;
?>
<div rv-controller="FieldListCtrl"
     data-field-definitions="<?php echo Helper::thing_to_html_attribute_string(\SW_WAPF_PRO\Includes\Classes\Fields::get_field_definitions('short')); ?>"
     data-raw-fields="<?php echo Helper::thing_to_html_attribute_string($model['fields']); ?>"
     data-field-conditions="<?php echo Helper::thing_to_html_attribute_string($model['condition_options']); ?>"
>

    <input type="hidden" name="wapf-fields" rv-value="fieldsJson" />

    <div class="wapf-performance wapf-list--empty" rv-if="hiddenForPerformance">
        <a href="#" class="button button-primary button-large" rv-on-click="renderFields"><?php _e('View all fields','sw-wapf');?></a>
        <div style="padding-top: 15px">
            <?php _e('To ensure optimal page load performance, the field list is not displayed yet. If you want to edit or add fields, click the button above to view the list. Rendering may take a moment to complete.','sw-wapf');?>
        </div>
    </div>

    <div rv-if="hiddenForPerformance | eq false" class="wapf-field-list">

        <div class="wapf-field-list__body">
            <span rv-show="renderedFields | isEmpty" class="wapf-list--empty" style="display: <?php echo empty($model['fields']) ? 'block' : 'none';?>;">
                <a href="#" class="button button-primary button-large" rv-on-click="addField"><?php _e('Add your first field','sw-wapf');?></a>
            </span>

            <?php \SW_WAPF_PRO\Includes\Classes\Html::admin_field([], $model['type']); ?>

        </div>

        <div rv-cloak>
            <div rv-show="renderedFields | isNotEmpty" class="wapf-field-list__footer">
                <a href="#" class="button button-primary button-large" rv-on-click="addField"><?php _e('Add a Field','sw-wapf');?></a>
            </div>
        </div>

    </div>

</div>