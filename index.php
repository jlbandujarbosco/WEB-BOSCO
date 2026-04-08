<?php
// Función para registrar el Custom Post Type 'Ciclos Formativos'
/**
 * REGISTRO DEL CUSTOM POST TYPE (CPT)
 * Esta función crea el tipo de contenido "Ciclos Formativos" que aparece en el menú de WordPress.
 */
function registrar_cpt_ciclos_fp() {

    // Etiquetas que se verán en el panel de administración
    $labels = array(
        'name'                  => 'Oferta FP',
        'singular_name'         => 'Ciclo Formativo',
        'menu_name'             => 'Oferta FP',
        'add_new'               => 'Añadir Nuevo Ciclo',
        'add_new_item'          => 'Añadir Nuevo Ciclo Formativo',
        'edit_item'             => 'Editar Ciclo',
        'new_item'              => 'Nuevo Ciclo',
        'view_item'             => 'Ver Ciclo',
        'search_items'          => 'Buscar Ciclos',
        'not_found'             => 'No se han encontrado ciclos',
    );

    // Configuración del comportamiento del CPT
    $args = array(
        'label'                 => 'Ciclo Formativo',
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ), // Funcionalidades básicas habilitadas
        'public'                => true,            // Es visible para los usuarios
        'show_ui'               => true,            // Muestra la interfaz en el admin
        'show_in_menu'          => true,            // Aparece en el menú lateral
        'menu_position'         => 5,               // Debajo de "Entradas"
        'menu_icon'             => 'dashicons-welcome-learn-more', // Icono de birrete (Dashicons)
        'has_archive'           => true,            // Permite tener una página de listado (tipo /ciclos_fp)
        'hierarchical'          => false,           // Se comporta como "Entradas", no como "Páginas"
        'show_in_rest'          => true,            // Habilita el editor Gutenberg (bloques)
    );

    // Registrar oficialmente el tipo de contenido en WordPress
    register_post_type( 'ciclos_fp', $args );
}

add_action( 'init', 'registrar_cpt_ciclos_fp' );

// 1. Crear la caja de campos en el editor
/**
 * META BOXES (CAMPOS PERSONALIZADOS)
 * 1. Crear la caja de campos en el editor de cada ciclo.
 */
function agregar_campos_ciclo_fp() {
    add_meta_box(
        'detalles_ciclo_box',           // ID único
        'Detalles del Ciclo Formativo', // Título de la caja
        'mostrar_campos_ciclo_fp',      // Función que dibuja el HTML
        'ciclos_fp',                    // Donde se muestra (nuestro CPT)
        'normal',                       // Contexto
        'high'                          // Prioridad
    );
}
add_action( 'add_meta_boxes', 'agregar_campos_ciclo_fp' );

// 2. Dibujar el HTML de los campos
/**
 * 2. Dibujar el HTML de los campos dentro de la caja del editor.
 */
function mostrar_campos_ciclo_fp( $post ) {
    // Recuperamos los valores actuales de la base de datos
    // Recuperamos los valores guardados (si existen) de la base de datos (Post Meta)
    $horas   = get_post_meta( $post->ID, '_ciclo_horas', true );
    $turno   = get_post_meta( $post->ID, '_ciclo_turno', true );
    $nivel   = get_post_meta( $post->ID, '_ciclo_nivel', true );
    $pdf_url = get_post_meta( $post->ID, '_ciclo_pdf', true );

    // Cargamos la librería de medios de WordPress (para el PDF)
    wp_enqueue_media();
    ?>
    <!-- Campo para Horas -->
    <p>
        <label><strong>Horas Totales:</strong></label><br>
        <input type="text" name="ciclo_horas" placeholder="2.000 horas" value="<?php echo esc_attr($horas); ?>" style="width:100%;">
    </p>

    <!-- Selector de Nivel -->
    <p>
        <label><strong>Nivel del Ciclo:</strong></label><br>
        <select name="ciclo_nivel" style="width:100%;">
            <option value="Grado Medio" <?php selected($nivel, 'Grado Medio'); ?>>Grado Medio</option>
            <option value="Grado Superior" <?php selected($nivel, 'Grado Superior'); ?>>Grado Superior</option>
            <option value="FP Básica" <?php selected($nivel, 'FP Básica'); ?>>FP Básica</option>
            <option value="Curso Especialización" <?php selected($nivel, 'Curso Especialización'); ?>>Curso de Especialización</option>
        </select>
    </p>

    <!-- Selector de Turno -->
    <p>
        <label><strong>Turno:</strong></label><br>
        <select name="ciclo_turno" style="width:100%;">
            <option value="mañana" <?php selected($turno, 'mañana'); ?>>Mañana</option>
            <option value="tarde" <?php selected($turno, 'tarde'); ?>>Tarde</option>
            <option value="distancia" <?php selected($turno, 'distancia'); ?>>A Distancia</option>
        </select>
    </p>
			
    <!-- Campo de PDF con botones para abrir la biblioteca de medios -->
    <p>
        <label><strong>PDF del Currículo:</strong></label><br>
        <input type="text" id="ciclo_pdf_url" name="ciclo_pdf" value="<?php echo esc_url($pdf_url); ?>" style="width:75%;" readonly>
        <button type="button" id="boton_subir_pdf" class="button">Seleccionar PDF</button>
        <button type="button" id="boton_limpiar_pdf" class="button">Borrar</button>
    </p>

    <!-- Script para manejar la ventana emergente de selección de archivos -->
    <script>
    jQuery(document).ready(function($){
        $('#boton_subir_pdf').click(function(e) {
            e.preventDefault();
            var custom_uploader = wp.media({
                title: 'Seleccionar PDF',
                button: { text: 'Usar este archivo' },
                multiple: false,
                library: { type: 'application/pdf' }
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $('#ciclo_pdf_url').val(attachment.url);
            }).open();
        });
        $('#boton_limpiar_pdf').click(function(){ $('#ciclo_pdf_url').val(''); });
    });
    </script>
    <?php
}
// 3. Guardar los datos cuando se pulsa "Actualizar"

/**
 * 3. Guardar los datos cuando el usuario pulsa "Actualizar" o "Publicar".
 */
function guardar_campos_ciclo_fp( $post_id ) {
    // Seguridad: Si WordPress está haciendo un autosave, no guardamos nada
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Guardar Horas
    if ( isset( $_POST['ciclo_horas'] ) ) {
        update_post_meta( $post_id, '_ciclo_horas', sanitize_text_field( $_POST['ciclo_horas'] ) );
    }

    // Guardar Nivel (¡Esta es la que te faltaba procesar!)
    // Guardar Nivel
    if ( isset( $_POST['ciclo_nivel'] ) ) {
        update_post_meta( $post_id, '_ciclo_nivel', sanitize_text_field( $_POST['ciclo_nivel'] ) );
    }

    // Guardar Turno
    if ( isset( $_POST['ciclo_turno'] ) ) {
        update_post_meta( $post_id, '_ciclo_turno', sanitize_text_field( $_POST['ciclo_turno'] ) );
    }

    // Guardar PDF
    if ( isset( $_POST['ciclo_pdf'] ) ) {
        update_post_meta( $post_id, '_ciclo_pdf', esc_url_raw( $_POST['ciclo_pdf'] ) );
    }
}
// Muy importante: Esta línea es la que le dice a WordPress que ejecute la función al guardar
add_action( 'save_post', 'guardar_campos_ciclo_fp' );



// // Función MEJORADA para mostrar la ficha técnica en la web
/**
 * VISUALIZACIÓN EN EL FRONTEND
 * Esta función inyecta una "Ficha Técnica" visual antes del contenido de cada ciclo.
 */
function mostrar_ficha_tecnica_ciclo( $content ) {
    // Solo actuar si estamos viendo un post individual del tipo 'ciclos_fp'
    if ( is_singular( 'ciclos_fp' ) ) {
        $id_actual = get_the_ID();
        // Obtener los datos que guardamos antes
        $horas = get_post_meta( $id_actual, '_ciclo_horas', true );
        $turno = get_post_meta( $id_actual, '_ciclo_turno', true );
        $pdf   = get_post_meta( $id_actual, '_ciclo_pdf', true );
        
        // Obtener la Familia Profesional asociada para saber el color
        $familias = get_the_terms( $id_actual, 'familia_fp' );
        $color_familia = '#0056b3'; // Color por defecto
        $nombre_familia = 'Formación Profesional';

        if ( !is_wp_error( $familias ) && !empty( $familias ) ) {
            $familia_obj = $familias[0];
            $nombre_familia = $familia_obj->name;
            
            // --- AQUÍ ESTÁ EL CAMBIO: Leemos el color del mantenimiento ---
            // Leemos el color que se configuró en la categoría (taxonomía)
            $color_guardado = get_term_meta($familia_obj->term_id, 'color_familia', true);
            if ($color_guardado) {
                $color_familia = $color_guardado;
            }
        }


		
        // El resto del HTML se mantiene igual, pero ahora $color_familia es dinámico
        // Construcción del bloque visual (HTML con estilos en línea)
        $ficha_html = '
        <div class="ficha-tecnica-ies" style="background: #fff; border: 1px solid #eee; border-left: 8px solid ' . $color_familia . '; padding: 25px; margin: 25px 0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                <div>
                    <span style="color: ' . $color_familia . '; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;">Familia Profesional</span>
                    <h3 style="margin: 5px 0 0 0; color: #333; font-size: 1.4rem;">' . esc_html($nombre_familia) . '</h3>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 20px; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                <div>
                    <span style="display:block; font-weight: bold; color: #777; font-size: 0.75rem; text-transform: uppercase;">⏱️ Duración</span>
                    <span style="font-size: 1.1rem;">' . esc_html($horas) . '</span>
                </div>
                <div>
                    <span style="display:block; font-weight: bold; color: #777; font-size: 0.75rem; text-transform: uppercase;">📅 Modalidad</span>
                    <span style="font-size: 1.1rem;">' . ucfirst(esc_html($turno)) . '</span>
                </div>';
	
        // Obtener y añadir el Nivel Académico (Limpiado duplicado)
        $nivel = get_post_meta( $id_actual, '_ciclo_nivel', true ) ?: 'Grado Medio';
        $ficha_html .= '
            <div>
                <span style="display:block; font-weight: bold; color: #777; font-size: 0.75rem; text-transform: uppercase;">🎓 Nivel Académico</span>
                <span style="display: inline-block; background: #f0f0f0; color: #333; padding: 2px 10px; border-radius: 4px; font-size: 0.9rem; margin-top: 5px; font-weight: 500; border: 1px solid #ddd;">' . esc_html($nivel) . '</span>
            </div>';
        
        // Si hay PDF, añadir el botón de descarga
        if ( !empty($pdf) ) {
            $ficha_html .= '
                <div style="grid-column: 1 / -1;">
                    <a href="' . esc_url($pdf) . '" target="_blank" style="display: inline-flex; align-items: center; background: ' . $color_familia . '; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                        📄 Descargar Currículo (PDF)
                    </a>
                </div>';
        }
        $ficha_html .= '</div></div>';

        // Retornamos la ficha concatenada con el contenido original de la página
        return $ficha_html . $content;
    }
    return $content;
}

// Añadimos el filtro con prioridad 10 para asegurar que se ejecute
add_filter( 'the_content', 'mostrar_ficha_tecnica_ciclo', 10 );

// Función para registrar la taxonomía de Familias Profesionales
/**
 * TAXONOMÍA PERSONALIZADA (FAMILIAS PROFESIONALES)
 * Esto funciona como las categorías de las entradas normales, pero solo para FP.
 */
function registrar_taxonomia_familias() {

    $labels = array(
        'name'              => 'Familias Profesionales',
        'singular_name'     => 'Familia Profesional',
        'search_items'      => 'Buscar Familias',
        'all_items'         => 'Todas las Familias',
        'parent_item'       => 'Familia Padre',
        'parent_item_colon' => 'Familia Padre:',
        'edit_item'         => 'Editar Familia',
        'update_item'       => 'Actualizar Familia',
        'add_new_item'      => 'Añadir Nueva Familia Profesional',
        'new_item_name'     => 'Nombre de la Nueva Familia',
        'menu_name'         => 'Familias',
    );

    $args = array(
        'hierarchical'      => true, // Comportamiento de categorías (con padres/hijos)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true, // Para que se vea en la lista de ciclos
        'query_var'         => true, // Permite hacer búsquedas por familia en la URL
        'rewrite'           => array( 'slug' => 'familia-profesional' ),
        'show_in_rest'      => true, // Obligatorio para que aparezca en el editor moderno (Gutenberg)
    );

    // Vinculamos la taxonomía al post type 'ciclos_fp'
    register_taxonomy( 'familia_fp', array( 'ciclos_fp' ), $args );
}

add_action( 'init', 'registrar_taxonomia_familias' );

/**
 * SCRIPTS PARA EL COLOR PICKER
 * Carga los archivos necesarios para que WordPress muestre el selector de color.
 */
function admin_color_picker_scripts( $hook ) {
    // Solo cargamos en las pantallas de edición de la taxonomía familia_fp
    if ( ( 'edit-tags.php' === $hook || 'term.php' === $hook ) && isset( $_GET['taxonomy'] ) && 'familia_fp' === $_GET['taxonomy'] ) {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        // Pequeño script para inicializarlo en los campos con la clase .color-picker-field
        add_action( 'admin_footer', function() {
            ?>
            <script>
            jQuery(document).ready(function($){
                $('.color-picker-field').wpColorPicker();
            });
            </script>
            <?php
        });
    }
}
add_action( 'admin_enqueue_scripts', 'admin_color_picker_scripts' );

/**
 * COLOR PERSONALIZADO PARA FAMILIAS
 * 1. Añadir campo de color al formulario de "Nueva Familia"
 */
function agregar_campo_color_familia($taxonomy) {
    ?>
    <div class="form-field term-group">
        <label for="color_familia">Color de la Familia</label>
        <input type="text" name="color_familia" id="color_familia" value="#0056b3" class="color-picker-field">
        <p class="description">Selecciona el color que identificará a esta familia profesional en la web.</p>
    </div>
    <?php
}
add_action('familia_fp_add_form_fields', 'agregar_campo_color_familia', 10, 1);

/**
 * 2. Añadir campo al formulario de "Editar Familia"
 */
function editar_campo_color_familia($term, $taxonomy) {
    $color = get_term_meta($term->term_id, 'color_familia', true);
    ?>
    <tr class="form-field term-group">
        <th scope="row"><label for="color_familia">Color de la Familia</label></th>
        <td>
            <input type="text" name="color_familia" id="color_familia" value="<?php echo esc_attr($color ? $color : '#0056b3'); ?>" class="color-picker-field">
            <p class="description">Este color se aplicará automáticamente a la ficha técnica y a las tarjetas del listado.</p>
        </td>
    </tr>
    <?php
}
add_action('familia_fp_edit_form_fields', 'editar_campo_color_familia', 10, 2);

// 3. Guardar el color cuando se guarda la familia
/**
 * 3. Guardar el color cuando se crea o edita la familia profesional.
 */
function guardar_color_familia($term_id) {
    if (isset($_POST['color_familia'])) {
        update_term_meta($term_id, 'color_familia', sanitize_hex_color($_POST['color_familia']));
    }
}
add_action('created_familia_fp', 'guardar_color_familia', 10, 1);
add_action('edited_familia_fp', 'guardar_color_familia', 10, 1);


//MUESTRA UN LISTADO DE FAMILIAS
// 1. SHORTCODE: MENÚ DE FILTROS (Botones)
/**
 * SHORTCODE: [menu_familias]
 * Crea el buscador de texto y los botones de filtro por familia.
 */
function funcion_shortcode_menu_familias() {
    // Obtener todas las familias que tienen al menos un ciclo asignado
    $familias = get_terms( array('taxonomy' => 'familia_fp', 'hide_empty' => true) );
    if ( is_wp_error( $familias ) || empty( $familias ) ) return '';

    $total_ciclos = wp_count_posts('ciclos_fp')->publish; // Contador total de ciclos

    // Estructura del Buscador + Filtros
    $output = '<div class="controles-fp" style="margin-bottom: 30px; background: #f9f9f9; padding: 20px; border-radius: 15px;">';
    
    // El Input del Buscador y Selector de Nivel
    $output .= '
    <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" id="buscador-fp" placeholder="🔍 Buscar ciclo por nombre (ej: Aplicaciones, Cuidados...)" 
                   style="width: 100%; padding: 12px 20px; border-radius: 10px; border: 2px solid #ddd; font-size: 1rem; outline: none; transition: border-color 0.3s;">
        </div>
        <div style="min-width: 200px;">
            <select id="filtro-nivel" style="width: 100%; padding: 12px 20px; border-radius: 10px; border: 2px solid #ddd; font-size: 1rem; background: #fff; cursor: pointer;">
                <option value="all">🎓 Todos los niveles</option>
                <option value="Grado Medio">Grado Medio</option>
                <option value="Grado Superior">Grado Superior</option>
                <option value="FP Básica">FP Básica</option>
                <option value="Curso Especialización">Curso de Especialización</option>
            </select>
        </div>
    </div>';

    // Los Botones de Familia
    $output .= '<div class="filtros-fp" style="display: flex; flex-wrap: wrap; gap: 10px;">';
    $output .= sprintf(
        '<button class="btn-filtro active" data-filter="all" style="background:#333; color:#fff; border:none; padding:10px 22px; border-radius:30px; cursor:pointer; font-weight:bold; display:flex; align-items:center; gap:8px;">Todos <span class="count-badge" data-family="all" style="background:rgba(255,255,255,0.2); padding:2px 8px; border-radius:10px; font-size:0.8rem;">%s</span></button>',
        $total_ciclos
    );

    // Botón para cada familia con su color asignado
    foreach ( $familias as $familia ) {
        $color = get_term_meta( $familia->term_id, 'color_familia', true ) ?: '#0056b3';
        $output .= sprintf(
            '<button class="btn-filtro" data-filter="fam-%s" style="background:%s; color:#fff; border:none; padding:10px 22px; border-radius:30px; cursor:pointer; font-weight:bold; opacity:0.8; transition:0.3s; display:flex; align-items:center; gap:8px;">%s <span class="count-badge" data-family="fam-%s" style="background:rgba(0,0,0,0.15); padding:2px 8px; border-radius:10px; font-size:0.8rem;">%s</span></button>',
            esc_attr($familia->slug), esc_attr($color), esc_html($familia->name), esc_attr($familia->slug), $familia->count
        );
    }
    $output .= '</div></div>';
    return $output;
}
add_shortcode( 'menu_familias', 'funcion_shortcode_menu_familias' );



// 2. SHORTCODE: LISTADO DE CICLOS (Con clases de filtrado)
/**
 * SHORTCODE: [lista_ciclos]
 * Crea la rejilla (grid) de tarjetas de todos los ciclos formativos.
 */
function funcion_shortcode_lista_ciclos() {
    // Consultar todos los ciclos publicados ordenados por título
    $query = new WP_Query( array('post_type' => 'ciclos_fp', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC') );
    
    // Estilos CSS para la rejilla
    $output = '<style>
        .grid-fp { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .tarjeta-fp { transition: all 0.3s ease; display: flex; flex-direction: column; justify-content: space-between; min-height: 180px; }
        .tarjeta-fp.hidden-filter { display: none !important; }
        #buscador-fp:focus { border-color: #0056b3 !important; }
    </style>';

    $output .= '<div class="grid-fp" id="contenedor-ciclos">';

    // Bucle para recorrer los resultados de la consulta
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $familias = get_the_terms( get_the_ID(), 'familia_fp' );
            // Valores por defecto
            $clase_familia = ''; $nombre_familia = 'FP'; $color_familia = '#0056b3';

            if ( !is_wp_error( $familias ) && !empty( $familias ) ) {
                $clase_familia = 'fam-' . $familias[0]->slug;
                $nombre_familia = $familias[0]->name;
                $color_familia = get_term_meta($familias[0]->term_id, 'color_familia', true) ?: '#0056b3';
            }

            // Obtenemos el nivel para el atributo data
            $nivel_ciclo = get_post_meta( get_the_ID(), '_ciclo_nivel', true ) ?: 'Grado Medio';

            // Guardamos el título en minúsculas en un atributo data para el buscador
            // HTML de cada tarjeta individual
            $output .= sprintf(
                '<div class="tarjeta-fp %s" data-nombre="%s" data-nivel="%s" data-family="%s" style="background:#fff; border-top:6px solid %s; border-radius:12px; padding:25px; box-shadow:0 4px 15px rgba(0,0,0,0.05);">
                    <div>
                        <span style="color:%s; font-size:0.75rem; font-weight:bold; text-transform:uppercase;">%s</span>
                        <h4 style="margin:12px 0; color:#333; line-height:1.3;">%s</h4>
                    </div>
                    <a href="%s" style="background:%s; color:#fff; text-decoration:none; padding:10px; border-radius:8px; text-align:center; font-size:0.95rem; font-weight:500;">Ver detalles</a>
                </div>',
                $clase_familia, mb_strtolower(get_the_title()), esc_attr($nivel_ciclo), esc_attr($clase_familia), $color_familia, $color_familia, $nombre_familia, get_the_title(), get_permalink(), $color_familia
            );
        }
        wp_reset_postdata();
    }
    $output .= '</div>';

    // 3. JAVASCRIPT: Lógica Combinada (Buscador + Filtros)
    /**
     * JAVASCRIPT: Lógica de filtrado en tiempo real.
     * Funciona comparando el texto del buscador y el botón de familia seleccionado.
     */
    $output .= "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const buscador = document.getElementById('buscador-fp');
        const selectorNivel = document.getElementById('filtro-nivel');
        const botones = document.querySelectorAll('.btn-filtro');
        const tarjetas = document.querySelectorAll('.tarjeta-fp');
        const badges = document.querySelectorAll('.count-badge');

        let filtroActual = 'all';
        let nivelActual = 'all';

        function filtrar() {
            const texto = buscador.value.toLowerCase();
            
            // Reiniciar contadores dinámicamente según los badges que existen
            const conteos = {};
            badges.forEach(badge => {
                conteos[badge.getAttribute('data-family')] = 0;
            });

            tarjetas.forEach(tarjeta => {
                const nombre = tarjeta.getAttribute('data-nombre');
                const nivel = tarjeta.getAttribute('data-nivel');
                const familia = tarjeta.getAttribute('data-family');
                
                const coincideTexto = nombre.includes(texto);
                const coincideNivel = (nivelActual === 'all' || nivel === nivelActual);

                // Si coincide el buscador y el nivel, sumamos a los contadores
                if (coincideTexto && coincideNivel) {
                    conteos['all']++;
                    if (familia && conteos.hasOwnProperty(familia)) {
                        conteos[familia]++;
                    }
                }

                const coincideFamilia = (filtroActual === 'all' || tarjeta.classList.contains(filtroActual));

                if (coincideTexto && coincideFamilia && coincideNivel) {
                    tarjeta.classList.remove('hidden-filter');
                } else {
                    tarjeta.classList.add('hidden-filter');
                }
            });

            // Actualizar los números (badges) en los botones
            badges.forEach(badge => {
                const key = badge.getAttribute('data-family');
                badge.textContent = conteos[key] || 0;
            });
        }

        // Evento Buscador
        buscador.addEventListener('input', filtrar);

        // Evento Selector de Nivel
        selectorNivel.addEventListener('change', function() {
            nivelActual = this.value;
            filtrar();
        });

        // Evento Botones
        botones.forEach(boton => {
            boton.addEventListener('click', function() {
                botones.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filtroActual = this.getAttribute('data-filter');
                filtrar();
            });
        });
    });
    </script>";

    return $output;
}
add_shortcode( 'lista_ciclos', 'funcion_shortcode_lista_ciclos' );