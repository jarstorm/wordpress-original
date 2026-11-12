<?php
/**
 * Plugin Name: Nortia Core
 * Description: Funcionalidad a medida de Nortia Reformas: tipos de contenido (proyectos, equipo), campos ACF, shortcodes y ficha técnica de proyectos. Simula el "código a medida" que suele tener la web de una empresa.
 * Version: 1.0.0
 * Author: Nortia Reformas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Tipos de contenido y taxonomías
 * ---------------------------------------------------------------------- */
add_action( 'init', function () {
	register_post_type( 'proyecto', array(
		'labels'       => array(
			'name'          => 'Proyectos',
			'singular_name' => 'Proyecto',
			'add_new_item'  => 'Añadir proyecto',
			'edit_item'     => 'Editar proyecto',
			'all_items'     => 'Todos los proyectos',
		),
		'public'       => true,
		'has_archive'  => 'proyectos',
		'rewrite'      => array( 'slug' => 'proyectos', 'with_front' => false ),
		'menu_icon'    => 'dashicons-building',
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'show_in_rest' => true,
	) );

	register_taxonomy( 'tipo_proyecto', 'proyecto', array(
		'labels'            => array(
			'name'          => 'Tipos de proyecto',
			'singular_name' => 'Tipo de proyecto',
		),
		'public'            => true,
		'hierarchical'      => true,
		'rewrite'           => array( 'slug' => 'tipo-proyecto', 'with_front' => false ),
		'show_admin_column' => true,
		'show_in_rest'      => true,
	) );

	register_post_type( 'equipo', array(
		'labels'       => array(
			'name'          => 'Equipo',
			'singular_name' => 'Miembro del equipo',
			'add_new_item'  => 'Añadir miembro',
		),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-groups',
		'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
		'show_in_rest' => true,
	) );
} );

/* -------------------------------------------------------------------------
 * Campos personalizados (ACF)
 * ---------------------------------------------------------------------- */
add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'      => 'group_nortia_proyecto',
		'title'    => 'Ficha del proyecto',
		'fields'   => array(
			array( 'key' => 'field_nortia_ubicacion', 'label' => 'Ubicación', 'name' => 'ubicacion', 'type' => 'text' ),
			array( 'key' => 'field_nortia_anio', 'label' => 'Año', 'name' => 'anio', 'type' => 'number' ),
			array( 'key' => 'field_nortia_superficie', 'label' => 'Superficie (m²)', 'name' => 'superficie_m2', 'type' => 'number' ),
			array( 'key' => 'field_nortia_duracion', 'label' => 'Duración (semanas)', 'name' => 'duracion_semanas', 'type' => 'number' ),
			array( 'key' => 'field_nortia_presupuesto', 'label' => 'Presupuesto aproximado', 'name' => 'presupuesto_aprox', 'type' => 'text' ),
			array(
				'key'     => 'field_nortia_cliente_tipo',
				'label'   => 'Tipo de cliente',
				'name'    => 'cliente_tipo',
				'type'    => 'select',
				'choices' => array(
					'particular' => 'Particular',
					'comunidad'  => 'Comunidad de vecinos',
					'empresa'    => 'Empresa',
				),
			),
			array( 'key' => 'field_nortia_antes', 'label' => 'Imagen antes', 'name' => 'imagen_antes', 'type' => 'image', 'return_format' => 'id' ),
			array( 'key' => 'field_nortia_despues', 'label' => 'Imagen después', 'name' => 'imagen_despues', 'type' => 'image', 'return_format' => 'id' ),
			array( 'key' => 'field_nortia_destacado', 'label' => 'Destacado en portada', 'name' => 'destacado', 'type' => 'true_false', 'ui' => 1 ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'proyecto' ) ) ),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_nortia_equipo',
		'title'    => 'Datos del miembro',
		'fields'   => array(
			array( 'key' => 'field_nortia_cargo', 'label' => 'Cargo', 'name' => 'cargo', 'type' => 'text' ),
			array( 'key' => 'field_nortia_experiencia', 'label' => 'Años de experiencia', 'name' => 'anios_experiencia', 'type' => 'number' ),
			array( 'key' => 'field_nortia_linkedin', 'label' => 'LinkedIn', 'name' => 'linkedin', 'type' => 'url' ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'equipo' ) ) ),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_nortia_servicio',
		'title'    => 'Datos del servicio',
		'fields'   => array(
			array( 'key' => 'field_nortia_precio_desde', 'label' => 'Precio desde (€)', 'name' => 'precio_desde', 'type' => 'number' ),
			array( 'key' => 'field_nortia_plazo', 'label' => 'Plazo orientativo', 'name' => 'plazo', 'type' => 'text' ),
			array( 'key' => 'field_nortia_incluye', 'label' => 'Qué incluye', 'name' => 'incluye', 'type' => 'textarea', 'instructions' => 'Un elemento por línea' ),
		),
		'location' => array( array( array( 'param' => 'page_type', 'operator' => '==', 'value' => 'child' ) ) ),
	) );
} );

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */
function nortia_field( $name, $post_id ) {
	if ( function_exists( 'get_field' ) ) {
		return get_field( $name, $post_id );
	}
	return get_post_meta( $post_id, $name, true );
}

/* -------------------------------------------------------------------------
 * Shortcodes
 * ---------------------------------------------------------------------- */

// [nortia_equipo] — rejilla con el equipo
add_shortcode( 'nortia_equipo', function () {
	$miembros = get_posts( array(
		'post_type'      => 'equipo',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );
	if ( ! $miembros ) {
		return '';
	}
	$html = '<div class="nortia-grid nortia-equipo">';
	foreach ( $miembros as $m ) {
		$html .= '<div class="nortia-card">';
		$html .= get_the_post_thumbnail( $m, 'medium', array( 'class' => 'nortia-card__img' ) );
		$html .= '<h3 class="nortia-card__title">' . esc_html( $m->post_title ) . '</h3>';
		$html .= '<p class="nortia-card__meta">' . esc_html( nortia_field( 'cargo', $m->ID ) ) . '</p>';
		$exp = nortia_field( 'anios_experiencia', $m->ID );
		if ( $exp ) {
			$html .= '<p class="nortia-card__small">' . intval( $exp ) . ' años de experiencia</p>';
		}
		$html .= '</div>';
	}
	return $html . '</div>';
} );

// [nortia_proyectos limite="3" destacados="1"] — rejilla de proyectos
add_shortcode( 'nortia_proyectos', function ( $atts ) {
	$atts = shortcode_atts( array( 'limite' => 6, 'destacados' => 0 ), $atts );
	$args = array(
		'post_type'      => 'proyecto',
		'posts_per_page' => intval( $atts['limite'] ),
	);
	if ( intval( $atts['destacados'] ) ) {
		$args['meta_query'] = array( array( 'key' => 'destacado', 'value' => '1' ) );
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		return '';
	}
	$html = '<div class="nortia-grid nortia-proyectos">';
	foreach ( $q->posts as $p ) {
		$html .= '<a class="nortia-card" href="' . esc_url( get_permalink( $p ) ) . '">';
		$html .= get_the_post_thumbnail( $p, 'medium_large', array( 'class' => 'nortia-card__img' ) );
		$html .= '<h3 class="nortia-card__title">' . esc_html( $p->post_title ) . '</h3>';
		$html .= '<p class="nortia-card__meta">' . esc_html( nortia_field( 'ubicacion', $p->ID ) ) . '</p>';
		$html .= '</a>';
	}
	wp_reset_postdata();
	return $html . '</div>';
} );

/* -------------------------------------------------------------------------
 * Ficha técnica automática en cada proyecto y datos del servicio
 * ---------------------------------------------------------------------- */
add_filter( 'the_content', function ( $content ) {
	if ( ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$id = get_the_ID();

	if ( is_singular( 'proyecto' ) ) {
		$clientes = array( 'particular' => 'Particular', 'comunidad' => 'Comunidad de vecinos', 'empresa' => 'Empresa' );
		$filas    = array(
			'Ubicación'          => nortia_field( 'ubicacion', $id ),
			'Año'                => nortia_field( 'anio', $id ),
			'Superficie'         => ( $s = nortia_field( 'superficie_m2', $id ) ) ? $s . ' m²' : '',
			'Duración'           => ( $d = nortia_field( 'duracion_semanas', $id ) ) ? $d . ' semanas' : '',
			'Presupuesto aprox.' => nortia_field( 'presupuesto_aprox', $id ),
			'Cliente'            => $clientes[ nortia_field( 'cliente_tipo', $id ) ] ?? '',
		);
		$ficha = '<table class="nortia-ficha"><tbody>';
		foreach ( $filas as $k => $v ) {
			if ( '' !== (string) $v ) {
				$ficha .= '<tr><th>' . esc_html( $k ) . '</th><td>' . esc_html( $v ) . '</td></tr>';
			}
		}
		$ficha .= '</tbody></table>';

		$antes   = nortia_field( 'imagen_antes', $id );
		$despues = nortia_field( 'imagen_despues', $id );
		$fotos   = '';
		if ( $antes || $despues ) {
			$fotos .= '<div class="nortia-antes-despues">';
			if ( $antes ) {
				$fotos .= '<figure>' . wp_get_attachment_image( $antes, 'large' ) . '<figcaption>Antes</figcaption></figure>';
			}
			if ( $despues ) {
				$fotos .= '<figure>' . wp_get_attachment_image( $despues, 'large' ) . '<figcaption>Después</figcaption></figure>';
			}
			$fotos .= '</div>';
		}
		return $ficha . $content . $fotos . '<p><a class="nortia-btn" href="' . esc_url( home_url( '/contacto/' ) ) . '">¿Quieres algo parecido? Pide presupuesto</a></p>';
	}

	if ( is_page() && wp_get_post_parent_id( $id ) ) {
		$precio  = nortia_field( 'precio_desde', $id );
		$plazo   = nortia_field( 'plazo', $id );
		$incluye = nortia_field( 'incluye', $id );
		if ( ! $precio && ! $plazo && ! $incluye ) {
			return $content;
		}
		$box = '<aside class="nortia-servicio">';
		if ( $precio ) {
			$box .= '<p class="nortia-servicio__precio">Desde <strong>' . number_format_i18n( $precio ) . ' €</strong> (IVA no incluido)</p>';
		}
		if ( $plazo ) {
			$box .= '<p>Plazo orientativo: ' . esc_html( $plazo ) . '</p>';
		}
		if ( $incluye ) {
			$box .= '<ul>';
			foreach ( preg_split( '/\r?\n/', $incluye ) as $item ) {
				if ( trim( $item ) ) {
					$box .= '<li>' . esc_html( trim( $item ) ) . '</li>';
				}
			}
			$box .= '</ul>';
		}
		$box .= '</aside>';
		return $content . $box;
	}

	return $content;
} );

/* -------------------------------------------------------------------------
 * Estilos de los componentes a medida
 * ---------------------------------------------------------------------- */
add_action( 'wp_head', function () {
	?>
	<style id="nortia-core">
		.nortia-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:24px;margin:24px 0}
		.nortia-card{display:block;background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;text-decoration:none;color:inherit}
		.nortia-card__img{width:100%;height:200px;object-fit:cover;display:block}
		.nortia-card__title{font-size:1.1rem;margin:12px 16px 4px}
		.nortia-card__meta{margin:0 16px 12px;color:#b45309;font-weight:600}
		.nortia-card__small{margin:-8px 16px 16px;color:#6b7280;font-size:.9rem}
		.nortia-ficha{width:100%;margin-bottom:24px}
		.nortia-ficha th{text-align:left;width:40%}
		.nortia-antes-despues{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:24px 0}
		.nortia-antes-despues figcaption{text-align:center;font-weight:600}
		.nortia-servicio{background:#fff7ed;border-left:4px solid #b45309;padding:16px 24px;margin:24px 0}
		.nortia-servicio__precio{font-size:1.25rem}
		.nortia-btn{display:inline-block;background:#b45309;color:#fff;padding:12px 24px;border-radius:4px;text-decoration:none}
	</style>
	<?php
} );
