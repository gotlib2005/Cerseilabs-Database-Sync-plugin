<?php

//enqueue style and scripts
add_action('admin_enqueue_scripts', 'enqueue_plugin_admin_style_and_scripts');

function enqueue_plugin_admin_style_and_scripts()
{
    wp_register_style('plugin-admin-css', plugins_url() . '/clbs-database-sync/assets/css/clbs_plugin_style.css', false);
    wp_enqueue_style('plugin-admin-css');

    wp_register_script('plugin-admin-js', plugins_url() . '/clbs-database-sync/assets/js/clbs_plugin_script.js', true);
    wp_enqueue_script('plugin-admin-js');

}

//add admin ajax url for AJAX function
add_action('admin_enqueue_scripts', 'add_website_url_in_head');

function add_website_url_in_head()
{
    ?>
    <script type="application/javascript">
        var _AJAXURL = "<?php echo site_url() . '/wp-admin/admin-ajax.php';?>";
    </script>
    <?php
}


//When I choose what type of website is (Source or Destination), insert option in databese.

add_action('wp_ajax_insert_plugin_option', 'insert_plugin_option');
add_action('wp_ajax_nopriv_insert_plugin_option', 'insert_plugin_option');

function insert_plugin_option()
{
    $jsonRespondArray = [];
    $getDataValue = $_POST['datavalue'];
    $checkTypeOfConnection = get_option('type_of_database');

    if (!isset($checkTypeOfConnection)) {

        add_option('type_of_database', $getDataValue);

    } else {
        update_option('type_of_database', $getDataValue);
    }


    if ($getDataValue === SOURCE_TYPE_OF_DATABASE) {
        $jsonRespondArray['database'] = 'source';
        create_empty_clbs_page();
    }

    if ($getDataValue === DESTINATION_TYPE_OF_DATABASE) {

        $jsonRespondArray['database'] = 'destination';
        delete_empty_clbs_page();
    }

    echo json_encode($jsonRespondArray);
    die();
}

/**
 * @param $data
 *  This is function for inserting URl of SOURCE website
 */

function insert_url_for_source_website($data)
{
    $getCdbSourceUrl = get_option('cdb_source_url');

    if (!isset($getCdbSourceUrl)) {
        add_option('cdb_source_url', $data);
    } else {
        update_option('cdb_source_url', $data);
    }

}

// insert URL in wp_options from form
if (isset($_POST['source_url'])) {

    insert_url_for_source_website($_POST['source_url']);

}

add_action('wp_ajax_getPostsFromAnotherServer', 'getPostsFromAnotherServer');
add_action('wp_ajax_nopriv_getPostsFromAnotherServer', 'getPostsFromAnotherServer');

function getPostsFromAnotherServer()
{
    $url = get_clbs_page_permalink();
    $iterations = getNumberOfIterations($url);

    $curl = curl_init();

    for ($i = 1; $i <= $iterations; $i++) {
        curl_setopt_array($curl, array(

            CURLOPT_URL => $url . '&paged=' . $i,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            )
        ));
        $jsonrespond = curl_exec($curl);

        $response = json_decode($jsonrespond);

        insertToDatabase($response->posts);

    }

    curl_close($curl);
    die();
}


/**
 * @param $posts
 */

function insertToDatabase($posts)
{
    foreach ($posts as $post) {

        //grab all image src tag from post content and feature image
        $regex = '/src="([^"]*)"/';
        preg_match_all($regex, $post->post_content . $post->featured_image, $matches);
        $matches = array_reverse($matches);

        $contentWithGoodImageUrl = replace_image_url($post->post_content);

        if (post_exists($post->post_title)) {

            $postIDFromDatabase = get_page_by_title($post->post_title, OBJECT, 'post');

            $arg = array(
                'ID' => $postIDFromDatabase->ID,
                'post_content' => $contentWithGoodImageUrl,
                'post_date' => $post->post_date,
                'post_status' => $post->post_status
            );

            wp_update_post($arg);

            foreach ($matches[0] as $key => $value) {
                uploadRemoteImageAndAttach($value, $postIDFromDatabase->ID, $post->featured_image);
            }
            insert_category_to_post($post->post_category, $postIDFromDatabase->ID);

        } else {

            $arg = array(
                'post_title' => $post->post_title,
                'post_content' => $contentWithGoodImageUrl,
                'post_date' => $post->post_date,
                'post_status' => $post->post_status
            );

            $postID = wp_insert_post($arg, true);

            foreach ($matches[0] as $key => $value) {
                uploadRemoteImageAndAttach($value, $postID, $post->featured_image);
            }
            insert_category_to_post($post->post_category, $postID);

        }

    }
}

/**
 * @param $url
 * @return float|int
 */

function getNumberOfIterations($url)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded"
        )
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        $phpresponsearray = json_decode($response, JSON_HEX_APOS);

        $totalPosts = $phpresponsearray['total'];
        $sizePerPage = $phpresponsearray['size'];

        if ($totalPosts < $sizePerPage) {
            return 1;
        } else {
            return round($totalPosts / $sizePerPage, 0, PHP_ROUND_HALF_UP);
        }
    }
    die();
}


add_filter('page_template', 'clbs_redirect_page_tempalte');

/**
 * @param $page_template
 * @return string
 * connecting page with json string(all posts) and page template
 */

function clbs_redirect_page_tempalte($page_template)
{
    if (is_page('clbs-list-all-posts') == true) {
        $page_template = WP_PLUGIN_DIR . '/clbs-database-sync/template-allposts.php';
    }
    return $page_template;
}

/**
 * Create empty page with all posts in json format
 */
function create_empty_clbs_page()
{
    $page = get_page_by_title('clbs-list-all-posts');
    if ($page === NULL) {
        $arg = array(
            'post_title' => 'clbs-list-all-posts',
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        wp_insert_post($arg, true);
    }
    delete_option('cdb_source_url');
}

add_action('wp_ajax_clbs_page_permalink', 'clbs_page_permalink');
add_action('wp_ajax_nopriv_clbs_page_permalink', 'clbs_page_permalink');

function clbs_page_permalink()
{
    $urlFromPage = get_permalink(get_page_by_title('clbs-list-all-posts'));

    echo $urlFromPage;

    die();
}

add_action('wp_ajax_get_clbs_page_permalink', 'get_clbs_page_permalink');
add_action('wp_ajax_nopriv_get_clbs_page_permalink', 'get_clbs_page_permalink');

/**
 * @return mixed
 */

function get_clbs_page_permalink()
{
    $getCdbSourceUrl = get_option('cdb_source_url');

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $getCdbSourceUrl . '/wp-admin/admin-ajax.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "action=clbs_page_permalink",
        CURLOPT_HTTPHEADER => array(
            "action: clbs_page_permalink",
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded"
        )
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        return $response;
    }
    die();
}

function delete_empty_clbs_page()
{
    $postID = get_page_by_title('clbs-list-all-posts');
    wp_delete_post($postID->ID, true);
}

/**
 * @param $postContent
 * @return mixed
 */

function replace_image_url($postContent)
{
    $siteUrl = get_option('siteurl');
    $previousSiteUrl = get_option('cdb_source_url');
    $newUrl = str_replace($previousSiteUrl, $siteUrl, $postContent);

    return $newUrl;
}

/**
 * @param $image_url
 * @param $parent_id
 * @param $featuredImage
 * @return bool|int|WP_Error
 */

function uploadRemoteImageAndAttach($image_url, $parent_id, $featuredImage)
{
    $image = $image_url;

    $thumb_id = does_image_exists(basename($image));

    if (null == $thumb_id) {

        $get = wp_remote_get($image);

        $type = wp_remote_retrieve_header($get, 'content-type');

        if (!$type)
            return false;

        $mirror = wp_upload_bits(basename($image), '', wp_remote_retrieve_body($get));

        $attachment = array(
            'post_title' => basename($image),
            'post_mime_type' => $type
        );

        $attach_id = wp_insert_attachment($attachment, $mirror['file'], $parent_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $mirror['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);


        if ($featuredImage !== '') {

            set_post_thumbnail($parent_id, $attach_id);
        }

        return $attach_id;
    } else {
        set_post_thumbnail($parent_id, $thumb_id);
    }
    return true;
}

/**
 * @param array $categoryName
 * @param $postID
 */

function insert_category_to_post(array $categoryName, $postID)
{
    wp_delete_object_term_relationships($postID, 'category');

    foreach ($categoryName as $category) {

        if (!term_exists($category, 'category')) {

            $categoryID = wp_insert_term($category, 'category');
            wp_set_post_categories($postID, $categoryID, true);

        } else {
            $getCategoryID = get_cat_ID($category);
            wp_set_post_categories($postID, $getCategoryID, true);

        }

    }
}

/**
 * @param $filename
 * @return int
 * Check ih Image exists in upload folder
 */

function does_image_exists($filename)
{
    global $wpdb;

    return intval($wpdb->get_var("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%/$filename'"));
}