<?php
/**
 * Theme functions for mi-tema
 */

// Create custom table on theme activation
function mitema_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'mi_mensajes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        area varchar(100) NOT NULL,
        mensaje text NOT NULL,
        likes int(11) NOT NULL DEFAULT 0,
        liked_users text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'mitema_create_table');

// Handle form submissions
function mitema_handle_form_submission() {
    if (isset($_POST['mi_mensaje_submit']) && check_admin_referer('mi_mensaje_form', 'mi_mensaje_nonce')) {
        global $wpdb;
        $table = $wpdb->prefix . 'mi_mensajes';
        $name = sanitize_text_field($_POST['mi_nombre']);
        $area = sanitize_text_field($_POST['mi_area']);
        $mensaje = sanitize_textarea_field($_POST['mi_mensaje']);

        $wpdb->insert($table, [
            'nombre' => $name,
            'area' => $area,
            'mensaje' => $mensaje,
            'likes' => 0,
            'liked_users' => ''
        ], [
            '%s', '%s', '%s', '%d', '%s'
        ]);
    }
}
add_action('init', 'mitema_handle_form_submission');

// Handle likes
function mitema_handle_like() {
    if (isset($_POST['mi_like_id']) && check_admin_referer('mi_like_action', 'mi_like_nonce') && is_user_logged_in()) {
        $id = intval($_POST['mi_like_id']);
        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'mi_mensajes';
        $entry = $wpdb->get_row($wpdb->prepare("SELECT liked_users, likes FROM $table WHERE id=%d", $id));

        if ($entry) {
            $liked_users = $entry->liked_users ? explode(',', $entry->liked_users) : [];
            if (!in_array($user_id, $liked_users)) {
                $liked_users[] = $user_id;
                $wpdb->update($table, [
                    'likes' => $entry->likes + 1,
                    'liked_users' => implode(',', $liked_users)
                ], [ 'id' => $id ], [ '%d', '%s' ], [ '%d' ]);
            }
        }
    }
}
add_action('init', 'mitema_handle_like');

// Shortcode to display form
function mitema_form_shortcode() {
    ob_start();
    ?>
    <form method="post">
        <p>
            <label>Nombre</label><br />
            <input type="text" name="mi_nombre" required />
        </p>
        <p>
            <label>Área</label><br />
            <select name="mi_area">
                <option value="Tecnolog\xEDa">Tecnolog\xEDa</option>
                <option value="Zonas comunas">Zonas comunas</option>
            </select>
        </p>
        <p>
            <label>Mensaje</label><br />
            <textarea name="mi_mensaje" required></textarea>
        </p>
        <?php wp_nonce_field('mi_mensaje_form', 'mi_mensaje_nonce'); ?>
        <p>
            <input type="submit" name="mi_mensaje_submit" value="Enviar" />
        </p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('mi_mensaje_form', 'mitema_form_shortcode');

// Shortcode to display messages
function mitema_display_mensajes() {
    global $wpdb;
    $table = $wpdb->prefix . 'mi_mensajes';
    $entries = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");

    ob_start();
    if ($entries) {
        foreach ($entries as $e) {
            $likes = intval($e->likes);
            $liked_users = $e->liked_users ? explode(',', $e->liked_users) : [];
            $already_liked = is_user_logged_in() && in_array(get_current_user_id(), $liked_users);
            ?>
            <div class="mi-mensaje">
                <h3><?php echo esc_html($e->nombre); ?></h3>
                <p><strong>Área:</strong> <?php echo esc_html($e->area); ?></p>
                <p><?php echo esc_html($e->mensaje); ?></p>
                <p>Likes: <?php echo $likes; ?></p>
                <?php if (is_user_logged_in()) : ?>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="mi_like_id" value="<?php echo intval($e->id); ?>" />
                        <?php wp_nonce_field('mi_like_action', 'mi_like_nonce'); ?>
                        <button type="submit" <?php disabled($already_liked); ?>>Me gusta</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php
        }
    } else {
        echo '<p>No hay mensajes.</p>';
    }
    return ob_get_clean();
}
add_shortcode('mi_mensajes', 'mitema_display_mensajes');
