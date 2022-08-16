<?php
/**
 * @var string $logsDir
 * @see \WPStaging\Backend\Notices\Notices::messages
 */
?>
<div class="notice notice-error">
    <p><strong><?php _e('WP STAGING - Folder Permission error.', 'wp-staging'); ?></strong>
        <br>
        <?php echo sprintf(esc_html__('The folder %s is not write and/or readable.', 'wp-staging'), '<code>' . esc_html($logsDir) . '</code>'); ?>
        <br>
        <?php _e('Check if this folder exists! Folder permissions should be chmod 755 or higher.', 'wp-staging'); ?>
    </p>
</div>
