<?php

namespace SW_WAPF_PRO\Includes\Classes {

    use SW_WAPF_PRO\Includes\Models\Conditional;
    use SW_WAPF_PRO\Includes\Models\ConditionalRule;
    use SW_WAPF_PRO\Includes\Models\ConditionRule;
    use SW_WAPF_PRO\Includes\Models\ConditionRuleGroup;
    use SW_WAPF_PRO\Includes\Models\Field;
    use SW_WAPF_PRO\Includes\Models\FieldGroup;

	class Field_Groups
    {

        private static $all_groups_cache_key = 'field-groups-';
        private static $field_group_cache_key = 'field-group-';
        private static $field_groups_product_cache_key = 'field-groups-product-';
        public static $allowed_html_minimal = [
            'a' => [
                'href' => [],
                'title' => [],
                'target' => [],
                'class' => []
            ],
            'b' => ['class' => []],
            'em' => ['class' => []],
            'strong' => ['class' => []],
            'i' => ['class' => []],
            'span' => ['style' => [],'class' => []],
            'ul' => ['class' => []],
            'ol' => ['class' => []],
            'li' => ['class' => []],
            'br' => [],
            'img' => ['style' => [], 'class' => [],'src' => [] ],
        ];

        public static function field_group_to_raw_fields_json(FieldGroup $fg) {

	        foreach($fg->fields as $field) {

		        if( isset( $field->parent_qty_based ) ) unset($field->parent_qty_based);
				unset($field->parent_clone); 

	        	if( $field->qty_based && !$field->clone['enabled'] ) {
	        		$field->clone['enabled'] = true;
	        		$field->clone['type'] = 'qty';
	        		if( !empty( $field->clone_txt ) ) $field->clone['label'] = $field->clone_txt;
		        }
	        	unset($field->qty_based);
	        	unset($field->clone_txt);

	        	$conditional_count = count($field->conditionals);

		        for ($j = 0; $j < $conditional_count;$j++) {

					$rules_count = count($field->conditionals[$j]->rules);

			        for($i=0;$i<$rules_count;$i++) {
				        if(in_array($field->conditionals[$j]->rules[$i]->condition, ['product_var','!product_var','patts','!patts'])) {
					        unset($field->conditionals[$j]->rules[$i]);
					        continue;
				        }

				        if(isset($field->conditionals[$j]->rules[$i]->generated)  && $field->conditionals[$j]->rules[$i]->generated === true )
					        unset( $field->conditionals[ $j ]->rules[ $i ] );

			        }

			        $field->conditionals[ $j ]->rules = array_values($field->conditionals[ $j ]->rules);

			        if(empty($field->conditionals[$j]->rules)) {
				        unset( $field->conditionals[ $j ] );
			        }

		        }

		        $field->conditionals = array_values($field->conditionals);

	        }

	        $json_array = json_decode(json_encode($fg->fields),true);

            foreach($json_array as &$field) {

                if (!empty($field["options"])) {
                    foreach ($field["options"] as $k => $v) {
                        $field[$k] = $v;
                    }
                    unset($field['options']);
                }
            }

            return $json_array;
        }

        public static function raw_json_to_field_group($raw) {

            $fg = new FieldGroup();

            $fg->id = sanitize_text_field($raw['id']);
            $fg->type = sanitize_text_field($raw['type']);

			if(isset($raw['variables'])) {
				foreach($raw['variables'] as $variable){
					$v = [];
					$v['default'] = sanitize_text_field($variable['default']);
					$v['name'] = sanitize_text_field($variable['name']);
					$v['rules'] = [];

					foreach ($variable['rules'] as $vr) {
						$rule = [];
						$rule['type'] = sanitize_text_field($vr['type']);
						$rule['field'] = sanitize_text_field($vr['field']);
						$rule['variable'] = sanitize_text_field($vr['variable']);
						$rule['condition'] = sanitize_text_field($vr['condition']);
						$rule['value'] = sanitize_text_field($vr['value']);
						$v['rules'][] = $rule;
					}
					$fg->variables[] = $v;
				}
			}

            if(isset($raw['layout'])) {

                if(isset($raw['layout']['labels_position']))
                    $fg->layout['labels_position'] = sanitize_text_field($raw['layout']['labels_position']);

                if(isset($raw['layout']['instructions_position']))
                    $fg->layout['instructions_position'] = sanitize_text_field($raw['layout']['instructions_position']);

                if(isset($raw['layout']['mark_required']))
                    $fg->layout['mark_required'] = $raw['layout']['mark_required'] == 'true' ? true : false;

                if(isset($raw['layout']['position']))
                    $fg->layout['position'] = sanitize_text_field($raw['layout']['position']);

	            if(isset($raw['layout']['enable_gallery_images'])) {

		            $fg->layout['enable_gallery_images'] = $raw['layout']['enable_gallery_images'] == 'true' ? true : false;

		            $fg->layout['swap_type'] = 'rules';
		            if(isset($raw['layout']['swap_type']))
			            $fg->layout['swap_type'] = $raw['layout']['swap_type'] === 'last' ? 'last' : 'rules';

		            $fg->layout['gallery_images'] = [];
		            if(!empty($raw['layout']['gallery_images'])) {
			            foreach ( $raw['layout']['gallery_images'] as $gallery_image ) {

			            	$new_gallery_image = [
					            'source'    => sanitize_text_field($gallery_image['source']),
					            'url'       => sanitize_text_field($gallery_image['url']),
					            'id'        => sanitize_text_field($gallery_image['id']),
					            'values'    => [],
				            ];

			            	if(!empty($gallery_image['values'])) {
			            		foreach ($gallery_image['values'] as $value) {
			            			$new_gallery_image['values'][] = [
			            				'field' => sanitize_text_field($value['field']),
			            				'value' => sanitize_text_field($value['value']),
						            ];
					            }
				            }

				            $fg->layout['gallery_images'][] = $new_gallery_image;

		                }

		            }
	            }

            }

            foreach($raw['fields'] as $raw_field) {

                $field = new Field();

                $field->id = sanitize_text_field($raw_field['id']);

	            if(isset($raw_field['label']))
                    $field->label = wp_kses($raw_field['label'], self::$allowed_html_minimal);
	            if(isset($raw_field['description']))
                    $field->description = wp_kses($raw_field['description'], self::$allowed_html_minimal);
                $field->type = sanitize_text_field($raw_field['type']);
                $field->required = isset($raw_field['required']) && $raw_field['required'] == 'true' ? true : false;
                if(isset($raw_field['class']))
                    $field->class = implode(' ',array_map('sanitize_html_class',explode(' ',$raw_field['class'])));
                if(isset($raw_field['width']))
                    $field->width = floatval($raw_field['width']);

                if(isset($raw_field['choices'])) {
                    $field->options['choices'] = [];
                    foreach ($raw_field['choices'] as $raw_choice) {
                        $choice = [
                            'slug'      => sanitize_text_field($raw_choice['slug']),
                            'label'     => wp_kses($raw_choice['label'], self::$allowed_html_minimal),
                            'selected'  =>  $raw_choice['selected'] == 'true' ? true : false,
	                        'options'   => []
                        ];
                        if(isset($raw_choice['pricing_type']))
                            $choice['pricing_type'] = sanitize_text_field($raw_choice['pricing_type']);
                        if(isset($raw_choice['pricing_amount']))
                            $choice['pricing_amount'] = $choice['pricing_type'] === 'fx' ?
                                Helper::sanitize_textfield_without_tags( $raw_choice['pricing_amount'] ) :
		                        floatval(Helper::normalize_string_decimal($raw_choice['pricing_amount']));
                        if(isset($raw_choice['color']))
                            $choice['color'] = sanitize_text_field($raw_choice['color']);
                       if(isset($raw_choice['image']))
                            $choice['image'] = esc_url_raw($raw_choice['image']);
						if( isset( $raw_choice['attachment'] ) )
							$choice['attachment'] = intval( $raw_choice['attachment'] );

                        if(isset($raw_choice['options']) && is_array($raw_choice['options'])) {

							foreach($raw_choice['options'] as $key => $val) {

								$choice['options'][sanitize_text_field($key)] = sanitize_text_field($val);
							}

                        }

                        $field->options['choices'][] = $choice;
                    }
                }

                if(isset($raw_field['placeholder']))
                    $field->options['placeholder'] = sanitize_text_field($raw_field['placeholder']);
                if(isset($raw_field['default']))
                    $field->options['default'] = $field->type === 'textarea' ? sanitize_textarea_field($raw_field['default']) : sanitize_text_field($raw_field['default']);

                if(isset($raw_field['hide_cart']))
                	$field->options['hide_cart'] = $raw_field['hide_cart'] == 'true';
	            if(isset($raw_field['hide_checkout']))
		            $field->options['hide_checkout'] = $raw_field['hide_checkout'] == 'true';
	            if(isset($raw_field['hide_order']))
		            $field->options['hide_order'] = $raw_field['hide_order'] == 'true';
				if(isset($raw_field['p_content']))
					$field->options['p_content'] = wp_kses($raw_field['p_content'],Html::$minimal_allowed_html_element);
	            if(isset($raw_field['image']))
		            $field->options['image'] = esc_url_raw($raw_field['image']);
	            if(isset($raw_field['attachment'])) {
	            	$field->options['attachment'] = intval($raw_field['attachment']);
	            }

                foreach($raw_field as $k => $v) {

                    $key = sanitize_text_field( $k );

                    if( in_array($key, ['id','key','label','description','default','placeholder','choices','conditionals','type','required','options','p_content','image','attachment','class','width','pricing','parent_clone','clone','hide_cart','hide_checkout','hide_order']) )
                        continue;

                    switch( $key ) {
                        case 'formula': $field->options[ $key ] = Helper::sanitize_textfield_without_tags( $v ); break;
                        default: $field->options[ $key ] = sanitize_textarea_field( $v ); break;
                    }

                }


	            if( ! empty( $raw_field['clone'] ) ) {
	            	$field->clone['enabled'] = $raw_field['clone']['enabled'] == 'true' ? true : false;
	            	if( $field->clone['enabled'] ) {
			            $field->clone['type'] =  $raw_field['clone']['type'] === 'button' ? 'button' : ( $raw_field['clone']['type'] === 'qty' ? 'qty' : 'field' );
			            if( !empty( $raw_field['clone']['add'] ) ) $field->clone['add'] = sanitize_text_field( $raw_field['clone']['add'] );
			            if( !empty( $raw_field['clone']['del'] ) ) $field->clone['del'] = sanitize_text_field( $raw_field['clone']['del'] );
			            if( !empty( $raw_field['clone']['label'] ) ) $field->clone['label'] = sanitize_text_field( $raw_field['clone']['label'] );
			            if( !empty( $raw_field['clone']['field'] ) ) $field->clone['field'] = sanitize_text_field( $raw_field['clone']['field'] );
			            if( !empty( $raw_field['clone']['max'] ) ) $field->clone['max'] = intval( $raw_field['clone']['max'] );
		            }

	            }

                if(isset($raw_field['pricing'])) {
                    $field->pricing->enabled = $raw_field['pricing']['enabled'] == 'true' ? true : false;
	                $field->pricing->type = sanitize_text_field($raw_field['pricing']['type']);

	                $field->pricing->amount = $field->pricing->type === 'fx' ?
                        Helper::sanitize_textfield_without_tags( $raw_field['pricing']['amount'] ) :
		                floatval(Helper::normalize_string_decimal($raw_field['pricing']['amount']));
                }

                foreach($raw_field['conditionals'] as $raw_conditional) {

                    $conditional = new Conditional();

                    foreach($raw_conditional['rules'] as $raw_rule){
                        $rule = new ConditionalRule();
                        $rule->field = sanitize_text_field($raw_rule['field']);
                        $rule->value = sanitize_text_field($raw_rule['value']);
                        $rule->condition = sanitize_text_field($raw_rule['condition']); 

                        $conditional->rules[] = $rule;
                    }

                    $field->conditionals[] = $conditional;

                }

                $fg->fields[] = $field;

            }

            if(!empty($raw['conditions'])) {
	            foreach ( $raw['conditions'] as $raw_condition ) {
		            $condition = new ConditionRuleGroup();

		            foreach ( $raw_condition['rules'] as $raw_rule ) {
			            $rule = new ConditionRule();

			            $rule->condition = sanitize_text_field( $raw_rule['condition'] );
			            if ( isset( $raw_rule['value'] ) ) {
				            $rule->value = is_string( $raw_rule['value'] ) ? sanitize_text_field( $raw_rule['value'] ) : Enumerable::from( $raw_rule['value'] )->select( function ( $value ) {
					            return [
						            'id'   => sanitize_text_field( $value['id'] ),
						            'text' => sanitize_text_field( $value['text'] )
					            ];
				            } )->toArray();
			            }
			            $rule->subject = sanitize_text_field( $raw_rule['subject'] );

			            $condition->rules[] = $rule;
		            }

		            $fg->rules_groups[] = $condition;

	            }
            }

	       foreach($fg->rules_groups as $rg) {
	        	$variation_rules = $rg->get_variation_rules();

		       if(!empty($variation_rules)) {

	        		foreach($variation_rules as $variation_rule) {
				        foreach ($fg->fields as $field) {
				        	$rule = new ConditionalRule();
				        	$rule->field = $field->id;
					        $rule->generated = true;
					        $rule->condition = $variation_rule->condition;

				        	$rule->value = Enumerable::from((array)$variation_rule->value)->join(function($value) use($variation_rule) {
						        return $variation_rule->subject === 'product_variation' ? intval($value['id']) : $value['id'];
					        },',');
				        	if(empty($field->conditionals)) {
						        $c = new Conditional();
						        $c->rules[] = $rule;
						        $field->conditionals[] = $c;
				            } else {
								foreach($field->conditionals as $conditional) {
									$conditional->rules[] = $rule;
								}
					        }
				        }
			        }
		        }
	        }

	        for ($i = 0;$i<count($fg->fields);$i++) {
	        	$clone_type = $fg->fields[$i]->get_clone_type();
	        	if($fg->fields[$i]->type === 'section' && ! empty( $clone_type ) ) {

	        		for($j = $i+1;$j<count($fg->fields);$j++) {
						if($fg->fields[$j]->type === 'sectionend')
							break;

						$fg->fields[$j]->parent_clone = [
							'type' => $clone_type,
							'label' => $fg->fields[$i]->get_clone_label()
						];

			        }

		        }

	        }

	        for ($i = 0;$i<count($fg->fields);$i++) {

		        if($fg->fields[$i]->type === 'section' && $fg->fields[$i]->has_conditionals()  ) {

		        	$field = $fg->fields[$i]; 

			        for($j = $i+1;$j<count($fg->fields);$j++) {

			        	if($fg->fields[$j]->type === 'sectionend')
					        break;

						$fieldB = $fg->fields[$j]; 

				        if(empty($fieldB->conditionals)) {
					        foreach($field->conditionals as $fieldA_Condition) {
					        	$c = new Conditional();
					        	$c->rules = Enumerable::from($fieldA_Condition->rules)->select(function($x){
					        		$r = new ConditionalRule();
					        		$r->generated = true;
					        		$r->field = $x->field;
					        		$r->value = $x->value;
					        		$r->condition = $x->condition;
					        		return $r;
						        })->toArray();
					        	$fieldB->conditionals[] = $c;
					        }
				        }
				        else { 
				        	$conditionals = [];
				        	foreach ($fieldB->conditionals as $fieldB_condition) {
								$ctr = 0;
				        		foreach($field->conditionals as $fieldA_condition) {
				        			$ctr++;

				        			$c = new Conditional();

				        			$rules_from_a = Enumerable::from($fieldA_condition->rules)->select(function($x) {
				        				$v = new ConditionalRule();
				        				$v->generated = true;
				        				$v->field = $x->field;
				        				$v->value = $x->value;
				        				$v->condition = $x->condition;
				        				return $v;
				        			})->toArray();

							        $rules_from_b = Enumerable::from($fieldB_condition->rules)->select(function($x) use ($ctr) {
								        $v = new ConditionalRule();
								        $v->generated = $ctr > 1 ? true : $x->generated; 
								        $v->field = $x->field;
								        $v->value = $x->value;
								        $v->condition = $x->condition;
								        return $v;
							        })->toArray();

							        $c->rules = array_merge($rules_from_b, $rules_from_a);

				        			$conditionals[] = $c;
						        }
					        }
				        	$fieldB->conditionals = $conditionals;
				        }

			        }

		        }

	        }

            return $fg;

        }

        public static function get_all($of_type = 'product') {

            $cache_key = self::$all_groups_cache_key . $of_type;

            $cached = Cache::get($cache_key);

            if($cached === false) {

            	$args = [
		            'numberposts'               => -1,
		            'post_type'                 => 'wapf_' . $of_type,
		            'posts_per_page'            => -1,
		            'post_status'               => 'publish',
		            'update_post_meta_cache'    => false    
	            ];

	            if(function_exists('icl_get_languages')) {
		            $args['suppress_filters'] = false;
	            }

	            $posts = get_posts($args);

                $groups = [];

                foreach ($posts as $post) {
                	$processed =  self::process_data($post->post_content);
                	if(!empty($processed->fields))
                        $groups[] = $processed;
                }

                $cached = $groups;

                Cache::set($cache_key,$groups);

            }

            return $cached;
        }

        public static function get_by_id($id) {

            $cache_key = self::$field_group_cache_key . $id;

            $cached = Cache::get($cache_key );
            if($cached !== false) {
                return $cached;
            }

            if(strpos($id, 'p_') !== false) {
                $the_group = Field_Groups::process_data(get_post_meta(intval(str_replace('p_','',$id)),'_wapf_fieldgroup', true));
                Cache::set($cache_key,$the_group);
                return $the_group;
            }

            $types = ['product'];

            foreach($types as $type) {
                $all_groups_cached = Cache::get(self::$all_groups_cache_key . $type);

                if($all_groups_cached !== false) {

                    $the_group = Enumerable::from($all_groups_cached)->firstOrDefault(function($x) use($id) {
                        return $x->id === $id;
                    });

                    if($the_group) {
                        Cache::set($cache_key, $the_group);
                        return $the_group;
                    }
                }
            }

            $post = get_post(intval($id));
            if(!$post || !in_array($post->post_type,wapf_get_setting('cpts')))
                return null;

            $cached = self::process_data($post->post_content);
            Cache::set($cache_key,$cached);

            return $cached;

        }

        public static function get_by_ids(array $ids) {

            $field_groups = [];

            foreach($ids as $id) {

                $field_group = self::get_by_id($id);
                if($field_group)
                    $field_groups[] = $field_group;
            }

            return $field_groups;

        }

        public static function get_valid_field_groups($of_type) {

            $field_groups = Field_Groups::get_all($of_type);
            $valid_field_groups = [];

            foreach ($field_groups as $field_group) {
                if(Conditions::is_field_group_valid($field_group))
                    $valid_field_groups[] = $field_group;
            }

            return $valid_field_groups;

        }

        public static function get_valid_rule_groups(FieldGroup $field_group) {

            $valids = [];

            foreach ($field_group->rules_groups as $rules_group) {
                if(Conditions::is_rule_group_valid($rules_group))
                    $valids[] = $rules_group;
            }

            return $valids;

        }

        public static function product_has_field_group($product) {

            if(is_int($product))
                $product = wc_get_product($product);

            $field_group_on_product = get_post_meta($product->get_id(),'_wapf_fieldgroup', true);

            if(!empty($field_group_on_product)) {
	            if(is_object($field_group_on_product)) {
		            $field_group_on_product = (array) $field_group_on_product;
	            }

	            if(!empty($field_group_on_product['fields'])) {
	            	return true;
	            }

            }

            $field_groups = Field_Groups::get_all('product');

            foreach ($field_groups as $group) {

            	if(empty($group->fields))
            		continue;

                if(Conditions::is_field_group_valid_for_product($group, $product))
                    return true;

            }

            return false;

        }

        public static function get_field_groups_of_product($product) {

	        if( is_int( $product ) )
		        $product = wc_get_product($product);

	        $product_id = $product->get_id();
	        if( $product->get_parent_id() )
	        	$product_id = $product->get_parent_id();

	        $cache_key = self::$field_groups_product_cache_key . $product_id;

	        $cached = Cache::get( $cache_key );

	        if( $cached !== false ) {
	        	return $cached;
	        }

	        $field_groups_of_product = [];
	        $field_group_on_product = self::process_data(get_post_meta($product_id,'_wapf_fieldgroup', true));

	        if( $field_group_on_product && ! empty( $field_group_on_product->fields ) || ! empty( $field_group_on_product->variables ) )
                array_push($field_groups_of_product, $field_group_on_product);

        	$all_field_groups = self::get_all();

	        foreach ($all_field_groups as $fg) {
        	    if(Conditions::is_field_group_valid_for_product($fg, $product))
        	    	$field_groups_of_product[] = $fg;
	        }

	        Cache::set( $cache_key, $field_groups_of_product );

	        return $field_groups_of_product;

        }

        public static function save(FieldGroup $fg, $post_type = 'wapf_product', $post_id = null, $post_title = null, $status = null) {

            $post_type = strtolower($post_type);
            $fg->type = $post_type;

            $save = [
                'post_type' => $post_type
            ];

            if($post_id != null) {
                $save['ID'] = $post_id;
                $fg->id = $post_id;
            }

            if($status != null)
                $save['post_status'] = $status;

            if($post_title != null)
                $save['post_title'] = sanitize_text_field($post_title);

            $save['post_content'] = Helper::wp_slash(serialize($fg->to_array()));

            if($post_id)
                $id = wp_update_post($save);
            else {
                $id = wp_insert_post($save);

                $fg->id = $id;
                $update_data = [
                    'ID'            => $id,
                    'post_content'  => Helper::wp_slash(serialize($fg->to_array()))
                ];
                $id = wp_update_post($update_data);
            }

            return $id;
        }

        public static function has_pricing_logic($groups) {
            return Enumerable::from($groups)->any(function($group){
                return Enumerable::from($group->fields)->any(function($field) {
                    return $field->pricing_enabled();
                });
            });
        }

        public static function process_data($data) {

	        if( is_serialized( $data ) ) {

	        	try {

	        		$unserialized = unserialize( $data );

	        							if( is_array( $unserialized ) ) {

						$fg = new FieldGroup();
						return $fg->from_array( $unserialized );
			        }

	        						return false;

		        } catch( \Exception $e ) {

			        return false;

		        				}

	        }

	        if( is_array( $data ) ) {

		        $fg = new FieldGroup();
		        return $fg->from_array( $data );

	        			}

	        return $data;

        }

    }

}