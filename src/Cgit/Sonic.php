<?php

namespace Cgit;

class Sonic
{
    /**
     * Common media types by extension
     *
     * @var array
     */
    private static $types = [
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
    ];

    /**
     * Private constructor
     *
     * @return void
     */
    private function __construct()
    {
        // :(
    }

    /**
     * Does variable contain item?
     *
     * Does a string contain a particular substring or does an array contain a
     * particular value?
     *
     * @param mixed $obj Variable to search
     * @param mixed $term Value or substring to find within variable
     *
     * @return bool
     */
    public static function contains($obj, $term)
    {
        if (is_array($obj)) {
            return in_array($term, $obj);
        }

        return strpos($obj, $term) !== false;
    }

    /**
     * Does variable start with an item?
     *
     * Does a string start with a particular substring or does an array start
     * with a particular value?
     *
     * @param mixed $obj Variable to search
     * @param mixed $term Value or substring to find within variable
     *
     * @return bool
     */
    public static function startsWith($obj, $term)
    {
        if (is_array($obj)) {
            return reset($obj) == $term;
        }

        return strpos($obj, $term) === 0;
    }

    /**
     * Does variable end with an item?
     *
     * Does a string end with a particular substring or does an array end with a
     * particular value?
     *
     * @param mixed $obj Variable to search
     * @param mixed $term Value or substring to find within variable
     *
     * @return bool
     */
    public static function endsWith($obj, $term)
    {
        if (is_array($obj)) {
            return end($obj) == $term;
        }

        return substr($obj, -strlen($term)) == $term;
    }

    /**
     * Current URI
     *
     * @return string URI of current page
     */
    public static function currentUri()
    {
        $scheme = 'http';
        $str = $_SERVER['HTTP_HOST'];

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $scheme .= 's';
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $str .= $_SERVER['REQUEST_URI'];
        }

        return $scheme . '://' . $str;
    }

    /**
     * Create base64 data URI from file
     *
     * @param string $file Path to file
     * @param string $type Media type of file
     *
     * @return string
     */
    public static function dataUri($file, $type = false)
    {
        // Check file exists
        if (!file_exists($file)) {
            return trigger_error('File not found ' . $file);
        }

        // If no media type specified, attempt to identify media type from file
        // name extension.
        if (!$type) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if (!isset(self::$types[$extension])) {
                return trigger_error('Unknown extension ' . $extension);
            }

            $type = self::$types[$extension];
        }

        $data = base64_encode(file_get_contents($file));

        return 'data:' . $type . ';base64' . $data;
    }

    /**
     * Format URI
     *
     * Converts a string into a consistently formatted URI, with or without the
     * scheme.
     *
     * @param string $str URI-like string
     * @param bool $human Remove scheme for human-readable URI
     *
     * @return string
     */
    public static function formatUri($str, $human = false)
    {
        // Check string contains URI scheme separator
        if (strpos($str, '//') === false) {
            $str = '//' . $str;
        }

        // Check string is a valid URI
        if (parse_url($str) === false) {
            return false;
        }

        // Return full URI with scheme
        if (!$human) {
            return $str;
        }

        // Remove scheme for human-readable URI
        $str = preg_replace('~^[^/]*//~', '', $str);

        if (substr_count($str, '/') == 1 && self::endsWith($str, '/')) {
            $str = substr($str, 0, -1);
        }

        return $str;
    }

    /**
     * Format link
     *
     * Converts a URI-like string into an HTML link. If no text is specified,
     * the human-readable version of the URI will be used.
     *
     * @param string $str URI-like string
     * @param mixed $text Link text
     *
     * @return string HTML link
     */
    public static function formatLink($str, $text = false)
    {
        $uri = self::formatUri($str);

        if (!$text) {
            $text = self::formatUri($str, true);
        }

        return '<a href="' . $uri . '">' . $text . '</a>';
    }

    /**
     * Normalize headings
     *
     * Promote or demote headings within content to fit surrounding document
     * outline.
     *
     * @param string $content HTML content
     * @param int $limit Maximum heading level in document outline
     *
     * @return string
     */
    public static function normalizeHeadings($content, $limit = 2)
    {
        $levels = range(1, 6);
        $diff = 0;

        foreach ($levels as $level) {
            if (strpos($content, '<h' . $level) !== false) {
                $diff = $limit - $level;
                break;
            }
        }

        if ($diff == 0 || !in_array($limit, $levels)) {
            return $content;
        }

        return preg_replace_callback('/(<\/?)h(\d)/', function($matches) {
            $level = intval($matches[2]) + $diff;
            $tag = in_array($level, $levels) ? 'h' + $level : 'p';

            return $matches[1] + $tag;
        }, $content);
    }

    /**
     * Human-readable time since
     *
     * Given a Unix timestamp, returns a human-readable string, e.g. "1 hour
     * ago", "3 days ago", or "Just now".
     *
     * @param int $time Unix time
     * @param string $suffix String to append to output
     * @param string $now String to return if time is now
     *
     * @return string
     */
    public static function timeSince($time, $suffix = 'ago', $now = 'Just now')
    {
        $elapsed = time() - $time;
        $periods = [
            'year'   => 60 * 60 * 24 * 365,
            'month'  => 60 * 60 * 24 * 30,
            'week'   => 60 * 60 * 24 * 7,
            'day'    => 60 * 60 * 24,
            'hour'   => 60 * 60,
            'minute' => 60,
            'second' => 1,
        ];

        foreach ($periods as $period => $seconds) {
            if ($seconds <= $elapsed) {
                $n = floor($elapsed / $seconds);
                $s = $n > 1 ? 's' : '';

                return "$n $period$s $suffix";
            }
        }

        return $now;
    }

    /**
     * Ordinal numbers
     *
     * Method to return the English language version of the number with its
     * ordinal suffix, such as "1st", "2nd", "23rd", etc.
     *
     * @param int $number
     *
     * @return string
     */
    public static function ordinal($number)
    {
        if (!in_array($number % 100, [11, 12, 13])) {
            switch ($number % 10) {
                case 1: return $number . 'st';
                case 2: return $number . 'nd';
                case 3: return $number . 'rd';
            }
        }

        return $number . 'th';
    }

    /**
     * Truncate string by characters
     *
     * If a string is longer than the given number of characters, it will be
     * truncated to the nearest complete word and the string $after will be
     * appended. For safety, all HTML tags are removed before the string is
     * checked and/or modified.
     *
     * @param string $str String to truncate
     * @param int $limit Maximum number of characters
     * @param string $after String to append to truncated text
     *
     * @return string
     */
    public static function truncate($str, $limit, $after = ' &#8230;')
    {
        $str = strip_tags($str);

        if (strlen($str) <= $limit) {
            return $str;
        }

        $truncated = substr($str, 0, $limit);
        $next = substr($str, $limit, 1);

        // If the truncated string breaks a word and it contains more than one
        // word, truncate it to the nearest word.
        if ($next != ' ' && strpos($truncated, ' ') !== false) {
            $truncated = substr($truncated, 0, strrpos($truncated, ' '));
        }

        return $truncated . $after;
    }

    /**
     * Truncate string by words
     *
     * If a string is longer than the given number of words, it will be
     * truncated and the string $after will be appended. For safety, all HTML
     * tags are removed before the string is checked and/or modified.
     *
     * @param string $str String to truncate
     * @param int $limit Maximum number of words
     * @param string $after String to append to truncated text
     *
     * @return string
     */
    public static function truncateWords($str, $limit, $after = ' &#8230;')
    {
        $str = strip_tags($str);
        $count = str_word_count($str);
        $words = str_word_count($str, 2);

        if ($count <= $limit) {
            return $str;
        }

        $truncated = substr($str, 0, array_keys($words)[$limit]);

        return $truncated . $after;
    }
}
