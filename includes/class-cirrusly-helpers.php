<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Cirrusly_Helpers {

    public static function get_icon( $url ) {
        $ext = pathinfo( $url, PATHINFO_EXTENSION );
        $url = strtolower( $url );

        if ( strpos( $url, 'youtube' ) !== false || strpos( $url, 'vimeo' ) !== false || strpos( $url, '/video/' ) !== false || in_array( $ext, ['mp4', 'mov', 'webm'] ) ) {
            return 'dashicons-video-alt3';
        }
        if ( in_array( $ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'] ) ) {
            return 'dashicons-format-image';
        }
        if ( in_array( $ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'] ) ) {
            return 'dashicons-media-document';
        }
        if ( in_array( $ext, ['zip', 'rar', '7z'] ) ) {
            return 'dashicons-media-archive';
        }
        return 'dashicons-admin-links';
    }

    public static function get_size_label( $file_ref ) {
        $file_path = '';
        if ( is_numeric( $file_ref ) ) {
            $file_path = get_attached_file( $file_ref );
        } else {
            $upload_dir = wp_upload_dir();
            if ( strpos( $file_ref, $upload_dir['baseurl'] ) !== false ) {
                $file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_ref );
            }
        }

        if ( $file_path && file_exists( $file_path ) ) {
            $bytes = filesize( $file_path );
            return '<span class="cw-file-size">(' . size_format( $bytes, 1 ) . ')</span>';
        }
        return '';
    }

    /**
     * Check if current user has the required role.
     */
    public static function check_permission( $restriction ) {
        if ( empty( $restriction ) || $restriction === 'all' ) return true;
        if ( $restriction === 'logged_in' && is_user_logged_in() ) return true;
        if ( $restriction === 'guest' && ! is_user_logged_in() ) return true;

        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            if ( in_array( $restriction, (array) $user->roles ) ) return true;
            // Admins always see everything
            if ( current_user_can('administrator') ) return true;
        }

        return false;
    }

    /**
     * Check if attachment is expired.
     */
    public static function check_expiry( $date_string ) {
        if ( empty( $date_string ) ) return false; // No expiry
        $expiry = strtotime( $date_string );
        return ( time() > $expiry ); // True if expired
    }
}