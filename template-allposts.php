<?php
/*
* Template Name : Listallposts
*/

$totalNumberOfPosts = wp_count_posts();
$totalNumberOfPublishedPosts = $totalNumberOfPosts->publish;
$numberPerPage = 10;
$makeMyArray = [];

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$args = array(
    'posts_per_page' => $numberPerPage,
    'paged' => $paged
);

$i = 0;
// The Query
$the_query = new WP_Query($args);

// The Loop
if ($the_query->have_posts()) {

    while ($the_query->have_posts()) {
        $the_query->the_post();


        $makeMyArray[$i]['ID'] = get_the_ID();
        $makeMyArray[$i]['post_title'] = get_the_title();
        $makeMyArray[$i]['post_content'] = get_the_content();
        $makeMyArray[$i]['post_data'] = get_the_date('Y-d-m - h:j:s');

        if (has_post_thumbnail() === true) {
            $makeMyArray[$i]['featured_image'] = '<img src="' . get_the_post_thumbnail_url(get_the_ID()) . '">';
        } else {
            $makeMyArray[$i]['featured_image'] = '';

        }
        $makeMyArray[$i]['post_status'] = get_post_status();

        $currentPostCategories = wp_get_object_terms(get_the_ID(), 'category');
        $j = 0;
        foreach ($currentPostCategories as $category) {

            $makeMyArray[$i]['post_category'][$j] = $category->name;
            $j++;
        }

        $i++;

    }

    wp_reset_postdata();
}

$getCurrentPostsInDatabase['posts'] = $makeMyArray;
$getCurrentPostsInDatabase['total'] = $totalNumberOfPublishedPosts;
$getCurrentPostsInDatabase['size'] = $numberPerPage;
$getCurrentPostsInDatabase['page'] = $paged;


echo json_encode($getCurrentPostsInDatabase, JSON_HEX_APOS);