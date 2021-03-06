<?php
// Report all PHP errors
/** Acoes iniciais ** */
//define('ALTERNATE_WP_CRON', true);
wp_register_script('jquery.min', get_template_directory_uri() . '/libraries/js/jquery.min.js', array('jquery'), '1.7');
wp_enqueue_script('jquery.min');
add_action('init', 'wpdbfix');
add_action('init', 'register_post_types');
add_action('init', 'register_taxonomies');
load_theme_textdomain("tainacan", dirname(__FILE__) . "/languages");
/* * **************** MENU FUNCTION PARA O WORDPRESS ADMIN *********************** */
/**
 * Registra o menu do Tainacan no Wordpress
 *
 * @return void Apenas insere o menu do tainacan no wordpress
 */
function register_my_menu() {
    register_nav_menu('header-menu', __('Header Menu', 'tainacan'));
}

add_action('init', 'register_my_menu');
/* * **************** END MENU FUNCTION PARA O WORDPRESS ADMIN *********************** */

$conditional_scripts = array(
    'html5shiv' => '//cdn.jsdelivr.net/html5shiv/3.7.2/html5shiv.js',
    'html5shiv-printshiv' => '//cdn.jsdelivr.net/html5shiv/3.7.2/html5shiv-printshiv.js',
    'respond' => '//cdn.jsdelivr.net/respond/1.4.2/respond.min.js'
);
foreach ($conditional_scripts as $handle => $src) {
    wp_enqueue_script($handle, $src, array(), '', false);
}
add_filter('script_loader_tag', function( $tag, $handle ) use ( $conditional_scripts ) {
    if (array_key_exists($handle, $conditional_scripts)) {
        $tag = "<!--[if lt IE 9]>$tag<![endif]-->";
    }
    return $tag;
}, 10, 2);

/* * * CONSTANTE PATH DO WORDPRESS * */
if (!defined('WORDPRESS_PATH')) {
    $iroot = getcwd();
    $folder = explode("/", $iroot);
    if(count($folder )==1){
	define('WORDPRESS_PATH', $folder[0]);
    }else{
	define('WORDPRESS_PATH', $iroot);
    }
}

/**
 * Retorna uma string com o tipo text/html.
 *
 * @return void retorna o tipo text/html.
 */
function set_html_content_type() {
    return 'text/html';
}

function modify_attachment_link($markup) {
    return preg_replace('/^<a([^>]+)>(.*)$/', '<a\\1 target="_blank">\\2', $markup);
}

add_filter('wp_get_attachment_link', 'modify_attachment_link', 10, 6);

/**
 * Altera o link para os feeds
 * * */
add_action('template_redirect', 'socialdb_catch_uri', 99);

function socialdb_catch_uri() {
    global $wp_query;
    if (get_query_var('collection_name')) {
        $_GET['collection_id'] = get_post_by_name(get_query_var('collection_name'))->ID;
        $_GET['operation'] = 'feed';
        $_GET['by_function'] = true;
        $get_privacity = wp_get_object_terms($_GET['collection_id'], 'socialdb_collection_type');
        if ($get_privacity) {
            foreach ($get_privacity as $privacity) {
                $privacity_name = $privacity->name;
            }
        }
        if ($privacity_name == 'socialdb_collection_public') {
            require_once 'controllers/rss/rss_controller.php';
            exit();
        } else {
            wp_redirect(get_the_permalink($_GET['collection_id']));
        }
    } else if (get_query_var('oaipmh')) {
        $_GET['by_function'] = true;
        require_once 'controllers/export/oaipmh_controller.php';
        exit();
    }
}

add_filter('query_vars', 'my_queryvars');

function my_queryvars($qvars) {
    $qvars[] = 'collection_name';
    $qvars[] = 'oaipmh';
    return $qvars;
}

function custom_rewrite_tag() {
    add_rewrite_tag('%collection_name%', '([^&]+)');
    add_rewrite_tag('%oaipmh%', '([^&]+)');
}

add_action('init', 'custom_rewrite_tag', 10, 0);

function custom_rewrite_basic() {
    add_rewrite_rule('^feed_collection/([^/]*)', 'index.php?collection_name=$matches[1]', 'top');
    add_rewrite_rule('^oai', 'index.php?oaipmh=true', 'top');
    flush_rewrite_rules();
}

add_action('init', 'custom_rewrite_basic', 10, 0);

/**
 * Mostra a barra de admin padrão do wordpress apenas para usuarios com permissao de administrador 
 * * */
if (!current_user_can('manage_options')) {
    show_admin_bar(false);
}

/**
 * Função responsavel pelas respostas dos comentários 
 * * */
function mytheme_comment($comment, $args, $depth) {
    global $global_collection_id;
    $object = get_post(get_the_ID());
    $GLOBALS['comment'] = $comment;
    extract($args, EXTR_SKIP);

    if ('div' == $args['style']) {
        $tag = 'div';
        $add_below = 'comment';
    } else {
        $tag = 'li';
        $add_below = 'div-comment';
    }
    ?>
    <<?php echo $tag ?> <?php comment_class(empty($args['has_children']) ? '' : 'parent' ) ?> id="comment-<?php comment_ID() ?>">
    <?php if ('div' != $args['style']) : ?>
        <div id="div-comment-<?php comment_ID() ?>" class="comment-body">
        <?php endif; ?>
        <div class="comment-author vcard">
            <?php if ($args['avatar_size'] != 0) echo get_avatar($comment, $args['avatar_size']); ?>
            <?php printf(__('<cite class="fn">%s</cite> <span class="says">says:</span>', 'tainacan'), get_comment_author_link()); ?>
        </div>
        <?php if ($comment->comment_approved == '0') : ?>
            <em class="comment-awaiting-moderation"><?php _e('Your comment is awaiting moderation.', 'tainacan'); ?></em>
            <br />
        <?php endif; ?>

        <div class="comment-meta commentmetadata"><a href="#"><!-- < ?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?> -->
                <?php
                /* translators: 1: date, 2: time */
                printf(__('%1$s at %2$s'), get_comment_date(), get_comment_time());
                ?></a><?php edit_comment_link(__('(Painel Edit )', 'tainacan'), '  ', '');
                ?>
        </div>
        <div id="comment_text_<?php comment_ID(); ?>">
            <?php comment_text(); ?>
        </div>    
        <div style="display:none"  id="comment_edit_field_<?php comment_ID(); ?>">
            <form class="form-inline">
                <div class="form-group">
                    <textarea id="edit_field_value_<?php comment_ID(); ?>" class="form-control" id="exampleInputEmail3">
                    </textarea>    
                </div>
                <button type="button" onclick="cancelEditComment('<?php comment_ID(); ?>')"  class="btn btn-default"><?php _e('Cancel', 'tainacan') ?></button>
                <button type="button" onclick="submitEditComment('<?php comment_ID(); ?>')"  class="btn btn-default"><?php _e('Save', 'tainacan') ?></button>
            </form>
        </div>

        <div class="reply" id="reply_<?php comment_ID(); ?>">
            <a href="#div-comment-<?php comment_ID(); ?>" onclick="showModalReply('<?php comment_ID(); ?>');"><b><?php _e("Reply", 'tainacan'); ?></b></a>&nbsp;&nbsp;
            <?php if (!CollectionModel::is_moderator($global_collection_id, get_current_user_id()) && get_userdata(get_current_user_id())->display_name !== get_comment_author()): ?>
                <a href="#div-comment-<?php comment_ID(); ?>" onclick="showModalReportAbuseComment('<?php comment_ID(); ?>');"><span class="glyphicon glyphicon-bullhorn"></span>&nbsp;<?php _e("Report Abuse", 'tainacan'); ?></a>
            <?php else: ?>
                <a href="#div-comment-<?php comment_ID(); ?>" onclick="showEditComment('<?php comment_ID(); ?>');"><span class="glyphicon glyphicon-pencil"></span>&nbsp;<?php _e("Edit", 'tainacan'); ?></a>&nbsp;
                <a href="#div-comment-<?php comment_ID(); ?>" onclick="showAlertDeleteComment('<?php comment_ID(); ?>', '<?php _e('Attention!') ?>', '<?php _e('Delete this comment?', 'tainacan') ?>', '<?php echo mktime(); ?>');"><span class="glyphicon glyphicon-remove"></span>&nbsp;<?php _e("Delete", 'tainacan'); ?></a>
            <?php endif; ?>
            <a target="_blank" href="http://www.facebook.com/sharer/sharer.php?s=100&amp;p[url]=<?php echo get_the_permalink($global_collection_id) . '?item=' . $object->post_name; ?>&amp;p[images][0]=<?php echo wp_get_attachment_url(get_post_thumbnail_id($object->ID)); ?>&amp;p[title]=<?php _e("Comment", 'tainacan'); ?> - <?php echo htmlentities($object->post_title); ?>&amp;p[summary]=<?php comment_text(); ?>">
                <img src="<?php echo get_template_directory_uri() ?>/libraries/images/icon_facebook.png" style="max-width: 32px;" />
            </a>
            <!-- ******************** GOOGLE PLUS ******************** -->
            <a target="_blank" href="https://plus.google.com/share?url=<?php echo get_the_permalink($global_collection_id) . '?item=' . $object->post_name; ?>"><img src="<?php echo get_template_directory_uri() ?>/libraries/images/icon_googleplus.png" style="max-width: 32px;" /></a>
            <!-- ******************** TWITTER ******************** -->
            <a target="_blank" href="https://twitter.com/intent/tweet?url=<?php echo get_the_permalink($global_collection_id) . '?item=' . $object->post_name; ?>&amp;text=<?php echo strip_tags(get_comment_text()); ?>&amp;via=socialdb"><img src="<?php echo get_template_directory_uri() ?>/libraries/images/icon_twitter.png" style="max-width: 32px;" /></a>
            <!--?php comment_reply_link(array_merge($args, array('add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth']))); ?-->
            <!-- ******************** MAIL ******************** -->
            <a href="mailto:email@email.com?subject=<?php _e("Comment", 'tainacan'); ?>&amp;body=<?php _e("Link:", 'tainacan'); ?>%20<?php echo get_the_permalink($global_collection_id) . '?item=' . $object->post_name; ?>"><img src="<?php echo get_template_directory_uri() ?>/libraries/images/icon_mail.png" style="max-width: 32px;" /></a>
        </div><br>

        <?php if ('div' != $args['style']) : ?>
        </div>
    <?php endif; ?>
    <?php
}

/**
 * Logout Redirect
 * Automatically redirect to current page after user logout WordPress.
 */
function get_current_logout($logout_url) {
    if (!is_admin()) {
        $logout_url = add_query_arg('redirect_to', urlencode(( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), $logout_url);
    }

    return $logout_url;
}

add_filter('logout_url', 'get_current_logout');
//add_filter('login_url', 'get_current_logout');

/* * ************************************************************************************* */

/**
 * SocialDB Theme Option Page
 */
function socialdb_theme_menu() {
    add_theme_page('SocialDB Option', 'SocialDB Option', 'manage_options', 'socialdb_theme_options.php', 'socialdb_theme_page');
}

add_action('admin_menu', 'socialdb_theme_menu');

/**
 * Callback function to the add_theme_page
 * Will display the theme options page
 */
function socialdb_theme_page() {
    ?>
    <div class="section panel">
        <h1>Custom SocialDB Options</h1>
        <form method="post" enctype="multipart/form-data" action="options.php">
            <?php
            settings_fields('socialdb_theme_options');

            do_settings_sections('socialdb_theme_options.php');
            ?>
            <p class="submit">  
                <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'tainacan') ?>" />  
            </p>  

        </form>

    </div>
    <?php
}

/**
 * Register the settings to use on the theme options page
 */
add_action('admin_init', 'socialdb_register_settings');

/**
 * Function to register the settings
 */
function socialdb_register_settings() {
    // Register the settings with Validation callback
    register_setting('socialdb_theme_options', 'socialdb_theme_options', 'socialdb_validate_settings');

    // Add settings section
    add_settings_section('socialdb_fb_section', 'Facebook API Login', 'socialdb_display_section', 'socialdb_theme_options.php');

    // Create textbox field
    $field_args_fb_id = array(
        'type' => 'text',
        'id' => 'socialdb_fb_api_id',
        'name' => 'socialdb_fb_api_id',
        'desc' => 'Facebook API ID',
        'std' => '',
        'label_for' => 'socialdb_fb_api_id',
        'class' => 'css_class'
    );

    $field_args_fb_secret = array(
        'type' => 'text',
        'id' => 'socialdb_fb_api_secret',
        'name' => 'socialdb_fb_api_secret',
        'desc' => 'Facebook API Secret',
        'std' => '',
        'label_for' => 'socialdb_fb_api_secret',
        'class' => 'css_class'
    );

    add_settings_field('socialdb_fb_api_id', 'API ID', 'socialdb_display_setting', 'socialdb_theme_options.php', 'socialdb_fb_section', $field_args_fb_id);
    add_settings_field('socialdb_fb_api_secret', 'API Secret', 'socialdb_display_setting', 'socialdb_theme_options.php', 'socialdb_fb_section', $field_args_fb_secret);

    // Add settings section
    add_settings_section('socialdb_embed_ly_section', 'Embed Ly API', 'socialdb_display_section_embed', 'socialdb_theme_options.php');

    // Create textbox field
    $field_args_embed_id = array(
        'type' => 'text',
        'id' => 'socialdb_embed_api_id',
        'name' => 'socialdb_embed_api_id',
        'desc' => 'API ID',
        'std' => '',
        'label_for' => 'socialdb_embed_api_id',
        'class' => 'css_class'
    );

    add_settings_field('socialdb_embed_api_id', 'Embed Ly API ID', 'socialdb_display_setting', 'socialdb_theme_options.php', 'socialdb_embed_ly_section', $field_args_embed_id);
}

/**
 * Function to add extra text to display on each section
 */
function socialdb_display_section($section) {
    _e('Session responsible for the use of facebook API to login.', 'tainacan');
}

/**
 * Function to add extra text to display on each section
 */
function socialdb_display_section_embed($section) {
    _e('Session responsible for the use of Embed Ly API. (http://embed.ly/)', 'tainacan');
}

/**
 * Function to display the settings on the page
 * This is setup to be expandable by using a switch on the type variable.
 * In future you can add multiple types to be display from this function,
 * Such as checkboxes, select boxes, file upload boxes etc.
 */
function socialdb_display_setting($args) {
    extract($args);

    $option_name = 'socialdb_theme_options';

    $options = get_option($option_name);

    switch ($type) {
        case 'text':
            $options[$id] = stripslashes($options[$id]);
            $options[$id] = esc_attr($options[$id]);
            echo "<input class='regular-text$class' type='text' id='$id' name='" . $option_name . "[$id]' value='$options[$id]' />";
            echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";
            break;
    }
}

/**
 * Callback function to the register_settings function will pass through an input variable
 * You can then validate the values and the return variable will be the values stored in the database.
 */
function socialdb_validate_settings($input) {
    foreach ($input as $k => $v) {
        $newinput[$k] = trim($v);

        // Check the input is a letter or a number
        if (!preg_match('/^[A-Z0-9 _]*$/i', $v)) {
            $newinput[$k] = '';
        }
    }

    return $newinput;
}

//************************************************************************************************************/
//************************************************************************************************************/

/* Quando o tema for ativado */
if (isset($_GET['activated']) && is_admin()) {

    register_post_types();
    register_taxonomies();
    wpdbfix();
    setup_taxonomymeta();
    create_collection_terms();
    create_property_terms();
    create_channel_terms();
    create_license_terms();
    create_category_terms();
    create_tag_terms();
    create_event_terms();
    create_init_collection();
    create_oai_post();
    active_cron();
    create_anonimous_user();
    create_standart_licenses();
    update_option('socialdb_divider', 'MVOh71Y482');
}
/* function register_post_types() */
/* Recebe () */
/* Registra todos os post type utilizados pelo SocialDB */
/* Autor: Eduardo Humberto */

function register_post_types() {
    /* Detalhes do post type collection */
    $collection_args = array(
        'public' => true,
        'query_var' => 'collection',
        'rewrite' => array(
            'slug' => 'collection',
            'with_front' => false),
        'supports' => array(
            'title',
            'editor',
            'author',
            'excerpt',
            'comments',
            'custom-fields',
            'thumbnail'),
        'labels' => array(
            'name' => __('Collections', 'tainacan'),
            'menu_name' => __('SocialDB', 'tainacan'),
            'all_items' => __('All Collections', 'tainacan'),
            'singular_name' => __('Collection', 'tainacan'),
            'add_new' => __('Add Collection', 'tainacan'),
            'add_new_item' => __('Add Collection', 'tainacan'),
            'edit_item' => __('Edit Collection', 'tainacan'),
            'new_item' => __('New Collection', 'tainacan'),
            'view_item' => __('View Collection', 'tainacan'),
            'search_items' => __('Search Collection', 'tainacan'),
            'not_found' => __('No Collection Found', 'tainacan'),
            'not_found_in_trash' => __('No Collection Found in Trash', 'tainacan')),
        //'menu_icon' => WP_IDEA_STREAM_PLUGIN_URL . '/images/is-logomenu.png',
        'taxonomies' => array(
            'socialdb_collection_type', 'socialdb_tag'),
    );
    /* register the collection post-type */
    register_post_type('socialdb_collection', $collection_args);
    /* Detalhes do post type collection */
    $oai_args = array(
        'public' => true,
        'query_var' => 'oai',
        'rewrite' => array(
            'slug' => 'oai',
            'with_front' => false)
            //'menu_icon' => WP_IDEA_STREAM_PLUGIN_URL . '/images/is-logomenu.png',
    );
    /* register the collection post-type */
    register_post_type('socialdb-oai', $oai_args);
    /* Detalhes do post type object */
    $object_args = array(
        'public' => true,
        'query_var' => 'object',
        'rewrite' => array(
            'slug' => 'object',
            'with_front' => false),
        'supports' => array(
            'title',
            'editor',
            'author',
            'excerpt',
            'comments',
            'custom-fields',
            'thumbnail'),
        'labels' => array(
            'name' => __('Object', 'tainacan'),
            'menu_name' => __('Object', 'tainacan'),
            'all_items' => __('All Objects', 'tainacan'),
            'singular_name' => __('Object', 'tainacan'),
            'add_new' => __('Add Object', 'tainacan'),
            'add_new_item' => __('Add Object', 'tainacan'),
            'edit_item' => __('Edit Object', 'tainacan'),
            'new_item' => __('New Object', 'tainacan'),
            'view_item' => __('View Object', 'tainacan'),
            'search_items' => __('Search Object', 'tainacan'),
            'not_found' => __('No Object Found', 'tainacan'),
            'not_found_in_trash' => __('No Object Found in Trash', 'tainacan')),
        // 'menu_icon' => WP_IDEA_STREAM_PLUGIN_URL . '/images/is-logomenu.png',
        'taxonomies' => array('socialdb_category'),
    );
    /* register the object post-type */
    register_post_type('socialdb_object', $object_args);
    flush_rewrite_rules();
    register_post_type('socialdb_channel');
    register_post_type('socialdb_vote');
    register_post_type('socialdb_event');
    register_post_type('socialdb_license');
}

/* function register_taxonomies() */
/* Recebe () */
/* Registra todos as txonomies utilizados pelo SocialDB */
/* Autor: Eduardo Humberto */

function register_taxonomies() {
    $category_args = array(
        'hierarchical' => true,
        'query_var' => 'category',
        'rewrite' => array(
            'slug' => 'category',
            'with_front' => false),
        'labels' => array(
            'name' => __('Category', 'tainacan'),
            'singular_name' => __('Category', 'tainacan'),
            'edit_item' => __('Edit Category', 'tainacan'),
            'update_item' => __('Update Category', 'tainacan'),
            'add_new_item' => __('Add New Category', 'tainacan'),
            'new_item_name' => __('New Category Name', 'tainacan'),
            'all_items' => __('All Categories', 'tainacan'),
            'search_items' => __('Search Categories', 'tainacan'),
            'parent_item' => __('Parent Category', 'tainacan'),
            'parent_item_colon' => __('Parent Category:', 'tainacan')),
    );
    register_taxonomy('socialdb_category_type', array('socialdb_object'), $category_args);
    register_taxonomy('socialdb_tag_type', array('socialdb_collection'));
    register_taxonomy('socialdb_channel_type', array('socialdb_channel'));
    register_taxonomy('socialdb_license_type', array('socialdb_license'));
    register_taxonomy('socialdb_collection_type', array('socialdb_collection'));
    register_taxonomy('socialdb_property_type', array('socialdb_vote'));
    register_taxonomy('socialdb_event_type', array('socialdb_event'));
}

function create_oai_post() {
    $post = array(
        'post_title' => 'socialdb-oai',
        'post_status' => 'publish',
        'post_type' => 'socialdb-oai'
    );
    $object_id = wp_insert_post($post);
    return $object_id;
}

function create_standart_licenses() {
    $getLicenses = get_option('socialdb_standart_licenses');
    if (!$getLicenses):
        $licenses = [
            'Creative Commons CC BY',
            'Creative Commons CC BY-ND',
            'Creative Commons CC BY-NC-SA',
            'Creative Commons CC BY-SA',
            'Creative Commons CC BY-NC',
            'Creative Commons CC BY-NC-ND'
        ];

        $arrId = array();

        foreach ($licenses as $license) {
            $post = array(
                'post_title' => $license,
                'post_status' => 'publish',
                'post_type' => 'socialdb_license'
            );
            $object_id = wp_insert_post($post);
            wp_set_object_terms($object_id, array((int) get_term_by('slug', 'socialdb_license_public', 'socialdb_license_type')->term_id), 'socialdb_license_type');
            $arrId[] = $object_id;
        }

        update_option('socialdb_standart_licenses', $arrId);
    endif;
    //return $arrId;
}

function create_anonimous_user() {
    $user = get_option('anonimous_user');
    if (!$user) {
        $user_id = wp_create_user('Anonimous', '12345678', 'anonimous@anonimous.com');
        if ($user_id) {
            update_option('anonimous_user', $user_id);
        }
    }
}

/**
 * function create_register($name_register,$taxonomy)
 * @param string $name_register 
 * @param string $taxonomy Metadata name.
 * @return array With term_id created.
 * 
 * Funcao generica para criar registros, Retorna o id do registro ou cria um novo, caso nao exista
 * Autor: Eduardo Humberto 
 */
function create_register($name_register, $taxonomy, $args = array()) {
    if (isset($args['slug'])) {
        $register_term = get_term_by('slug', $args['slug'], $taxonomy);
    } else {
        $register_term = get_term_by('name', $name_register, $taxonomy);
    }
    //inserting
    if (!$register_term) {
        $register_term = wp_insert_term($name_register, $taxonomy, $args);
    } else {
        $term_id = $register_term->term_id;
        $register_term = array();
        $register_term['term_id'] = $term_id;
    }
    return $register_term;
}

/**
 * function create_metas($term_id,$meta_key,$meta_value,$previous_value)
 * @param int $term_id
 * @param string $meta_key Metadata name.
 * @param string $meta_value Metadata value.
 * @param string $previous_value Metadata name.
 * @return array With term_id created.
 * 
 * Funcao generica para criar ou atualizar os meta dados na tabela taxonomy meta
 * Autor: Eduardo Humberto 
 */
function create_metas($term_id, $meta_key, $meta_value, $previous_value) {
    $register_term = get_term_meta($term_id, $meta_key); // pega os valores que estao neste meta key
    if (!$register_term) {//se ele nao exisitr 
        $result = add_term_meta($term_id, $meta_key, $meta_value); // insere
    } else {
        if ($register_term[0] != '' && $meta_value == '') {// se o registro for vazio e se atualizacao tb for vazia
            $result = update_term_meta($term_id, $meta_key, $register_term[0]);
        } elseif (in_array($previous_value, $register_term)) {// se o valor anterior ja exisitir ele atualiza o valor anterior
            $result = update_term_meta($term_id, $meta_key, $meta_value, $previous_value);
        } else {// se nao apenas adiciona
            $result = add_term_meta($term_id, $meta_key, $meta_value);
        }
    }
    return $result;
}

/**
 * function init_nav()
 * Funcao para iniciar a navegação do JIT
 * Autor: Eduardo Humberto 
 */
function init_nav($data) {
    switch ($data) {
        case "regular":
            wp_register_script('ExecuteDefault', get_template_directory_uri() . '/libraries/js/jit/executeDefault.js');
            wp_enqueue_script('ExecuteDefault');
            break;
        case "hypertree":
            wp_register_script('HypertreeJs', get_template_directory_uri() . '/libraries/js/jit/Hypertree.js');
            wp_enqueue_script('HypertreeJs');

            wp_register_style('HypertreeCss', get_template_directory_uri() . '/libraries/css/jit/Hypertree.css');
            wp_enqueue_style('HypertreeCss');


            wp_register_script('ExecuteHypertree', get_template_directory_uri() . '/libraries/js/jit/executeHypertree.js');
            wp_enqueue_script('ExecuteHypertree');
            break;
        case "spacetree":
            wp_register_script('SpacetreeJs', get_template_directory_uri() . '/libraries/js/jit/Spacetree.js');
            wp_enqueue_script('SpacetreeJs');

            wp_register_style('SpacetreeCss', get_template_directory_uri() . '/libraries/css/jit/Spacetree.css');
            wp_enqueue_style('SpacetreeCss');


            wp_register_script('ExecuteSpacetree', get_template_directory_uri() . '/libraries/js/jit/executeSpacetree.js');
            wp_enqueue_script('ExecuteSpacetree');
            break;
        case "treemap":
            wp_register_script('TreemapJs', get_template_directory_uri() . '/libraries/js/jit/Treemap.js');
            wp_enqueue_script('TreemapJs');

            wp_register_style('TreemapCss', get_template_directory_uri() . '/libraries/css/jit/Treemap.css');
            wp_enqueue_style('TreemapCss');


            wp_register_script('ExecuteTreemap', get_template_directory_uri() . '/libraries/js/jit/executeTreemap.js');
            wp_enqueue_script('ExecuteTreemap');
            break;
        case "rgraph":
            wp_register_script('RgraphJs', get_template_directory_uri() . '/libraries/js/jit/Rgraph.js');
            wp_enqueue_script('RgraphJs');

            wp_register_style('RgraphCss', get_template_directory_uri() . '/libraries/css/jit/Rgraph.css');
            wp_enqueue_style('RgraphCss');


            wp_register_script('ExecuteRgraph', get_template_directory_uri() . '/libraries/js/jit/executeRgraph.js');
            wp_enqueue_script('ExecuteRgraph');
            break;
        default:
            wp_register_script('ExecuteDefault', get_template_directory_uri() . '/libraries/js/jit/executeDefault.js');
            wp_enqueue_script('ExecuteDefault');
            break;
    }
}

/**
 * function create_register()
 * Funcao para criar os registros da colecao
 * Autor: Eduardo Humberto 
 */
function create_collection_terms() {
    $collection_root_term = create_register('socialdb_collection', 'socialdb_collection_type');
    /* adiciona ou atualiza os metas */
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_post_type', 'socialdb_collection_post_type');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_facet_type', 'socialdb_collection_facet_type');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_facets', 'socialdb_collection_facets');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_object_type', 'socialdb_collection_object_type');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_moderators', 'socialdb_collection_moderators');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_group_admin', 'socialdb_collection_group_admin');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_channel', 'socialdb_collection_channel');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_license', 'socialdb_collection_license');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_default_ordering', 'socialdb_collection_default_ordering');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_columns', 'socialdb_collection_columns');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_board_background_color', 'socialdb_collection_board_background_color');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_board_border_color', 'socialdb_collection_board_border_color');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_board_link_color', 'socialdb_collection_board_link_color');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_board_skin_mode', 'socialdb_collection_board_skin_mode');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_board_font_color', 'socialdb_collection_board_font_color');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_hide_title', 'socialdb_collection_hide_title');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_hide_description', 'socialdb_collection_hide_description');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_hide_thumbnail', 'socialdb_collection_hide_thumbnail');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_hide_menu', 'socialdb_collection_hide_menu');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_hide_categories', 'socialdb_collection_hide_categories');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_hide_rankings', 'socialdb_collection_hide_rankings');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_size_thumbnail', 'socialdb_collection_size_thumbnail');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_hide_tags', 'socialdb_collection_hide_tags');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_ordenation_form', 'socialdb_collection_ordenation_form');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_address', 'socialdb_collection_address');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_mapping_exportation_active', 'socialdb_collection_mapping_exportation_active');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_allow_hierarchy', 'socialdb_collection_allow_hierarchy');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_parent', 'socialdb_collection_parent');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_license_pattern', 'socialdb_collection_license_pattern');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_license_enabled', 'socialdb_collection_license_enabled');
    //Permissions
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_create_category', 'socialdb_collection_permission_create_category');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_edit_category', 'socialdb_collection_permission_edit_category');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_delete_category', 'socialdb_collection_permission_delete_category');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_add_classification', 'socialdb_collection_permission_add_classification');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_delete_classification', 'socialdb_collection_permission_delete_classification');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_create_object', 'socialdb_collection_permission_create_object');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_delete_object', 'socialdb_collection_permission_delete_object');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_create_property_data', 'socialdb_collection_permission_create_property_data');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_edit_property_data', 'socialdb_collection_permission_edit_property_data');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_delete_property_data', 'socialdb_collection_permission_delete_property_data');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_edit_property_data_value', 'socialdb_collection_permission_edit_property_data_value');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_create_property_object', 'socialdb_collection_permission_create_property_object');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_edit_property_object', 'socialdb_collection_permission_edit_property_object');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_delete_property_object', 'socialdb_collection_permission_delete_property_object');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_edit_property_object_value', 'socialdb_collection_permission_edit_property_object_value');

    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_create_property_term', 'socialdb_collection_permission_create_property_term');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_edit_property_term', 'socialdb_collection_permission_edit_property_term');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_delete_property_term', 'socialdb_collection_permission_delete_property_term');
    //Permissions Comment
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_create_comment', 'socialdb_collection_permission_create_comment');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_edit_comment', 'socialdb_collection_permission_edit_comment');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_delete_comment', 'socialdb_collection_permission_delete_comment');
    //Permissions Tags
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_create_tags', 'socialdb_collection_permission_create_tags');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_edit_tags', 'socialdb_collection_permission_edit_tags');
    create_metas($collection_root_term['term_id'], 'socialdb_collection_metas', 'socialdb_collection_permission_delete_tags', 'socialdb_collection_permission_delete_tags');

    //exit();
    /* subfilhos */
    $collection_public_term = create_register('socialdb_collection_public', 'socialdb_collection_type', array('parent' => $collection_root_term['term_id']));
    $collection_private_term = create_register('socialdb_collection_private', 'socialdb_collection_type', array('parent' => $collection_root_term['term_id']));
}

/**
 * function create_tag_terms()
 * Funcao para criar os registros tag principal
 * Autor: Eduardo Humberto 
 */
function create_tag_terms() {
    $tag_term = create_register('socialdb_tag', 'socialdb_tag_type');
}

/**
 * function create_property_terms()
 * Funcao para criar os registros dos canais
 * Autor: Eduardo Humberto 
 */
function create_property_terms() {
    $property_root_term = create_register('socialdb_property', 'socialdb_property_type');
    create_metas($property_root_term['term_id'], 'socialdb_property_metas', 'socialdb_property_required', 'socialdb_property_required');
    create_metas($property_root_term['term_id'], 'socialdb_property_metas', 'socialdb_property_term_cardinality', 'socialdb_property_term_cardinality');
    create_metas($property_root_term['term_id'], 'socialdb_property_metas', 'socialdb_property_default_value', 'socialdb_property_default_value');
    create_metas($property_root_term['term_id'], 'socialdb_property_metas', 'socialdb_property_help', 'socialdb_property_help');
    create_metas($property_root_term['term_id'], 'socialdb_property_metas', 'socialdb_property_created_category', 'socialdb_property_created_category');
    /* subfilhos */
    $property_data_term = create_register('socialdb_property_data', 'socialdb_property_type', array('parent' => $property_root_term['term_id']));
    create_metas($property_data_term['term_id'], 'socialdb_property_data_metas', 'socialdb_property_data_column_ordenation', 'socialdb_property_data_column_ordenation');
    create_metas($property_data_term['term_id'], 'socialdb_property_data_metas', 'socialdb_property_data_widget', 'socialdb_property_data_widget');
    /* Criando a propriedade recentes para ordenacao da colecao */
    create_register(__('Recents', 'tainacan'), 'socialdb_property_type', array('parent' => $property_data_term['term_id'], 'slug' => 'socialdb_ordenation_recent'));


    $property_object_term = create_register('socialdb_property_object', 'socialdb_property_type', array('parent' => $property_root_term['term_id']));
    create_metas($property_object_term['term_id'], 'socialdb_property_object_metas', 'socialdb_property_object_category_id', 'socialdb_property_object_category_id');
    create_metas($property_object_term['term_id'], 'socialdb_property_object_metas', 'socialdb_property_object_is_facet', 'socialdb_property_object_is_facet');
    create_metas($property_object_term['term_id'], 'socialdb_property_object_metas', 'socialdb_property_object_is_reverse', 'socialdb_property_object_is_reverse');
    create_metas($property_object_term['term_id'], 'socialdb_property_object_metas', 'socialdb_property_object_reverse', 'socialdb_property_object_reverse');

    $property_term_term = create_register('socialdb_property_term', 'socialdb_property_type', array('parent' => $property_root_term['term_id']));
    create_metas($property_term_term['term_id'], 'socialdb_property_object_metas', 'socialdb_property_term_root', 'socialdb_property_term_root');
    create_metas($property_term_term['term_id'], 'socialdb_property_object_metas', 'socialdb_property_term_widget', 'socialdb_property_term_widget');

    $property_ranking_term = create_register('socialdb_property_ranking', 'socialdb_property_type', array('parent' => $property_root_term['term_id']));
    create_metas($property_ranking_term['term_id'], 'socialdb_property_ranking_metas', 'socialdb_property_ranking_vote', 'socialdb_property_ranking_vote');
    /* sub-subfilhos */
    $property_ranking_like_term = create_register('socialdb_property_ranking_like', 'socialdb_property_type', array('parent' => $property_ranking_term['term_id']));
    $property_ranking_binary_term = create_register('socialdb_property_ranking_binary', 'socialdb_property_type', array('parent' => $property_ranking_term['term_id']));
    $property_ranking_stars_term = create_register('socialdb_property_ranking_stars', 'socialdb_property_type', array('parent' => $property_ranking_term['term_id']));
}

/**
 * function create_channel_terms()
 * Funcao para criar os registros dos canais
 * Autor: Eduardo Humberto 
 */
function create_channel_terms() {
    $channel_root_term = create_register('socialdb_channel', 'socialdb_channel_type');
    create_metas($channel_root_term['term_id'], 'socialdb_channel_metas', 'socialdb_channel_identificator', 'socialdb_channel_identificator');
    /* subfilhos */
    $channel_youtube_term = create_register('socialdb_channel_youtube', 'socialdb_channel_type', array('parent' => $channel_root_term['term_id']));
    create_metas($channel_youtube_term['term_id'], 'socialdb_channel_youtube_metas', 'socialdb_channel_youtube_last_update', 'socialdb_channel_youtube_last_update');
    $channel_mapping_term = create_register('socialdb_channel_oaipmhdc', 'socialdb_channel_type', array('parent' => $channel_root_term['term_id']));
    create_metas($channel_mapping_term['term_id'], 'socialdb_channel_oaipmhdc_metas', 'socialdb_channel_oaipmhdc_type', 'socialdb_channel_oaipmhdc_type');
    $channel_mapping_term = create_register('socialdb_channel_csv', 'socialdb_channel_type', array('parent' => $channel_root_term['term_id']));
    create_metas($channel_mapping_term['term_id'], 'socialdb_channel_csv_metas', 'socialdb_channel_csv_delimiter', 'socialdb_channel_csv_delimiter');
    create_metas($channel_mapping_term['term_id'], 'socialdb_channel_csv_metas', 'socialdb_channel_csv_has_header', 'socialdb_channel_csv_has_header');
}

/**
 * function create_license_terms()
 * Funcao para criar os registros das licencas
 * Autor: Eduardo Humberto 
 */
function create_license_terms() {
    create_register('socialdb_license_public', 'socialdb_license_type');
    create_register('socialdb_license_custom', 'socialdb_license_type');
}

/**
 * function create_channel_terms()
 * Funcao para criar os registros dos canais
 * Autor: Eduardo Humberto 
 */
function create_category_terms() {
    $category_root_term = create_register('socialdb_category', 'socialdb_category_type');
    create_metas($category_root_term['term_id'], 'socialdb_category_metas', 'socialdb_category_owner', 'socialdb_category_owner');
    create_metas($category_root_term['term_id'], 'socialdb_category_metas', 'socialdb_category_moderators', 'socialdb_category_moderators');
    create_metas($category_root_term['term_id'], 'socialdb_category_metas', 'socialdb_category_date', 'socialdb_category_date');
    create_metas($category_root_term['term_id'], 'socialdb_category_metas', 'socialdb_category_permission', 'socialdb_category_permission');
    create_metas($category_root_term['term_id'], 'socialdb_category_metas', 'socialdb_category_property_id', 'socialdb_category_property_id');
}

/**
 * function create_channel_terms()
 * Funcao para criar os registros dos canais
 * Autor: Eduardo Humberto 
 */
function create_event_terms() {
    $event_root_term = create_register('socialdb_event', 'socialdb_event_type');
    create_metas($event_root_term['term_id'], 'socialdb_event_metas', 'socialdb_event_user_id', 'socialdb_event_user_id');
    create_metas($event_root_term['term_id'], 'socialdb_event_metas', 'socialdb_event_collection_id', 'socialdb_event_collection_id');
    create_metas($event_root_term['term_id'], 'socialdb_event_metas', 'socialdb_event_confirmed', 'socialdb_event_confirmed');
    create_metas($event_root_term['term_id'], 'socialdb_event_metas', 'socialdb_event_create_date', 'socialdb_event_create_date');
    create_metas($event_root_term['term_id'], 'socialdb_event_metas', 'socialdb_event_approval_date', 'socialdb_event_approval_date');
    create_metas($event_root_term['term_id'], 'socialdb_event_metas', 'socialdb_event_approval_by', 'socialdb_event_approval_by');
    create_metas($event_root_term['term_id'], 'socialdb_event_metas', 'socialdb_event_observation', 'socialdb_event_observation');
    /*     * Object* */
    $event_object_term = create_register('socialdb_event_object', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    create_metas($event_object_term['term_id'], 'socialdb_event_object_metas', 'socialdb_event_object_item_id', 'socialdb_event_object_item_id');
    $event_object_create_term = create_register('socialdb_event_object_create', 'socialdb_event_type', array('parent' => $event_object_term['term_id']));
    $event_object_delete_term = create_register('socialdb_event_object_delete', 'socialdb_event_type', array('parent' => $event_object_term['term_id']));
    /*     * Classification* */
    $event_classification_term = create_register('socialdb_event_classification', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    create_metas($event_classification_term['term_id'], 'socialdb_event_classification_metas', 'socialdb_event_classification_term_id', 'socialdb_event_classification_term_id');
    create_metas($event_classification_term['term_id'], 'socialdb_event_classification_metas', 'socialdb_event_classification_object_id', 'socialdb_event_classification_object_id');
    create_metas($event_classification_term['term_id'], 'socialdb_event_classification_metas', 'socialdb_event_classification_type', 'socialdb_event_classification_type');
    $event_object_create_term = create_register('socialdb_event_classification_create', 'socialdb_event_type', array('parent' => $event_classification_term['term_id']));
    $event_object_delete_term = create_register('socialdb_event_classification_delete', 'socialdb_event_type', array('parent' => $event_classification_term['term_id']));
    /*     * term* */
    $event_term_term = create_register('socialdb_event_term', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    $event_create_term = create_register('socialdb_event_term_create', 'socialdb_event_type', array('parent' => $event_term_term['term_id']));
    create_metas($event_create_term['term_id'], 'socialdb_event_term_create_metas', 'socialdb_event_term_suggested_name', 'socialdb_event_term_suggested_name');
    create_metas($event_create_term['term_id'], 'socialdb_event_term_create_metas', 'socialdb_event_term_parent', 'socialdb_event_term_parent');
    $event_edit_term = create_register('socialdb_event_term_edit', 'socialdb_event_type', array('parent' => $event_term_term['term_id']));
    create_metas($event_edit_term['term_id'], 'socialdb_event_term_edit_metas', 'socialdb_event_term_id', 'socialdb_event_term_id');
    create_metas($event_edit_term['term_id'], 'socialdb_event_term_edit_metas', 'socialdb_event_term_suggested_name', 'socialdb_event_term_suggested_name');
    create_metas($event_edit_term['term_id'], 'socialdb_event_term_edit_metas', 'socialdb_event_term_suggested_parent', 'socialdb_event_term_suggested_parent');
    create_metas($event_edit_term['term_id'], 'socialdb_event_term_edit_metas', 'socialdb_event_term_previous_name', 'socialdb_event_term_previous_name');
    create_metas($event_edit_term['term_id'], 'socialdb_event_term_edit_metas', 'socialdb_event_term_previous_parent', 'socialdb_event_term_previous_parent');
    $event_delete_term = create_register('socialdb_event_term_delete', 'socialdb_event_type', array('parent' => $event_term_term['term_id']));
    create_metas($event_delete_term['term_id'], 'socialdb_event_term_delete_metas', 'socialdb_event_term_id', 'socialdb_event_term_id');
    /*     * property data* */
    $event_property_data_term = create_register('socialdb_event_property_data', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    $event_create_property_data = create_register('socialdb_event_property_data_create', 'socialdb_event_type', array('parent' => $event_property_data_term['term_id']));
    create_metas($event_create_property_data['term_id'], 'socialdb_event_property_data_create_metas', 'socialdb_event_property_data_create_name', 'socialdb_event_property_data_create_name');
    create_metas($event_create_property_data['term_id'], 'socialdb_event_property_data_create_metas', 'socialdb_event_property_data_create_widget', 'socialdb_event_property_data_create_widget');
    create_metas($event_create_property_data['term_id'], 'socialdb_event_property_data_create_metas', 'socialdb_event_property_data_create_ordenation_column', 'socialdb_event_property_data_create_ordenation_column');
    create_metas($event_create_property_data['term_id'], 'socialdb_event_property_data_create_metas', 'socialdb_event_property_data_create_required', 'socialdb_event_property_data_create_required');
    create_metas($event_create_property_data['term_id'], 'socialdb_event_property_data_create_metas', 'socialdb_property_default_value', 'socialdb_property_default_value');
    $event_edit_property_data = create_register('socialdb_event_property_data_edit', 'socialdb_event_type', array('parent' => $event_property_data_term['term_id']));
    create_metas($event_edit_property_data['term_id'], 'socialdb_event_property_data_edit_metas', 'socialdb_event_property_data_edit_id', 'socialdb_event_property_data_edit_id');
    create_metas($event_edit_property_data['term_id'], 'socialdb_event_property_data_edit_metas', 'socialdb_event_property_data_edit_name', 'socialdb_event_property_data_edit_name');
    create_metas($event_edit_property_data['term_id'], 'socialdb_event_property_data_edit_metas', 'socialdb_event_property_data_edit_widget', 'socialdb_event_property_data_edit_widget');
    create_metas($event_edit_property_data['term_id'], 'socialdb_event_property_data_edit_metas', 'socialdb_event_property_data_edit_ordenation_column', 'socialdb_event_property_data_edit_ordenation_column');
    create_metas($event_edit_property_data['term_id'], 'socialdb_event_property_data_edit_metas', 'socialdb_event_property_data_edit_required', 'socialdb_event_property_data_edit_required');
    create_metas($event_edit_property_data['term_id'], 'socialdb_event_property_data_edit_metas', 'socialdb_property_default_value', 'socialdb_property_default_value');
    $event_delete_property_data = create_register('socialdb_event_property_data_delete', 'socialdb_event_type', array('parent' => $event_property_data_term['term_id']));
    create_metas($event_delete_property_data['term_id'], 'socialdb_event_property_data_delete_metas', 'socialdb_event_property_data_delete_id', 'socialdb_event_property_data_delete_id');
    $event_edit_property_data_value = create_register('socialdb_event_property_data_edit_value', 'socialdb_event_type', array('parent' => $event_property_data_term['term_id']));
    create_metas($event_edit_property_data_value['term_id'], 'socialdb_event_property_data_edit_value_metas', 'socialdb_event_property_data_edit_value_object_id', 'socialdb_event_property_data_edit_value_object_id');
    create_metas($event_edit_property_data_value['term_id'], 'socialdb_event_property_data_edit_value_metas', 'socialdb_event_property_data_edit_value_property_id', 'socialdb_event_property_data_edit_value_property_id');
    create_metas($event_edit_property_data_value['term_id'], 'socialdb_event_property_data_edit_value_metas', 'socialdb_event_property_data_edit_value_attribute_value', 'socialdb_event_property_data_edit_value_attribute_value');
    /*     * property object* */
    $event_property_object_term = create_register('socialdb_event_property_object', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    $event_create_property_object = create_register('socialdb_event_property_object_create', 'socialdb_event_type', array('parent' => $event_property_object_term['term_id']));
    create_metas($event_create_property_object['term_id'], 'socialdb_event_property_object_create_metas', 'socialdb_event_property_object_create_name', 'socialdb_event_property_object_create_name');
    create_metas($event_create_property_object['term_id'], 'socialdb_event_property_object_create_metas', 'socialdb_event_property_object_create_category_id', 'socialdb_event_property_object_create_category_id');
    create_metas($event_create_property_object['term_id'], 'socialdb_event_property_object_create_metas', 'socialdb_event_property_object_create_is_facet', 'socialdb_event_property_object_create_is_facet');
    create_metas($event_create_property_object['term_id'], 'socialdb_event_property_object_create_metas', 'socialdb_event_property_object_create_is_reverse', 'socialdb_event_property_object_create_is_reverse');
    create_metas($event_create_property_object['term_id'], 'socialdb_event_property_object_create_metas', 'socialdb_event_property_object_create_reverse', 'socialdb_event_property_object_create_reverse');
    create_metas($event_create_property_object['term_id'], 'socialdb_event_property_object_create_metas', 'socialdb_event_property_object_create_required', 'socialdb_event_property_object_create_required');
    $event_edit_property_object = create_register('socialdb_event_property_object_edit', 'socialdb_event_type', array('parent' => $event_property_object_term['term_id']));
    create_metas($event_edit_property_object['term_id'], 'socialdb_event_property_object_edit_metas', 'socialdb_event_property_object_edit_id', 'socialdb_event_property_object_edit_id');
    create_metas($event_edit_property_object['term_id'], 'socialdb_event_property_object_edit_metas', 'socialdb_event_property_object_edit_name', 'socialdb_event_property_object_edit_name');
    create_metas($event_edit_property_object['term_id'], 'socialdb_event_property_object_edit_metas', 'socialdb_event_property_object_category_id', 'socialdb_event_property_object_category_id');
    create_metas($event_edit_property_object['term_id'], 'socialdb_event_property_object_edit_metas', 'socialdb_event_property_object_edit_is_facet', 'socialdb_event_property_object_edit_is_facet');
    create_metas($event_edit_property_object['term_id'], 'socialdb_event_property_object_edit_metas', 'socialdb_event_property_object_edit_is_reverse', 'socialdb_event_property_object_edit_is_reverse');
    create_metas($event_edit_property_object['term_id'], 'socialdb_event_property_object_edit_metas', 'socialdb_event_property_object_edit_reverse', 'socialdb_event_property_object_edit_reverse');
    create_metas($event_edit_property_object['term_id'], 'socialdb_event_property_object_edit_metas', 'socialdb_event_property_object_edit_required', 'socialdb_event_property_object_edit_required');
    $event_delete_property_object = create_register('socialdb_event_property_object_delete', 'socialdb_event_type', array('parent' => $event_property_object_term['term_id']));
    create_metas($event_delete_property_object['term_id'], 'socialdb_event_property_object_delete_metas', 'socialdb_event_property_object_delete_id', 'socialdb_event_property_object_delete_id');
    $event_edit_property_object_value = create_register('socialdb_event_property_object_edit_value', 'socialdb_event_type', array('parent' => $event_property_object_term['term_id']));
    create_metas($event_edit_property_object_value['term_id'], 'socialdb_event_property_object_edit_value_metas', 'socialdb_event_property_object_edit_object_id', 'socialdb_event_property_object_edit_object_id');
    create_metas($event_edit_property_object_value['term_id'], 'socialdb_event_property_object_edit_value_metas', 'socialdb_event_property_object_edit_property_id', 'socialdb_event_property_object_edit_property_id');
    create_metas($event_edit_property_object_value['term_id'], 'socialdb_event_property_object_edit_value_metas', 'socialdb_event_property_object_edit_value_suggested_value', 'socialdb_event_property_object_edit_value_suggested_value');
    /* Property Term* */
    $event_property_term_term = create_register('socialdb_event_property_term', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    $event_create_property_term = create_register('socialdb_event_property_term_create', 'socialdb_event_type', array('parent' => $event_property_term_term['term_id']));
    create_metas($event_create_property_term['term_id'], 'socialdb_event_property_term_create_metas', 'socialdb_event_property_term_create_name', 'socialdb_event_property_term_create_name');
    create_metas($event_create_property_term['term_id'], 'socialdb_event_property_term_create_metas', 'socialdb_event_property_term_create_cardinality', 'socialdb_event_property_term_create_cardinality');
    create_metas($event_create_property_term['term_id'], 'socialdb_event_property_term_create_metas', 'socialdb_event_property_term_create_widget', 'socialdb_event_property_term_create_widget');
    create_metas($event_create_property_term['term_id'], 'socialdb_event_property_term_create_metas', 'socialdb_event_property_term_create_required', 'socialdb_event_property_term_create_required');
    create_metas($event_create_property_term['term_id'], 'socialdb_event_property_term_create_metas', 'socialdb_event_property_term_create_root', 'socialdb_event_property_term_create_root');
    create_metas($event_create_property_term['term_id'], 'socialdb_event_property_term_create_metas', 'socialdb_event_property_term_create_help', 'socialdb_event_property_term_create_help');
    $event_edit_property_term = create_register('socialdb_event_property_term_edit', 'socialdb_event_type', array('parent' => $event_property_term_term['term_id']));
    create_metas($event_edit_property_term['term_id'], 'socialdb_event_property_term_edit_metas', 'socialdb_event_property_term_edit_id', 'socialdb_event_property_term_edit_id');
    create_metas($event_edit_property_term['term_id'], 'socialdb_event_property_term_edit_metas', 'socialdb_event_property_term_edit_name', 'socialdb_event_property_term_edit_name');
    create_metas($event_edit_property_term['term_id'], 'socialdb_event_property_term_edit_metas', 'socialdb_event_property_term_edit_widget', 'socialdb_event_property_term_edit_widget');
    create_metas($event_edit_property_term['term_id'], 'socialdb_event_property_term_edit_metas', 'socialdb_event_property_term_edit_cardinality', 'socialdb_event_property_term_edit_cardinality');
    create_metas($event_edit_property_term['term_id'], 'socialdb_event_property_term_edit_metas', 'socialdb_event_property_term_edit_required', 'socialdb_event_property_term_edit_required');
    create_metas($event_edit_property_term['term_id'], 'socialdb_event_property_term_edit_metas', 'socialdb_event_property_term_edit_root', 'socialdb_event_property_term_edit_root');
    create_metas($event_edit_property_term['term_id'], 'socialdb_event_property_term_edit_metas', 'socialdb_event_property_term_edit_help', 'socialdb_event_property_term_edit_help');
    $event_delete_property_term = create_register('socialdb_event_property_term_delete', 'socialdb_event_type', array('parent' => $event_property_term_term['term_id']));
    create_metas($event_delete_property_term['term_id'], 'socialdb_event_property_term_delete_metas', 'socialdb_event_property_term_delete_id', 'socialdb_event_property_term_delete_id');

    /** tag* */
    $event_tag_tag = create_register('socialdb_event_tag', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    $event_create_tag = create_register('socialdb_event_tag_create', 'socialdb_event_type', array('parent' => $event_tag_tag['term_id']));
    create_metas($event_create_tag['term_id'], 'socialdb_event_tag_create_metas', 'socialdb_event_tag_suggested_name', 'socialdb_event_tag_suggested_name');
    $event_edit_tag = create_register('socialdb_event_tag_edit', 'socialdb_event_type', array('parent' => $event_tag_tag['term_id']));
    create_metas($event_edit_tag['term_id'], 'socialdb_event_tag_edit_metas', 'socialdb_event_tag_id', 'socialdb_event_tag_id');
    create_metas($event_edit_tag['term_id'], 'socialdb_event_tag_edit_metas', 'socialdb_event_tag_suggested_name', 'socialdb_event_tag_suggested_name');
    $event_delete_tag = create_register('socialdb_event_tag_delete', 'socialdb_event_type', array('parent' => $event_tag_tag['term_id']));
    create_metas($event_delete_tag['term_id'], 'socialdb_event_tag_delete_metas', 'socialdb_event_tag_id', 'socialdb_event_tag_id');
    /* Collection */
    $event_collection = create_register('socialdb_event_collection', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    $event_delete_collection = create_register('socialdb_event_collection_delete', 'socialdb_event_type', array('parent' => $event_collection['term_id']));
    create_metas($event_delete_collection['term_id'], 'socialdb_event_collection_delete_metas', 'socialdb_event_delete_collection_id', 'socialdb_event_delete_collection_id');
    $event_create_collection = create_register('socialdb_event_collection_create', 'socialdb_event_type', array('parent' => $event_collection['term_id']));
    create_metas($event_create_collection['term_id'], 'socialdb_event_collection_create_metas', 'socialdb_event_create_collection_id', 'socialdb_event_create_collection_id');
    /** Comments * */
    $event_comment = create_register('socialdb_event_comment', 'socialdb_event_type', array('parent' => $event_root_term['term_id']));
    $event_create_comment = create_register('socialdb_event_comment_create', 'socialdb_event_type', array('parent' => $event_comment['term_id']));
    create_metas($event_create_comment['term_id'], 'socialdb_event_comment_create_metas', 'socialdb_event_comment_create_content', 'socialdb_event_comment_create_content');
    create_metas($event_create_comment['term_id'], 'socialdb_event_comment_create_metas', 'socialdb_event_comment_create_object_id', 'socialdb_event_comment_create_object_id');
    create_metas($event_create_comment['term_id'], 'socialdb_event_comment_create_metas', 'socialdb_event_comment_parent', 'socialdb_event_comment_parent');
    create_metas($event_create_comment['term_id'], 'socialdb_event_comment_create_metas', 'socialdb_event_comment_author_name', 'socialdb_event_comment_author_name');
    create_metas($event_create_comment['term_id'], 'socialdb_event_comment_create_metas', 'socialdb_event_comment_author_email', 'socialdb_event_comment_author_email');
    create_metas($event_create_comment['term_id'], 'socialdb_event_comment_create_metas', 'socialdb_event_comment_author_website', 'socialdb_event_comment_author_website');
    $event_edit_comment = create_register('socialdb_event_comment_edit', 'socialdb_event_type', array('parent' => $event_comment['term_id']));
    create_metas($event_edit_comment['term_id'], 'socialdb_event_comment_edit_metas', 'socialdb_event_comment_edit_id', 'socialdb_event_comment_edit_id');
    create_metas($event_edit_comment['term_id'], 'socialdb_event_comment_edit_metas', 'socialdb_event_comment_edit_content', 'socialdb_event_comment_edit_content');
    $event_delete_comment = create_register('socialdb_event_comment_delete', 'socialdb_event_type', array('parent' => $event_comment['term_id']));
    create_metas($event_delete_comment['term_id'], 'socialdb_event_comment_delete_metas', 'socialdb_event_comment_delete_id', 'socialdb_event_comment_delete_id');
}

/**
 * function theme_styles()
 * Funcao para registrar os estilos usados
 * Autor: Eduardo Humberto 
 */
if (!function_exists("theme_styles")) {

    function theme_styles() {
        // This is the compiled css file from LESS - this means you compile the LESS file locally and put it in the appropriate directory if you want to make any changes to the master bootstrap.css.
        wp_register_style('bootstrap', get_template_directory_uri() . '/libraries/css/bootstrap.css', array(), '1.0', 'all');
        wp_register_style('bootstrap', get_template_directory_uri() . '/libraries/css/bootstrap.css', array(), '1.0', 'all');
        /* jquery UI CSS */
        wp_register_style("UiStyle", get_template_directory_uri() . '/libraries/css/jquery_ui/jquery-ui.css');
        /* Dynatree CSS */
        wp_register_style("DynatreeCss", get_template_directory_uri() . "/libraries/css/dynatree/skin-vista/ui.dynatree.css");
        /* Cotext  mENU */
        wp_register_style("contextMenu", get_template_directory_uri() . "/libraries/css/contextMenu/contextMenu.css");
        /* ColorPicker Bootstrap CSS */
        wp_register_style('ColorPickerCss', get_template_directory_uri() . '/libraries/js/colorpicker/css/bootstrap-colorpicker.css');

        /* SweetAlert */
        wp_register_style("socialdbSweetAlert", get_template_directory_uri() . "/libraries/css/SweetAlert/sweet-alert.css");
        /* Pagination */
        // wp_register_style("jqpaginationcss", get_template_directory_uri() . '/libraries/css/pagination/jqpagination.css');

        /* Data Table */
        wp_register_style('data_table', get_template_directory_uri() . '/libraries/css/bootstrap_data_table/data_table.css');
        /* Raty */
        wp_register_style('raty', get_template_directory_uri() . '/libraries/css/raty/jquery.raty.css');
        /* dropzone */
        wp_register_style("dropzone", get_template_directory_uri() . '/libraries/css/dropzone/dropzone.css');
        /* fuelux */
        wp_register_style("fuelux", get_template_directory_uri() . '/libraries/css/fuelux/fuelux.css');
        /* fuelux_responsive */
        wp_register_style("fuelux_responsive", get_template_directory_uri() . '/libraries/css/fuelux/fuelux_responsive.css');
        /* Socialdb css */
        wp_register_style("socialdbcss", get_template_directory_uri() . "/libraries/css/socialdb.css");
        /* Lightbox css */
        //wp_register_style("lightboxcss", get_template_directory_uri() . "/libraries/js/lightbox/css/lightbox.css");
        /* PrettyPhoto css */
        wp_register_style("prettyphotocss", get_template_directory_uri() . "/libraries/js/prettyphoto/css/prettyPhoto.css");
        /* Toastr css -- Notificacoes na tela */
        wp_register_style("toastr", get_template_directory_uri() . "/libraries/js/toastr/toastr.css");
        /* Tainacan css */
        wp_register_style("tainacan", get_template_directory_uri() . "/libraries/css/tainacan.css");

        wp_enqueue_style('bootstrap');
        wp_enqueue_style("UiStyle");
        wp_enqueue_style("DynatreeCss");
        wp_enqueue_style("contextMenu");
        wp_enqueue_style('ColorPickerCss');
        wp_enqueue_style('socialdbSweetAlert');
        //wp_enqueue_style("jqpaginationcss");
        wp_enqueue_style('data_table');
        wp_enqueue_style('raty');
        wp_enqueue_style("dropzone");
        wp_enqueue_style('fuelux');
        wp_enqueue_style("fuelux_responsive");

        wp_enqueue_style('socialdbcss');
        //wp_enqueue_style('lightboxcss');
        wp_enqueue_style('prettyphotocss');
        wp_enqueue_style('toastr');
        wp_enqueue_style('tainacan');
    }

}
/**
 * action que executa os estilos
 * Autor: Eduardo Humberto 
 */
add_action('wp_enqueue_scripts', 'theme_styles');
/**
 * function theme_styles()
 * Funcao para registrar os estilos usados
 * Autor: Eduardo Humberto 
 */
if (!function_exists("theme_js")) {

    function theme_js() {

        /* jquery UI */
        wp_register_script('jqueryUi', get_template_directory_uri() . '/libraries/js/jquery_ui/jquery-ui.min.js', array('jquery'), '1.2');
        wp_register_script('bootstrap.min', get_template_directory_uri() . '/libraries/js/bootstrap.min.js', array('jquery'), '1.11');
        /* JIT JS */
        wp_register_script('JitJs', get_template_directory_uri() . '/libraries/js/jit/jit.js');
        /* JIT Excanvas JS */
        wp_register_script('JitExcanvasJs', get_template_directory_uri() . '/libraries/js/jit/extras/excanvas.js');

        wp_register_script('my-script', get_template_directory_uri() . '/libraries/js/my-script.js', array('jquery'), '1.11');
        /* Dynatree JS */
        wp_register_script('DynatreeJs', get_template_directory_uri() . '/libraries/js/dynatree/jquery.dynatree.js');
        /* Ckeditor JS */
        wp_register_script('ckeditorjs', get_template_directory_uri() . '/libraries/js/ckeditor/ckeditor.js');
        /* Context Menu (Dynatree) JS */
        wp_register_script('contextMenu', get_template_directory_uri() . '/libraries/js/contextMenu/jquery.contextMenu-custom.js');
        /* ColorPicker Bootstrap JS */
        wp_register_script('ColorPicker', get_template_directory_uri() . '/libraries/js/colorpicker/js/bootstrap-colorpicker.js');
        /* SweetAlert Bootstrap JS */
        wp_register_script('SweetAlert', get_template_directory_uri() . '/libraries/js/SweetAlert/sweet-alert.js');
        wp_register_script('SweetAlertJS', get_template_directory_uri() . '/libraries/js/SweetAlert/functionsAlert.js');
        /* Data Table */
        wp_register_script('jquerydataTablesmin', get_template_directory_uri() . '/libraries/js/bootstrap_data_table/jquery.dataTables.min.js');
        wp_register_script('data_table', get_template_directory_uri() . '/libraries/js/bootstrap_data_table/data_table.js');
        /* Raty */
        wp_register_script('raty', get_template_directory_uri() . '/libraries/js/raty/jquery.raty.js');
        /* Pagination */
        wp_register_script('jqpagination', get_template_directory_uri() . '/libraries/js/pagination/jquery.jqpagination.js');
        /* dropzone */
        wp_register_script('dropzone', get_template_directory_uri() . '/libraries/js/dropzone/dropzone.js');
        /* dropzone */
        wp_register_script('bootstrap-combobox', get_template_directory_uri() . '/libraries/js/combobox/bootstrap-combobox.js');
        /* Facebook */
        wp_register_script('FacebookJS', 'http://connect.facebook.net/en_US/all.js');
        /* Row Sorter */
        wp_register_script('row-sorter', get_template_directory_uri() . '/libraries/js/row_sorter/jquery.rowsorter.js');
        /* Masked Input */
        wp_register_script('maskedInput', get_template_directory_uri() . '/libraries/js/maskedinput/jquery.mask.min.js');
        /* Lightbox */
        //wp_register_script('lightbox', get_template_directory_uri() . '/libraries/js/lightbox/js/lightbox.min.js');
        /* PrettyPhoto */
        wp_register_script('prettyphoto', get_template_directory_uri() . '/libraries/js/prettyphoto/js/jquery.prettyPhoto.js');
        /* Toastr - notificacoes na tela */
        wp_register_script('toastrjs', get_template_directory_uri() . '/libraries/js/toastr/toastr.min.js');
        /* PrettyPhoto */
        wp_register_script('montage', get_template_directory_uri() . '/libraries/js/montage/jquery.montage.min.js');

        wp_enqueue_script('jqueryUi');
        wp_enqueue_script('bootstrap.min');
        wp_enqueue_script('JitJs');
        wp_enqueue_script('JitExcanvasJs');
        wp_enqueue_script('my-script');
        wp_enqueue_script('DynatreeJs');
        wp_enqueue_script('ckeditorjs');
        wp_enqueue_script('contextMenu');
        wp_enqueue_script('ColorPicker');
        wp_enqueue_script('SweetAlert');
        wp_enqueue_script('SweetAlertJS');
        wp_enqueue_script('jquerydataTablesmin');
        wp_enqueue_script('data_table');
        wp_enqueue_script('raty');
        wp_enqueue_script('jqpagination');
        wp_enqueue_script('dropzone');
        wp_enqueue_script('bootstrap-combobox');
        wp_enqueue_script('FacebookJS');
        wp_enqueue_script('row-sorter');
        wp_enqueue_script('maskedInput');
        wp_enqueue_script('montage');
        //wp_enqueue_script('lightbox');
        wp_enqueue_script('prettyphoto');
        wp_enqueue_script('toastrjs');
        if (isset($_GET["nav"])) {
            init_nav($_GET["nav"]);
        }
    }

}
add_action('wp_enqueue_scripts', 'theme_js');

/**
 * function create_init_collection()
 * Funcao para criar colecao inicial
 * Autor: Eduardo Humberto 
 */
function create_init_collection() {
    $post = verify_init_collection();
    if (!isset($post) || !$post) {
        $init_collection = array(
            'post_type' => 'socialdb_collection',
            'post_title' => __('Tainacan - Collections', 'tainacan'),
            'post_content' => __('The root collection', 'tainacan'),
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        );
        $collection_root_id = wp_insert_post($init_collection);
        $post = get_post($collection_root_id);
    }
    insert_collection_option($post->ID); // Apenas para a colecao inicial
    insert_taxonomy($post->ID, 'socialdb_collection', 'socialdb_collection_type'); // insere a categoria que identifica o tipo da colecao
    create_root_collection_category($post->ID, __('Categories of Collection', 'tainacan')); // cria a categoria inicial que identifica os objetos da colecao 
}

/**
 * function insert_collection_option($collection_id)
 * @param int $collection_id  O id do colecao
 * @return void 
 * Funcao que insere nas opcoes do wordpress a colecao das colecoes
 * Autor: Eduardo Humberto 
 */
function insert_collection_option($collection_id) {
    $option_name = 'collection_root_id';
    if (get_option($option_name) !== false) {
        update_option($option_name, $collection_id);
    } else {
        $autoload = 'yes';
        add_option($option_name, $collection_id, $deprecated, $autoload);
    }
}

/**
 * function include_core_wp()
 * @return void 
 * Funcao que faz include do core do wordpress para utlizar suas funcoes
 * Autor: Eduardo Humberto 
 */
function include_core_wp() {
    if (isset($_GET['by_function'])) {
        include_once (WORDPRESS_PATH . '/wp-config.php');
        include_once (WORDPRESS_PATH . '/wp-load.php');
        include_once (WORDPRESS_PATH . '/wp-includes/wp-db.php');
    } else {
        include_once ('../../../../../wp-config.php');
        include_once ('../../../../../wp-load.php');
        include_once ('../../../../../wp-includes/wp-db.php');
    }
}

/**
 * function theme_styles()
 * @return mix O post com a colecao ou null caso nao exista
 * Funcao que verifica se a colecao inicial foi criada, se criou retorna o post da colecao
 * Autor: Eduardo Humberto 
 */
function verify_init_collection() {
    global $wpdb;
    $postid = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_title = 'Tainacan - Collections' OR post_title = 'Tainacan - Coleções'");
    $value = get_post($postid);
    return $value;
}

/**
 * function insert_taxonomy()
 * @param int $id  O id do post
 * @param string $name  O nome do registro mais especifico
 * @param string $taxonomy A taxonomia do registro
 * Funcao que instancia (cria) os metadados de qualquer objeto
 * Autor: Eduardo Humberto 
 */
function insert_taxonomy($post_id, $category_name, $taxonomy) {
    $category = get_term_by('name', $category_name, $taxonomy);
    $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
    if (empty($post_terms) && !in_array($category->term_id, $post_terms)) {
        wp_set_object_terms($post_id, $category->term_id, $taxonomy);
    }

    instantiate_metas($post_id, $category_name, $taxonomy);
}

/**
 * function instantiate_metas()
 * @param int $id  O id do post
 * @param string $name  O nome do registro mais especifico
 * @param string $taxonomy A taxonomia do registro
 * @param boolean $is_tax Se por acaso for instanciar um termo
 * Funcao que instancia (cria) os metadados de qualquer objeto
 * Autor: Eduardo Humberto 
 */
function instantiate_metas($id, $name, $taxonomy, $is_tax = false) {
    global $wpdb;
    $term = get_term_by('name', $name, $taxonomy);
    if ($term) {
        $metas = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}taxonomymeta"
                . " WHERE meta_key like '{$name}_metas' ");
        if (is_array($metas)) {
            foreach ($metas as $meta) {
                if (!$is_tax) {
                    $post_meta = get_post_meta($id, $meta->meta_value);
                    if (!$post_meta) {
                        add_post_meta($id, $meta->meta_value, '');
                    }
                } else {
                    create_metas($id, $meta->meta_value, '', '');
                }
            }
        }
        $parent = get_term_by('id', $term->parent, $taxonomy);
        if (isset($parent->name)) {
            instantiate_metas($id, $parent->name, $taxonomy, $is_tax);
        }
    } else {
        return false;
    }
}

/**
 * function get_register_id()
 * @param string $name  O nome do registro mais especifico
 * @param string $taxonomy A taxonomia do registro
 * @return mixed 
 * Funcao que instancia (cria) os metadados de qualquer objeto
 * Autor: Eduardo Humberto 
 */
function get_register_id($name, $taxonomy) {
    $register = get_term_by('name', $name, $taxonomy);
    if ($register) {
        return $register->term_id;
    } else {
        return false;
    }
}

/**
 * function create_root_collection_category()
 * @param int id  O id da colecao
 * Funcao que cra a categoria inicial da colecao
 * Autor: Eduardo Humberto 
 */
function create_root_collection_category($collection_id, $category_name) {
    $parent_category_id = get_register_id('socialdb_category', 'socialdb_category_type');
    /* Criando a categoria raiz e adicionando seus metas */
    $category_root_id = create_register($category_name, 'socialdb_category_type', array('parent' => $parent_category_id, 'slug' => sanitize_title(remove_accent($category_name)) . "_" . mktime()));
    instantiate_metas($category_root_id['term_id'], 'socialdb_category', 'socialdb_category_type', true);
    insert_meta_default_values($category_root_id['term_id']);
    /* Pego o termo e verifico se ele ja esta como classificacao da colecao */
    $category_root = get_term_by('id', $category_root_id['term_id'], 'socialdb_category_type');
    $collection_terms = wp_get_object_terms($collection_id, 'socialdb_category_type', array('fields' => 'ids'));
    if (empty($collection_terms) && !in_array($category_root->term_id, $collection_terms)) {
        wp_set_object_terms($collection_id, $category_root->term_id, 'socialdb_category_type');
        update_post_meta($collection_id, 'socialdb_collection_object_type', $category_root->term_id);
        update_post_meta($collection_id, 'socialdb_collection_facets', $category_root->term_id);
        update_post_meta($collection_id, 'socialdb_collection_facet_' . $category_root->term_id . '_color', 'color1');
        update_post_meta($collection_id, 'socialdb_collection_facet_' . $category_root->term_id . '_widget', 'tree');
        update_post_meta($collection_id, 'socialdb_collection_facet_' . $category_root->term_id . '_priority', '1');
    }
    //$properties = instantiate_properties($category_root->term_id);
    //if (is_array($properties)) {
    //  delete_term_meta($category_root->term_id, 'socialdb_category_property_id');
    //  foreach ($properties as $property) {
    //     add_term_meta($category_root->term_id, 'socialdb_category_property_id', $property);
    //     $is_facet = get_term_meta($property, 'socialdb_property_object_is_facet', true);
    //    if ($is_facet && !empty($is_facet) && $is_facet == 'true') {
    //       add_post_meta($collection_id, 'socialdb_collection_facet_' . $property . '_color', 'color_property1');
    //    }
    // }
    //}
    create_initial_property($category_root->term_id, $collection_id);
}

/**
 * function create_root_collection_category()
 * @param int id  O id da colecao
 * Funcao que cra a categoria inicial da colecao
 * Autor: Eduardo Humberto 
 */
function create_initial_property($category_id, $collection_id) {
    $slug = sanitize_title(remove_accent(__('Categories', 'tainacan'))) . "_collection" . $collection_id;
    if (!get_term_by('slug', $slug, 'socialdb_property_type')) {
        $new_property = wp_insert_term(__('Categories', 'tainacan'), 'socialdb_property_type', array('parent' => get_term_by('name', 'socialdb_property_term', 'socialdb_property_type')->term_id,
            'slug' => sanitize_title(remove_accent(__('Categories', 'tainacan'))) . "_collection" . $collection_id));
        $result[] = update_term_meta($new_property['term_id'], 'socialdb_property_required', 'false');
        $result[] = update_term_meta($new_property['term_id'], 'socialdb_property_term_cardinality', 'n');
        $result[] = update_term_meta($new_property['term_id'], 'socialdb_property_term_widget', 'checkbox');
        $result[] = update_term_meta($new_property['term_id'], 'socialdb_property_term_root', $category_id);
        update_term_meta($new_property['term_id'], 'socialdb_property_created_category', $category_id); // adiciono a categoria de onde partiu esta propriedade
        add_term_meta($category_id, 'socialdb_category_property_id', $new_property['term_id']);
    }
}

/**
 * function insert_meta_default_values()
 * @param int id  O id da categoria criada
 * Funcao que da os valores por default as categorias
 * Autor: Eduardo Humberto 
 */
function insert_meta_default_values($category_root_id) {
    update_term_meta($category_root_id, 'socialdb_category_owner', get_current_user_id());
    update_term_meta($category_root_id, 'socialdb_category_date', date("Y/m/d H:i:s"));
    update_term_meta($category_root_id, 'socialdb_category_permission', 'private');
    return $category_root_id['term_id'];
}

function instantiate_properties($term_id, $all_properties_id = array()) {
    $term = get_term_by('id', $term_id, 'socialdb_category_type');
    if ($term_id != 0) {
        $properties = get_term_meta($term->term_id, 'socialdb_category_property_id');
        if ($properties && isset($properties[0]) && $properties[0] != '') {
            $all_properties_id = array_merge($all_properties_id, $properties);
        }
        return instantiate_properties($term->parent, $all_properties_id);
    } else {
        return array_unique($all_properties_id);
    }
}

/**
 * function verify_collection_owner()
 * @param int id  O id do dono da colecao
 * @return boolean 
 * Funcao que cra a categoria inicial da colecao
 * Autor: Eduardo Humberto 
 */
function verify_collection_owner($collection_owner_id) {

    if ($collection_owner_id == get_current_user_id()) {
        return true;
    } else {
        return false;
    }
}

/**
 * function verify_collection_moderators()
 * @param int id  O id do dono da colecao
 * @return boolean 
 * Funcao que cra a categoria inicial da colecao
 * Autor: Eduardo Humberto 
 */
function verify_collection_moderators($collection_id, $user_id) {
    $owner = get_post($collection_id)->post_author;
    $moderators = get_post_meta($collection_id, 'socialdb_collection_moderator');
    if ($user_id != 0 && ($user_id == $owner || in_array($user_id, $moderators))) {
        return true;
    } else {
        return false;
    }
}

//
// Taxonomy meta functions
//

/**
 * Add meta data field to a term.
 *
 * @param int $term_id Post ID.
 * @param string $key Metadata name.
 * @param mixed $value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function add_term_meta($term_id, $meta_key, $meta_value, $unique = false) {
    return add_metadata('taxonomy', $term_id, $meta_key, $meta_value, $unique);
}

/**
 * Remove metadata matching criteria from a term.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int $term_id term ID
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function delete_term_meta($term_id, $meta_key, $meta_value = '') {
    return delete_metadata('taxonomy', $term_id, $meta_key, $meta_value);
}

/**
 * Retrieve term meta field for a term.
 *
 * @param int $term_id Term ID.
 * @param string $key The meta key to retrieve.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function get_term_meta($term_id, $key, $single = false) {
    return get_metadata('taxonomy', $term_id, $key, $single);
}

/**
 * Update term meta field based on term ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @param int $term_id Term ID.
 * @param string $key Metadata key.
 * @param mixed $value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '') {
    return update_metadata('taxonomy', $term_id, $meta_key, $meta_value, $prev_value);
}

/**
 * Criando tabela taxonomymeta
 *
 */
function setup_taxonomymeta() {
    global $wpdb;

    $charset_collate = '';
    if (!empty($wpdb->charset))
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    if (!empty($wpdb->collate))
        $charset_collate .= " COLLATE $wpdb->collate";

    $tables = $wpdb->get_results("show tables like '{$wpdb->prefix}taxonomymeta'");
    if (!count($tables))
        $wpdb->query("CREATE TABLE {$wpdb->prefix}taxonomymeta (
				meta_id bigint(20) unsigned NOT NULL auto_increment,
				taxonomy_id bigint(20) unsigned NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value longtext,
				PRIMARY KEY	(meta_id),
				KEY taxonomy_id (taxonomy_id),
				KEY meta_key (meta_key)
			) $charset_collate;");
}

/*
 * Quick touchup to wpdb
 */

/*
 * Quick touchup to wpdb
 */

function wpdbfix() {
    global $wpdb;
    $wpdb->taxonomymeta = "{$wpdb->prefix}taxonomymeta";
}

/* * ****************************************** */
/*            SOCIALDB HELPERS               */
/* * ****************************************** */

function Words($String, $Limite, $Pointer = null) {
    $Data = strip_tags(trim($String));
    $Format = (int) $Limite;

    //$ArrWords = explode(' ', $Data);
    $NumWords = strlen($Data);
    $NewWords = substr($Data, 0, $Format);

    $Pointer = (empty($Pointer) ? '...' : ' ' . $Pointer);

    $Result = ( $Format < $NumWords ? $NewWords . $Pointer : $Data);

    return $Result;
}

function remove_accent($Name) {
    $Format = array();
    $Format['a'] = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª';
    $Format['b'] = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr                                 ';

    $Data = strtr(utf8_decode($Name), utf8_decode($Format['a']), utf8_decode($Format['b']));
    $Data = strip_tags(trim($Data));

    return utf8_encode($Data);
}

function cut_string($String, $Limite, $Pointer = null) {
    $Data = strip_tags(trim($String));
    $Format = (int) $Limite;

    $CountChar = strlen($Data);
    $NewWords = substr($Data, 0, $Format);

    $Pointer = (empty($Pointer) ? '...' : ' ' . $Pointer);

    $Result = ( $Format < $CountChar ? $NewWords . $Pointer : $Data);

    return $Result;
}

/**
 *
 * Funcao que semelhante a wp_insert_term do wordpress porem otimizada por nao atualizar o count
 *
 * @param string $name O nome do termo a ser inserido.
 * @param string $taxonomy Metadata key.
 * @param string $parent Metadata value.
 * @param string $slug O slug do termo a ser utilizado
 * @return array com o id do termo criado.
 */
function socialdb_insert_term($name, $taxonomy, $parent, $slug) {
    global $wpdb;
    $args = array('name' => $name, 'taxonomy' => $taxonomy, 'alias_of' => '', 'slug' => $slug, 'description' => '', 'parent' => $parent, 'term_group' => 0);
    $args = sanitize_term($args, $taxonomy, $taxonomy, 'db');
    extract($args, EXTR_SKIP);

    $name = stripslashes($name);
//$slug = wp_unique_term_slug(sanitize_title($name), (object)$args);
    $array = socialdb_term_exists_by_slug(sanitize_title(remove_accent($name)), $taxonomy, $parent);
    if (!isset($array['term_id'])) {
        $wpdb->insert($wpdb->terms, compact('name', 'slug', 'term_group'));
        $term_id = (int) $wpdb->insert_id;

        $wpdb->insert($wpdb->term_taxonomy, compact('term_id', 'taxonomy', 'description', 'parent') + array('count' => 1));
        $tt_id = (int) $wpdb->insert_id;
        $array = array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
        add_term_meta($term_id, 'socialdb_category_owner', get_current_user_id());
    } else {
        $array['already_exists'] = true;
    }

    return $array;
}

/**
 *
 * Funcao que verifica a existencia de um termo pelo seu slug do tipo categoria
 *
 * @param string $term O nome do termo a ser inserido.
 * @return array com o id do termo se ele existe.
 */
function socialdb_term_exists_by_slug($term, $taxonomy, $parent = null) {
    global $wpdb;
    if (!isset($parent)) {
        $sql = "select t.term_id, tt.term_taxonomy_id from {$wpdb->term_taxonomy} tt inner join {$wpdb->terms} t t on t.term_id = tt.term_id where t.slug LIKE '$term%' and tt.taxonomy LIKE '$taxonomy' ";
    } else {
        $parent = str_replace('_facet_category', '', $parent);
        $sql = "select t.term_id, tt.term_taxonomy_id from {$wpdb->term_taxonomy} tt inner join {$wpdb->terms} t on t.term_id = tt.term_id where t.slug LIKE '{$term}_%' and tt.taxonomy LIKE '$taxonomy' and tt.parent = $parent ";
    }
    return $wpdb->get_row($sql, ARRAY_A);
}

/**
 *
 * Funcao que verifica a existencia de um termo independente de sua taxonomuia
 *
 * @param int O id do termo a ser verificado.
 * @param string $taxonomy Metadata key A taxonomia do tipo do termo.
 * @return array com o id do termo se ele existir.
 */
function socialdb_term_exists($term, $taxonomy) {
    global $wpdb;
    $sql = sprintf("select t.term_id, tt.term_taxonomy_id from %s tt inner join %s t on t.term_id = tt.term_id where t.term_id = %s ", $wpdb->term_taxonomy, $wpdb->terms, $term);
    return $wpdb->get_row($sql, ARRAY_A);
}

/**
 *
 * Funcao que verifica a existencia de um relacao entre um termo e o objeto
 *
 * @param int O id do termo a ser verificado.
 * @param string $term Metadata key A taxonomia do tipo do termo.
 * @param string $object Metadata key A taxonomia do tipo do termo.
 * @return array com o id do termo se ele existir.
 */
function socialdb_relation_exists($term, $object) {
    global $wpdb;
    $sql = sprintf("select tt.object_id, tt.term_taxonomy_id from %s tt WHERE tt.object_id = %s AND tt.term_taxonomy_id = %s ", $wpdb->term_relationships, $object, $term);
    return $wpdb->get_row($sql, ARRAY_A);
}

/**
 *
 * Funcao que insere o relacionamento de um termo com um objeto
 *
 * @param string $object_id O id do objeto.
 * @param array $terms O array de IDs dos termos que serao vinculados ao objeto.
 * @param string $taxonomy A taxonomia dos termos que serao vinculados.
 * @return void.
 */
function socialdb_add_tax_terms($object_id, $terms, $taxonomy) {
    global $wpdb;
    foreach ($terms as $term) {
        $term_info = socialdb_term_exists($term, $taxonomy);
        $verify = socialdb_relation_exists($term, $object_id);
        if (!isset($verify['object_id'])) {
            $tt_id = $term_info['term_taxonomy_id'];
            $wpdb->insert($wpdb->term_relationships, array('object_id' => $object_id, 'term_taxonomy_id' => $tt_id));
        }
//wp_update_term_count(array($tt_id), $taxonomy);
    }
}

/**
 *
 * Funcao que insere o relacionamento de um termo com um objeto
 *
 * @param string $post_name O post_name da colecao.
 * @param string (optional) $output O tipo de retono.
 * @return wp post O post da colecao.
 */
function get_post_by_name($post_name, $output = OBJECT) {
    global $wpdb;
    $post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type='socialdb_collection'", $post_name));
    if ($post)
        return get_post($post, $output);

    return null;
}

/**
 *
 * Funcao que faz o download de uma pagina
 *
 * @param string $object_id O id do objeto.
 * @param array $terms O array de IDs dos termos que serao vinculados ao objeto.
 * @param string $taxonomy A taxonomia dos termos que serao vinculados.
 * @return void.
 */
function download_page($path) {
    session_write_close();
    ini_set('max_execution_time', '0');
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, trim($path));
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $retValue = curl_exec($ch);
        curl_close($ch);
    } catch (Exception $e) {
        $retValue = file_get_contents($path);
    }
    return $retValue;
}

/**
 *
 * Funcao que insere um objeto
 *
 * @param string $object_id O id do objeto.
 * @param array $terms O array de IDs dos termos que serao vinculados ao objeto.
 * @param string $taxonomy A taxonomia dos termos que serao vinculados.
 * @return void.
 */
function socialdb_insert_object($post_title, $post_date = null) {
    if (!isset($post_date)) {
        $post_date = array(date('Y-m-d H:i:s'));
    }
    $status = 'publish';
    $post_author = 1;
    $post = array(
        'post_author' => $post_author,
        'post_title' => $post_title[0],
        'post_date' => str_replace("Z", "", str_replace("T", " ", $post_date[0])),
        'post_content' => "",
        'post_status' => $status,
        'post_type' => 'socialdb_object'
    );
    $post_id = wp_insert_post($post);
    return $post_id;
}

/**
 *
 * Funcao que retorna a url do thumbnail default de acordo com o tipo do item
 *
 * @param string $object_id O id do objeto.
 * @return void.
 */
function get_item_thumbnail_default($object_id) {
    $type = get_post_meta($object_id, 'socialdb_object_dc_type', true);
    if($type=='image'){
        return get_template_directory_uri().'/libraries/images/imagem.png';
    }elseif($type=='audio'){
        return get_template_directory_uri().'/libraries/images/audio.png';
    }elseif($type=='video'){
        return get_template_directory_uri().'/libraries/images/video.png';
    }elseif($type=='pdf'){
        return get_template_directory_uri().'/libraries/images/pdf-ebook.png';
    }elseif($type=='text'){
        return get_template_directory_uri().'/libraries/images/texto.png';
    }else{
        return get_template_directory_uri().'/libraries/images/imagem.png';
    }
}

/**
 *
 * Funcao que atualiza o content de um post
 *
 * @param string $object_id O id do objeto.
 * @param string $content O conteudo a ser atualizado.
 * @return void.
 */
function update_post_content($object_id, $content) {
    global $wpdb;
    $wp_posts = $wpdb->prefix . "posts";
    $query = "UPDATE $wp_posts SET post_content = '" . $content . "' WHERE ID = $object_id
	";
    $results = $wpdb->get_results($query);
}
/*******************************************************************************
 *               HARVESTING                                                    *        
 *******************************************************************************/
/**
 *
 * Funcao que atualiza o content de um post
 *
 * @param string $object_id O id do objeto.
 * @param string $content O conteudo a ser atualizado.
 * @return void.
 */
function harvesting() {
    $already_harvested = array();
    session_write_close();
    ini_set('max_execution_time', '0');
    register_post_types();
    register_taxonomies();
    wpdbfix();
    include_once(dirname(__FILE__) . '/models/export/oaipmh_model.php');
    include_once(dirname(__FILE__) . '/models/import/harvesting_oaipmh_model.php');
    $oai_pmh_model = new OAIPMHModel;
    $harvesting_oaipmh_model = new HarvestingOAIPMHModel();
    $harvesting_mappings = $oai_pmh_model->get_harvesting_mappings();
    if ($harvesting_mappings && !empty($harvesting_mappings)) {
        foreach ($harvesting_mappings as $harvesting_mapping) {
            $data['collection_id'] = $harvesting_mapping['collection_id'];
            $data['until'] = date("Y-m-d\TH:i:s\Z", time());
            foreach ($harvesting_mapping['mappings'] as $mapping_id) {
                if (!in_array($mapping_id, $already_harvested)) {
                    $data['from'] = date("Y-m-d\TH:i:s\Z", get_post_meta($mapping_id, 'socialdb_channel_oaipmhdc_last_update', true));
                    $data['mapping_id'] = $mapping_id;
                    $sets = get_post_meta($mapping_id, 'socialdb_channel_oaipmhdc_sets', true);
                    if ($sets && $sets != '') {
                        $data['sets'] = $sets;
                    }
                    $data['url'] = get_post($mapping_id)->post_title;
                    $harvesting_oaipmh_model->execute($data);
                    $already_harvested[] = $mapping_id;
                    update_post_meta($mapping_id, 'socialdb_channel_oaipmhdc_last_update', time());
                }
            }
        }
    }
}

add_action('CronHarvesting', 'harvesting', 10, 4);

/**
 * ativa o cron para o harvesting OAIPMH
 *

 * @return void.
 */
function active_cron() {
    if (!wp_next_scheduled('CronHarvesting')) {
        wp_schedule_event(time(), 'hourly', 'CronHarvesting');
    }
}

/**
 * Compara as prioridades para efetuar a ordenação dos arrays recebidos
 *
 */
function compare_priority($a, $b) {
    return strnatcmp($a['priority'], $b['priority']);
}

// Returns a file size limit in bytes based on the PHP upload_max_filesize
// and post_max_size
function file_upload_max_size() {
    static $max_size = -1;

    if ($max_size < 0) {
// Start with post_max_size.
        $max_size = parse_size(ini_get('post_max_size'));

// If upload_max_size is less, then reduce. Except if upload_max_size is
// zero, which indicates no limit.
        $upload_max = parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }
    }
    return $max_size;
}

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    if ($unit) {
// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

/*
  function translate_domain_to_ip($url){
  $parse = parse_url($url);
  $parse['host'] = getAddrByHost($parse['host']);
  $new_url = unparse_url($parse);
  return  $new_url;
  }

  function unparse_url($parsed_url) {
  $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
  $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
  $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
  $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
  $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
  $pass     = ($user || $pass) ? "$pass@" : '';
  $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
  $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
  $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
  return "$scheme$user$pass$host$port$path$query$fragment";
  }


  function getAddrByHost($host, $timeout = 3) {
  $query = `nslookup -timeout=$timeout -retry=1 $host`;
  if(preg_match('/\nAddress: (.*)\n/', $query, $matches))
  return trim($matches[1]);
  return $host;
  } */

/* * ********************************** DESIGN FUNTIONS ****************************************** */

function display_img_items_collection($collection_id, $max_itens, $is_popular = false) {

    $tax_query[] = array(
        'taxonomy' => 'socialdb_category_type',
        'field' => 'id',
        'terms' => get_post_meta($collection_id, 'socialdb_collection_object_type', true),
        'operator' => 'IN'
    );
    if (!$is_popular) {
        $args = array(
            'post_type' => 'socialdb_object',
            'posts_per_page' => 20,
            'tax_query' => $tax_query,
            //'no_found_rows' => true, // counts posts, remove if pagination required
            'update_post_term_cache' => false, // grabs terms, remove if terms required (category, tag...)
            'update_post_meta_cache' => false, // grabs post meta, remove if post meta required
        );
    } else {
        $form = get_post_meta($collection_id, 'socialdb_collection_ordenation_form', true);
        $order = get_post_meta($collection_id, 'socialdb_collection_default_ordering', true);
        $meta_key = 'socialdb_property_' . trim($order);
        $args = array(
            'post_type' => 'socialdb_object',
            'posts_per_page' => 20,
            'tax_query' => $tax_query,
            'orderby' => $meta_key,
            'order' => $form,
            //'no_found_rows' => true, // counts posts, remove if pagination required
            'update_post_term_cache' => false, // grabs terms, remove if terms required (category, tag...)
            'update_post_meta_cache' => false, // grabs post meta, remove if post meta required
        );
    }
    $loop = new WP_Query($args);
    if ($loop->have_posts()) :
        $count = 0;
        while ($loop->have_posts() && $count < $max_itens) : $loop->the_post();
            $url_image = wp_get_attachment_url(get_post_thumbnail_id(get_the_ID()));
            if ($url_image):
                echo ' <a href="' . get_the_permalink($collection_id) . '"><img src="' . $url_image . '"></img></a>';
            else:
               echo ' <a href="' . get_the_permalink($collection_id) . '"><img src="' . get_item_thumbnail_default(get_the_ID()) . '"></img></a>';
               // echo ' <a href="' . get_the_permalink($collection_id) . '"><img src="' . get_template_directory_uri() . '/libraries/images/empty_image' . rand(1, 5) . '.jpg"></img></a>';
            endif;
            $count++;
        endwhile;
    endif;
    
//    $url_image = wp_get_attachment_url(get_post_thumbnail_id($collection_id));
//    if ($url_image):
//        echo ' <a href="' . get_the_permalink($collection_id) . '"><img src="' . $url_image . '"></img></a>';
//    else:
//       echo ' <a href="' . get_the_permalink($collection_id) . '"><img src="' . get_template_directory_uri().'/libraries/images/thumbcolecao.png"></img></a>';
//       // echo ' <a href="' . get_the_permalink($collection_id) . '"><img src="' . get_template_directory_uri() . '/libraries/images/empty_image' . rand(1, 5) . '.jpg"></img></a>';
//    endif;

 if($count<$max_itens):
 while ($count<$max_itens):
   echo ' <a href="'.  get_the_permalink($collection_id).'"><img src="'.get_template_directory_uri().'/libraries/images/thumbcolecao.png"></img></a>';
   $count++;
 endwhile;    
endif;
}
