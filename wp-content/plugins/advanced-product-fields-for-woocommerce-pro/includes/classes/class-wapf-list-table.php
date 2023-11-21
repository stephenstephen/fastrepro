<?php

namespace SW_WAPF_PRO\Includes\Classes {

    class Wapf_List_Table extends \WP_List_Table
    {

        private $count_cache = [];

        public function get_columns() {

            $table_columns = [
                'cb'                => '<input type="checkbox" />', 
                'post_title'        => __( 'Title' ),
                'fields'            => __('Fields', 'sw-wapf'),
                'post_date'	        => __( 'Date'),
            ];

            return $table_columns;

        }

        function get_sortable_columns() {

            $sortable_columns = [
                'post_title'    => ['title',false],
                'post_date'     => ['date',false],
            ];

            return $sortable_columns;
        }

        public function column_post_date($post) {
            global $mode;

            if ( '0000-00-00 00:00:00' === $post->post_date ) {
                $t_time    = $h_time = __( 'Unpublished' );
                $time_diff = 0;
            } else {
                $t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
                $m_time = $post->post_date;
                $time   = get_post_time( 'G', true, $post );

                $time_diff = time() - $time;

                if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
                    $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
                } else {
                    $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
                }
            }

            if ( 'publish' === $post->post_status ) {
                $status = __( 'Published' );
            } elseif ( 'future' === $post->post_status ) {
                if ( $time_diff > 0 ) {
                    $status = '<strong class="error-message">' . __( 'Missed schedule' ) . '</strong>';
                } else {
                    $status = __( 'Scheduled' );
                }
            } else {
                $status = __( 'Last Modified' );
            }

            $status = apply_filters( 'post_date_column_status', $status, $post, 'date', $mode );

            if ( $status ) {
                echo $status . '<br />';
            }

            if ( 'excerpt' === $mode ) {
                echo apply_filters( 'post_date_column_time', $t_time, $post, 'date', $mode );
            } else {
                echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, 'date', $mode ) . '</abbr>';
            }

        }

        public function column_fields($post) {

            if( empty( $post->post_content ) )
                return 0;

            $field_group = Field_Groups::process_data( $post->post_content );

            if( empty( $field_group ) ) return 0;

            return count( $field_group->fields );
        }

        public function column_post_title($post) {

            $actions            = [];
            $post_type_object   = get_post_type_object( $post->post_type );
            $title              = _draft_or_post_title($post);

            if( current_user_can( 'edit_post', $post->ID ) && $post->post_status != 'trash') {
                $actions['edit'] = sprintf(
                    '<a href="%s" aria-label="%s">%s</a>',
                    get_edit_post_link( $post->ID ),
                    esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ),
                    __( 'Edit' )
                );
                if($post->post_status === 'publish') {
                    $actions['duplicate'] = sprintf(
                        '<a href="%s" aria-label="%s">%s</a>',
                        admin_url('admin.php?page=wapf-field-groups&wapf_duplicate='.$post->ID),
                        esc_attr( sprintf( __( 'Duplicate &#8220;%s&#8221;','sw-wapf' ), $title ) ),
                        __( 'Duplicate', 'sw-wapf' )
                    );
                }
            }

            if( current_user_can('delete_post', $post->ID)) {

                if($post->post_status === 'trash') {
                    $actions['untrash'] = sprintf(
                        '<a href="%s" aria-label="%s">%s</a>',
                        wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ),
                        esc_attr( sprintf( __( 'Restore &#8220;%s&#8221; from the Trash' ), $title ) ),
                        __( 'Restore' )
                    );
                }

                if($post->post_status === 'trash') {
                    $actions['delete'] = sprintf(
                        '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                        get_delete_post_link( $post->ID, '', true ),
                        esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
                        __( 'Delete Permanently' )
                    );
                }

                if($post->post_status !== 'trash') {
                    $actions['trash'] = sprintf(
                        '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                        get_delete_post_link( $post->ID ),
                        esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash' ), $title ) ),
                        _x( 'Trash', 'verb' )
                    );
                }

            }

            return sprintf(
                '<strong><a class="row-title" href="%s">%s</a>%s</strong>%s',
                get_edit_post_link($post->ID),
                esc_html($title),
                $post->post_status === 'draft' ? ' &mdash; <span class="post-state">'.__('Draft').'</span>' : '',
                $this->row_actions($actions)
            );

        }

        public function column_cb($post) {
            return sprintf(
                '<input type="checkbox" name="fieldgroups[]" value="%s" />', $post->ID
            );
        }

        public function column_default( $post, $column_name ) {

            return esc_html($post->{$column_name});

        }

        public function no_items() {

            _e( 'No Product Field Groups found.', 'sw-wapf');

        }

        public function get_bulk_actions() {

            $actions = [
                'trash'    =>  isset($_GET['post_status']) && $_GET['post_status'] === 'trash' ? __('Delete Permanently'): __('Move to Trash')
            ];

            return $actions;
        }

        public function process_bulk_actions() {

            if($this->current_action() === 'trash' && isset($_POST['fieldgroups'])) {
                foreach($_POST['fieldgroups'] as $post_id) {
                    if(current_user_can('delete_post', $post_id)) {
                        $post = get_post($post_id);
                        if($post && $post->post_status === 'trash')
                            wp_delete_post($post_id);
                        else wp_trash_post($post_id);
                    }
                }
                wp_safe_redirect(admin_url('admin.php?page=wapf-field-groups'));
            }

        }

        public function get_views() {

            global $wp_post_statuses;
            $counts = $this->get_all_counts();
            $status = $this->get_current_post_status();

            $status_links = [];

            if( isset( $wp_post_statuses['publish'] ) && $counts['publish'] > 0 ) {
                $post_status = $wp_post_statuses['publish'];
                $status_label = sprintf(
                    translate_nooped_plural( $post_status->label_count, $counts['publish'] ),
                    number_format_i18n( $counts['publish'] )
                );

                $status_links['publish'] = sprintf('<a href="%s" class="%s">%s</a>',admin_url('admin.php?page=wapf-field-groups&post_status=publish'), $status === 'publish' ? 'current' : '', $status_label);

            }

            if( isset( $wp_post_statuses['draft'] ) && $counts['draft'] > 0 ) {

                $post_status = $wp_post_statuses['draft'];
                $status_label = sprintf(
                    translate_nooped_plural( $post_status->label_count, $counts['draft'] ),
                    number_format_i18n( $counts['draft'] )
                );

                $status_links['draft'] = sprintf('<a href="%s" class="%s">%s</a>',admin_url('admin.php?page=wapf-field-groups&post_status=draft'), $status === 'draft' ? 'current' : '', $status_label);

            }

            if( isset( $wp_post_statuses['trash'] ) && $counts['trash'] > 0 ) {

                $post_status = $wp_post_statuses['trash'];
                $status_label = sprintf(
                    translate_nooped_plural( $post_status->label_count, $counts['trash'] ),
                    number_format_i18n( $counts['trash'] )
                );

                $status_links['trash'] = sprintf('<a href="%s" class="%s">%s</a>',admin_url('admin.php?page=wapf-field-groups&post_status=trash'), $status === 'trash' ? 'current' : '', $status_label);

            }

            return $status_links;
        }

        public function prepare_items() {

	        $user = get_current_user_id();
	        $screen = get_current_screen();
	        $screen_option = $screen->get_option('per_page', 'option');

            $columns                = $this->get_columns();
            $hidden                 = [];
            $sortable               = $this->get_sortable_columns();
            $this->_column_headers  = [$columns, $hidden, $sortable];

            $items_per_page         = 20;
	        if( ! empty($screen_option) ) {
		        $per_page = get_user_meta($user, $screen_option, true);
		        if( ! empty($per_page ) )
		        	$items_per_page = intval( $per_page );
	        }

            $page                   = isset($_GET['paged']) ? $_GET['paged'] : 1;
            $status                 = $this->get_current_post_status();

            $query_options = [
                'post_type'     => wapf_get_setting('cpts'),
                'numberposts'   => $items_per_page,
                'paged'         => $page,
                'post_status'   => $status === 'all' ? ['publish','draft'] : $status
            ];

            $this->set_pagination_args( [
                'total_items' => $this->get_all_counts()[$status],
                'per_page'    => $items_per_page
            ] );

            if(!empty($_GET['orderby']))
                $query_options['orderby'] = $_GET['orderby'];

            if(!empty($_GET['order']))
                $query_options['order'] = strtoupper($_GET['order']);

            $this->process_bulk_actions();


            $posts = get_posts($query_options);
            $this->items = $posts;

        }

        private function get_current_post_status() {

            $status = 'publish';

            if( ! empty( $_GET['post_status'] ) ) {
                switch ( $_GET['post_status'] ) {
                    case 'draft': $status = 'draft'; break;
                    case 'trash': $status = 'trash'; break;
                }
            }
            return $status;
        }

        private function get_all_counts() {

            if(!empty($this->count_cache))
                return $this->count_cache;

            $this->count_cache = [ 'publish' => 0, 'draft' => 0, 'trash' => 0, 'all' => 0 ];

            foreach(wapf_get_setting('cpts') as $cpt) {
                $count = wp_count_posts($cpt);
                $this->count_cache['publish'] += $count->publish;
                $this->count_cache['trash'] += $count->trash;
                $this->count_cache['draft'] += $count->draft;
            }

            $this->count_cache['all'] = $this->count_cache['publish'] + $this->count_cache['draft'];

            return $this->count_cache;

        }
    }
}