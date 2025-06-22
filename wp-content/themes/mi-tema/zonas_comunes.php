<?php 

// Handle form submissions
function mitema_handle_form_submission() {
   
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
        }
    } else {
        echo '<p>No hay mensajes.</p>';
    }
    return ob_get_clean();
}
add_shortcode('mi_mensajes', 'mitema_display_mensajes');

