<?php /* @var $model array */ ?>

<div style="display: flex">
    <div style="width:48%;">
        <div class="wapf-input-prepend-append">
            <div class="wapf-input-prepend"><?php _e('Width','sw-wapf'); ?></div>
            <input
                    rv-on-keyup="onChange" min="0" max="100"
                    step="any"
                    rv-value="<?php echo $model['is_field_setting'] ? 'field' : 'settings'; ?>.width"
                    type="number"
            />
            <div class="wapf-input-append">%</div>
        </div>
    </div>
    <div style="width:48%; padding-left:2%;">
        <div class="wapf-input-with-prepend">
            <div class="wapf-input-prepend"><?php _e('Class','sw-wapf'); ?></div>
            <input
                    rv-on-keyup="onChange"
                    rv-value="<?php echo $model['is_field_setting'] ? 'field' : 'settings'; ?>.class"
                    type="text"
            />
        </div>
    </div>
</div>