<?php 

function create_buzon_sugerencias_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'buzon_sugerencias';
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
        add_option('buzon_sugerencias_table_created', '1');
    }
}

function buzon_sugerencias_setup_database() {
    if (!get_option('buzon_sugerencias_table_created')) {
        create_buzon_sugerencias_table();
    }
}
add_action('wp_loaded', 'buzon_sugerencias_setup_database');

function buzon_sugerencias_ensure_table_exists() {
    if (!get_option('buzon_sugerencias_table_created')) {
        create_buzon_sugerencias_table();
    }
}

function buzon_sugerencias_display($offset) {
    buzon_sugerencias_ensure_table_exists();
    global $wpdb;
    $table = $wpdb->prefix . 'buzon_sugerencias';

    // Orden híbrido - solo las de HOY sin likes primero, luego por likes
    $fecha_hoy = date('Y-m-d'); // Fecha actual en formato YYYY-MM-DD
    
    $sugerencias = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table 
        ORDER BY 
            CASE 
                WHEN likes = 0 AND DATE(fecha) = %s THEN fecha 
                ELSE '1970-01-01' 
            END DESC,
            likes DESC, 
            id DESC
			LIMIT 10
			OFFSET %d
    ", array($fecha_hoy, intval($offset))));
    ob_start();
    ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Titillium+Web:wght@300;400;600;700&family=Roboto:wght@300;400;500;700&family=Suez+One&display=swap');
		
    .buzon-sugerencias-container {
        max-width: 1000px;
        margin: 0 auto;
		margin-top: 80px;
        padding: 20px;
        font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    
    .buzon-header {
		text-align: center;
		margin-bottom: 30px;
		padding: 100px 20px;
		background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 25%, #ffffff 50%, #f8f8f8 75%, #ffffff 100%);
		border-radius: 15px 15px 0 0;
		color: #DE3329;
		box-shadow: 
			inset 0 3px 6px rgba(255, 255, 255, 1), 
			inset 0 -3px 6px rgba(0, 0, 0, 0.08);
		border: none;
	}
		
    .buzon-header h2 {
        margin: 0 0 10px 0;
        font-size: 2.2em;
        font-weight: 600;
        font-family: 'Titillium Web', sans-serif;
    }
    
    .buzon-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1em;
        font-family: 'Roboto', sans-serif;
    }
    
    .buzon-filtros {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        margin-bottom: 25px;
        border: 1px solid #EBECEC;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .filtro-section {
        flex: 1;
        min-width: 250px;
    }
    
    .buzon-filtros label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #3d4543;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-family: 'Titillium Web', sans-serif;
    }
    
    .buzon-filtros select {
        width: 100%;
		padding: 12px 16px;
		border: 2px solid #EBECEC;
		border-radius: 8px;
		font-size: 16px;
		transition: all 0.3s ease;
		font-family: 'Roboto', sans-serif;
		cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%233d4543' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 40px;
        appearance: none;
    }
    
    .buzon-filtros select:focus {
        outline: none;
        border-color: #3d4543;
        box-shadow: 0 0 0 3px rgba(61, 69, 67, 0.1);
    }
    
    .sugerencias-stats {
        background: white;
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        margin-bottom: 25px;
        border: 1px solid #EBECEC;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .stats-item {
        text-align: center;
        flex: 1;
        min-width: 120px;
    }
    
    .stats-number {
        font-size: 2em;
        font-weight: 700;
        color: #3d4543;
        display: block;
        font-family: 'Suez One', serif;
    }
    
    .stats-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 500;
        font-family: 'Roboto', sans-serif;
    }
    
    .top-section-wrapper {
		background: white;
		border-radius: 16px;
		padding: 0;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
		border: 1px solid #EBECEC;
		margin-bottom: 50px;
		overflow: hidden;
	}

	.top-section-wrapper .buzon-header {
		margin-bottom: 0;
		border-radius: 0;
	}

	.top-section-wrapper .sugerencias-stats {
		margin: 0;
		box-shadow: none;
		border: none;
		border-top: 1px solid #EBECEC;
		border-radius: 0;
	}

	.filters-row {
		padding: 20px;
		border-top: 1px solid #EBECEC;
		background: #fafbfc;
	}

	.filters-row .buzon-filtros {
		margin: 0;
		box-shadow: none;
		border: none;
		background: transparent;
		padding: 0;
	}

	.comments-pdf-btn {
		background: #3d4543;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        opacity: 0.8;
        font-family: 'Roboto', sans-serif;
	}

	.comments-pdf-btn:hover {
		opacity: 1;
        transform: translateY(-1px);
        text-decoration: none;
        color: white;
        background: #2a2f2d;
	}
		
    .buzon-sugerencias {
        display: grid;
        gap: 20px;
        margin-top: 20px;
    }
    
    .buzon-mensaje {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #EBECEC;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .buzon-mensaje:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .buzon-mensaje::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: #3d4543;
    }
    
    /* NUEVOS ESTILOS PARA ANIMACIONES DINÁMICAS */
    .buzon-mensaje.nueva-sugerencia {
        animation: slideInFromTop 0.8s ease-out;
        border-left: 5px solid #28a745;
    }
    
    .buzon-mensaje.nueva-sugerencia::before {
        background: #28a745;
    }
    
    @keyframes slideInFromTop {
        0% {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }
        50% {
            opacity: 0.7;
            transform: translateY(-10px) scale(0.98);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .stats-item.updating {
        animation: statsUpdate 0.6s ease;
    }
    
    @keyframes statsUpdate {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); color: #28a745; }
        100% { transform: scale(1); }
    }
    
    .nueva-badge {
        position: absolute;
        top: 10px;
        right: 15px;
        background: #28a745;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        animation: pulseNew 2s ease-in-out 3;
        font-family: 'Roboto', sans-serif;
    }
    
    @keyframes pulseNew {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    /* Indicador de zona de nuevas sugerencias */
	.zona-nuevas-separator {
		border-top: 2px dashed #3D4543;
		margin: 20px 0 100px 0;
		position: relative;
		opacity: 0.6;
	}

	.zona-nuevas-separator::before {
		content: "⬆️ Sugerencias nuevas";
		position: absolute;
		top: -12px;
		left: 50%;
		transform: translateX(-50%);
		background: white;
		padding: 0 15px;
		font-size: 14px;
		color: #3D4543;
		font-weight: 600;
		font-family: 'Roboto', sans-serif;
	}

	@media (max-width: 768px) {
		.zona-nuevas-separator::before {
			content: "⬆️ Hoy";
			font-size: 10px;
			padding: 0 10px;
		}
	}
    
    /* Mensaje para lista vacía */
    .no-sugerencias-wrapper {
        transition: all 0.5s ease;
    }
    
    .no-sugerencias-wrapper.fade-out {
        opacity: 0;
        transform: translateY(-20px);
    }
    
    .mensaje-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .mensaje-author {
        font-weight: 600;
        color: #3d4543;
        font-size: 16px;
        font-family: 'Roboto', sans-serif;
    }
    
    .mensaje-area {
        background: #f8f9fa;
        color: #495057;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-family: 'Roboto', sans-serif;
    }
    
    .mensaje-fecha {
        color: #6c757d;
        font-size: 12px;
        margin-left: auto;
        font-family: 'Roboto', sans-serif;
    }
    
    .mensaje-content {
        color: #3d4543;
        line-height: 1.6;
        margin-bottom: 20px;
        font-size: 15px;
        font-family: 'Roboto', sans-serif;
    }
    
    .mensaje-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 15px;
        border-top: 1px solid #EBECEC;
    }
    
    .like-section {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* ESTILOS ACTUALIZADOS PARA LIKES/UNLIKES */
    .heart {
        background: white;
        border: 2px solid #EBECEC;
        color: #ff6b6b;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        position: relative;
    }
    
    .heart:hover {
        transform: scale(1.1);
        border-color: #ff6b6b;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.2);
        background: #fff5f5;
    }
    
    .heart.liked {
        background: #ffc3c3;
        border-color: #ff6b6b;
        color: white;
        box-shadow: 0 2px 10px rgba(255, 107, 107, 0.3);
    }
    
    .heart.liked:hover {
        background: #ff5252;
        border-color: #ff5252;
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
    }
    
    /* Tooltip para unlike */
    .heart.liked::after {
        content: "Clic para quitar like";
        position: absolute;
        bottom: -35px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        z-index: 1000;
        font-family: 'Roboto', sans-serif;
    }
    
    .heart.liked:hover::after {
        opacity: 1;
    }
    
    .like-count {
        font-weight: 600;
        color: #3d4543;
        font-size: 16px;
        font-family: 'Roboto', sans-serif;
        transition: all 0.3s ease;
    }
    
    .like-form {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Animación de cambio de likes */
    .like-count.updating {
        animation: likeUpdate 0.6s ease;
    }
    
    @keyframes likeUpdate {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); color: #ff6b6b; }
        100% { transform: scale(1); }
    }
    
    /* Colores específicos por área - NO CAMBIAR */
    .area-espacio-de-trabajo .mensaje-area {
        background: linear-gradient(135deg, #007cba, #0056b3);
        color: white;
    }
    
    .area-tecnologia .mensaje-area {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    .area-zonas-comunes .mensaje-area {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
    }
    
    .area-otras .mensaje-area {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
    }
    
    .no-sugerencias {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .no-sugerencias-icon {
        font-size: 4em;
        margin-bottom: 20px;
        opacity: 0.3;
    }
    
    .no-sugerencias h3 {
        color: #3d4543;
        margin: 0 0 10px 0;
        font-family: 'Titillium Web', sans-serif;
    }
    
    .no-sugerencias p {
        color: #9ca3af;
        margin: 0;
        font-family: 'Roboto', sans-serif;
    }
    
    /* Footer con botones de descarga */
    .footer-downloads {
        background: #EBECEC;
        padding: 20px;
        border-radius: 12px;
        margin-top: 40px;
        text-align: center;
        border: 1px solid #EBECEC;
    }
    
    .footer-downloads h4 {
        color: #3d4543;
        margin: 0 0 20px 0;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        font-family: 'Titillium Web', sans-serif;
    }
    
    .download-buttons {
        display: flex;
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }
    
    .full-report-btn {
        background: #3d4543;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        opacity: 0.8;
        font-family: 'Roboto', sans-serif;
    }
    
    .full-report-btn:hover {
        opacity: 1;
        transform: translateY(-1px);
        text-decoration: none;
        color: white;
        background: #2a2f2d;
    }
		
	/* BOTONES DE DESCARGA DESHABILITADOS */
	.comments-pdf-btn.disabled,
	.full-report-btn.disabled {
		opacity: 0.9;
		cursor: not-allowed;
		background: #e0e0e0 !important;
	}
    
    @media (max-width: 768px) {
        .buzon-sugerencias-container {
            padding: 15px;
        }
        
        .buzon-header {
            padding: 15px;
            text-align: center;
        }
        
        .buzon-header h2 {
            font-size: 1.8em;
        }
        
        .buzon-mensaje {
            padding: 20px;
        }
        
        .mensaje-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .mensaje-fecha {
            margin-left: 0;
        }
        
        .buzon-filtros {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filtro-section {
            width: 100%;
        }
        
        .sugerencias-stats {
            flex-direction: column;
            gap: 10px;
        }
        
        .stats-item {
            min-width: auto;
        }
        
        .comments-pdf-btn {
            font-size: 14px;
            padding: 12px 25px;
            width: 100%;
            justify-content: center;
        }
        
        .download-buttons {
            gap: 10px;
        }
        
        .full-report-btn {
            width: 100%;
            justify-content: center;
        }
        
        /* Tooltip responsive */
        .heart.liked::after {
            font-size: 10px;
            padding: 3px 6px;
        }
        
        .zona-nuevas-separator::before {
            font-size: 10px;
            padding: 0 10px;
        }
    }
    </style>
    
    <script>
	document.addEventListener("DOMContentLoaded", function () {
    const filtro = document.getElementById("filtro-area");
    const mensajes = document.querySelectorAll(".buzon-mensaje");
    
    if (filtro) {
        filtro.addEventListener("change", function () {
            const areaSeleccionada = filtro.value.toLowerCase();
            
            mensajes.forEach(msg => {
                const areaClass = msg.className.toLowerCase();
                if (areaSeleccionada === "" || areaClass.includes(areaSeleccionada)) {
                    msg.style.display = "block";
                    msg.style.animation = "fadeIn 0.3s ease";
                } else {
                    msg.style.display = "none";
                }
            });
            
            // También aplicar filtro a separadores
            document.querySelectorAll('.zona-nuevas-separator').forEach(sep => {
                // Ocultar separador si está filtrando
                sep.style.display = areaSeleccionada === "" ? "block" : "none";
            });
        });
    }
    
    // Animación de entrada
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    mensajes.forEach(msg => {
        msg.style.opacity = '0';
        msg.style.transform = 'translateY(20px)';
        msg.style.transition = 'all 0.6s ease';
        observer.observe(msg);
    });

    // Animación en likes
    const likeButtons = document.querySelectorAll('.heart');
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const likeCount = this.closest('.like-section').querySelector('.like-count');
            if (likeCount) {
                likeCount.classList.add('updating');
                setTimeout(() => {
                    likeCount.classList.remove('updating');
                }, 600);
            }
        });
    });

    // FUNCIÓN: Manejar filtros para elementos dinámicos
    window.aplicarFiltroActual = function(nuevoElemento) {
        if (!filtro) return;
        
        const areaSeleccionada = filtro.value.toLowerCase();
        if (areaSeleccionada === "") {
            nuevoElemento.style.display = "block";
            return;
        }
        
        const areaClass = nuevoElemento.className.toLowerCase();
        if (areaClass.includes(areaSeleccionada)) {
            nuevoElemento.style.display = "block";
        } else {
            nuevoElemento.style.display = "none";
        }
    };

    // FUNCIÓN: Actualizar estadísticas
    window.actualizarEstadisticas = function(nuevaArea) {
        const totalElement = document.querySelector('.stats-item:first-child .stats-number');
        if (totalElement) {
            const currentTotal = parseInt(totalElement.textContent);
            totalElement.textContent = currentTotal + 1;
            totalElement.parentElement.classList.add('updating');
            setTimeout(() => {
                totalElement.parentElement.classList.remove('updating');
            }, 600);
        }
    };

    // FUNCIÓN: Crear separador entre zonas
    window.crearSeparadorZonas = function() {
        const listaSugerencias = document.getElementById('lista-sugerencias');
        if (!listaSugerencias) return;

        // Obtener fecha de hoy en formato YYYY-MM-DD
        const fechaHoy = new Date().toISOString().split('T')[0];

        // Buscar primera sugerencia que NO sea de hoy con 0 likes, o que tenga likes > 0
        const sugerencias = listaSugerencias.querySelectorAll('.buzon-mensaje');
        let primeraSinHoy = null;

        sugerencias.forEach(sugerencia => {
            const likeCountElement = sugerencia.querySelector('.like-count');
            const likes = likeCountElement ? parseInt(likeCountElement.textContent || '0') : 0;
           			
            const fechaElement = sugerencia.querySelector('.mensaje-fecha');
            const fechaElemento = fechaElement ? fechaElement.textContent || '' : '';
            
            // Extraer fecha del formato "📅 DD/MM/YYYY HH:MM"
            const fechaMatch = fechaElemento.match(/(\d{2})\/(\d{2})\/(\d{4})/);
            let esDehoy = false;
            
            if (fechaMatch) {
                const dia = fechaMatch[1];
                const mes = fechaMatch[2];
                const año = fechaMatch[3];
                const fechaFormateada = año + '-' + mes + '-' + dia;
                esDehoy = fechaFormateada === fechaHoy;
            }
			
            // Si tiene likes > 0 O no es de hoy, es candidata para separador
            if ((likes > 0 || !esDehoy) && !primeraSinHoy) {
                primeraSinHoy = sugerencia;
            }
        });

        // Si hay sugerencias que no son "nuevas de hoy", crear separador
        if (primeraSinHoy) {
            const separadorExistente = document.querySelector('.zona-nuevas-separator');
            if (!separadorExistente) {
                const separador = document.createElement('div');
                separador.className = 'zona-nuevas-separator';
                primeraSinHoy.parentNode.insertBefore(separador, primeraSinHoy);
            }
        }
    };

    // Crear separador inicial si es necesario
    window.crearSeparadorZonas();
});
</script>
    
    <div class="buzon-sugerencias-container">
        <!-- CONTENEDOR SUPERIOR: Mismo ancho que los comentarios -->
        <div class="top-section-wrapper">
            <div class="buzon-header">
                <h2>💡 Buzón de Ideas</h2>
                <p>Comparte tus sugerencias para mejorar nuestro espacio de trabajo</p>
            </div>
            
            <?php if ($sugerencias): 
                // Calcular estadísticas
                $total_sugerencias = count($sugerencias);
                $total_likes = array_sum(array_column($sugerencias, 'likes'));
                $areas = array_count_values(array_column($sugerencias, 'area'));
                $area_mas_popular = $areas ? array_keys($areas, max($areas))[0] : 'N/A';
            ?>
            
            <div class="sugerencias-stats">
                <div class="stats-item">
                    <span class="stats-number"><?php echo $total_sugerencias; ?></span>
                    <span class="stats-label">Sugerencias</span>
                </div>
                <div class="stats-item">
                    <span class="stats-number"><?php echo $total_likes; ?></span>
                    <span class="stats-label">Total Likes</span>
                </div>
                <div class="stats-item">
                    <span class="stats-number"><?php echo count($areas); ?></span>
                    <span class="stats-label">Categorías Activas</span>
                </div>
                <div class="stats-item">
                    <span class="stats-number" style="font-size: 1.2em;"><?php echo esc_html($area_mas_popular); ?></span>
                    <span class="stats-label">Categoría Más Popular</span>
                </div>
            </div>
            
            <!-- FILA SOLO CON FILTROS -->
            <div class="filters-row">
                <div class="buzon-filtros">
                    <div class="filtro-section">
                        <label for="filtro-area">🔍 Filtrar por categoría:</label>
                        <select id="filtro-area">
                            <option value="">📋 Todas las categorías</option>
                            <option value="espacio-de-trabajo">🏢 Espacio de trabajo</option>
                            <option value="tecnologia">💻 Tecnología</option>
                            <option value="zonas-comunes">🤝 Zonas comunes</option>
                            <option value="otras">📝 Otras</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
        
        <?php
		if ($sugerencias) {
    echo '<div class="buzon-sugerencias" id="lista-sugerencias">';
    
		$primera_con_separador = true;
		$fecha_hoy = date('Y-m-d'); // Fecha actual

		foreach ($sugerencias as $index => $sugerencia) {
			$likes = intval($sugerencia->likes);
			$fecha_sugerencia = date('Y-m-d', strtotime($sugerencia->fecha));
			$es_de_hoy = ($fecha_sugerencia === $fecha_hoy);

			// Insertar separador antes de la primera sugerencia que NO sea "nueva de hoy"
			// (es decir, que tenga likes > 0 O no sea de hoy)
			if ($primera_con_separador && ($likes > 0 || !$es_de_hoy)) {
				echo '<div class="zona-nuevas-separator"></div>';
				$primera_con_separador = false;
			}

			echo render_sugerencia_html($sugerencia);
		}
	echo '<nav id="pager" aria-label="Paginación">
		<button type="button" id="prev" class="btn pagination" disabled>← Anterior</button>
			<span id="page-indicator" data-page="1">1</span>
		<button type="button" id="next" class="btn pagination">Siguiente →</button>
	</nav>';
    echo '</div>';
        } else {
            echo '<div class="no-sugerencias-wrapper" id="no-sugerencias-wrapper">';
            echo '<div class="no-sugerencias">';
            echo '<div class="no-sugerencias-icon">💭</div>';
            echo '<h3>No hay sugerencias aún</h3>';
            echo '<p>¡Sé el primero en compartir una idea!</p>';
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <?php if ($sugerencias): ?>
        <!-- ÁREA DE DESCARGAS CON AMBOS BOTONES -->
        <div class="footer-downloads">
            <h4>📊 Reportes y Descargas</h4>
            <div class="download-buttons">
                <?php 
                // VARIABLE DE CONTROL - Cambiar a true para activar los botones
                $botones_activos = false; // <-- CAMBIAR ESTO A true PARA ACTIVAR
                
                if ($botones_activos) {
                    // BOTONES ACTIVOS
                    $comments_pdf_url = add_query_arg([
                        'action' => 'generate_comments_only_pdf',
                        'nonce' => wp_create_nonce('pdf_comments_nonce')
                    ], admin_url('admin-ajax.php'));
                    ?>
                    <a href="<?php echo esc_url($comments_pdf_url); ?>" target="_blank" class="comments-pdf-btn">
                        💬 Descargar Comentarios PDF
                    </a>
                    
                    <?php 
                    $full_pdf_url = add_query_arg([
                        'action' => 'generate_all_sugerencias_pdf',
                        'nonce' => wp_create_nonce('pdf_all_nonce')
                    ], admin_url('admin-ajax.php'));
                    ?>
                    <a href="<?php echo esc_url($full_pdf_url); ?>" target="_blank" class="full-report-btn">
                        📋 Reporte Completo con Estadísticas
                    </a>
                    <?php
                } else {
                    // BOTONES DESHABILITADOS
                    ?>
                    <span class="comments-pdf-btn disabled">
                        💬 Descargar Comentarios PDF
                    </span>
                    <span class="full-report-btn disabled">
                        📋 Reporte Completo con Estadísticas
                    </span>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode( 'buzon_sugerencias', function( $atts, $content = null ){
        var_dump("Soy el shortcode".$atts);
        return buzon_sugerencias_display($atts["offset"]);
} );

function buzon_sugerencias_get_page( $offset ) {
    buzon_sugerencias_ensure_table_exists();
    global $wpdb;
    $table = $wpdb->prefix . 'buzon_sugerencias';
    $fecha_hoy = date('Y-m-d');
    $sugerencias = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table ORDER BY CASE WHEN likes = 0 AND DATE(fecha) = %s THEN fecha ELSE '1970-01-01' END DESC, likes DESC, id DESC LIMIT 10 OFFSET %d",
        $fecha_hoy,
        intval( $offset )
    ) );

    ob_start();
    echo '<div class="buzon-sugerencias" id="lista-sugerencias">';
    $primera_con_separador = true;
    foreach ( $sugerencias as $sugerencia ) {
        $likes = intval( $sugerencia->likes );
        $fecha_sugerencia = date( 'Y-m-d', strtotime( $sugerencia->fecha ) );
        $es_de_hoy = ( $fecha_sugerencia === $fecha_hoy );
        if ( $primera_con_separador && ( $likes > 0 || ! $es_de_hoy ) ) {
            echo '<div class="zona-nuevas-separator"></div>';
            $primera_con_separador = false;
        }
        echo render_sugerencia_html( $sugerencia );
    }
    echo '<nav id="pager" aria-label="Paginación">';
    echo '<button type="button" id="prev" class="btn pagination" disabled>← Anterior</button>';
    echo '<span id="page-indicator" data-page="' . ( (int) ( $offset / 10 ) + 1 ) . '">' . ( (int) ( $offset / 10 ) + 1 ) . '</span>';
    echo '<button type="button" id="next" class="btn pagination">Siguiente →</button>';
    echo '</nav>';
    echo '</div>';

    return ob_get_clean();
}

// NUEVA FUNCIÓN: Renderizar HTML de una sugerencia
function render_sugerencia_html($sugerencia) {
    $likes = intval($sugerencia->likes);
    $liked_users = $sugerencia->liked_users ? explode(',', $sugerencia->liked_users) : [];
    
    // CORRECCIÓN: Identificación correcta del usuario
    if (is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        $already_liked = in_array($current_user_id, $liked_users);
    } else {
        $current_user_id = md5($_SERVER['REMOTE_ADDR']);
        $already_liked = in_array($current_user_id, $liked_users);
    }
    
    $area_class = 'area-' . sanitize_title($sugerencia->area);
    $fecha_formateada = date('d/m/Y H:i', strtotime($sugerencia->fecha));
    
    $html = '<div class="buzon-mensaje ' . esc_attr($area_class) . '" data-id="' . intval($sugerencia->id) . '">';
    $html .= '<div class="mensaje-header">';
    $html .= '<span class="mensaje-author">👤 ' . esc_html($sugerencia->nombre) . '</span>';
    $html .= '<span class="mensaje-area">' . esc_html($sugerencia->area) . '</span>';
    $html .= '<span class="mensaje-fecha">📅 ' . $fecha_formateada . '</span>';
    $html .= '</div>';
    $html .= '<div class="mensaje-content">' . nl2br(esc_html($sugerencia->mensaje)) . '</div>';
    $html .= '<div class="mensaje-actions">';
    $html .= '<div class="like-section">';
    
    // NUEVA LÓGICA: Botón siempre activo, cambia entre like/unlike
    $html .= '<form method="post" class="like-form">';
    $html .= wp_nonce_field('mi_like_action', 'mi_like_nonce', true, false);
    $html .= '<input type="hidden" name="mi_like_id" value="' . intval($sugerencia->id) . '" />';
    
    if ($already_liked) {
        $html .= '<input type="hidden" name="mi_like_action" value="unlike" />';
        $html .= '<button type="submit" class="heart liked" title="Quitar like">❤️</button>';
    } else {
        $html .= '<input type="hidden" name="mi_like_action" value="like" />';
        $html .= '<button type="submit" class="heart" title="Me gusta">❤️</button>';
    }
    
    $html .= '<span class="like-count">' . $likes . '</span>';
    $html .= '</form>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

?>

<?php 

// FUNCIÓN MODIFICADA: Respuesta AJAX mejorada con datos para inserción dinámica
function buzon_sugerencias_submit_form() {
    buzon_sugerencias_ensure_table_exists();

    if (!wp_verify_nonce($_POST['buzon_sugerencias_nonce'], 'buzon_sugerencias_action')) {
        wp_die('Error de seguridad');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'buzon_sugerencias';
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
        // Obtener la sugerencia recién creada
        $nueva_sugerencia_id = $wpdb->insert_id;
        $nueva_sugerencia = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d", 
            $nueva_sugerencia_id
        ));
        
        if ($nueva_sugerencia) {
            wp_send_json_success([
                'message' => '¡Sugerencia enviada correctamente! 🎉',
                'sugerencia' => [
                    'id' => $nueva_sugerencia->id,
                    'nombre' => $nueva_sugerencia->nombre,
                    'area' => $nueva_sugerencia->area,
                    'mensaje' => $nueva_sugerencia->mensaje,
                    'likes' => $nueva_sugerencia->likes,
                    'liked_users' => $nueva_sugerencia->liked_users,
                    'fecha' => $nueva_sugerencia->fecha,
                    'html' => render_sugerencia_html($nueva_sugerencia)
                ]
            ]);
        } else {
            wp_send_json_success([
                'message' => '¡Sugerencia enviada correctamente! 🎉'
            ]);
        }
    } else {
        wp_send_json_error('Error al enviar el mensaje. Por favor, inténtalo de nuevo.');
    }
}

//Manejo de Paginación
function buzon_sugerencias_pagination() {
    if (!wp_verify_nonce($_POST['buzon_sugerencias_pagination_nonce'], 'buzon_sugerencias_pagination_action')) {
        wp_die('Error de seguridad');
    }

    $offset = intval($_POST['offset']);
    global $wpdb;
    $table = $wpdb->prefix . 'buzon_sugerencias';
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $html  = buzon_sugerencias_get_page($offset);
    $page  = floor($offset / 10) + 1;
    $max   = ceil($total / 10);

    wp_send_json_success([
        'html' => $html,
        'page' => $page,
        'max'  => $max,
    ]);
}

//  Manejo de Like/Unlike
function buzon_sugerencias_handle_like() {
    if (isset($_POST['mi_like_id']) && check_admin_referer('mi_like_action', 'mi_like_nonce')) {
        $id = intval($_POST['mi_like_id']);
        $action = sanitize_text_field($_POST['mi_like_action']); // 'like' o 'unlike'
        
        // CORRECCIÓN: Identificación correcta del usuario
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        } else {
            $user_id = md5($_SERVER['REMOTE_ADDR']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'buzon_sugerencias';
        $entry = $wpdb->get_row($wpdb->prepare("SELECT liked_users, likes FROM $table WHERE id=%d", $id));

        if ($entry) {
            $liked_users = $entry->liked_users ? explode(',', $entry->liked_users) : [];
            $current_likes = intval($entry->likes);
            $user_has_liked = in_array($user_id, $liked_users);

            if ($action === 'like' && !$user_has_liked) {
                // AGREGAR LIKE
                $liked_users[] = $user_id;
                $new_likes = $current_likes + 1;
                
                $wpdb->update($table, [
                    'likes' => $new_likes,
                    'liked_users' => implode(',', $liked_users)
                ], ['id' => $id], ['%d', '%s'], ['%d']);
                
            } elseif ($action === 'unlike' && $user_has_liked) {
                // QUITAR LIKE
                $liked_users = array_filter($liked_users, function($uid) use ($user_id) {
                    return $uid != $user_id;
                });
                $new_likes = max(0, $current_likes - 1); // Evitar likes negativos
                
                $wpdb->update($table, [
                    'likes' => $new_likes,
                    'liked_users' => implode(',', $liked_users)
                ], ['id' => $id], ['%d', '%s'], ['%d']);
            }
        }
    }
}
add_action('init', 'buzon_sugerencias_handle_like');

function buzon_sugerencias_form_shortcode() {
    ob_start();
    ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Titillium+Web:wght@300;400;600;700&family=Roboto:wght@300;400;500;700&family=Suez+One&display=swap');
    
    .buzon-form-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
        font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    
    .form-header {
		text-align: center;
		margin-bottom: 30px;
		background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 25%, #ffffff 50%, #f8f8f8 75%, #ffffff 100%);
		padding: 25px;
		border-radius: 15px;
		color: #DE3329;
		box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3), inset 0 3px 6px rgba(255, 255, 255, 1), inset 0 -3px 6px rgba(0, 0, 0, 0.08);
		border: solid rgba(222, 51, 41, 0.4) 2px;
	}
    
    .form-header h2 {
        margin: 0 0 10px 0;
        font-size: 2em;
        font-weight: 600;
        font-family: 'Titillium Web', sans-serif;
    }
    
    .form-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1em;
        font-family: 'Roboto', sans-serif;
    }
    
    #buzon-sugerencias-form {
        background: white;
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid #EBECEC;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #3d4543;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-family: 'Titillium Web', sans-serif;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 15px 18px;
        border: 2px solid #EBECEC;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: white;
        box-sizing: border-box;
        font-family: 'Roboto', sans-serif;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3d4543;
        box-shadow: 0 0 0 4px rgba(61, 69, 67, 0.1);
        transform: translateY(-1px);
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 120px;
        font-family: 'Roboto', sans-serif;
        line-height: 1.5;
    }
    
    .form-group select {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%233d4543' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 40px;
        appearance: none;
    }
    
    #btn-submit-form {
        width: 100%;
        background: #DE3329;
        color: white;
        padding: 18px 30px;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        font-family: 'Roboto', sans-serif;
    }
    
    #btn-submit-form:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
    }
    
    #btn-submit-form:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }
    
    #form-response {
        margin-top: 20px;
        padding: 15px 20px;
        border-radius: 12px;
        font-weight: 500;
        display: none;
        animation: slideDown 0.3s ease;
        font-family: 'Roboto', sans-serif;
    }
    
    #form-response.success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    #form-response.error {
        background: linear-gradient(135deg, #f8d7da, #f1aeb5);
        color: #721c24;
        border: 1px solid #f1aeb5;
    }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 10px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .form-tips {
        background: linear-gradient(135deg, #dadada, #EBECEC);
    	border: 1px solid #c4c4c4;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        color: #2F3634;
    }
    
    .form-tips h4 {
        margin: 0 0 10px 0;
        color: #2F3634;
        font-size: 16px;
        font-family: 'Titillium Web', sans-serif;
    }
    
    .form-tips ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .form-tips li {
        margin-bottom: 5px;
        font-size: 14px;
        font-family: 'Roboto', sans-serif;
    }
    
    @media (max-width: 768px) {
        .buzon-form-container {
            padding: 15px;
        }
        
        .form-header {
            padding: 20px;
        }
        
        .form-header h2 {
            font-size: 1.8em;
        }
        
        #buzon-sugerencias-form {
            padding: 25px 20px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
        }
        
        #btn-submit-form {
            padding: 15px 25px;
        }
    }
    </style>
    
    <div class="buzon-form-container">
        <div class="form-header">
            <h2>✨ Nueva Sugerencia</h2>
            <p>Ayúdanos a mejorar compartiendo tus ideas</p>
        </div>
        
        <div class="form-tips">
            <h4>💡 Tips para una buena sugerencia:</h4>
            <ul>
                <li>Sé específico y claro en tu mensaje</li>
                <li>Incluye detalles que ayuden a entender el contexto</li>
                <li>Propón soluciones cuando sea posible</li>
                <li>Mantén un tono constructivo y positivo</li>
            </ul>
        </div>
        
        <form id="buzon-sugerencias-form">
            <div class="form-group">
                <label for="mi_nombre">👤 Nombre:</label>
                <input type="text" id="mi_nombre" name="mi_nombre" required placeholder="Escribe tu nombre y apellidos">
            </div>

            <div class="form-group">
                <label for="mi_area">🏢 Categoría:</label>
                <select id="mi_area" name="mi_area" required>
                    <option value="">Selecciona un categoría</option>
                    <option value="Espacio de trabajo">🏢 Espacio de trabajo</option>
                    <option value="Tecnología">💻 Tecnología</option>
                    <option value="Zonas comunes">🤝 Zonas comunes</option>
                    <option value="Otras">📝 Otras</option>
                </select>
            </div>

            <div class="form-group">
                <label for="mi_mensaje">💬 Mensaje:</label>
                <textarea id="mi_mensaje" name="mi_mensaje" rows="5" required placeholder="Describe tu sugerencia. ¿Qué mejora propones?"></textarea>
            </div>

            <button type="submit" id="btn-submit-form">
                🚀 Enviar Sugerencia
            </button>
            
            <div id="form-response"></div>
        </form>
    </div>

    <script>
	jQuery(document).ready(function($) {
    $('#buzon-sugerencias-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'buzonsugerenciassubmitform',
            mi_nombre: $('#mi_nombre').val(),
            mi_area: $('#mi_area').val(),
            mi_mensaje: $('#mi_mensaje').val(),
            buzon_sugerencias_nonce: buzon_sugerencias_ajax.nonce
        };

        var $submitBtn = $('#btn-submit-form');
        var originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<span class="loading-spinner"></span>Enviando...');

        $.ajax({
            url: buzon_sugerencias_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                var $responseDiv = $('#form-response');

                if (response.success) {
                    var message = response.data.message || response.data;
                    var successMessage = '<strong>¡Éxito!</strong> ' + message;

                    $responseDiv.removeClass('error').addClass('success')
                              .html(successMessage).show();
                    
                    // NUEVA FUNCIONALIDAD: Agregar sugerencia dinámicamente
                    if (response.data.sugerencia && response.data.sugerencia.html) {
                        agregarSugerenciaHibrida(response.data.sugerencia);
                    }
                    
                    $('#buzon-sugerencias-form')[0].reset();
                    
                    // Scroll to response
                    $('html, body').animate({
                        scrollTop: $responseDiv.offset().top - 20
                    }, 500);
                    
                } else {
                    $responseDiv.removeClass('success').addClass('error')
                              .html('<strong>Error:</strong> ' + response.data).show();
                }
            },
            error: function() {
                $('#form-response').removeClass('success').addClass('error')
                                   .html('<strong>Error de conexión:</strong> No se pudo enviar la sugerencia. Por favor, verifica tu conexión e inténtalo de nuevo.').show();
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
		
        // Configuración de paginación
        const perPage = 10;
        let current = parseInt($('#page-indicator').data('page')) || 1;
        let $feed  = $('#lista-sugerencias');
        let $prev  = $('#prev');
        let $next  = $('#next');
        let $badge = $('#page-indicator');

        function attachPaginationEvents() {
            $prev.off('click').on('click', function() { loadPage(current - 1); });
            $next.off('click').on('click', function() { loadPage(current + 1); });
        }

        function loadPage(page) {
            if (page < 1) return;

            $prev.prop('disabled', true);
            $next.prop('disabled', true);
            $badge.text('…');

            $.ajax({
                type: 'POST',
                url: buzon_sugerencias_pagination_ajax.ajax_url,
                data: {
                    action: 'buzonsugerenciaspagination',
                    offset: (page - 1) * perPage,
                    buzon_sugerencias_pagination_nonce: buzon_sugerencias_pagination_ajax.nonce
                },
                dataType: 'json'
            }).done(function(resp) {
                if (!resp.success) return;

                $feed.replaceWith(resp.data.html);
                $feed  = $('#lista-sugerencias');
                $prev  = $('#prev');
                $next  = $('#next');
                $badge = $('#page-indicator');
                current = resp.data.page;
                $badge.text(current);
                $prev.prop('disabled', current === 1);
                $next.prop('disabled', current >= resp.data.max);

                if (typeof window.crearSeparadorZonas === 'function') {
                    window.crearSeparadorZonas();
                }
                if (typeof window.aplicarFiltroActual === 'function') {
                    $('#lista-sugerencias .buzon-mensaje').each(function(){
                        window.aplicarFiltroActual(this);
                    });
                }

                attachPaginationEvents();
            }).fail(function(xhr, status) {
                console.warn('AJAX fail:', status);
                $badge.text('⚠️');
            });
        }

        attachPaginationEvents();
    });
		
		
		
		

    // NUEVA FUNCIÓN: Inserción híbrida inteligente
    function agregarSugerenciaHibrida(sugerenciaData) {
        var $listaSugerencias = $('#lista-sugerencias');
        var $noSugerenciasWrapper = $('#no-sugerencias-wrapper');
        
        // Si no existe la lista, crearla
        if ($listaSugerencias.length === 0) {
            // Ocultar mensaje de "no hay sugerencias"
            if ($noSugerenciasWrapper.length > 0) {
                $noSugerenciasWrapper.addClass('fade-out');
                setTimeout(function() {
                    $noSugerenciasWrapper.remove();
                }, 500);
            }
            
            // Crear contenedor de la lista
            var $containerPrincipal = $('.buzon-sugerencias-container');
            var $topSection = $containerPrincipal.find('.top-section-wrapper');
            $topSection.after('<div class="buzon-sugerencias" id="lista-sugerencias"></div>');
            $listaSugerencias = $('#lista-sugerencias');
        }
        
        // Crear elemento de la nueva sugerencia
        var $nuevaSugerencia = $(sugerenciaData.html);
        $nuevaSugerencia.addClass('nueva-sugerencia');
        $nuevaSugerencia.append('<div class="nueva-badge">¡Nueva de hoy!</div>');
        
        var likesNuevaSugerencia = parseInt(sugerenciaData.likes) || 0;
        var fechaHoy = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
        var insertada = false;
        
        // Las nuevas sugerencias SIEMPRE son de hoy y con 0 likes
        if (likesNuevaSugerencia === 0) {
            // LÓGICA HÍBRIDA ACTUALIZADA: Solo sugerencias de HOY con 0 likes
            var $separador = $listaSugerencias.find('.zona-nuevas-separator');
            
            if ($separador.length > 0) {
                // Insertar antes del separador (zona de "hoy")
                $separador.before($nuevaSugerencia);
                insertada = true;
            } else {
                // No hay separador, buscar primera que NO sea "nueva de hoy"
                $listaSugerencias.find('.buzon-mensaje').each(function() {
                    var $elemento = $(this);
                    var likesElemento = parseInt($elemento.find('.like-count').text()) || 0;
                    var fechaElemento = $elemento.find('.mensaje-fecha').text() || '';
                    
                    // Extraer fecha
                    var fechaMatch = fechaElemento.match(/(\d{2})\/(\d{2})\/(\d{4})/);
                    var esDehoy = false;
                    
                    if (fechaMatch) {
                        var dia = fechaMatch[1];
                        var mes = fechaMatch[2];
                        var año = fechaMatch[3];
                        var fechaFormateada = año + '-' + mes + '-' + dia;
                        esDehoy = fechaFormateada === fechaHoy;
                    }
                    
                    // Si tiene likes > 0 O no es de hoy
                    if (likesElemento > 0 || !esDehoy) {
                        // Crear separador y insertar antes
                        var separadorHTML = '<div class="zona-nuevas-separator"></div>';
                        $elemento.before(separadorHTML);
                        $elemento.before($nuevaSugerencia);
                        insertada = true;
                        return false; // break
                    }
                });
            }
            
            // Si todas son de hoy con 0 likes, insertar al principio
            if (!insertada) {
                $listaSugerencias.prepend($nuevaSugerencia);
            }
        }
        
        // Aplicar filtro actual si existe
        if (typeof window.aplicarFiltroActual === 'function') {
            window.aplicarFiltroActual($nuevaSugerencia[0]);
        }
        
        // Actualizar estadísticas
        if (typeof window.actualizarEstadisticas === 'function') {
            window.actualizarEstadisticas(sugerenciaData.area);
        }
        
        // Agregar event listeners para likes
        $nuevaSugerencia.find('.heart').on('click', function() {
            var likeCount = $(this).closest('.like-section').find('.like-count');
            if (likeCount.length) {
                likeCount.addClass('updating');
                setTimeout(function() {
                    likeCount.removeClass('updating');
                }, 600);
            }
        });
        
        // Remover badge y clase especial después de un tiempo
        setTimeout(function() {
            $nuevaSugerencia.find('.nueva-badge').fadeOut(300, function() {
                $(this).remove();
            });
            $nuevaSugerencia.removeClass('nueva-sugerencia');
        }, 5000);
        
        // SCROLL CORREGIDO: Verificar que el elemento existe y está en el DOM
        setTimeout(function() {
            // Verificar que el elemento existe y está visible
            if ($nuevaSugerencia.length && $nuevaSugerencia.is(':visible') && $nuevaSugerencia.offset()) {
                try {
                    $('html, body').animate({
                        scrollTop: $nuevaSugerencia.offset().top - 100
                    }, 800);
                } catch (error) {
                    console.log('Error en scroll automático:', error);
                    // Scroll alternativo sin animación
                    if ($nuevaSugerencia[0] && $nuevaSugerencia[0].scrollIntoView) {
                        $nuevaSugerencia[0].scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                    }
                }
            } else {
                console.log('Elemento no disponible para scroll');
            }
        }, 1500); // Aumentado el tiempo para asegurar que el elemento esté en el DOM
    }
    
    // Validación en tiempo real
    $('#mi_mensaje').on('input', function() {
        var $this = $(this);
        var length = $this.val().length;
        var minLength = 10;
        
        if (length < minLength) {
            $this.css('border-color', '#ffc107');
        } else {
            $this.css('border-color', '#28a745');
        }
    });
});
</script>


    <?php
    return ob_get_clean();
}
add_shortcode('buzon_sugerencias_form', 'buzon_sugerencias_form_shortcode');

// Hooks AJAX del formulario
add_action('wp_ajax_nopriv_buzonsugerenciassubmitform', 'buzon_sugerencias_submit_form');
add_action('wp_ajax_buzonsugerenciassubmitform', 'buzon_sugerencias_submit_form');
add_action('wp_ajax_nopriv_buzonsugerenciaspagination', 'buzon_sugerencias_pagination');
add_action('wp_ajax_buzonsugerenciaspagination', 'buzon_sugerencias_pagination');

// Enqueue scripts and styles
function buzon_sugerencias_enqueue_scripts() {
    wp_enqueue_script('jquery');

    wp_register_script('buzon-sugerencias-script', '', array('jquery'), '3.0', true);
    wp_enqueue_script('buzon-sugerencias-script');

    wp_localize_script('buzon-sugerencias-script', 'buzon_sugerencias_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('buzon_sugerencias_action')
    ]);
    wp_localize_script('buzon-sugerencias-script', 'buzon_sugerencias_pagination_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('buzon_sugerencias_pagination_action')
    ]);
}
add_action('wp_enqueue_scripts', 'buzon_sugerencias_enqueue_scripts');

?>

<?php 

// ==================== FUNCIÓN PDF REPORTE COMPLETO (Discreto) ====================
function generate_all_sugerencias_pdf() {
    buzon_sugerencias_ensure_table_exists();
    
    if (!wp_verify_nonce($_GET['nonce'], 'pdf_all_nonce')) {
        wp_die('Error de seguridad');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'buzon_sugerencias';
    $sugerencias = $wpdb->get_results("SELECT * FROM $table ORDER BY likes DESC, id DESC");
    
    if (!$sugerencias) {
        wp_die('No hay sugerencias para generar el reporte');
    }

    $html_content = generate_all_sugerencias_pdf_content($sugerencias);
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="reporte-completo-sugerencias-' . date('Y-m-d') . '.html"');
    
    echo $html_content;
    exit;
}

function generate_all_sugerencias_pdf_content($sugerencias) {
    $fecha_generacion = date('d/m/Y H:i');
    $total_sugerencias = count($sugerencias);
    $total_likes = array_sum(array_column($sugerencias, 'likes'));
    
    // Calcular estadísticas por área
    $areas_stats = [];
    foreach ($sugerencias as $sugerencia) {
        $area = $sugerencia->area;
        if (!isset($areas_stats[$area])) {
            $areas_stats[$area] = ['count' => 0, 'likes' => 0];
        }
        $areas_stats[$area]['count']++;
        $areas_stats[$area]['likes'] += intval($sugerencia->likes);
    }
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte Completo de Sugerencias - Oficina Santa Ana</title>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Titillium+Web:wght@300;400;600;700&family=Roboto:wght@300;400;500;700&family=Suez+One&display=swap");
            
            body {
                font-family: "Roboto", Arial, sans-serif;
                margin: 0;
                padding: 20px;
                color: #3d4543;
                background: #f8f9fa;
                line-height: 1.4;
            }
            .container {
                max-width: 1000px;
                margin: 0 auto;
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
                padding: 40px;
            }
            .header h1 {
                margin: 0 0 10px 0;
                font-size: 32px;
                font-weight: 700;
                font-family: "Titillium Web", sans-serif;
            }
            .header h2 {
                margin: 0 0 20px 0;
                font-size: 18px;
                opacity: 0.9;
                font-weight: normal;
                font-family: "Roboto", sans-serif;
            }
            .header-stats {
                display: flex;
                justify-content: center;
                gap: 40px;
                margin-top: 20px;
                flex-wrap: wrap;
            }
            .header-stat {
                text-align: center;
                background: rgba(255,255,255,0.1);
                padding: 15px 25px;
                border-radius: 10px;
                backdrop-filter: blur(10px);
            }
            .header-stat-number {
                font-size: 24px;
                font-weight: bold;
                display: block;
                font-family: "Suez One", serif;
            }
            .header-stat-label {
                font-size: 12px;
                opacity: 0.8;
                text-transform: uppercase;
                letter-spacing: 1px;
                font-family: "Roboto", sans-serif;
            }
            .content {
                padding: 40px;
            }
            .stats-section {
                margin-bottom: 40px;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .stat-card {
                background: #EBECEC;
                padding: 20px;
                border-radius: 12px;
                border-left: 4px solid #3d4543;
                text-align: center;
            }
            .stat-title {
                font-weight: bold;
                color: #3d4543;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-bottom: 10px;
                font-family: "Titillium Web", sans-serif;
            }
            .stat-value {
                font-size: 20px;
                color: #3d4543;
                font-weight: 600;
                font-family: "Suez One", serif;
            }
            .areas-breakdown {
                background: #e3f2fd;
                padding: 25px;
                border-radius: 12px;
                margin-bottom: 30px;
            }
            .areas-title {
                color: #1565c0;
                font-size: 18px;
                font-weight: 600;
                margin: 0 0 20px 0;
                text-align: center;
                font-family: "Titillium Web", sans-serif;
            }
            .areas-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            .area-card {
                background: white;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                border: 1px solid #90caf9;
            }
            .area-name {
                font-weight: 600;
                color: #1565c0;
                margin-bottom: 5px;
                font-family: "Roboto", sans-serif;
            }
            .area-stats {
                font-size: 12px;
                color: #666;
                font-family: "Roboto", sans-serif;
            }
            .sugerencias-section {
                margin-top: 40px;
            }
            .section-title {
                color: #3d4543;
                font-size: 24px;
                font-weight: 600;
                margin: 0 0 25px 0;
                padding-bottom: 10px;
                border-bottom: 2px solid #EBECEC;
                display: flex;
                align-items: center;
                gap: 10px;
                font-family: "Titillium Web", sans-serif;
            }
            .sugerencia-card {
                background: #fff;
                border: 1px solid #EBECEC;
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 20px;
                border-left: 4px solid #3d4543;
                page-break-inside: avoid;
            }
            .sugerencia-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 15px;
                flex-wrap: wrap;
                gap: 10px;
            }
            .sugerencia-meta {
                display: flex;
                align-items: center;
                gap: 15px;
                flex-wrap: wrap;
            }
            .meta-item {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 14px;
                color: #666;
                font-family: "Roboto", sans-serif;
            }
            .sugerencia-author {
                font-weight: 600;
                color: #3d4543;
                font-family: "Roboto", sans-serif;
            }
            .sugerencia-area {
                background: #3d4543;
                color: white;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-family: "Roboto", sans-serif;
            }
			/* Colores específicos por área - Igual que en la página principal */
            .sugerencia-area.area-espacio-de-trabajo {
                background: linear-gradient(135deg, #007cba, #0056b3);
            }
            
            .sugerencia-area.area-tecnologia {
                background: linear-gradient(135deg, #28a745, #20c997);
            }
            
            .sugerencia-area.area-zonas-comunes {
                background: linear-gradient(135deg, #ffc107, #fd7e14);
            }
            
            .sugerencia-area.area-otras {
                background: linear-gradient(135deg, #6c757d, #495057);
            }
			
            .sugerencia-fecha {
                color: #6c757d;
                font-size: 12px;
                font-family: "Roboto", sans-serif;
            }
            .sugerencia-likes {
                background: white;
                color: #6c757d;
				border: 1px solid #EBECEC;
                padding: 5px 10px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 5px;
                font-family: "Roboto", sans-serif;
            }
            .sugerencia-content {
                color: #3d4543;
                line-height: 1.6;
                margin-top: 15px;
                font-size: 14px;
                font-family: "Roboto", sans-serif;
            }
            .divider {
                height: 2px;
                background: linear-gradient(90deg, transparent, #3d4543, transparent);
                margin: 40px 0;
            }
            .footer {
                background: #3d4543;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 12px;
                line-height: 1.6;
                font-family: "Roboto", sans-serif;
            }
            .footer p {
                margin: 5px 0;
            }
            .footer-logo {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 10px;
                font-family: "Titillium Web", sans-serif;
            }
            
            @media print {
                body { background: white; }
                .container { box-shadow: none; }
                .sugerencia-card { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📋 REPORTE COMPLETO DE SUGERENCIAS</h1>
                <h2>Sistema de Gestión - Oficina Santa Ana</h2>
                <div class="header-stats">
                    <div class="header-stat">
                        <span class="header-stat-number">' . $total_sugerencias . '</span>
                        <span class="header-stat-label">Sugerencias Totales</span>
                    </div>
                    <div class="header-stat">
                        <span class="header-stat-number">' . $total_likes . '</span>
                        <span class="header-stat-label">Total de Likes</span>
                    </div>
                    <div class="header-stat">
                        <span class="header-stat-number">' . count($areas_stats) . '</span>
                        <span class="header-stat-label">Áreas Participantes</span>
                    </div>
                </div>
            </div>
            
            <div class="content">
                <div class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-title">📊 Promedio de Likes</div>
                            <div class="stat-value">' . ($total_sugerencias > 0 ? number_format($total_likes / $total_sugerencias, 1) : '0') . '</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">📅 Fecha del Reporte</div>
                            <div class="stat-value" style="font-size: 16px;">' . $fecha_generacion . '</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-title">👤 Generado por</div>
                            <div class="stat-value" style="font-size: 16px;">Departamento Software&amp;Development</div>
                        </div>
                    </div>
                </div>
                
                <div class="areas-breakdown">
                    <h3 class="areas-title">📈 Desglose por Áreas</h3>
                    <div class="areas-grid">';
    
    foreach ($areas_stats as $area => $stats) {
        $html .= '
                        <div class="area-card">
                            <div class="area-name">' . esc_html($area) . '</div>
                            <div class="area-stats">
                                ' . $stats['count'] . ' sugerencias • ' . $stats['likes'] . ' likes
                            </div>
                        </div>';
    }
    
    $html .= '
                    </div>
                </div>
                
                <div class="divider"></div>
                
                <div class="sugerencias-section">
                    <h2 class="section-title">💬 Todas las Sugerencias</h2>';
    
    $contador = 1;
    foreach ($sugerencias as $sugerencia) {
        $fecha_formateada = date('d/m/Y H:i', strtotime($sugerencia->fecha));
        $likes = intval($sugerencia->likes);
        
        $html .= '
                    <div class="sugerencia-card">
                        <div class="sugerencia-header">
                            <div class="sugerencia-meta">
                                <div class="meta-item">
                                    <strong>#' . str_pad($contador, 3, '0', STR_PAD_LEFT) . '</strong>
                                </div>
                                <div class="meta-item">
                                    <span>👤</span>
                                    <span class="sugerencia-author">' . esc_html($sugerencia->nombre) . '</span>
                                </div>
                                <div class="meta-item">
                                    <span class="sugerencia-area ' . (
                                        $sugerencia->area === 'Espacio de trabajo' ? 'area-espacio-de-trabajo' :
                                        ($sugerencia->area === 'Tecnología' ? 'area-tecnologia' :
                                        ($sugerencia->area === 'Zonas comunes' ? 'area-zonas-comunes' : 'area-otras'))
                                    ) . '">' . esc_html($sugerencia->area) . '</span>
                                </div>
                                <div class="meta-item">
                                    <span>📅</span>
                                    <span class="sugerencia-fecha">' . $fecha_formateada . '</span>
                                </div>
                            </div>
                            <div class="sugerencia-likes">
                                <span>❤️</span>
                                <span>' . $likes . '</span>
                            </div>
                        </div>
                        <div class="sugerencia-content">' . nl2br(esc_html($sugerencia->mensaje)) . '</div>
                    </div>';
        $contador++;
    }
    
    $html .= '
                </div>
            </div>
            
            <div class="footer">
                <div class="footer-logo">🏢 Oficina Santa Ana</div>
                <p><strong>📄 Reporte generado automáticamente</strong></p>
                <p>Fecha de generación: ' . $fecha_generacion . ' | Departamento: Software &amp; Development</p>
                <p>Sistema de Gestión de Sugerencias v3.0 - WordPress</p>
                <p>📊 Total de registros incluidos: ' . $total_sugerencias . ' sugerencias</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// ==================== FUNCIÓN PDF SOLO COMENTARIOS (Principal) ====================
function generate_comments_only_pdf() {
    buzon_sugerencias_ensure_table_exists();
    
    if (!wp_verify_nonce($_GET['nonce'], 'pdf_comments_nonce')) {
        wp_die('Error de seguridad');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'buzon_sugerencias';
    // Ordenar por likes
    $sugerencias = $wpdb->get_results("SELECT * FROM $table ORDER BY likes DESC, id DESC");
    
    if (!$sugerencias) {
        wp_die('No hay sugerencias para generar el reporte');
    }

    $html_content = generate_comments_only_pdf_content($sugerencias);
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="comentarios-sugerencias-' . date('Y-m-d') . '.html"');
    
    echo $html_content;
    exit;
}

function generate_comments_only_pdf_content($sugerencias) {
    $fecha_generacion = date('d/m/Y H:i');
    $total_sugerencias = count($sugerencias);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
		<title>Lista de Comentarios - Oficina Santa Ana</title>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Titillium+Web:wght@300;400;600;700&family=Roboto:wght@300;400;500;700&family=Suez+One&display=swap");
            
            body {
                font-family: "Roboto", Arial, sans-serif;
                margin: 0;
                padding: 20px;
                color: #3d4543;
                background: #f8f9fa;
                line-height: 1.5;
            }
            .container {
                max-width: 900px;
                margin: 0 auto;
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
                padding: 40px;
            }
            .header h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
                font-weight: 700;
                font-family: "Titillium Web", sans-serif;
            }
            .header p {
                margin: 0;
                font-size: 16px;
                opacity: 0.9;
                font-family: "Roboto", sans-serif;
            }
            .header-info {
                background: rgba(255,255,255,0.1);
                padding: 15px;
                border-radius: 10px;
                margin-top: 20px;
                backdrop-filter: blur(10px);
                font-family: "Roboto", sans-serif;
            }
            .content {
                padding: 30px;
            }
            .intro-section {
                background: #e3f2fd;
                padding: 20px;
                border-radius: 12px;
                margin-bottom: 30px;
                text-align: center;
                border: 1px solid #90caf9;
            }
            .intro-title {
                color: #1565c0;
                font-size: 18px;
                font-weight: 600;
                margin: 0 0 10px 0;
                font-family: "Titillium Web", sans-serif;
            }
            .intro-text {
                color: #666;
                font-size: 14px;
                margin: 0;
				text-align: -webkit-auto;
                font-family: "Roboto", sans-serif;
            }
            .comment-card {
                background: #fff;
                border: 2px solid #EBECEC;
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 25px;
                border-left: 5px solid #3d4543;
                page-break-inside: avoid;
                transition: all 0.3s ease;
            }
            .comment-card:hover {
                border-color: #3d4543;
                box-shadow: 0 4px 15px rgba(61, 69, 67, 0.1);
            }
            .comment-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 20px;
                flex-wrap: wrap;
                gap: 15px;
                padding-bottom: 15px;
                border-bottom: 1px solid #EBECEC;
            }
            .comment-meta {
                display: flex;
                align-items: center;
                gap: 20px;
                flex-wrap: wrap;
            }
            .meta-badge {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
                font-family: "Roboto", sans-serif;
            }
            .author-badge {
                background: #EBECEC;
                color: #3d4543;
                border: 1px solid #EBECEC;
            }
             .area-badge {
                color: white;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-size: 11px;
            }
            
            /* Colores específicos por área - Igual que en la página principal */
            .area-badge.area-espacio-de-trabajo {
                background: linear-gradient(135deg, #007cba, #0056b3);
            }
            
            .area-badge.area-tecnologia {
                background: linear-gradient(135deg, #28a745, #20c997);
            }
            
            .area-badge.area-zonas-comunes {
                background: linear-gradient(135deg, #ffc107, #fd7e14);
            }
            
            .area-badge.area-otras {
                background: linear-gradient(135deg, #6c757d, #495057);
            }
			
            .date-badge {
                background: #e3f2fd;
                color: #1565c0;
                border: 1px solid #90caf9;
                font-size: 12px;
            }
            .likes-badge {
				background: white;
				color: #6c757d;
				border: 1px solid #EBECEC;
				font-weight: 600;
				font-size: 14px;
				min-width: 50px;
				justify-content: center;
			}
             .comment-number {
                background: #3d4543;
                color: white;
                font-weight: 700;
                padding: 8px 15px;
                border-radius: 25px;
                font-size: 14px;
                min-width: 40px;
                text-align: center;
                font-family: "Suez One", serif;
            }
            .comment-content {
                color: #3d4543;
                line-height: 1.7;
                font-size: 15px;
                margin-top: 20px;
                padding: 20px;
                background: #EBECEC;
                border-radius: 8px;
                border-left: 3px solid #3d4543;
                font-family: "Roboto", sans-serif;
            }
            .comment-ranking {
                position: absolute;
                top: -10px;
                right: 20px;
                background: #ffc107;
                color: #212529;
                padding: 5px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: 700;
                box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
                font-family: "Roboto", sans-serif;
            }
            .footer {
                background: #3d4543;
                color: white;
                padding: 25px;
                text-align: center;
                font-size: 12px;
                line-height: 1.6;
                font-family: "Roboto", sans-serif;
            }
            .footer p {
                margin: 3px 0;
            }
            .footer-logo {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 10px;
                font-family: "Titillium Web", sans-serif;
            }
            .divider {
                height: 1px;
                background: linear-gradient(90deg, transparent, #EBECEC, transparent);
                margin: 30px 0;
            }
            
            @media print {
                body { background: white; }
                .container { box-shadow: none; }
                .comment-card { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>💬 LISTA DE COMENTARIOS</h1>
                <p>Todas las sugerencias ordenadas por popularidad</p>
                <div class="header-info">
                    <strong>' . $total_sugerencias . ' comentarios</strong> • Generado el ' . $fecha_generacion . '
                </div>
            </div>
            
            <div class="content">
                <div class="intro-section">
                    <h3 class="intro-title">📋 Información del Documento</h3>
                    <p class="intro-text">
                        Este documento contiene todos los comentarios y sugerencias registrados, 
                        ordenados por número de "me gusta" de mayor a menor.
                    </p>
                </div>';
    
    $contador = 1;
    foreach ($sugerencias as $sugerencia) {
        $fecha_formateada = date('d/m/Y H:i', strtotime($sugerencia->fecha));
        $likes = intval($sugerencia->likes);
        
        // Determinar ranking visual
        $ranking_text = '';
        if ($contador <= 3) {
            $ranking_medals = ['🥇 TOP 1', '🥈 TOP 2', '🥉 TOP 3'];
            $ranking_text = $ranking_medals[$contador - 1];
        } elseif ($likes > 5) {
            $ranking_text = '⭐ POPULAR';
        }
        
        $html .= '
                <div class="comment-card" style="position: relative;">
                    ' . ($ranking_text ? '<div class="comment-ranking">' . $ranking_text . '</div>' : '') . '
                    
                    <div class="comment-header">
                        <div class="comment-meta">
                            <div class="comment-number">#' . str_pad($contador, 3, '0', STR_PAD_LEFT) . '</div>
                            
                            <div class="meta-badge author-badge">
                                <span>👤</span>
                                <strong>' . esc_html($sugerencia->nombre) . '</strong>
                            </div>
                            
                             <div class="meta-badge area-badge ' . (
                                $sugerencia->area === 'Espacio de trabajo' ? 'area-espacio-de-trabajo' :
                                ($sugerencia->area === 'Tecnología' ? 'area-tecnologia' :
                                ($sugerencia->area === 'Zonas comunes' ? 'area-zonas-comunes' : 'area-otras'))
                            ) . '">
                                ' . esc_html($sugerencia->area) . '
                            </div>
                            
                            <div class="meta-badge date-badge">
                                <span>📅</span>
                                ' . $fecha_formateada . '
                            </div>
                        </div>
                        
                        <div class="meta-badge likes-badge">
                            <span>❤️</span>
                            <span>' . $likes . '</span>
                        </div>
                    </div>
                    
                    <div class="comment-content">
                        ' . nl2br(esc_html($sugerencia->mensaje)) . '
                    </div>
                </div>';
        
        $contador++;
    }
    
    $html .= '
                <div class="divider"></div>
                
                <div style="text-align: center; padding: 20px; background: #EBECEC; border-radius: 8px;">
                    <p style="margin: 0; color: #666; font-size: 14px; font-family: \'Roboto\', sans-serif;">
                        <strong>Total de comentarios:</strong> ' . $total_sugerencias . ' • 
                        <strong>Likes totales:</strong> ' . array_sum(array_column($sugerencias, 'likes')) . '
                    </p>
                </div>
            </div>
            
            <div class="footer">
                <div class="footer-logo">🏢 Oficina Santa Ana</div>
                <p><strong>💬 Lista de Comentarios generada automáticamente</strong></p>
                <p>Fecha: ' . $fecha_generacion . ' | Departamento: Software &amp; Development</p>
                <p>Sistema de Gestión de Sugerencias v3.0 - WordPress</p>
                <p>Ordenado por popularidad (número de likes)</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// Registrar las acciones AJAX
add_action('wp_ajax_generate_all_sugerencias_pdf', 'generate_all_sugerencias_pdf');
add_action('wp_ajax_nopriv_generate_all_sugerencias_pdf', 'generate_all_sugerencias_pdf');

add_action('wp_ajax_generate_comments_only_pdf', 'generate_comments_only_pdf');
add_action('wp_ajax_nopriv_generate_comments_only_pdf', 'generate_comments_only_pdf');

?>

