<?php
// Función para registrar el Custom Post Type 'Ciclos Formativos'
function registrar_cpt_ciclos_fp() {

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

    $args = array(
        'label'                 => 'Ciclo Formativo',
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ), // Permite título, texto e imagen
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-welcome-learn-more', // Icono de birrete
        'has_archive'           => true,
        'hierarchical'          => false,
        'show_in_rest'          => true, // Importante para que funcione el editor Gutenberg
    );

    register_post_type( 'ciclos_fp', $args );
}

add_action( 'init', 'registrar_cpt_ciclos_fp' );

// 1. Crear la caja de campos en el editor
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
function mostrar_campos_ciclo_fp( $post ) {
    // Recuperamos los valores actuales de la base de datos
    $horas   = get_post_meta( $post->ID, '_ciclo_horas', true );
    $turno   = get_post_meta( $post->ID, '_ciclo_turno', true );
    $nivel   = get_post_meta( $post->ID, '_ciclo_nivel', true );
    $pdf_url = get_post_meta( $post->ID, '_ciclo_pdf', true );

    wp_enqueue_media();
    ?>
    <p>
        <label><strong>Horas Totales:</strong></label><br>
        <input type="text" name="ciclo_horas" placeholder="2.000 horas" value="<?php echo esc_attr($horas); ?>" style="width:100%;">
    </p>

    <p>
        <label><strong>Nivel del Ciclo:</strong></label><br>
        <select name="ciclo_nivel" style="width:100%;">
            <option value="Grado Medio" <?php selected($nivel, 'Grado Medio'); ?>>Grado Medio</option>
            <option value="Grado Superior" <?php selected($nivel, 'Grado Superior'); ?>>Grado Superior</option>
            <option value="FP Básica" <?php selected($nivel, 'FP Básica'); ?>>FP Básica</option>
            <option value="Curso Especialización" <?php selected($nivel, 'Curso Especialización'); ?>>Curso de Especialización</option>
        </select>
    </p>

    <p>
        <label><strong>Turno:</strong></label><br>
        <select name="ciclo_turno" style="width:100%;">
            <option value="mañana" <?php selected($turno, 'mañana'); ?>>Mañana</option>
            <option value="tarde" <?php selected($turno, 'tarde'); ?>>Tarde</option>
            <option value="distancia" <?php selected($turno, 'distancia'); ?>>A Distancia</option>
        </select>
    </p>
			
    <p>
        <label><strong>PDF del Currículo:</strong></label><br>
        <input type="text" id="ciclo_pdf_url" name="ciclo_pdf" value="<?php echo esc_url($pdf_url); ?>" style="width:75%;" readonly>
        <button type="button" id="boton_subir_pdf" class="button">Seleccionar PDF</button>
        <button type="button" id="boton_limpiar_pdf" class="button">Borrar</button>
    </p>

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
function guardar_campos_ciclo_fp( $post_id ) {
    // Seguridad: Si WordPress está haciendo un autosave, no guardamos nada
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    // Guardar Horas
    if ( isset( $_POST['ciclo_horas'] ) ) {
        update_post_meta( $post_id, '_ciclo_horas', sanitize_text_field( $_POST['ciclo_horas'] ) );
    }

    // Guardar Nivel (¡Esta es la que te faltaba procesar!)
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
function mostrar_ficha_tecnica_ciclo( $content ) {
    if ( is_singular( 'ciclos_fp' ) ) {
        $id_actual = get_the_ID();
        $horas = get_post_meta( $id_actual, '_ciclo_horas', true );
        $turno = get_post_meta( $id_actual, '_ciclo_turno', true );
        $pdf   = get_post_meta( $id_actual, '_ciclo_pdf', true );
        
        $familias = get_the_terms( $id_actual, 'familia_fp' );
        $color_familia = '#0056b3'; // Color por defecto
        $nombre_familia = 'Formación Profesional';

        if ( !is_wp_error( $familias ) && !empty( $familias ) ) {
            $familia_obj = $familias[0];
            $nombre_familia = $familia_obj->name;
            
            // --- AQUÍ ESTÁ EL CAMBIO: Leemos el color del mantenimiento ---
            $color_guardado = get_term_meta($familia_obj->term_id, 'color_familia', true);
            if ($color_guardado) {
                $color_familia = $color_guardado;
            }
        }


		
        // El resto del HTML se mantiene igual, pero ahora $color_familia es dinámico
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
	
				//CAMPO NIVEL
// Busca esta parte dentro de mostrar_ficha_tecnica_ciclo
$nivel = get_post_meta( $id_actual, '_ciclo_nivel', true ) ?: 'Grado Medio';

// Añade el HTML dentro de la rejilla de detalles (donde están las horas y el turno)
$ficha_html .= '
    <div>
        <span style="display:block; font-weight: bold; color: #777; font-size: 0.75rem; text-transform: uppercase;">🎓 Nivel Académico</span>
        <span style="display: inline-block; background: #f0f0f0; color: #333; padding: 2px 10px; border-radius: 4px; font-size: 0.9rem; margin-top: 5px; font-weight: 500; border: 1px solid #ddd;">' . esc_html($nivel) . '</span>
    </div>';
        
        if ( !empty($pdf) ) {
            $ficha_html .= '
                <div style="grid-column: 1 / -1;">
                    <a href="' . esc_url($pdf) . '" target="_blank" style="display: inline-flex; align-items: center; background: ' . $color_familia . '; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                        📄 Descargar Currículo (PDF)
                    </a>
                </div>';
        }
        $ficha_html .= '</div></div>';

        return $ficha_html . $content;
    }
    return $content;
}

// Añadimos el filtro con prioridad 10 para asegurar que se ejecute
add_filter( 'the_content', 'mostrar_ficha_tecnica_ciclo', 10 );

// Función para registrar la taxonomía de Familias Profesionales
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
        'hierarchical'      => true, // Para que aparezcan cuadraditos de selección (como categorías)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true, // Para que se vea en la lista de ciclos
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'familia-profesional' ),
        'show_in_rest'      => true, // Obligatorio para que aparezca en el editor moderno (Gutenberg)
    );

    // Vinculamos la taxonomía al post type 'ciclos_fp'
    register_taxonomy( 'familia_fp', array( 'ciclos_fp' ), $args );
}

add_action( 'init', 'registrar_taxonomia_familias' );

//añadir color a la familia
function agregar_campo_color_familia($taxonomy) {
    ?>
    <div class="form-field term-group">
        <label for="color_familia">Color de la Familia (Hexadecimal)</label>
        <input type="text" name="color_familia" id="color_familia" value="#0056b3">
        <p>Ejemplo: #2ecc71 para verde, #e74c3c para rojo. <a href="https://htmlcolorcodes.com/es/" target="_blank">Elegir color aquí</a>.</p>
    </div>
    <?php
}
add_action('familia_fp_add_form_fields', 'agregar_campo_color_familia', 10, 1);

// 2. Añadir campo al formulario de "Editar Familia"
function editar_campo_color_familia($term, $taxonomy) {
    $color = get_term_meta($term->term_id, 'color_familia', true);
    ?>
    <tr class="form-field term-group">
        <th scope="row"><label for="color_familia">Color de la Familia</label></th>
        <td>
            <input type="text" name="color_familia" id="color_familia" value="<?php echo esc_attr($color ? $color : '#0056b3'); ?>">
            <p class="description">Color que tendrá la ficha y la tarjeta de esta familia profesional.</p>
        </td>
    </tr>
    <?php
}
add_action('familia_fp_edit_form_fields', 'editar_campo_color_familia', 10, 2);

// 3. Guardar el color cuando se guarda la familia
function guardar_color_familia($term_id) {
    if (isset($_POST['color_familia'])) {
        update_term_meta($term_id, 'color_familia', sanitize_hex_color($_POST['color_familia']));
    }
}
add_action('created_familia_fp', 'guardar_color_familia', 10, 1);
add_action('edited_familia_fp', 'guardar_color_familia', 10, 1);


//MUESTRA UN LISTADO DE FAMILIAS
// 1. SHORTCODE: MENÚ DE FILTROS (Botones)
function funcion_shortcode_menu_familias() {
    $familias = get_terms( array('taxonomy' => 'familia_fp', 'hide_empty' => true) );
    if ( is_wp_error( $familias ) || empty( $familias ) ) return '';

    $total_ciclos = wp_count_posts('ciclos_fp')->publish;

    // Estructura del Buscador + Filtros
    $output = '<div class="controles-fp" style="margin-bottom: 30px; background: #f9f9f9; padding: 20px; border-radius: 15px;">';
    
    // El Input del Buscador
    $output .= '
    <div style="margin-bottom: 20px;">
        <input type="text" id="buscador-fp" placeholder="🔍 Buscar ciclo por nombre (ej: Aplicaciones, Cuidados...)" 
               style="width: 100%; padding: 12px 20px; border-radius: 10px; border: 2px solid #ddd; font-size: 1rem; outline: none; transition: border-color 0.3s;">
    </div>';

    // Los Botones de Familia
    $output .= '<div class="filtros-fp" style="display: flex; flex-wrap: wrap; gap: 10px;">';
    $output .= sprintf(
        '<button class="btn-filtro active" data-filter="all" style="background:#333; color:#fff; border:none; padding:10px 22px; border-radius:30px; cursor:pointer; font-weight:bold; display:flex; align-items:center; gap:8px;">Todos <span style="background:rgba(255,255,255,0.2); padding:2px 8px; border-radius:10px; font-size:0.8rem;">%s</span></button>',
        $total_ciclos
    );

    foreach ( $familias as $familia ) {
        $color = get_term_meta( $familia->term_id, 'color_familia', true ) ?: '#0056b3';
        $output .= sprintf(
            '<button class="btn-filtro" data-filter="fam-%s" style="background:%s; color:#fff; border:none; padding:10px 22px; border-radius:30px; cursor:pointer; font-weight:bold; opacity:0.8; transition:0.3s; display:flex; align-items:center; gap:8px;">%s <span style="background:rgba(0,0,0,0.15); padding:2px 8px; border-radius:10px; font-size:0.8rem;">%s</span></button>',
            esc_attr($familia->slug), esc_attr($color), esc_html($familia->name), $familia->count
        );
    }
    $output .= '</div></div>';
    return $output;
}
add_shortcode( 'menu_familias', 'funcion_shortcode_menu_familias' );



// 2. SHORTCODE: LISTADO DE CICLOS (Con clases de filtrado)
function funcion_shortcode_lista_ciclos() {
    $query = new WP_Query( array('post_type' => 'ciclos_fp', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC') );
    
    $output = '<style>
        .grid-fp { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .tarjeta-fp { transition: all 0.3s ease; display: flex; flex-direction: column; justify-content: space-between; min-height: 180px; }
        .tarjeta-fp.hidden-filter { display: none !important; }
        #buscador-fp:focus { border-color: #0056b3 !important; }
    </style>';

    $output .= '<div class="grid-fp" id="contenedor-ciclos">';

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $familias = get_the_terms( get_the_ID(), 'familia_fp' );
            $clase_familia = ''; $nombre_familia = 'FP'; $color_familia = '#0056b3';

            if ( !is_wp_error( $familias ) && !empty( $familias ) ) {
                $clase_familia = 'fam-' . $familias[0]->slug;
                $nombre_familia = $familias[0]->name;
                $color_familia = get_term_meta($familias[0]->term_id, 'color_familia', true) ?: '#0056b3';
            }

            // Guardamos el título en minúsculas en un atributo data para el buscador
            $output .= sprintf(
                '<div class="tarjeta-fp %s" data-nombre="%s" style="background:#fff; border-top:6px solid %s; border-radius:12px; padding:25px; box-shadow:0 4px 15px rgba(0,0,0,0.05);">
                    <div>
                        <span style="color:%s; font-size:0.75rem; font-weight:bold; text-transform:uppercase;">%s</span>
                        <h4 style="margin:12px 0; color:#333; line-height:1.3;">%s</h4>
                    </div>
                    <a href="%s" style="background:%s; color:#fff; text-decoration:none; padding:10px; border-radius:8px; text-align:center; font-size:0.95rem; font-weight:500;">Ver detalles</a>
                </div>',
                $clase_familia, mb_strtolower(get_the_title()), $color_familia, $color_familia, $nombre_familia, get_the_title(), get_permalink(), $color_familia
            );
        }
        wp_reset_postdata();
    }
    $output .= '</div>';

    // 3. JAVASCRIPT: Lógica Combinada (Buscador + Filtros)
    $output .= "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const buscador = document.getElementById('buscador-fp');
        const botones = document.querySelectorAll('.btn-filtro');
        const tarjetas = document.querySelectorAll('.tarjeta-fp');
        let filtroActual = 'all';

        function filtrar() {
            const texto = buscador.value.toLowerCase();

            tarjetas.forEach(tarjeta => {
                const nombre = tarjeta.getAttribute('data-nombre');
                const coincideTexto = nombre.includes(texto);
                const coincideFamilia = (filtroActual === 'all' || tarjeta.classList.contains(filtroActual));

                if (coincideTexto && coincideFamilia) {
                    tarjeta.classList.remove('hidden-filter');
                } else {
                    tarjeta.classList.add('hidden-filter');
                }
            });
        }

        // Evento Buscador
        buscador.addEventListener('input', filtrar);

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