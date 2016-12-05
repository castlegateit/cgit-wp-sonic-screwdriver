<?php

namespace Cgit\Sonic;

use Cgit\Sonic;

class DateTime
{
    /**
     * Base time or start time in Unix format
     *
     * @var int
     */
    private $start;

    /**
     * End time in Unix format
     *
     * @var int
     */
    private $end;

    /**
     * Default time format
     *
     * @var string
     */
    private $format = 'H:i j F Y';

    /**
     * Default time and date range formats
     *
     * @var array
     */
    private $rangeFormats = [
        'time' => ['H:i', '&ndash;', 'H:i d F Y'],
        'day' => ['d', '&ndash;', 'd F Y'],
        'month' => ['d F', '&ndash;', 'd F Y'],
        'year' => ['d F Y', '&ndash;', 'd F Y'],
    ];

    /**
     * Range tolerance
     *
     * If the start and end times overlap by this number of seconds, they are
     * considered to be the same time.
     *
     * @var int
     */
    private $tolerance = 0;

    /**
     * Constructor
     *
     * Set the start and end times, accepting any format. If it looks like Unix
     * time, assume it is Unix time; otherwise try to convert a string to time.
     * The end time is entirely optional. If the start time is not provided, the
     * current time will be used.
     *
     * @param mixed $time
     * @param mixed $end
     * @return void
     */
    public function __construct($start = null, $end = null)
    {
        $this->set($start, $end);
    }

    /**
     * Set the time
     *
     * If you only enter one time, this behaves like an alias of setStart() and
     * might be preferred when you are not working with a range of dates. If you
     * enter two times, this sets the start and end times.
     *
     * @param mixed $time
     * @return void
     */
    public function set($start = null, $end = null)
    {
        $this->setStart($start);
        $this->setEnd($end);
    }

    /**
     * Set the base or start time
     *
     * @param mixed $time
     * @return void
     */
    public function setStart($time = null)
    {
        if (is_null($time)) {
            $time = time();
        }

        $this->start = self::sanitizeDateTime($time);
    }

    /**
     * Set the end time
     *
     * @param mixed $time
     * @return void
     */
    public function setEnd($time = null)
    {
        $time = self::sanitizeDateTime($time);

        if ($time && $time < $this->start) {
            trigger_error('End time cannot be before start time');
            return;
        }

        $this->end = $time;
    }

    /**
     * Sanitize time input
     *
     * Accepts anything and returns Unix time. Strings that are not entirely
     * composed of numerals are converted to Unix time via strtotime().
     *
     * @param mixed $time
     * @return int
     */
    private static function sanitizeDateTime($time)
    {
        // Integers and strings that look like integers are assumed to be Unix
        // time and are returned as integers immediately.
        if (is_int($time) || ctype_digit($time)) {
            return intval($time);
        }

        // Strings that do not look like integers are converted to Unix time via
        // the default PHP functions. If that results in a Unix time, use that;
        // otherwise, give up.
        $time = strtotime($time);

        if ($time && is_int($time)) {
            return $time;
        }

        return;
    }

    /**
     * Get the base time
     *
     * Behaves as an alias of getStart() and is provided for convenience when
     * working with a single time instead of a range.
     *
     * @param string $format
     * @return string
     */
    public function get($format = null)
    {
        return $this->getStart($format);
    }

    /**
     * Get the start time
     *
     * Return the base or start time in a given format or, if no format is
     * specified, the default format.
     *
     * @param string $format
     * @return string
     */
    public function getStart($format = null)
    {
        if (is_null($format)) {
            $format = $this->format;
        }

        return date($format, $this->start);
    }

    /**
     * Get the end time
     *
     * Return the end time in a given format or, if no format is specified, the
     * default format.
     *
     * @param string $format
     * @return string
     */
    public function getEnd($format = null)
    {
        if (is_null($format)) {
            $format = $this->format;
        }

        return date($format, $this->end);
    }

    /**
     * Set the default date format
     *
     * In addition to the default PHP date formats, this accepts the string
     * 'MySQL' as an alias for a MySQL compatible date and time format.
     *
     * @param string $format
     * @return void
     */
    public function setFormat($format)
    {
        if (strtolower($format) == 'mysql') {
            $format = 'Y-m-d H:i:s';
        }

        $this->format = $format;
    }

    /**
     * Get a range of dates
     *
     * @param array $formats
     * @return string
     */
    public function getRange($formats = [])
    {
        $formats = array_merge($this->rangeFormats, $formats);

        // Default format is a range of years
        $format = $formats['year'];

        // If the times are too close to each other, return a single date or
        // time string.
        if (!$this->hasRange()) {
            return $this->get();
        }

        // Set format to a range of months within one year
        if (date('Y', $this->start) == date('Y', $this->end)) {
            $format = $formats['month'];
        }

        // Set format to a range of days within one month
        if (date('Y-m', $this->start) == date('Y-m', $this->end)) {
            $format = $formats['day'];
        }

        // Set format to a range of times within one day
        if (date('Y-m-d', $this->start) == date('Y-m-d', $this->end)) {
            $format = $formats['time'];
        }

        // Return a range in the correct format
        return date($format[0], $this->start) . $format[1]
            . date($format[2], $this->end);
    }

    /**
     * Set the default date range formats
     *
     * The formats must use the same array keys as the default values and each
     * "format" must consist of an array with three values.
     *
     * @param array $formats
     * @return void
     */
    public function setRangeFormats($formats)
    {
        foreach ($formats as $key => $range) {
            if (array_key_exists($key, $this->rangeFormats) &&
                count($range) == 3) {
                $this->rangeFormats[$key] = $range;
            }
        }
    }

    /**
     * Set the range tolerance
     *
     * If the start and end times overlap by this number of seconds, they are
     * considered to be the same time.
     *
     * @param int $seconds
     * @return void
     */
    public function setRangeTolerance($seconds)
    {
        $this->tolerance = $seconds;
    }

    /**
     * Do we have a range of times?
     *
     * If the end time has not been set or the start and end times are the same,
     * the instance represents a single time and not a range. You can also set a
     * threshold, so that, for example, the start time is within 60 seconds of
     * the end time, it is not considered a range of times.
     *
     * @return boolean
     */
    private function hasRange()
    {
        $start = $this->start;
        $end = is_null($this->end) ? $this->start : $this->end;
        $tolerance = $this->tolerance;

        if ($end == $start || $end - $start < $tolerance) {
            return false;
        }

        return true;
    }

    /**
     * Return the interval between the start and end times
     *
     * The first argument determines whether the value is returned as a
     * human-readable string (the default) or the raw number of seconds as an
     * integer.
     *
     * @param boolean $raw
     * @return mixed
     */
    public function getInterval($raw = false)
    {
        $difference = $this->end - $this->start;

        if ($raw) {
            return $difference;
        }

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
            if ($seconds <= $difference) {
                $n = floor($difference / $seconds);
                $s = $n > 1 ? 's' : '';

                return "$n $period$s";
            }
        }

        return 0;
    }
}
