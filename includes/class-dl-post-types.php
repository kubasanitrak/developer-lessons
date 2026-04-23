<?php
/**
 * Custom Post Types
 */

if (!defined('ABSPATH')) {
    exit;
}

class DL_Post_Types {

    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_lesson', array($this, 'save_lesson_meta'), 10, 2);
        add_filter('manage_lesson_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_lesson_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);

        // Enqueue media for tile image selector
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    /**
     * Register Lesson post type
     */
    public function register_post_types() {
        $labels = array(
            'name' => __('Lessons', 'developer-lessons'),
            'singular_name' => __('Lesson', 'developer-lessons'),
            'menu_name' => __('Lessons', 'developer-lessons'),
            'add_new' => __('Add New', 'developer-lessons'),
            'add_new_item' => __('Add New Lesson', 'developer-lessons'),
            'edit_item' => __('Edit Lesson', 'developer-lessons'),
            'new_item' => __('New Lesson', 'developer-lessons'),
            'view_item' => __('View Lesson', 'developer-lessons'),
            'search_items' => __('Search Lessons', 'developer-lessons'),
            'not_found' => __('No lessons found', 'developer-lessons'),
            'not_found_in_trash' => __('No lessons found in trash', 'developer-lessons'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'lesson'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => true,
        );

        register_post_type('lesson', $args);

        // Register lesson categories
        register_taxonomy('lesson_category', 'lesson', array(
            'labels' => array(
                'name' => __('Categories', 'developer-lessons'),
                'singular_name' => __('Category', 'developer-lessons'),
            ),
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'lesson-category'),
        ));
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes($post_type = '') {
        if (is_object($post_type)) {
            $post_type = $post_type->post_type ?? '';
        }
        
        add_meta_box(
            'dl_lesson_settings',
            __('Lesson Settings', 'developer-lessons'),
            array($this, 'render_settings_metabox'),
            'lesson',
            'side',
            'high'
        );

        add_meta_box(
            'dl_lesson_teaser',
            __('Teaser Content (shown to non-purchasers)', 'developer-lessons'),
            array($this, 'render_teaser_metabox'),
            'lesson',
            'normal',
            'high'
        );

        add_meta_box(
            'dl_lesson_stats',
            __('Lesson Statistics', 'developer-lessons'),
            array($this, 'render_stats_metabox'),
            'lesson',
            'side',
            'default'
        );

        // Only add tile image metabox if ACF is not handling it
        if (!function_exists('get_field') || !$this->acf_field_exists('lesson_tile_img_id')) {
            add_meta_box(
                'dl_lesson_tile_image',
                __('Tile Image', 'developer-lessons'),
                array($this, 'render_tile_image_metabox'),
                'lesson',
                'side',
                'default'
            );
        }
    }

    /**
     * Check if ACF field exists
     */
    private function acf_field_exists($field_name) {
        if (!function_exists('acf_get_field')) {
            return false;
        }
        
        $field = acf_get_field($field_name);
        return !empty($field);
    }

    /**
     * Render tile image metabox
     */
    public function render_tile_image_metabox($post) {
        wp_nonce_field('dl_lesson_tile_image', 'dl_lesson_tile_image_nonce');
        
        $image_id = get_post_meta($post->ID, '_dl_tile_image_id', true);
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
        ?>
        <div class="dl-tile-image-metabox">
            <div class="dl-tile-image-preview" style="margin-bottom: 10px;">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 100%; height: auto;">
                <?php else: ?>
                    <div class="dl-no-image" style="background: #f0f0f0; padding: 30px; text-align: center; color: #999;">
                        <?php _e('No image selected', 'developer-lessons'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <input type="hidden" name="dl_tile_image_id" id="dl_tile_image_id" value="<?php echo esc_attr($image_id); ?>">
            
            <p>
                <button type="button" class="button dl-select-tile-image" id="dl_select_tile_image">
                    <?php echo $image_id ? __('Change Image', 'developer-lessons') : __('Select Image', 'developer-lessons'); ?>
                </button>
                <?php if ($image_id): ?>
                    <button type="button" class="button dl-remove-tile-image" id="dl_remove_tile_image">
                        <?php _e('Remove', 'developer-lessons'); ?>
                    </button>
                <?php endif; ?>
            </p>
            
            <p class="description">
                <?php _e('This image will be displayed in lesson grids and listings.', 'developer-lessons'); ?>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var frame;
            
            $('#dl_select_tile_image').on('click', function(e) {
                e.preventDefault();
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: '<?php _e('Select Tile Image', 'developer-lessons'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'developer-lessons'); ?>'
                    },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#dl_tile_image_id').val(attachment.id);
                    $('.dl-tile-image-preview').html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 100%; height: auto;">');
                    $('#dl_select_tile_image').text('<?php _e('Change Image', 'developer-lessons'); ?>');
                    
                    if ($('#dl_remove_tile_image').length === 0) {
                        $('#dl_select_tile_image').after(' <button type="button" class="button dl-remove-tile-image" id="dl_remove_tile_image"><?php _e('Remove', 'developer-lessons'); ?></button>');
                    }
                });
                
                frame.open();
            });
            
            $(document).on('click', '#dl_remove_tile_image', function(e) {
                e.preventDefault();
                $('#dl_tile_image_id').val('');
                $('.dl-tile-image-preview').html('<div class="dl-no-image" style="background: #f0f0f0; padding: 30px; text-align: center; color: #999;"><?php _e('No image selected', 'developer-lessons'); ?></div>');
                $('#dl_select_tile_image').text('<?php _e('Select Image', 'developer-lessons'); ?>');
                $(this).remove();
            });
        });
        </script>
        <?php
    }


    /**
     * Render settings metabox
     */
    public function render_settings_metabox($post) {
        wp_nonce_field('dl_lesson_settings', 'dl_lesson_settings_nonce');
        
        $price = get_post_meta($post->ID, '_dl_price', true);
        $access_type = get_post_meta($post->ID, '_dl_access_type', true) ?: 'paid';
        $currency_symbol = get_option('dl_currency_symbol', 'Kč');
        ?>
        <p>
            <label for="dl_access_type"><strong><?php _e('Access Type', 'developer-lessons'); ?></strong></label><br>
            <select name="dl_access_type" id="dl_access_type" style="width:100%;">
                <option value="paid" <?php selected($access_type, 'paid'); ?>><?php _e('Paid', 'developer-lessons'); ?></option>
                <option value="free" <?php selected($access_type, 'free'); ?>><?php _e('Free for Registered Users', 'developer-lessons'); ?></option>
            </select>
        </p>
        <p class="dl-price-field" style="<?php echo $access_type === 'free' ? 'display:none;' : ''; ?>">
            <label for="dl_price"><strong><?php _e('Price', 'developer-lessons'); ?></strong></label><br>
            <input type="number" name="dl_price" id="dl_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" style="width:80%;">
            <span><?php echo esc_html($currency_symbol); ?></span>
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('#dl_access_type').on('change', function() {
                if ($(this).val() === 'free') {
                    $('.dl-price-field').hide();
                } else {
                    $('.dl-price-field').show();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render teaser metabox
     */
    public function render_teaser_metabox($post) {
        $teaser_content = get_post_meta($post->ID, '_dl_teaser_content', true);
        
        wp_editor($teaser_content, 'dl_teaser_content', array(
            'textarea_name' => 'dl_teaser_content',
            'textarea_rows' => 10,
            'media_buttons' => true,
            'teeny' => false,
        ));
        ?>
        <p class="description">
            <?php _e('This content will be shown to logged-in users who have not purchased this lesson.', 'developer-lessons'); ?>
        </p>
        <?php
    }

    /**
     * Render stats metabox
     */
    public function render_stats_metabox($post) {
        global $wpdb;
        
        $purchases_table = $wpdb->prefix . 'dl_purchases';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as sales, SUM(price) as revenue FROM $purchases_table WHERE lesson_id = %d",
            $post->ID
        ));
        
        $currency_symbol = get_option('dl_currency_symbol', 'Kč');
        ?>
        <p>
            <strong><?php _e('Total Sales:', 'developer-lessons'); ?></strong>
            <span><?php echo intval($stats->sales); ?></span>
        </p>
        <p>
            <strong><?php _e('Total Revenue:', 'developer-lessons'); ?></strong>
            <span><?php echo number_format((float)$stats->revenue, 2); ?> <?php echo esc_html($currency_symbol); ?></span>
        </p>
        <?php
    }

   /**
     * Save lesson meta
     */
    public function save_lesson_meta($post_id, $post) {
        // Check if this is a lesson
        if ($post->post_type !== 'lesson') {
            return;
        }

        // Check nonce for settings
        if (isset($_POST['dl_lesson_settings_nonce']) && 
            wp_verify_nonce($_POST['dl_lesson_settings_nonce'], 'dl_lesson_settings')) {
            
            // Check autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            // Check permissions
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            // Save access type
            if (isset($_POST['dl_access_type'])) {
                update_post_meta($post_id, '_dl_access_type', sanitize_text_field($_POST['dl_access_type']));
            }

            // Save price
            if (isset($_POST['dl_price'])) {
                update_post_meta($post_id, '_dl_price', floatval($_POST['dl_price']));
            }

            // Save teaser content
            if (isset($_POST['dl_teaser_content'])) {
                update_post_meta($post_id, '_dl_teaser_content', wp_kses_post($_POST['dl_teaser_content']));
            }
        }

        // Check nonce for tile image
        if (isset($_POST['dl_lesson_tile_image_nonce']) && 
            wp_verify_nonce($_POST['dl_lesson_tile_image_nonce'], 'dl_lesson_tile_image')) {
            
            if (isset($_POST['dl_tile_image_id'])) {
                $image_id = intval($_POST['dl_tile_image_id']);
                if ($image_id > 0) {
                    update_post_meta($post_id, '_dl_tile_image_id', $image_id);
                } else {
                    delete_post_meta($post_id, '_dl_tile_image_id');
                }
            }
        }
    }


    /**
     * Add custom columns
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['dl_price'] = __('Price', 'developer-lessons');
                $new_columns['dl_sales'] = __('Sales', 'developer-lessons');
            }
        }
        
        return $new_columns;
    }

    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        global $wpdb;
        
        $currency_symbol = get_option('dl_currency_symbol', 'Kč');
        $currency_position = get_option('dl_currency_position', 'after');
        
        switch ($column) {
            case 'dl_price':
                $access_type = get_post_meta($post_id, '_dl_access_type', true);
                
                if ($access_type === 'free') {
                    echo '<span class="dl-free-badge">' . __('Free', 'developer-lessons') . '</span>';
                } else {
                    $price = get_post_meta($post_id, '_dl_price', true);
                    if ($currency_position === 'before') {
                        echo esc_html($currency_symbol . ' ' . number_format((float)$price, 2));
                    } else {
                        echo esc_html(number_format((float)$price, 2) . ' ' . $currency_symbol);
                    }
                }
                break;
                
            case 'dl_sales':
                $purchases_table = $wpdb->prefix . 'dl_purchases';
                $sales = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $purchases_table WHERE lesson_id = %d",
                    $post_id
                ));
                echo intval($sales);
                break;
        }
    }
    /**
     * Enqueue media scripts for tile image selector
     */
    public function enqueue_media_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'lesson' && in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_media();
        }
    }
}
