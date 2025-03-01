<?php

namespace Leat\Utils;

/**
 * Post Helper class.
 */
class Post
{
    /**
     * Get post meta data.
     *
     * @param int    $post_id        The post ID.
     * @param string $key            The meta key to retrieve.
     * @param mixed  $fallback_value Optional. Default value if meta is empty.
     * @return mixed The meta value or fallback value.
     */
    public static function get_post_meta_data($post_id, $key, $fallback_value = null, $single = true)
    {
        $value = get_post_meta($post_id, $key, $single);

        return empty($value) ? $fallback_value : $value;
    }

    /**
     * Get post meta data as an array.
     *
     * @param int    $post_id        The post ID.
     * @param string $key            The meta key to retrieve.
     * @param mixed  $fallback_value Optional. Default value if meta is empty.
     * @return array The meta value or fallback value.
     */
    public static function get_post_meta_data_array($post_id, $key, $fallback_value = null)
    {
        return self::get_post_meta_data($post_id, $key, $fallback_value, false);
    }
}
