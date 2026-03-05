<?php
/**
 * Settings page template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap dl-admin-wrap">
    <h1><?php _e('Developer Lessons Settings', 'developer-lessons'); ?></h1>

    <nav class="nav-tab-wrapper">
        <?php foreach ($this->tabs as $tab_id => $tab_name): ?>
            <a href="<?php echo admin_url('admin.php?page=dl-settings&tab=' . $tab_id); ?>" 
               class="nav-tab <?php echo $this->current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="dl-settings-content">
        <?php
        if (isset($_GET['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved.', 'developer-lessons') . '</p></div>';
        }

        $this->render_tab_content();
        ?>
    </div>
</div>
