<?php
/**
 * WooThemes Projects Core Functions
 *
 * Functions available on both the front-end and admin.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	Projects/Functions
 * @version     1.0.0
 */

if ( ! function_exists( 'projects_get_page_id' ) ) {

	/**
	 * WooThemes Projects page IDs
	 *
	 * retrieve page ids - used for projects
	 *
	 * returns -1 if no page is found
	 *
	 * @access public
	 * @param string $page
	 * @return int
	 */
	function projects_get_page_id ( $page ) {
		$options 	= get_option( 'projects-pages-fields' );
		$page 		= apply_filters( 'projects_get_' . $page . '_page_id', $options[ $page . '_page_id' ] );

		return $page ? $page : -1;
	} // End projects_get_page_id()
}

if ( ! function_exists( 'projects_get_image' ) ) {

	/**
	 * Get the image for the given ID.
	 * @param  int 				$id   Post ID.
	 * @param  string/array/int $size Image dimension. (default: "projects-thumbnail")
	 * @since  1.0.0
	 * @return string       	<img> tag.
	 */
	function projects_get_image ( $id, $size = 'projects-thumbnail' ) {
		$response = '';

		if ( has_post_thumbnail( $id ) ) {
			// If not a string or an array, and not an integer, default to 150x9999.
			if ( is_int( $size ) || ( 0 < intval( $size ) ) ) {
				$size = array( intval( $size ), intval( $size ) );
			} elseif ( ! is_string( $size ) && ! is_array( $size ) ) {
				$size = array( 150, 9999 );
			}
			$response = get_the_post_thumbnail( intval( $id ), $size );
		}

		return $response;
	} // End projects_get_image()

}

/**
 * is_projects - Returns true if on a page which uses WooThemes Projects templates
 *
 * @access public
 * @return bool
 */
function is_projects () {
	return ( is_projects_archive() || is_product_category() || is_project() ) ? true : false;
} // End is_projects()

if ( ! function_exists( 'is_projects_archive' ) ) {

	/**
	 * is_projects_archive - Returns true when viewing the project type archive.
	 *
	 * @access public
	 * @return bool
	 */
	function is_projects_archive() {
		return ( is_post_type_archive( 'project' ) || is_project_taxonomy() || is_page( projects_get_page_id( 'projects' ) ) ) ? true : false;
	} // End is_projects_archive()
}

if ( ! function_exists( 'is_project_taxonomy' ) ) {

	/**
	 * is_project_taxonomy - Returns true when viewing a project taxonomy archive.
	 *
	 * @access public
	 * @return bool
	 */
	function is_project_taxonomy() {
		return is_tax( get_object_taxonomies( 'project' ) );
	} // End is_project_taxonomy()
}

if ( ! function_exists( 'is_product_category' ) ) {

	/**
	 * is_product_category - Returns true when viewing a project category.
	 *
	 * @access public
	 * @param string $term (default: '') The term slug your checking for. Leave blank to return true on any.
	 * @return bool
	 */
	function is_product_category( $term = '' ) {
		return is_tax( 'project-category', $term );
	} // End is_product_category()
}

if ( ! function_exists( 'is_project' ) ) {

	/**
	 * is_project - Returns true when viewing a single project.
	 *
	 * @access public
	 * @return bool
	 */
	function is_project() {
		return is_singular( array( 'project' ) );
	} // End is_project()
}

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @access public
	 * @return bool
	 */
	function is_ajax() {
		if ( defined('DOING_AJAX') )
			return true;

		return ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) ? true : false;
	}
}

/**
 * Get template part (for templates like the projects-loop).
 *
 * @access public
 * @param mixed $slug
 * @param string $name (default: '')
 * @return void
 */
function projects_get_template_part( $slug, $name = '' ) {
	global $projects;
	$template = '';
	// Look in yourtheme/slug-name.php and yourtheme/projects/slug-name.php
	if ( $name )
		$template = locate_template( array ( "{$slug}-{$name}.php", "{$projects->template_url}{$slug}-{$name}.php" ) );

	// Get default slug-name.php
	if ( !$template && $name && file_exists( $projects->plugin_path() . "/templates/{$slug}-{$name}.php" ) )
		$template = $projects->plugin_path() . "/templates/{$slug}-{$name}.php";

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/projects/slug.php
	if ( !$template )
		$template = locate_template( array ( "{$slug}.php", "{$projects->template_url}{$slug}.php" ) );

	if ( $template )
		load_template( $template, false );
} // End projects_get_template_part()


/**
 * Get other templates and including the file.
 *
 * @access public
 * @param mixed $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return void
 */
function projects_get_template ( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	global $projects;

	if ( $args && is_array($args) )
		extract( $args );

	$located = projects_locate_template( $template_name, $template_path, $default_path );

	do_action( 'projects_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'projects_after_template_part', $template_name, $template_path, $located, $args );
} // End projects_get_template()


/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *		yourtheme		/	$template_path	/	$template_name
 *		yourtheme		/	$template_name
 *		$default_path	/	$template_name
 *
 * @access public
 * @param mixed $template_name
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return string
 */
function projects_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	global $projects;

	if ( ! $template_path ) $template_path = $projects->template_url;
	if ( ! $default_path ) $default_path = $projects->plugin_path() . '/templates/';

	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template
	if ( ! $template )
		$template = $default_path . $template_name;

	// Return what we found
	return apply_filters( 'projects_locate_template', $template, $template_name, $template_path );
} // End projects_locate_template()

/**
 * Filter to allow product_cat in the permalinks for projects.
 *
 * @access public
 * @param string $permalink The existing permalink URL.
 * @param object $post
 * @return string
 */
function projects_project_post_type_link( $permalink, $post ) {
    // Abort if post is not a project
    if ( $post->post_type !== 'project' )
    	return $permalink;

    // Abort early if the placeholder rewrite tag isn't in the generated URL
    if ( false === strpos( $permalink, '%' ) )
    	return $permalink;

    // Get the custom taxonomy terms in use by this post
    $terms = get_the_terms( $post->ID, 'project-category' );

    if ( empty( $terms ) ) {
    	// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
        $product_cat = _x( 'uncategorized', 'slug', 'projects-by-mzoo' );
    } else {
    	// Replace the placeholder rewrite tag with the first term's slug
        $first_term = array_shift( $terms );
        $product_cat = $first_term->slug;
    }

    $find = array(
    	'%year%',
    	'%monthnum%',
    	'%day%',
    	'%hour%',
    	'%minute%',
    	'%second%',
    	'%post_id%',
    	'%category%',
    	'%product_category%'
    );

    $replace = array(
    	date_i18n( 'Y', strtotime( $post->post_date ) ),
    	date_i18n( 'm', strtotime( $post->post_date ) ),
    	date_i18n( 'd', strtotime( $post->post_date ) ),
    	date_i18n( 'H', strtotime( $post->post_date ) ),
    	date_i18n( 'i', strtotime( $post->post_date ) ),
    	date_i18n( 's', strtotime( $post->post_date ) ),
    	$post->ID,
    	$product_cat,
    	$product_cat
    );

    $replace = array_map( 'sanitize_title', $replace );

    $permalink = str_replace( $find, $replace, $permalink );

    return $permalink;
} // End projects_project_post_type_link()

add_filter( 'post_type_link', 'projects_project_post_type_link', 10, 2 );

/**
 * projects_get_gallery_attachment_ids function.
 *
 * @access public
 * @return array
 */
function projects_get_gallery_attachment_ids ( $post_id = 0 ) {
	global $post;
	if ( 0 == $post_id ) $post_id = get_the_ID();
	$project_image_gallery = get_post_meta( $post_id, '_project_image_gallery', true );
	if ( '' == $project_image_gallery ) {
		// Backwards compat
		$attachment_ids = get_posts( 'post_parent=' . intval( $post_id ) . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image&fields=ids' );
		$attachment_ids = array_diff( $attachment_ids, array( get_post_thumbnail_id() ) );
		$project_image_gallery = implode( ',', $attachment_ids );
	}
	return array_filter( (array) explode( ',', $project_image_gallery ) );
} // End projects_get_gallery_attachment_ids()


/**
 * Add body classes for Projects pages
 *
 * @param  array $classes
 * @return array
 */
function woo_projects_body_class( $classes ) {
	$classes = (array) $classes;

	if ( is_projects() ) {
		$classes[] = 'projects';
		$classes[] = 'projects-page';
	}

	if ( is_project() ) {

		$attachments = count( projects_get_gallery_attachment_ids() );

		if ( $attachments > 0 ) {
			$classes[] = 'has-gallery';
		} else {
			$classes[] = 'no-gallery';
		}

		if ( !has_post_thumbnail() ) {
			$classes[] = 'no-cover-image';
		}

	}

	return array_unique( $classes );
}


/**
 * Find a category image.
 * @since  1.0.0
 * @return string
 */
function projects_category_image ( $cat_id = 0 ) {

	global $post;

	if ( 0 == $cat_id  ) return;

	$image = '';

	if ( false === ( $image = get_transient( 'projects_category_image_' . $cat_id ) ) ) {

		$cat = get_term( $cat_id, 'project-category' );

		$query_args = array(
			'post_type' => 'project',
			'posts_per_page' => -1,
			'no_found_rows' => 1,
			'tax_query' => array(
				array(
					'taxonomy'	=>	'project-category',
					'field'		=>	'id',
					'terms'		=>	array( $cat->term_id )
				)
			)
		);

		$query = new WP_Query( $query_args );

		while ( $query->have_posts() && $image == '' ) : $query->the_post();

			$image = projects_get_image( get_the_ID() );

			if ( $image ) {
				$image = '<a href="' . get_term_link( $cat ) . '" title="' . $cat->name . '">' . $image . '</a>';
				set_transient( 'projects_category_image_' . $cat->term_id, $image, 60 * 60 ); // 1 Hour.
			}

		endwhile;

		wp_reset_postdata();

	} // get transient

	return $image;

} // End projects_category_image()

/**
 * When a post is saved, flush the transient used to store the category images.
 * @since  1.0.0
 * @return void
 */
function projects_category_image_flush_transient ( $post_id ) {
	if ( get_post_type( $post_id ) != 'project' ) return; // we only want projects
	$categories = get_the_terms( $post_id, 'project-category' );
	if ( $categories ) {
		foreach ($categories as $category ) {
			delete_transient( 'projects_category_image_' . $category->term_id );
		}
	}
} // End projects_category_image_flush_transient()
add_action( 'save_post', 'projects_category_image_flush_transient' );

/**
 * Enqueue styles
 */
function projects_script() {

	// Register projects CSS 
	wp_register_style( 'projects-styles', plugins_url( '/dist/css/woo-projects.css', __FILE__ ), array(), PROJECTS_VERSION );
	wp_register_style( 'projects-handheld', plugins_url( '/dist/css/woo-projects-handheld.css', __FILE__ ), array(), PROJECTS_VERSION );
	  
	if ( is_project() ) {
		wp_enqueue_style( 'pswp-css', plugins_url( '/pswp/photoswipe.css', __FILE__ ) );
	    wp_enqueue_style( 'pswp-skin', plugins_url( '/pswp/default-skin/default-skin.css', __FILE__ )  );
	    
	    wp_enqueue_style( 'slick-css', plugins_url( '/slick/slick.css', __FILE__ ));
	    wp_enqueue_style( 'slick-theme', plugins_url( '/slick/slick-theme.css', __FILE__ ));

		wp_enqueue_script( 'slick', plugins_url( '/slick/slick.min.js', __FILE__ ), ['jquery'], PROJECTS_VERSION, true );
	    
	    wp_enqueue_script( 'pswp', plugins_url( '/pswp/photoswipe.min.js', __FILE__ ), null, PROJECTS_VERSION, true );
	    wp_enqueue_script( 'pswp-ui', plugins_url( '/pswp/photoswipe-ui-default.min.js', __FILE__ ), ['jquery'], PROJECTS_VERSION, true );
	    	
	    wp_enqueue_style( 'mz-project-archive', plugins_url( '/dist/css/slickswipe.css', __FILE__ ));
	    wp_enqueue_script( 'mz-project-archive', plugins_url( '/dist/js/slickswipe.js', __FILE__ ), ['jquery'], PROJECTS_VERSION, true );
	    
	}
	
	if ( is_archive('project') || is_page('portfolio')) {
		wp_enqueue_style( 'pswp-css', plugins_url( '/pswp/photoswipe.css', __FILE__ ) );
	    wp_enqueue_style( 'pswp-skin', plugins_url( '/pswp/default-skin/default-skin.css', __FILE__ )  );
	    
	    wp_enqueue_style( 'mz-project-archive-flickity-skin', plugins_url( '/dist/css/flickity.css', __FILE__ ));
	    wp_enqueue_style( 'projects-flickity', plugins_url( '/flickity/dist/flickity.min.css', __FILE__ ));
	    
	    wp_register_script( 'projects-flickity', plugins_url( '/flickity/dist/flickity.pkgd.min.js', __FILE__ ), ['jquery'], PROJECTS_VERSION, true );
	    wp_register_script( 'pswp', plugins_url( '/pswp/photoswipe.min.js', __FILE__ ), ['jquery'], PROJECTS_VERSION, true );
	    wp_register_script( 'pswp-ui', plugins_url( '/pswp/photoswipe-ui-default.min.js', __FILE__ ), ['jquery'], PROJECTS_VERSION, true );
	    wp_register_script( 'mz-project-archive-init', plugins_url( '/dist/js/project-init.js', __FILE__ ), ['jquery', 'projects-flickity', 'pswp', 'pswp-ui'], PROJECTS_VERSION, true );
	    
	    wp_enqueue_script( 'projects-flickity');
		wp_enqueue_script( 'pswp');
		wp_enqueue_script( 'pswp-ui');
		wp_enqueue_script( 'mz-project-archive-init');

	    // Send _GET variable to script so we start on correct slide if present.
	    $page = isset($_GET['portfolio_item']) ? $_GET['portfolio_item'] : 0;
		$data = array(
			'page' => $page
		);
		wp_localize_script( 'mz-project-archive-init', 'mz_project_archive', $data );
	}

	if ( apply_filters( 'projects_enqueue_styles', true ) ) {

		// Load Main styles
		wp_enqueue_style( 'projects-styles' );

		// Load styles applied to handheld devices
		wp_enqueue_style( 'projects-handheld' );

		// Load Dashicons
		if ( is_project() ) {
			wp_enqueue_style( 'dashicons' );
		}
	}

}

/* Taxonomy Breadcrumb */

    /**
     * In version 1.5 Jan 2020 Mike iLL replaces this with Breadcrumb NavXT plugin, called in theme.
     *
     * Source: https://gist.github.com/saqibsarwar/471cb91a6b17ffc457e2
     * @param $post_id  int Post id
     * @param $breadcrumbs_taxonomy string Taxonomy name
     * @return mixed|void
     */

/* Get Term Children */

if (!function_exists('mzoo_get_the_term_children')) {
	/**
     * Get term id and taxonomy. Return array with name and link.
     *
     * @param $term int Term id
     * @param $taxonomy string Taxonomy name
     * @return array
     */
	function mzoo_get_the_term_children($term, $taxonomy = 'project-category') {
		$term_query = new WP_Term_Query( array( 'taxonomy' => $taxonomy, 
												'orderby' => 'name', 
												'child_of' => $term,
												'hide_empty' => true ) );
		if ($term_query->terms == '') return false;
		$term_children_links = array();
		foreach ($term_query->terms as $term):
			$term_children_links[$term->name] = get_term_link($term->term_id);
		endforeach;
		return $term_children_links;
	}
} // if function not exists

/* Return number of posts */

if (!function_exists('mzoo_get_project_count')) {
	/**
     * Grab global query and echo count
     *
     * @return int
     */
	function mzoo_get_project_count() {
		global $wp_query;
		echo  $wp_query->found_posts;
	}
}

if (!function_exists('mzoo_project_archive_default_image')) {
	function mzoo_project_archive_default_image(){
		return plugins_url( 'mz-project-archive/dist/images/placeholder.png', dirname(__FILE__) );
	}
}

add_action( 'wp', 'mzoo_projects_remove_featured_images', 15 );
function mzoo_projects_remove_featured_images() {
    if ( is_singular( 'project' ) ){
        remove_action( 'generate_after_header','generate_page_header', 10 );
    }
}
