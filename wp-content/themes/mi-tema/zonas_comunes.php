<?php 

// Función para crear la tabla de mensajes
function create_zonas_comunes_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mi_mensajes';
    
    // Verificar si la tabla ya existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            area varchar(100) NOT NULL,
            mensaje text NOT NULL,
            likes int(11) DEFAULT 0,
            liked_users text,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_area (area),
            KEY idx_likes (likes),
            KEY idx_fecha (fecha)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Marcar que la tabla fue creada
        add_option('zonas_comunes_table_created', '1');
    }
}

// Hook más transparente - se ejecuta cuando WordPress se inicializa completamente
function zonas_comunes_setup_database() {
    if (!get_option('zonas_comunes_table_created')) {
        create_zonas_comunes_table();
    }
}
add_action('wp_loaded', 'zonas_comunes_setup_database');

// También crear la tabla cuando se ejecute cualquier funcionalidad relacionada (fallback)
function zonas_comunes_ensure_table_exists() {
    if (!get_option('zonas_comunes_table_created')) {
        create_zonas_comunes_table();
    }
}

// Handle form submissions
function FormZonasComunesSubmission() {
    // Asegurar que la tabla existe antes de procesar
    zonas_comunes_ensure_table_exists();
    
    // Verificar nonce para seguridad
    if (!wp_verify_nonce($_POST['zonas_comunes_nonce'], 'zonas_comunes_action')) {
        wp_die('Error de seguridad');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'mi_mensajes';
    $name = sanitize_text_field($_POST['mi_nombre']);
    $area = sanitize_text_field($_POST['mi_area']);
    $mensaje = sanitize_textarea_field($_POST['mi_mensaje']);

    $result = $wpdb->insert($table, [
        'nombre' => $name,
        'area' => $area,
        'mensaje' => $mensaje,
        'likes' => 0,
        'liked_users' => ''
    ], [
        '%s', '%s', '%s', '%d', '%s'
    ]);

    if ($result !== false) {
        $inserted_id = $wpdb->insert_id;
        
        // Generar URL del PDF
        $pdf_url = add_query_arg([
            'action' => 'generate_zonas_comunes_pdf',
            'id' => $inserted_id,
            'nonce' => wp_create_nonce('pdf_nonce_' . $inserted_id)
        ], admin_url('admin-ajax.php'));
        
        wp_send_json_success([
            'message' => 'Mensaje enviado correctamente',
            'pdf_url' => $pdf_url
        ]);
    } else {
        wp_send_json_error('Error al enviar el mensaje');
    }
}

// Handle likes
function mitema_handle_like() {
    if (isset($_POST['mi_like_id']) && check_admin_referer('mi_like_action', 'mi_like_nonce') && is_user_logged_in()) {
        // Asegurar que la tabla existe
        zonas_comunes_ensure_table_exists();
        
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
    // Asegurar que la tabla existe
    zonas_comunes_ensure_table_exists();
    
    global $wpdb;
    $table = $wpdb->prefix . 'mi_mensajes';
    $entries = $wpdb->get_results("SELECT * FROM $table ORDER BY likes DESC, id DESC");

    ob_start();
    if ($entries) {
        echo '<div class="mi-mensajes">';
        foreach ($entries as $e) {
            $likes = intval($e->likes);
            $liked_users = $e->liked_users ? explode(',', $e->liked_users) : [];
            $already_liked = is_user_logged_in() && in_array(get_current_user_id(), $liked_users);

            echo '<div class="mi-mensaje">';
            echo '<p><strong>' . esc_html($e->nombre) . ' (' . esc_html($e->area) . '):</strong> ' . esc_html($e->mensaje) . '</p>';

            echo '<div class="mensaje-actions">';
            if ($already_liked) {
                echo '<span class="heart liked">&#x2764;</span>';
                echo '<span class="like-count">' . $likes . '</span>';
            } else {
                echo '<form method="post" class="like-form">';
                wp_nonce_field('mi_like_action', 'mi_like_nonce');
                echo '<input type="hidden" name="mi_like_id" value="' . intval($e->id) . '" />';
                echo '<button type="submit" class="heart">&#x2764;</button>';
                echo '<span class="like-count">' . $likes . '</span>';
                echo '</form>';
            }
            
            // Agregar botón de PDF
            $pdf_url = add_query_arg([
                'action' => 'generate_zonas_comunes_pdf',
                'id' => $e->id,
                'nonce' => wp_create_nonce('pdf_nonce_' . $e->id)
            ], admin_url('admin-ajax.php'));
            
            echo '<a href="' . esc_url($pdf_url) . '" target="_blank" class="pdf-btn">📄 PDF</a>';
            echo '</div>';

            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>No hay mensajes.</p>';
    }
    return ob_get_clean();
}
add_shortcode('mi_mensajes', 'mitema_display_mensajes');

// Shortcode para mostrar el formulario
function zonas_comunes_form_shortcode() {
    ob_start();
    ?>
    <form id="zonas-comunes-form">
        <div class="form-group">
            <label for="mi_nombre">Nombre:</label>
            <input type="text" id="mi_nombre" name="mi_nombre" required>
        </div>
        
        <div class="form-group">
            <label for="mi_area">Área:</label>
            <select id="mi_area" name="mi_area" required>
                <option value="">Selecciona un área</option>
                <option value="Piscina">Piscina</option>
                <option value="Gimnasio">Gimnasio</option>
                <option value="Salón Social">Salón Social</option>
                <option value="Parqueadero">Parqueadero</option>
                <option value="Otro">Otro</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="mi_mensaje">Mensaje:</label>
            <textarea id="mi_mensaje" name="mi_mensaje" rows="4" required></textarea>
        </div>
        
        <button type="submit">Enviar Mensaje</button>
        <div id="form-response"></div>
    </form>

    <script>
    jQuery(document).ready(function($) {
        $('#zonas-comunes-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'FormZonasComunesSubmission',
                mi_nombre: $('#mi_nombre').val(),
                mi_area: $('#mi_area').val(),
                mi_mensaje: $('#mi_mensaje').val(),
                zonas_comunes_nonce: zonas_comunes_ajax.nonce
            };
            
            $('button[type="submit"]').prop('disabled', true).text('Enviando...');
            
            $.ajax({
                url: zonas_comunes_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    var responseDiv = $('#form-response');
                    
                    if (response.success) {
                        var message = response.data.message || response.data;
                        var pdfUrl = response.data.pdf_url;
                        
                        var successMessage = message;
                        if (pdfUrl) {
                            successMessage += '<br><br><a href="' + pdfUrl + '" target="_blank" class="pdf-download-btn">📄 Descargar PDF del mensaje</a>';
                        }
                        
                        responseDiv.removeClass('error').addClass('success')
                                  .html(successMessage).show();
                        $('#zonas-comunes-form')[0].reset();
                    } else {
                        responseDiv.removeClass('success').addClass('error')
                                  .text(response.data).show();
                    }
                },
                error: function() {
                    $('#form-response').removeClass('success').addClass('error')
                                       .text('Error de conexión. Inténtalo de nuevo.').show();
                },
                complete: function() {
                    $('button[type="submit"]').prop('disabled', false).text('Enviar Mensaje');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('zonas_comunes_form', 'zonas_comunes_form_shortcode');

add_action('wp_ajax_nopriv_FormZonasComunesSubmission', 'FormZonasComunesSubmission');
add_action('wp_ajax_FormZonasComunesSubmission', 'FormZonasComunesSubmission');

// Enqueue scripts and styles
function zonas_comunes_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'zonas_comunes_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('zonas_comunes_action')
    ]);
}
add_action('wp_enqueue_scripts', 'zonas_comunes_enqueue_scripts');

// Agregar estilos CSS
function zonas_comunes_add_styles() {
    echo '<style>
    #zonas-comunes-form .form-group {
        margin-bottom: 15px;
    }

    #zonas-comunes-form .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    #zonas-comunes-form .form-group input,
    #zonas-comunes-form .form-group select,
    #zonas-comunes-form .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    #zonas-comunes-form button[type="submit"] {
        background-color: #007cba;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }

    #zonas-comunes-form button[type="submit"]:hover {
        background-color: #005a87;
    }

    #zonas-comunes-form button[type="submit"]:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    #form-response {
        margin-top: 10px;
        padding: 10px;
        border-radius: 4px;
        display: none;
    }

    #form-response.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    #form-response.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .pdf-download-btn {
        display: inline-block;
        background-color: #dc3545;
        color: white !important;
        padding: 8px 15px;
        text-decoration: none !important;
        border-radius: 4px;
        font-weight: bold;
        margin-top: 10px;
        transition: background-color 0.3s;
    }

    .pdf-download-btn:hover {
        background-color: #c82333;
        color: white !important;
    }

    .mi-mensajes {
        margin-top: 20px;
    }

    .mi-mensaje {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        position: relative;
    }

    .mi-mensaje:first-child {
        border-left: 4px solid #28a745;
        background-color: #f0f8f0;
    }

    .heart {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        padding: 5px;
        margin-right: 5px;
    }

    .heart.liked {
        color: #dc3545;
    }

    .like-count {
        font-weight: bold;
        color: #666;
    }

    .like-form {
        display: inline-block;
        margin-top: 10px;
    }

    .mensaje-actions {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #e9ecef;
    }

    .pdf-btn {
        background-color: #6c757d;
        color: white !important;
        padding: 5px 10px;
        text-decoration: none !important;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        transition: background-color 0.3s;
    }

    .pdf-btn:hover {
        background-color: #5a6268;
        color: white !important;
    }
    </style>';
}
add_action('wp_head', 'zonas_comunes_add_styles');

// Función para generar PDF del mensaje
function generate_zonas_comunes_pdf() {
    // Asegurar que la tabla existe
    zonas_comunes_ensure_table_exists();
    
    // Verificar nonce
    $id = intval($_GET['id']);
    if (!wp_verify_nonce($_GET['nonce'], 'pdf_nonce_' . $id)) {
        wp_die('Error de seguridad');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'mi_mensajes';
    $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));
    
    if (!$entry) {
        wp_die('Mensaje no encontrado');
    }

    // Contenido HTML del PDF
    $html_content = generate_pdf_html_content($entry);
    
    // Si existe el plugin WP-PDF, usarlo
    if (function_exists('wp_print')) {
        wp_print($html_content);
    }
    
    // Si no hay plugin de PDF, mostrar HTML formateado
    echo $html_content;
    exit;
}

// Función para generar el contenido HTML del PDF
function generate_pdf_html_content($entry) {
    $fecha = date('d/m/Y H:i', strtotime($entry->fecha ?? 'now'));
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Mensaje - Zonas Comunes</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                color: #333;
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #007cba;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #007cba;
                margin: 0;
                font-size: 24px;
            }
            .header h2 {
                color: #666;
                margin: 5px 0 0 0;
                font-size: 16px;
                font-weight: normal;
            }
            .info-section {
                background-color: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                border-left: 4px solid #007cba;
            }
            .info-row {
                display: flex;
                margin-bottom: 10px;
                align-items: flex-start;
            }
            .info-label {
                font-weight: bold;
                min-width: 120px;
                color: #007cba;
            }
            .info-value {
                flex: 1;
                padding-left: 10px;
            }
            .message-content {
                background-color: #fff;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                margin-top: 20px;
                line-height: 1.6;
            }
            .stats-section {
                background-color: #e8f4f8;
                padding: 15px;
                border-radius: 8px;
                margin-top: 20px;
                text-align: center;
            }
            .likes-count {
                font-size: 18px;
                font-weight: bold;
                color: #dc3545;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 15px;
            }
            .qr-section {
                text-align: center;
                margin-top: 20px;
                padding: 15px;
                background-color: #f0f8ff;
                border-radius: 8px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>REPORTE DE MENSAJE</h1>
            <h2>Sistema de Zonas Comunes</h2>
        </div>
        
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">ID del Mensaje:</span>
                <span class="info-value">#' . str_pad($entry->id, 4, '0', STR_PAD_LEFT) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Nombre del Usuario:</span>
                <span class="info-value">' . esc_html($entry->nombre) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Área:</span>
                <span class="info-value">' . esc_html($entry->area) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de Envío:</span>
                <span class="info-value">' . $fecha . '</span>
            </div>
        </div>
        
        <div class="message-content">
            <h3 style="color: #007cba; margin-top: 0;">Contenido del Mensaje:</h3>
            <p>' . nl2br(esc_html($entry->mensaje)) . '</p>
        </div>
        
        <div class="stats-section">
            <h4 style="margin-top: 0; color: #007cba;">Estadísticas de Interacción</h4>
            <div class="likes-count">❤️ ' . intval($entry->likes) . ' likes</div>
            <p style="margin: 5px 0 0 0; font-size: 14px;">Total de usuarios que han dado like a este mensaje</p>
        </div>
        
        <div class="qr-section">
            <p style="margin: 0; font-size: 14px;"><strong>Código de Referencia:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">ZC-' . date('Ymd') . '-' . str_pad($entry->id, 4, '0', STR_PAD_LEFT) . '</p>
        </div>
        
        <div class="footer">
            <p>Documento generado automáticamente el ' . date('d/m/Y \a \l\a\s H:i') . '</p>
            <p>Sistema de Gestión de Zonas Comunes - WordPress</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Registrar la acción AJAX para generar PDF
add_action('wp_ajax_generate_zonas_comunes_pdf', 'generate_zonas_comunes_pdf');
add_action('wp_ajax_nopriv_generate_zonas_comunes_pdf', 'generate_zonas_comunes_pdf');
