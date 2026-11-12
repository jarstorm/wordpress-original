<?php
/**
 * Contenido de demostración de Nortia Reformas (empresa ficticia).
 * Se ejecuta con: wp eval-file /setup/content.php
 *
 * Crea: páginas (con jerarquía), entradas de blog, categorías y etiquetas,
 * proyectos y equipo (tipos de contenido a medida + ACF), formulario CF7,
 * metadatos de Yoast, menús, logo, comentarios y un usuario editor.
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

if ( get_option( 'nortia_demo_version' ) ) {
	WP_CLI::log( 'El contenido de demostración ya estaba creado. Para regenerarlo: docker compose down -v y volver a instalar.' );
	return;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* =========================================================================
 * Helpers de bloques de Gutenberg
 * ====================================================================== */

function nb_p( $t ) {
	return "<!-- wp:paragraph -->\n<p>$t</p>\n<!-- /wp:paragraph -->\n\n";
}

function nb_h( $t, $level = 2 ) {
	$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';
	return "<!-- wp:heading$attrs -->\n<h$level class=\"wp-block-heading\">$t</h$level>\n<!-- /wp:heading -->\n\n";
}

function nb_list( $items ) {
	$li = '';
	foreach ( $items as $i ) {
		$li .= "<!-- wp:list-item -->\n<li>$i</li>\n<!-- /wp:list-item -->";
	}
	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">$li</ul>\n<!-- /wp:list -->\n\n";
}

function nb_buttons( $buttons ) {
	$b = '';
	foreach ( $buttons as $text => $url ) {
		$b .= "<!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"$url\">$text</a></div>\n<!-- /wp:button -->";
	}
	return "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\">$b</div>\n<!-- /wp:buttons -->\n\n";
}

function nb_columns( $cols ) {
	$c = '';
	foreach ( $cols as $inner ) {
		$c .= "<!-- wp:column -->\n<div class=\"wp-block-column\">" . trim( $inner ) . "</div>\n<!-- /wp:column -->\n\n";
	}
	return "<!-- wp:columns -->\n<div class=\"wp-block-columns\">" . trim( $c ) . "</div>\n<!-- /wp:columns -->\n\n";
}

function nb_image( $id, $size = 'large' ) {
	if ( ! $id ) {
		return '';
	}
	$url = wp_get_attachment_image_url( $id, $size );
	$alt = esc_attr( get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	return "<!-- wp:image {\"id\":$id,\"sizeSlug\":\"$size\",\"linkDestination\":\"none\"} -->\n<figure class=\"wp-block-image size-$size\"><img src=\"$url\" alt=\"$alt\" class=\"wp-image-$id\"/></figure>\n<!-- /wp:image -->\n\n";
}

function nb_cover( $id, $inner ) {
	$url = $id ? wp_get_attachment_image_url( $id, 'full' ) : '';
	return "<!-- wp:cover {\"url\":\"$url\",\"id\":$id,\"dimRatio\":50,\"minHeight\":520,\"isDark\":true,\"align\":\"full\"} -->\n"
		. "<div class=\"wp-block-cover alignfull is-dark\" style=\"min-height:520px\"><span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim\"></span>"
		. "<img class=\"wp-block-cover__image-background wp-image-$id\" alt=\"\" src=\"$url\" data-object-fit=\"cover\"/>"
		. "<div class=\"wp-block-cover__inner-container\">" . trim( $inner ) . "</div></div>\n<!-- /wp:cover -->\n\n";
}

function nb_group( $inner, $bg = '#fff7ed' ) {
	return "<!-- wp:group {\"style\":{\"color\":{\"background\":\"$bg\"},\"spacing\":{\"padding\":{\"top\":\"48px\",\"bottom\":\"48px\",\"left\":\"24px\",\"right\":\"24px\"}}},\"layout\":{\"type\":\"constrained\"}} -->\n"
		. "<div class=\"wp-block-group has-background\" style=\"background-color:$bg;padding-top:48px;padding-right:24px;padding-bottom:48px;padding-left:24px\">" . trim( $inner ) . "</div>\n<!-- /wp:group -->\n\n";
}

function nb_quote( $text, $cite ) {
	return "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>$text</p>\n<!-- /wp:paragraph --><cite>$cite</cite></blockquote>\n<!-- /wp:quote -->\n\n";
}

function nb_separator() {
	return "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->\n\n";
}

function nb_shortcode( $sc ) {
	return "<!-- wp:shortcode -->\n$sc\n<!-- /wp:shortcode -->\n\n";
}

function nb_html( $html ) {
	return "<!-- wp:html -->\n$html\n<!-- /wp:html -->\n\n";
}

/* =========================================================================
 * Imágenes: intenta descargar una foto de picsum.photos; si no hay red,
 * genera una imagen de relleno con GD.
 * ====================================================================== */

function nortia_placeholder_file( $text, $w, $h ) {
	$small = imagecreatetruecolor( 320, (int) round( 320 * $h / $w ) );
	$hash  = crc32( $text );
	$bg    = imagecolorallocate( $small, 60 + ( $hash & 0x7f ), 60 + ( ( $hash >> 8 ) & 0x7f ), 60 + ( ( $hash >> 16 ) & 0x7f ) );
	$fg    = imagecolorallocate( $small, 255, 255, 255 );
	imagefill( $small, 0, 0, $bg );
	$text = function_exists( 'remove_accents' ) ? remove_accents( $text ) : $text;
	$lines = explode( "\n", wordwrap( $text, 34, "\n", true ) );
	$y     = (int) ( imagesy( $small ) / 2 - count( $lines ) * 8 );
	foreach ( $lines as $line ) {
		$x = (int) ( ( 320 - strlen( $line ) * imagefontwidth( 4 ) ) / 2 );
		imagestring( $small, 4, max( 4, $x ), $y, $line, $fg );
		$y += 18;
	}
	$big = imagecreatetruecolor( $w, $h );
	imagecopyresampled( $big, $small, 0, 0, 0, 0, $w, $h, imagesx( $small ), imagesy( $small ) );
	$tmp = wp_tempnam( 'nortia' );
	imagejpeg( $big, $tmp, 82 );
	imagedestroy( $small );
	imagedestroy( $big );
	return $tmp;
}

function nortia_image( $seed, $title, $w = 1200, $h = 800, $allow_download = true ) {
	$tmp = $allow_download ? download_url( "https://picsum.photos/seed/nortia-$seed/$w/$h.jpg", 20 ) : new WP_Error( 'skip', 'skip' );
	if ( is_wp_error( $tmp ) ) {
		$tmp = nortia_placeholder_file( $title, $w, $h );
	}
	$file = array(
		'name'     => sanitize_title( $title ) . '.jpg',
		'tmp_name' => $tmp,
	);
	$id = media_handle_sideload( $file, 0, $title );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		WP_CLI::warning( "No se pudo crear la imagen '$title': " . $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	// Prefijo "foto-" para que el slug del adjunto no choque con el de la entrada/proyecto.
	wp_update_post( array( 'ID' => $id, 'post_name' => 'foto-' . sanitize_title( $title ) ) );
	WP_CLI::log( "  imagen: $title" );
	return $id;
}

/* =========================================================================
 * Helpers de contenido
 * ====================================================================== */

function nortia_insert( $args, $seo = null ) {
	$defaults = array(
		'post_status' => 'publish',
		'post_author' => 1,
	);
	$id = wp_insert_post( array_merge( $defaults, $args ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	if ( $seo ) {
		update_post_meta( $id, '_yoast_wpseo_title', $seo[0] );
		update_post_meta( $id, '_yoast_wpseo_metadesc', $seo[1] );
		if ( isset( $seo[2] ) ) {
			update_post_meta( $id, '_yoast_wpseo_focuskw', $seo[2] );
		}
	}
	return $id;
}

function nortia_set_field( $key, $value, $post_id ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( $key, $value, $post_id );
	} else {
		// Sin ACF: guarda como meta normal (mismo nombre que el campo).
		$names = array(
			'field_nortia_ubicacion'    => 'ubicacion',
			'field_nortia_anio'         => 'anio',
			'field_nortia_superficie'   => 'superficie_m2',
			'field_nortia_duracion'     => 'duracion_semanas',
			'field_nortia_presupuesto'  => 'presupuesto_aprox',
			'field_nortia_cliente_tipo' => 'cliente_tipo',
			'field_nortia_antes'        => 'imagen_antes',
			'field_nortia_despues'      => 'imagen_despues',
			'field_nortia_destacado'    => 'destacado',
			'field_nortia_cargo'        => 'cargo',
			'field_nortia_experiencia'  => 'anios_experiencia',
			'field_nortia_linkedin'     => 'linkedin',
			'field_nortia_precio_desde' => 'precio_desde',
			'field_nortia_plazo'        => 'plazo',
			'field_nortia_incluye'      => 'incluye',
		);
		update_post_meta( $post_id, $names[ $key ], $value );
	}
}

WP_CLI::log( 'Creando contenido de Nortia Reformas...' );

/* =========================================================================
 * Limpieza del contenido por defecto de WordPress
 * ====================================================================== */
foreach ( array( 'hello-world', 'sample-page', 'pagina-ejemplo', 'hola-mundo' ) as $slug ) {
	foreach ( get_posts( array( 'name' => $slug, 'post_type' => array( 'post', 'page' ), 'post_status' => 'any' ) ) as $p ) {
		wp_delete_post( $p->ID, true );
	}
}
$privacy_draft = (int) get_option( 'wp_page_for_privacy_policy' );
if ( $privacy_draft && 'draft' === get_post_status( $privacy_draft ) ) {
	wp_delete_post( $privacy_draft, true );
}

/* =========================================================================
 * Usuario editor (autora del blog)
 * ====================================================================== */
$editor_id = username_exists( 'marta' );
if ( ! $editor_id ) {
	$editor_id = wp_insert_user( array(
		'user_login'   => 'marta',
		'user_pass'    => 'marta',
		'user_email'   => 'marta@nortiareformas.example',
		'first_name'   => 'Marta',
		'last_name'    => 'Rodríguez',
		'display_name' => 'Marta Rodríguez',
		'role'         => 'editor',
		'description'  => 'Responsable de comunicación de Nortia Reformas.',
	) );
}

/* =========================================================================
 * Logo e icono del sitio (siempre generados localmente)
 * ====================================================================== */
$logo_tmp = ( function () {
	$im = imagecreatetruecolor( 480, 120 );
	imagesavealpha( $im, true );
	imagefill( $im, 0, 0, imagecolorallocatealpha( $im, 0, 0, 0, 127 ) );
	$naranja = imagecolorallocate( $im, 180, 83, 9 );
	$gris    = imagecolorallocate( $im, 31, 41, 55 );
	imagefilledrectangle( $im, 8, 20, 88, 100, $naranja );
	$small = imagecreatetruecolor( 120, 30 );
	imagesavealpha( $small, true );
	imagefill( $small, 0, 0, imagecolorallocatealpha( $small, 0, 0, 0, 127 ) );
	imagestring( $small, 5, 2, 7, 'NORTIA', imagecolorallocate( $small, 31, 41, 55 ) );
	imagecopyresampled( $im, $small, 104, 20, 0, 0, 360, 90, 120, 30 );
	imagestring( $im, 5, 36, 52, 'N', imagecolorallocate( $im, 255, 255, 255 ) );
	$tmp = wp_tempnam( 'nortia-logo' );
	imagepng( $im, $tmp );
	imagedestroy( $im );
	imagedestroy( $small );
	unset( $gris );
	return $tmp;
} )();
$logo_id = media_handle_sideload( array( 'name' => 'logo-nortia-reformas.png', 'tmp_name' => $logo_tmp ), 0, 'Logo Nortia Reformas' );
if ( ! is_wp_error( $logo_id ) ) {
	set_theme_mod( 'custom_logo', $logo_id );
	update_option( 'site_icon', $logo_id );
}

/* =========================================================================
 * Imágenes principales
 * ====================================================================== */
$img = array(
	'hero'     => nortia_image( 'hero', 'Reforma integral de vivienda en Gijón', 1920, 1080 ),
	'equipo'   => nortia_image( 'equipo', 'Equipo de Nortia Reformas en obra', 1200, 800 ),
	'cocinas'  => nortia_image( 'cocinas', 'Reforma de cocina moderna', 1200, 800 ),
	'banos'    => nortia_image( 'banos', 'Reforma de baño con plato de ducha', 1200, 800 ),
	'integral' => nortia_image( 'integral', 'Reforma integral de piso', 1200, 800 ),
	'fachadas' => nortia_image( 'fachadas', 'Rehabilitación de fachada', 1200, 800 ),
	'contacto' => nortia_image( 'oficina', 'Oficina de Nortia Reformas', 1200, 800 ),
);

/* =========================================================================
 * Categorías y etiquetas del blog
 * ====================================================================== */
$cats = array();
foreach ( array(
	'consejos'       => array( 'Consejos', 'Ideas y recomendaciones para planificar tu reforma.' ),
	'antes-despues'  => array( 'Antes y después', 'Transformaciones reales de nuestros clientes.' ),
	'normativa'      => array( 'Normativa y ayudas', 'Licencias, permisos y subvenciones para reformas.' ),
	'noticias'       => array( 'Noticias', 'Novedades de Nortia Reformas.' ),
) as $slug => $data ) {
	$t = term_exists( $slug, 'category' );
	if ( ! $t ) {
		$t = wp_insert_term( $data[0], 'category', array( 'slug' => $slug, 'description' => $data[1] ) );
	}
	$cats[ $slug ] = (int) $t['term_id'];
}
$uncat = get_term_by( 'slug', 'sin-categoria', 'category' ) ?: get_term_by( 'slug', 'uncategorized', 'category' );
if ( $uncat ) {
	update_option( 'default_category', $cats['noticias'] );
	wp_delete_term( $uncat->term_id, 'category' );
}

/* =========================================================================
 * Formulario de contacto (Contact Form 7)
 * ====================================================================== */
$form_shortcode = '<p>[Formulario de contacto no disponible: activa Contact Form 7]</p>';
if ( class_exists( 'WPCF7_ContactForm' ) ) {
	$cf = WPCF7_ContactForm::get_template( array( 'title' => 'Solicitud de presupuesto', 'locale' => 'es_ES' ) );
	$form = '<label> Nombre y apellidos*
    [text* your-name autocomplete:name] </label>

<label> Email*
    [email* your-email autocomplete:email] </label>

<label> Teléfono
    [tel your-phone autocomplete:tel] </label>

<label> Tipo de obra
    [select tipo-obra "Reforma integral" "Cocina" "Baño" "Fachada" "Otro"] </label>

<label> Cuéntanos tu proyecto*
    [textarea* your-message] </label>

[acceptance privacidad] He leído y acepto la <a href="/politica-de-privacidad/">política de privacidad</a> [/acceptance]

[submit "Solicitar presupuesto"]';
	$mail                = $cf->prop( 'mail' );
	$mail['subject']     = 'Nueva solicitud de presupuesto: [tipo-obra]';
	$mail['recipient']   = 'info@nortiareformas.example';
	$mail['body']        = "Nombre: [your-name]\nEmail: [your-email]\nTeléfono: [your-phone]\nTipo de obra: [tipo-obra]\n\nMensaje:\n[your-message]";
	$cf->set_properties( array( 'form' => $form, 'mail' => $mail ) );
	$cf->save();
	$cf_ref         = method_exists( $cf, 'hash' ) && $cf->hash() ? substr( $cf->hash(), 0, 7 ) : $cf->id();
	$form_shortcode = '[contact-form-7 id="' . $cf_ref . '" title="Solicitud de presupuesto"]';
}

/* =========================================================================
 * Páginas
 * ====================================================================== */
WP_CLI::log( 'Páginas...' );

$pages = array();

// Servicios (padre) e hijas
$servicios = array(
	'reformas-integrales' => array(
		'title'   => 'Reformas integrales',
		'img'     => 'integral',
		'intro'   => 'Transformamos tu vivienda de arriba abajo con un único interlocutor: proyecto, licencias, obra y limpieza final.',
		'precio'  => 450,
		'plazo'   => '8 a 14 semanas según superficie',
		'incluye' => "Visita y medición sin compromiso\nProyecto y render 3D\nGestión de licencias municipales\nDemolición y retirada de escombros\nFontanería, electricidad y carpintería\nLimpieza final de obra",
		'seo'     => array( 'Reformas integrales en Gijón y Asturias | Nortia Reformas', 'Reformas integrales de pisos y casas en Asturias con precio cerrado y plazos garantizados. Pide tu presupuesto sin compromiso.', 'reformas integrales Gijón' ),
		'nota'    => 'Precio por m² orientativo.',
	),
	'reforma-de-cocinas' => array(
		'title'   => 'Reforma de cocinas',
		'img'     => 'cocinas',
		'intro'   => 'Diseñamos cocinas funcionales y bonitas, adaptadas a tu forma de cocinar y al espacio disponible.',
		'precio'  => 6500,
		'plazo'   => '2 a 4 semanas',
		'incluye' => "Diseño de distribución\nMobiliario y encimera\nElectrodomésticos (opcional)\nAlicatado y suelo\nInstalación eléctrica y fontanería",
		'seo'     => array( 'Reforma de cocinas en Asturias | Nortia Reformas', 'Reformamos tu cocina en 2-4 semanas: diseño, muebles, encimeras e instalaciones. Presupuesto cerrado.', 'reforma de cocinas' ),
		'nota'    => '',
	),
	'reforma-de-banos' => array(
		'title'   => 'Reforma de baños',
		'img'     => 'banos',
		'intro'   => 'Cambiamos la bañera por plato de ducha en 48 horas o reformamos el baño completo en menos de dos semanas.',
		'precio'  => 4200,
		'plazo'   => '1 a 2 semanas',
		'incluye' => "Cambio de bañera por plato de ducha\nSanitarios y grifería\nMampara a medida\nAlicatado completo\nMueble de lavabo y espejo",
		'seo'     => array( 'Reforma de baños en Gijón | Nortia Reformas', 'Reforma de baños completa en menos de dos semanas y cambio de bañera por ducha en 48 horas.', 'reforma de baños Gijón' ),
		'nota'    => '',
	),
	'rehabilitacion-de-fachadas' => array(
		'title'   => 'Rehabilitación de fachadas',
		'img'     => 'fachadas',
		'intro'   => 'Trabajamos con comunidades de vecinos para rehabilitar fachadas, mejorar el aislamiento y acceder a ayudas públicas.',
		'precio'  => 35000,
		'plazo'   => '6 a 12 semanas',
		'incluye' => "Inspección técnica del edificio\nProyecto técnico y dirección de obra\nAislamiento térmico (SATE)\nGestión de ayudas y subvenciones\nAndamios y seguridad",
		'seo'     => array( 'Rehabilitación de fachadas y SATE en Asturias | Nortia Reformas', 'Rehabilitación energética de fachadas para comunidades de vecinos, con gestión de ayudas incluida.', 'rehabilitación de fachadas' ),
		'nota'    => 'Precio orientativo para un edificio pequeño.',
	),
);

$servicios_intro = nb_p( 'En Nortia Reformas llevamos más de 20 años reformando viviendas, locales y edificios en Asturias. Trabajamos con presupuesto cerrado, plazos por contrato y un jefe de obra dedicado a cada proyecto.' );
$cols            = array();
foreach ( $servicios as $slug => $s ) {
	$cols[] = nb_image( $img[ $s['img'] ], 'medium_large' ) . nb_h( '<a href="/servicios/' . $slug . '/">' . $s['title'] . '</a>', 3 ) . nb_p( $s['intro'] );
}
$pages['servicios'] = nortia_insert(
	array(
		'post_type'    => 'page',
		'post_title'   => 'Servicios',
		'post_name'    => 'servicios',
		'menu_order'   => 3,
		'post_content' => $servicios_intro . nb_columns( array_slice( $cols, 0, 2 ) ) . nb_columns( array_slice( $cols, 2, 2 ) ) . nb_buttons( array( 'Pide presupuesto sin compromiso' => '/contacto/' ) ),
	),
	array( 'Servicios de reformas en Asturias | Nortia Reformas', 'Reformas integrales, cocinas, baños y rehabilitación de fachadas en Gijón, Oviedo y Avilés.' )
);

$order = 1;
foreach ( $servicios as $slug => $s ) {
	$content  = nb_p( '<strong>' . $s['intro'] . '</strong>' );
	$content .= nb_image( $img[ $s['img'] ] );
	$content .= nb_h( 'Cómo trabajamos' );
	$content .= nb_list( array(
		'<strong>Visita gratuita:</strong> vamos a tu casa, medimos y escuchamos lo que necesitas.',
		'<strong>Presupuesto cerrado</strong> en menos de 7 días, desglosado partida a partida.',
		'<strong>Planificación:</strong> fecha de inicio y de fin por contrato.',
		'<strong>Ejecución</strong> con un jefe de obra como único interlocutor.',
		'<strong>Entrega y garantía</strong> de 2 años en mano de obra.',
	) );
	$content .= nb_h( 'Preguntas frecuentes' );
	$content .= nb_h( '¿Necesito licencia de obra?', 3 ) . nb_p( 'Depende del alcance. Nosotros lo comprobamos y, si hace falta, tramitamos la licencia o la declaración responsable ante el ayuntamiento.' );
	$content .= nb_h( '¿Puedo seguir viviendo en casa durante la obra?', 3 ) . nb_p( 'En reformas parciales normalmente sí. En reformas integrales te recomendamos buscar alternativa durante las fases de demolición e instalaciones.' );
	if ( $s['nota'] ) {
		$content .= nb_p( '<em>' . $s['nota'] . '</em>' );
	}
	$content .= nb_buttons( array( 'Quiero presupuesto' => '/contacto/' ) );

	$id = nortia_insert(
		array(
			'post_type'    => 'page',
			'post_title'   => $s['title'],
			'post_name'    => $slug,
			'post_parent'  => $pages['servicios'],
			'menu_order'   => $order++,
			'post_content' => $content,
		),
		$s['seo']
	);
	set_post_thumbnail( $id, $img[ $s['img'] ] );
	nortia_set_field( 'field_nortia_precio_desde', $s['precio'], $id );
	nortia_set_field( 'field_nortia_plazo', $s['plazo'], $id );
	nortia_set_field( 'field_nortia_incluye', $s['incluye'], $id );
	$pages[ $slug ] = $id;
}

// Inicio
$home  = nb_cover(
	$img['hero'],
	nb_h( 'Reformas en Asturias con precio cerrado y sin sorpresas', 1 )
	. nb_p( 'Más de 20 años y 1.200 obras terminadas en Gijón, Oviedo y Avilés.' )
	. nb_buttons( array( 'Pide presupuesto gratis' => '/contacto/', 'Ver proyectos' => '/proyectos/' ) )
);
$home .= nb_h( 'Nuestros servicios' );
$home .= nb_columns( array(
	nb_h( 'Reformas integrales', 3 ) . nb_p( $servicios['reformas-integrales']['intro'] ) . nb_p( '<a href="/servicios/reformas-integrales/">Más información →</a>' ),
	nb_h( 'Cocinas', 3 ) . nb_p( $servicios['reforma-de-cocinas']['intro'] ) . nb_p( '<a href="/servicios/reforma-de-cocinas/">Más información →</a>' ),
	nb_h( 'Baños', 3 ) . nb_p( $servicios['reforma-de-banos']['intro'] ) . nb_p( '<a href="/servicios/reforma-de-banos/">Más información →</a>' ),
	nb_h( 'Fachadas', 3 ) . nb_p( $servicios['rehabilitacion-de-fachadas']['intro'] ) . nb_p( '<a href="/servicios/rehabilitacion-de-fachadas/">Más información →</a>' ),
) );
$home .= nb_separator();
$home .= nb_columns( array(
	nb_image( $img['equipo'] ),
	nb_h( '¿Por qué elegir Nortia?' ) . nb_list( array(
		'Presupuesto cerrado: lo que firmas es lo que pagas.',
		'Plazos por contrato, con penalización si nos retrasamos.',
		'Un jefe de obra como único interlocutor.',
		'Equipo propio, sin subcontratas en cadena.',
		'2 años de garantía en mano de obra.',
	) ) . nb_buttons( array( 'Conoce al equipo' => '/sobre-nosotros/' ) ),
) );
$home .= nb_h( 'Proyectos destacados' );
$home .= nb_shortcode( '[nortia_proyectos limite="3" destacados="1"]' );
$home .= nb_h( 'Lo que dicen nuestros clientes' );
$home .= nb_columns( array(
	nb_quote( 'Nos reformaron la cocina en tres semanas, justo lo que nos dijeron. Limpios, puntuales y muy atentos.', 'Carmen, Gijón' ),
	nb_quote( 'La comunidad llevaba años con humedades. Nos gestionaron hasta las ayudas. Muy recomendables.', 'Presidente de comunidad, Oviedo' ),
	nb_quote( 'Cambiamos la bañera por ducha en dos días. Mi madre está encantada.', 'José Luis, Avilés' ),
) );
$home .= nb_h( 'Últimas entradas del blog' );
$home .= "<!-- wp:latest-posts {\"postsToShow\":3,\"displayPostDate\":true,\"displayFeaturedImage\":true,\"featuredImageSizeSlug\":\"medium\",\"postLayout\":\"grid\",\"columns\":3} /-->\n\n";
$home .= nb_group( nb_h( '¿Tienes un proyecto en mente?' ) . nb_p( 'Te visitamos sin compromiso y en menos de una semana tienes tu presupuesto.' ) . nb_buttons( array( 'Llámanos: 985 000 000' => 'tel:+34985000000', 'Escríbenos' => '/contacto/' ) ) );

$pages['inicio'] = nortia_insert(
	array(
		'post_type'    => 'page',
		'post_title'   => 'Inicio',
		'post_name'    => 'inicio',
		'menu_order'   => 1,
		'post_content' => $home,
	),
	array( 'Nortia Reformas | Reformas integrales en Gijón y Asturias', 'Empresa de reformas en Asturias: reformas integrales, cocinas, baños y fachadas con presupuesto cerrado. Más de 20 años de experiencia.', 'reformas Asturias' )
);
// Ajustes de Astra para la portada: sin título de página y a ancho completo.
update_post_meta( $pages['inicio'], 'site-post-title', 'disabled' );
update_post_meta( $pages['inicio'], 'ast-site-content-layout', 'full-width-container' );
update_post_meta( $pages['inicio'], 'site-content-style', 'unboxed' );
update_post_meta( $pages['inicio'], 'site-sidebar-layout', 'no-sidebar' );

// Sobre nosotros
$about  = nb_p( 'Nortia Reformas nació en 2004 en Gijón como un pequeño taller de carpintería. Hoy somos un equipo de 18 personas que se encarga de todo el proceso de una reforma, del primer boceto a la limpieza final.' );
$about .= nb_image( $img['equipo'] );
$about .= nb_h( 'Nuestra forma de trabajar' );
$about .= nb_p( 'Creemos que una reforma no tiene por qué ser una pesadilla. Por eso trabajamos con presupuestos cerrados, comunicación semanal con el cliente y equipos propios que conocen nuestro nivel de exigencia.' );
$about .= nb_columns( array(
	nb_h( '+20', 3 ) . nb_p( 'años de experiencia' ),
	nb_h( '+1.200', 3 ) . nb_p( 'obras terminadas' ),
	nb_h( '4,8/5', 3 ) . nb_p( 'valoración media de clientes' ),
) );
$about .= nb_h( 'El equipo' );
$about .= nb_shortcode( '[nortia_equipo]' );
$about .= nb_h( 'Certificaciones' );
$about .= nb_list( array( 'Empresa inscrita en el Registro de Empresas Acreditadas (REA)', 'Instaladores autorizados de electricidad y fontanería', 'Seguro de responsabilidad civil de 600.000 €' ) );

$pages['sobre-nosotros'] = nortia_insert(
	array(
		'post_type'    => 'page',
		'post_title'   => 'Sobre nosotros',
		'post_name'    => 'sobre-nosotros',
		'menu_order'   => 2,
		'post_content' => $about,
	),
	array( 'Sobre nosotros | Nortia Reformas', 'Conoce al equipo de Nortia Reformas: más de 20 años haciendo reformas en Asturias con equipo propio.' )
);
set_post_thumbnail( $pages['sobre-nosotros'], $img['equipo'] );

// Blog (página de entradas)
$pages['blog'] = nortia_insert(
	array(
		'post_type'  => 'page',
		'post_title' => 'Blog',
		'post_name'  => 'blog',
		'menu_order' => 5,
	),
	array( 'Blog de reformas | Nortia Reformas', 'Consejos, ideas, normativa y ejemplos reales de reformas en Asturias.' )
);

// Contacto
$contact  = nb_p( 'Cuéntanos qué necesitas y te llamamos en menos de 24 horas laborables. La visita y el presupuesto son gratuitos.' );
$contact .= nb_columns( array(
	nb_shortcode( $form_shortcode ),
	nb_h( 'Datos de contacto', 3 )
	. nb_p( '<strong>Teléfono:</strong> <a href="tel:+34985000000">985 000 000</a><br><strong>WhatsApp:</strong> 600 000 000<br><strong>Email:</strong> <a href="mailto:info@nortiareformas.example">info@nortiareformas.example</a>' )
	. nb_p( '<strong>Oficina y exposición:</strong><br>Calle Ejemplo 12, bajo<br>33201 Gijón (Asturias)' )
	. nb_p( '<strong>Horario:</strong><br>Lunes a jueves: 8:00–14:00 y 16:00–19:00<br>Viernes: 8:00–15:00' )
	. nb_image( $img['contacto'], 'medium_large' ),
) );
$contact .= nb_h( 'Cómo llegar' );
$contact .= nb_html( '<iframe title="Mapa de la oficina de Nortia Reformas" width="100%" height="400" style="border:0" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=-5.6730%2C43.5370%2C-5.6530%2C43.5470&amp;layer=mapnik&amp;marker=43.5420%2C-5.6630"></iframe>' );

$pages['contacto'] = nortia_insert(
	array(
		'post_type'    => 'page',
		'post_title'   => 'Contacto',
		'post_name'    => 'contacto',
		'menu_order'   => 6,
		'post_content' => $contact,
	),
	array( 'Contacto y presupuesto sin compromiso | Nortia Reformas', 'Pide presupuesto gratis para tu reforma en Asturias. Te llamamos en menos de 24 horas.' )
);

// Páginas legales
$legal = array(
	'aviso-legal'           => array( 'Aviso legal', array(
		'Datos identificativos' => 'En cumplimiento de la Ley 34/2002, de Servicios de la Sociedad de la Información y de Comercio Electrónico, se informa de que el titular de este sitio web es NORTIA REFORMAS S.L. (empresa ficticia de demostración), con domicilio en Calle Ejemplo 12, 33201 Gijón (Asturias) y correo electrónico info@nortiareformas.example.',
		'Condiciones de uso'    => 'El acceso a este sitio web atribuye la condición de usuario e implica la aceptación de estas condiciones. El usuario se compromete a hacer un uso adecuado de los contenidos.',
		'Propiedad intelectual' => 'Todos los contenidos de este sitio web, incluidos textos, fotografías y logotipos, son propiedad del titular o se usan con autorización.',
	) ),
	'politica-de-privacidad' => array( 'Política de privacidad', array(
		'Responsable del tratamiento' => 'NORTIA REFORMAS S.L. (empresa ficticia de demostración). Email: info@nortiareformas.example.',
		'Finalidad'                   => 'Tratamos los datos que nos facilitas a través del formulario de contacto para responder a tu solicitud y, en su caso, elaborar un presupuesto.',
		'Legitimación'                => 'El consentimiento del interesado al enviar el formulario.',
		'Derechos'                    => 'Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad escribiendo a info@nortiareformas.example.',
	) ),
	'politica-de-cookies'   => array( 'Política de cookies', array(
		'¿Qué son las cookies?'        => 'Las cookies son pequeños archivos que se guardan en tu navegador cuando visitas un sitio web.',
		'Cookies que utilizamos'       => 'Usamos cookies técnicas necesarias para el funcionamiento de la web y, si lo aceptas, cookies de análisis para conocer el uso del sitio.',
		'Cómo desactivar las cookies'  => 'Puedes cambiar tus preferencias desde el banner de cookies o desde la configuración de tu navegador.',
	) ),
);
foreach ( $legal as $slug => $l ) {
	$c = '';
	foreach ( $l[1] as $h => $t ) {
		$c .= nb_h( $h ) . nb_p( $t );
	}
	$pages[ $slug ] = nortia_insert( array(
		'post_type'    => 'page',
		'post_title'   => $l[0],
		'post_name'    => $slug,
		'menu_order'   => 20,
		'post_content' => $c,
	) );
}
update_option( 'wp_page_for_privacy_policy', $pages['politica-de-privacidad'] );

// Portada y página de entradas
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pages['inicio'] );
update_option( 'page_for_posts', $pages['blog'] );

/* =========================================================================
 * Equipo
 * ====================================================================== */
WP_CLI::log( 'Equipo...' );
$equipo = array(
	array( 'Luis Menéndez', 'Gerente y fundador', 25 ),
	array( 'Ana Suárez', 'Arquitecta técnica', 12 ),
	array( 'Pablo García', 'Jefe de obra', 18 ),
	array( 'Lucía Álvarez', 'Interiorista', 9 ),
);
foreach ( $equipo as $i => $m ) {
	$id = nortia_insert( array(
		'post_type'  => 'equipo',
		'post_title' => $m[0],
		'menu_order' => $i,
	) );
	set_post_thumbnail( $id, nortia_image( 'persona-' . $i, $m[0] . ', ' . $m[1], 600, 600 ) );
	nortia_set_field( 'field_nortia_cargo', $m[1], $id );
	nortia_set_field( 'field_nortia_experiencia', $m[2], $id );
	nortia_set_field( 'field_nortia_linkedin', 'https://www.linkedin.com/', $id );
}

/* =========================================================================
 * Proyectos
 * ====================================================================== */
WP_CLI::log( 'Proyectos...' );
$tipos = array();
foreach ( array( 'Reforma integral', 'Cocina', 'Baño', 'Fachada' ) as $t ) {
	$term           = term_exists( $t, 'tipo_proyecto' ) ?: wp_insert_term( $t, 'tipo_proyecto' );
	$tipos[ $t ] = (int) $term['term_id'];
}

$proyectos = array(
	array( 'Reforma integral de piso en el Muro de San Lorenzo', 'Reforma integral', 'Gijón', 2025, 110, 12, '68.000 €', 'particular', true,
		'Piso de los años 70 con vistas al mar que necesitaba una actualización completa. Abrimos la cocina al salón, ganamos un segundo baño y renovamos todas las instalaciones.' ),
	array( 'Cocina abierta en una casa de La Calzada', 'Cocina', 'Gijón', 2025, 18, 3, '14.500 €', 'particular', true,
		'Eliminamos un tabique para integrar la cocina en el comedor, con isla central, encimera de porcelánico y electrodomésticos integrados.' ),
	array( 'Baño accesible para persona mayor', 'Baño', 'Avilés', 2024, 6, 1, '5.900 €', 'particular', false,
		'Sustituimos la bañera por un plato de ducha a ras de suelo, con asideros, asiento abatible y grifería termostática.' ),
	array( 'Rehabilitación de fachada con SATE', 'Fachada', 'Oviedo', 2024, 950, 10, '185.000 €', 'comunidad', true,
		'Edificio de 24 viviendas con problemas de humedades. Instalamos un sistema de aislamiento por el exterior (SATE) y gestionamos las ayudas de rehabilitación energética.' ),
	array( 'Reforma de oficinas para una asesoría', 'Reforma integral', 'Gijón', 2023, 240, 8, '96.000 €', 'empresa', false,
		'Redistribución de una planta de oficinas con despachos acristalados, sala de reuniones y zona de descanso, trabajando por fases sin cerrar la actividad.' ),
	array( 'Baño en suite con ducha de obra', 'Baño', 'Oviedo', 2023, 8, 2, '8.200 €', 'particular', false,
		'Baño principal con ducha de obra, mampara fija, doble lavabo y revestimiento de microcemento.' ),
);
foreach ( $proyectos as $i => $p ) {
	list( $title, $tipo, $ubic, $anio, $m2, $sem, $pres, $cliente, $dest, $desc ) = $p;
	$content  = nb_p( $desc );
	$content .= nb_h( 'El reto' ) . nb_p( 'Los propietarios necesitaban terminar la obra en un plazo ajustado y con el mínimo impacto para los vecinos. Planificamos cada fase con antelación y coordinamos los gremios para no tener tiempos muertos.' );
	$content .= nb_h( 'La solución' ) . nb_list( array( 'Proyecto previo con render 3D para validar la distribución', 'Materiales elegidos en nuestra exposición', 'Seguimiento semanal con fotos para el cliente' ) );
	$id = nortia_insert( array(
		'post_type'    => 'proyecto',
		'post_title'   => $title,
		'post_content' => $content,
		'post_excerpt' => $desc,
		'post_date'    => sprintf( '%d-%02d-15 10:00:00', $anio, 3 + $i ),
	), array( $title . ' | Proyectos Nortia Reformas', wp_trim_words( $desc, 24 ) ) );
	wp_set_object_terms( $id, array( $tipos[ $tipo ] ), 'tipo_proyecto' );
	$main = nortia_image( 'proyecto-' . $i, $title );
	set_post_thumbnail( $id, $main );
	nortia_set_field( 'field_nortia_ubicacion', $ubic, $id );
	nortia_set_field( 'field_nortia_anio', $anio, $id );
	nortia_set_field( 'field_nortia_superficie', $m2, $id );
	nortia_set_field( 'field_nortia_duracion', $sem, $id );
	nortia_set_field( 'field_nortia_presupuesto', $pres, $id );
	nortia_set_field( 'field_nortia_cliente_tipo', $cliente, $id );
	nortia_set_field( 'field_nortia_destacado', $dest ? 1 : 0, $id );
	nortia_set_field( 'field_nortia_antes', nortia_image( 'antes-' . $i, 'Antes: ' . $title ), $id );
	nortia_set_field( 'field_nortia_despues', $main, $id );
}

/* =========================================================================
 * Entradas del blog
 * ====================================================================== */
WP_CLI::log( 'Entradas del blog...' );
$posts = array(
	array( 'Cuánto cuesta reformar un piso en Asturias', 'consejos', array( 'presupuesto', 'reforma integral' ), '-2 weeks',
		'Una de las preguntas que más nos hacen es cuánto cuesta reformar un piso. La respuesta depende de muchos factores, pero podemos darte rangos realistas.',
		array( 'Reforma básica' => 'Pintura, suelos y cambio de carpintería interior: entre 250 y 400 €/m².', 'Reforma integral' => 'Incluye instalaciones, cocina y baños: entre 450 y 800 €/m² según calidades.', 'Qué encarece una reforma' => 'Mover bajantes, cambiar la distribución, materiales de importación o plazos muy cortos.' ) ),
	array( '7 errores que debes evitar al reformar tu cocina', 'consejos', array( 'cocinas', 'diseño' ), '-6 weeks',
		'Reformar la cocina es una inversión importante. Estos son los errores más habituales que vemos y cómo evitarlos.',
		array( 'Olvidar el triángulo de trabajo' => 'Fregadero, placa y nevera deben estar a una distancia cómoda entre sí.', 'Quedarse corto de enchufes' => 'Calcula al menos dos enchufes más de los que crees que necesitas.', 'Elegir la encimera solo por estética' => 'Piensa en la resistencia a manchas, calor y golpes.' ) ),
	array( 'Ayudas para rehabilitar edificios en Asturias', 'normativa', array( 'ayudas', 'fachadas', 'eficiencia energética' ), '-3 months',
		'Las comunidades de vecinos pueden acceder a ayudas públicas para mejorar la eficiencia energética de sus edificios. Te explicamos cómo funcionan en términos generales.',
		array( 'Qué obras suelen ser subvencionables' => 'Aislamiento de fachadas y cubiertas, cambio de ventanas y mejora de instalaciones térmicas.', 'Requisitos habituales' => 'Certificado energético antes y después de la obra y una mejora mínima de la calificación.', 'Cómo te ayudamos' => 'Revisamos qué convocatorias están abiertas y preparamos la documentación técnica. Consulta siempre las bases oficiales vigentes.' ) ),
	array( 'Antes y después: un baño de los 80 convertido en spa', 'antes-despues', array( 'baños', 'antes y después' ), '-4 months',
		'Este baño conservaba los azulejos originales de 1985. En diez días lo transformamos por completo.',
		array( 'El punto de partida' => 'Bañera de hierro fundido, azulejo marrón y una sola toma de luz.', 'Lo que hicimos' => 'Plato de ducha extraplano, microcemento, mueble suspendido e iluminación LED indirecta.' ) ),
	array( '¿Necesito licencia para reformar mi casa?', 'normativa', array( 'licencias', 'ayuntamiento' ), '-6 months',
		'Depende del tipo de obra. Te explicamos la diferencia entre obra menor, obra mayor y declaración responsable.',
		array( 'Obra menor' => 'Cambios de acabados sin tocar estructura: normalmente basta con una comunicación o declaración responsable.', 'Obra mayor' => 'Cambios en estructura, fachada o distribución importante: requiere proyecto técnico y licencia.', 'Nuestro consejo' => 'Consulta siempre la ordenanza de tu ayuntamiento antes de empezar.' ) ),
	array( 'Nortia Reformas cumple 20 años', 'noticias', array( 'empresa' ), '-10 months',
		'En 2004 empezamos con un pequeño taller de carpintería en Gijón. Veinte años después seguimos con la misma ilusión.',
		array( 'Gracias' => 'A los más de 1.200 clientes que han confiado en nosotros durante estos años.', 'Lo que viene' => 'Nueva exposición de cocinas y baños y más servicios de rehabilitación energética.' ) ),
	array( 'Suelo laminado, vinílico o porcelánico: cuál elegir', 'consejos', array( 'suelos', 'materiales' ), '-12 months',
		'Elegir el suelo adecuado depende del uso de cada estancia, del presupuesto y de si hay mascotas o niños en casa.',
		array( 'Laminado' => 'Económico y cálido, pero sensible al agua.', 'Vinílico' => 'Resistente al agua y silencioso, ideal para cocinas y baños.', 'Porcelánico' => 'El más duradero y resistente, perfecto con suelo radiante.' ) ),
	array( 'Antes y después: cocina cerrada a cocina abierta', 'antes-despues', array( 'cocinas', 'antes y después' ), '-15 months',
		'Abrir la cocina al salón es una de las reformas más demandadas. Así quedó esta vivienda de La Calzada.',
		array( 'El punto de partida' => 'Cocina pequeña, oscura y separada del salón por un tabique.', 'El resultado' => 'Un espacio diáfano con isla, más luz natural y mucho más almacenaje.' ) ),
);
$post_ids = array();
foreach ( $posts as $i => $p ) {
	list( $title, $cat, $tags, $when, $intro, $sections ) = $p;
	$feat     = nortia_image( 'post-' . $i, $title );
	$content  = nb_p( '<strong>' . $intro . '</strong>' );
	$first    = true;
	foreach ( $sections as $h => $t ) {
		$content .= nb_h( $h ) . nb_p( $t );
		if ( $first ) {
			$content .= nb_p( 'En Nortia Reformas siempre recomendamos visitar varias obras terminadas antes de decidir y pedir que el presupuesto venga desglosado por partidas.' );
			$first    = false;
		}
	}
	if ( 'antes-despues' === $cat ) {
		$content .= nb_columns( array( nb_image( nortia_image( 'post-antes-' . $i, 'Antes: ' . $title ), 'medium_large' ), nb_image( $feat, 'medium_large' ) ) );
	}
	$content .= nb_quote( 'Una buena reforma empieza mucho antes de la primera demolición: empieza con una buena planificación.', 'Luis Menéndez, gerente de Nortia Reformas' );
	$content .= nb_p( '¿Tienes dudas sobre tu reforma? <a href="/contacto/">Escríbenos</a> y te asesoramos sin compromiso.' );

	$date = gmdate( 'Y-m-d H:i:s', strtotime( $when ) );
	$id   = nortia_insert( array(
		'post_type'     => 'post',
		'post_title'    => $title,
		'post_content'  => $content,
		'post_excerpt'  => $intro,
		'post_author'   => $editor_id && ! is_wp_error( $editor_id ) ? $editor_id : 1,
		'post_date'     => $date,
		'post_category' => array( $cats[ $cat ] ),
		'tags_input'    => $tags,
	), array( $title . ' | Blog Nortia Reformas', wp_trim_words( $intro, 24 ), $tags[0] ) );
	set_post_thumbnail( $id, $feat );
	$post_ids[] = $id;
}

// Comentarios
$comentarios = array(
	array( 0, 'Raquel', '¿Estos precios incluyen IVA? Estamos pensando en reformar un piso de 80 m².' ),
	array( 0, 'Nortia Reformas', 'Hola Raquel, los precios son sin IVA. Si quieres, te hacemos una visita sin compromiso y te damos un presupuesto cerrado.' ),
	array( 1, 'Andrés', 'Lo de los enchufes es muy cierto, en nuestra cocina nos quedamos cortos.' ),
	array( 3, 'Elena', '¡Qué cambio! ¿Cuánto tiempo estuvisteis sin poder usar el baño?' ),
);
$parent = 0;
foreach ( $comentarios as $c ) {
	$is_reply = 'Nortia Reformas' === $c[1];
	$cid      = wp_insert_comment( array(
		'comment_post_ID'      => $post_ids[ $c[0] ],
		'comment_author'       => $c[1],
		'comment_author_email' => sanitize_title( $c[1] ) . '@example.com',
		'comment_content'      => $c[2],
		'comment_approved'     => 1,
		'comment_parent'       => $is_reply ? $parent : 0,
		'user_id'              => $is_reply ? 1 : 0,
	) );
	$parent = $cid;
}

/* =========================================================================
 * Menús
 * ====================================================================== */
WP_CLI::log( 'Menús...' );
function nortia_menu_page( $menu, $page_id, $parent = 0 ) {
	return wp_update_nav_menu_item( $menu, 0, array(
		'menu-item-object-id' => $page_id,
		'menu-item-object'    => 'page',
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => $parent,
	) );
}

$main = wp_create_nav_menu( 'Menú principal' );
nortia_menu_page( $main, $pages['inicio'] );
nortia_menu_page( $main, $pages['sobre-nosotros'] );
$serv_item = nortia_menu_page( $main, $pages['servicios'] );
foreach ( array_keys( $servicios ) as $slug ) {
	nortia_menu_page( $main, $pages[ $slug ], $serv_item );
}
wp_update_nav_menu_item( $main, 0, array(
	'menu-item-title'  => 'Proyectos',
	'menu-item-type'   => 'post_type_archive',
	'menu-item-object' => 'proyecto',
	'menu-item-status' => 'publish',
) );
nortia_menu_page( $main, $pages['blog'] );
nortia_menu_page( $main, $pages['contacto'] );

$footer = wp_create_nav_menu( 'Menú legal' );
foreach ( array( 'aviso-legal', 'politica-de-privacidad', 'politica-de-cookies' ) as $slug ) {
	nortia_menu_page( $footer, $pages[ $slug ] );
}

set_theme_mod( 'nav_menu_locations', array(
	'primary'     => $main,
	'mobile_menu' => $main,
	'footer_menu' => $footer,
) );

/* =========================================================================
 * Final
 * ====================================================================== */
update_option( 'nortia_demo_version', '1.0.0' );
flush_rewrite_rules();

WP_CLI::success( sprintf(
	'Contenido creado: %d páginas, %d entradas, %d proyectos, %d miembros del equipo.',
	count( $pages ),
	count( $post_ids ),
	count( $proyectos ),
	count( $equipo )
) );
