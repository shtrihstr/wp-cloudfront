<?php

if( defined( 'AWS_CLOUDFRONT_DOMAIN' ) ) {

    if( ! function_exists( 'get_cloudfront_attachment_url' ) ) {

        function get_cloudfront_attachment_url( $url ) {

            // get site domains
            if( false === ( $hosts = get_transient( 'cf-site-hosts' ) ) ) {

                $hosts = [ parse_url( home_url(), PHP_URL_HOST ) ];

                if (defined('DOMAIN_MAPPING')) {

                    global $wpdb, $blog_id;
                    $domains1 = $wpdb->get_col("SELECT domain FROM {$wpdb->blogs} WHERE blog_id = '$blog_id'");

                    $mapping = $wpdb->base_prefix . 'domain_mapping';
                    $domains2 = $wpdb->get_col("SELECT domain FROM {$mapping} WHERE blog_id = '$blog_id'");
                    $hosts = array_merge($hosts, $domains1, $domains2);
                }

                set_transient( 'cf-site-hosts', $hosts, HOUR_IN_SECONDS );
            }

            // get global uploads dir
            if( false === ( $uploads_url_path = get_transient( 'cf-uploads-url-path' ) ) ) {

                if( is_multisite() ) {
                    global $current_site;
                    switch_to_blog($current_site->blog_id);
                }

                $upload_dir = wp_upload_dir();
                $uploads_url_path = parse_url( $upload_dir['baseurl'], PHP_URL_PATH );

                if( is_multisite() ) {
                    restore_current_blog();
                }

                set_transient( 'cf-uploads-url-path', $uploads_url_path, HOUR_IN_SECONDS );
            }

            if( in_array( parse_url( $url, PHP_URL_HOST ), $hosts ) && false !== mb_strpos( $url, $uploads_url_path ) ) {

                $aws_s3_key = mb_substr( $url, mb_strpos( $url, $uploads_url_path ) + mb_strlen( $uploads_url_path ) );
                $protocol = is_ssl() ? 'https://' : 'http://';
                $url = $protocol . AWS_CLOUDFRONT_DOMAIN . $aws_s3_key;
            }

            return $url;
        }

    }

    if( ! is_admin() || defined( 'DOING_AJAX' ) ) { //todo: should be only for not logged users?

        add_filter( 'wp_get_attachment_url', 'get_cloudfront_attachment_url', 999 );

        add_filter( 'the_content', function( $content ) {
            $regex  = '/((http|https):\/\/)?[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?/';

            return preg_replace_callback( $regex, function( $url ) {
                return get_cloudfront_attachment_url( $url[0] );
            }, $content );

        }, 999 );

    }

}



