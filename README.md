# wordpress-original

**Español** · [English](README.en.md)

Web WordPress de **Nortia Reformas**, una empresa ficticia de reformas de Gijón. Simula la web típica de una pyme española y sirve como **punto de partida para la prueba de concepto de migración a Astro + WordPress headless**.

## Requisitos

- Docker Desktop (o Docker Engine + Compose v2)
- Conexión a internet la primera vez (descarga WordPress, el tema, los plugins, traducciones y fotos de ejemplo)

## Arranque

```bash
docker compose up -d
docker compose run --rm wpcli sh /setup/setup.sh
```

La instalación tarda unos minutos (sobre todo por las ~35 imágenes).

- Web: http://localhost:8080
- Admin: http://localhost:8080/wp-admin → `admin` / `admin`
- Editora del blog: `marta` / `marta`

Para usar otro puerto: `WP_PORT=8090 docker compose up -d` (y la misma variable al ejecutar el setup).

## Reiniciar desde cero

```bash
docker compose down -v
docker compose up -d
docker compose run --rm wpcli sh /setup/setup.sh
```

## Comandos útiles

```bash
docker compose run --rm wpcli wp plugin list
docker compose run --rm wpcli wp post list --post_type=proyecto
docker compose run --rm wpcli wp db export /var/www/html/backup.sql
docker compose run --rm wpcli sh /setup/diagnostico.sh   # tiempo de respuesta con cada plugin activo
```

## Qué incluye

### Tema y plugins (los típicos de una pyme)

| Elemento | Para qué |
|---|---|
| Astra | Tema muy usado en webs corporativas |
| Advanced Custom Fields | Campos personalizados (proyectos, equipo, servicios) |
| Yoast SEO | Títulos y metadescripciones (ya rellenados en páginas, entradas y proyectos) |
| Contact Form 7 + Flamingo | Formulario de presupuesto y registro de mensajes |
| Cookie Notice | Banner de cookies |
| Redirection | Redirecciones |
| UpdraftPlus | Copias de seguridad |
| Wordfence | Seguridad (instalado pero **desactivado**: en local añade unos 16 s por petición) |
| WP Mail SMTP | Envío de correo |
| Smush | Optimización de imágenes |
| Yoast Duplicate Post | Duplicar contenido |

### Código a medida (`mu-plugins/nortia-core.php`)

Simula lo que suele programar la agencia que hizo la web:

- Tipo de contenido **Proyectos** (`/proyectos/`) con taxonomía **Tipo de proyecto**.
- Tipo de contenido **Equipo** (no público, se muestra mediante shortcode).
- Grupos de campos ACF: ficha del proyecto, datos del miembro del equipo y datos del servicio (precio, plazo y qué incluye) en las páginas hijas.
- Shortcodes `[nortia_proyectos]` y `[nortia_equipo]`.
- Filtro `the_content` que añade automáticamente la ficha técnica y las fotos de antes y después en cada proyecto, y el recuadro de precio en cada servicio.

### Contenido (`setup/content.php`)

- **Páginas:** Inicio, Sobre nosotros, Servicios (con 4 subpáginas), Blog, Contacto, Aviso legal, Política de privacidad y Política de cookies.
- **Blog:** 8 entradas en 4 categorías, con etiquetas, imagen destacada, autora (editora) y comentarios con respuesta.
- **Proyectos:** 6, con campos ACF, imágenes de antes y después y 3 destacados en portada.
- **Equipo:** 4 personas.
- Menú principal con submenú, menú legal, logo, icono del sitio, portada estática y enlaces permanentes `/%postname%/`.
- Bloques de Gutenberg variados: portada (cover), columnas, botones, imágenes, listas, citas, grupo, últimas entradas, shortcode y HTML (mapa de OpenStreetMap).

Las fotos se descargan de picsum.photos. Si no hay red, se generan imágenes de relleno.

## Qué medir antes de migrar

1. PageSpeed Insights / Lighthouse (móvil) de: inicio, un servicio, un proyecto y una entrada del blog.
2. Peso total de la página y número de peticiones (DevTools → Network).
3. Tiempo de respuesta del servidor (TTFB).
4. Número de plugins y de scripts y hojas de estilo cargados.

Para que la comparación sea justa, mide también con algún plugin de caché activo (por ejemplo WP Super Cache), como tendría un cliente real.

## Casos que la migración debe cubrir

- Jerarquía de páginas y URLs (`/servicios/reforma-de-banos/`, `/proyectos/...`, `/category/...`, `/tipo-proyecto/...`).
- Metadatos SEO de Yoast.
- Campos ACF y lógica del filtro `the_content` y de los shortcodes.
- Formulario de Contact Form 7.
- Menús anidados, comentarios, autores, categorías y etiquetas.
- Bloque dinámico "Últimas entradas" y bloque HTML con iframe.

## Pendiente (escenario 2)

Una variante con la portada maquetada en **Elementor**, para medir el caso "sucio" más habitual en clientes reales.
