<?php

if ( !class_exists( 'Calendar' ) ) {
// Include dependencies
require_once ( dirname( __FILE__ ) . '/event-subject.php' );
require_once ( dirname( __FILE__ ) . '/event-observer.php' );
require_once ( dirname( __FILE__ ) . '/calendar-event.php' );

/**
 * Calendar creation class.
 *
 * @author     Kohana Team, Corey Worrell
 * @copyright  (c) 2007-2008 Kohana Team
 * @version    1.0
 * @package    eventbrite-parent
 */
class Calendar extends Event_Subject {

	// Month and year to use for calendaring
	protected $month;
	protected $year;

	// Observed data
	protected $observed_data;

	// Configuration
	protected $config = array();

	/**
	 * Create a new Calendar instance. A month and year can be specified.
	 * By default, the current month and year are used.
	 *
	 * @param   integer  month number
	 * @param   integer  year number
	 * @return  object
	 */
	public static function factory($month = NULL, $year = NULL, $config = array())
	{
		return new Calendar($month, $year, $config);
	}

	/**
	 * Create a new Calendar instance. A month and year can be specified.
	 * By default, the current month and year are used.
	 *
	 * @param   integer  month number
	 * @param   integer  year number
	 * @return  void
	 */
	public function __construct($month = NULL, $year = NULL, $config = array())
	{
		empty($month) and $month = date('n'); // Current month
		empty($year)  and $year  = date('Y'); // Current year

		// Set the month and year
		$this->month = (int) $month;
		$this->year  = (int) $year;

		$this->config($config);
	}

	/**
	 * Allows fetching the current month and year.
	 *
	 * @param   string  key to get
	 * @return  mixed
	 */
	public function __get($key)
	{
		if ($key === 'month' OR $key === 'year')
		{
			return $this->$key;
		}
	}

	/**
	 * Returns an array of the names of the days, using the current locale.
	 *
	 * @param   integer  left of day names
	 * @return  array
	 */
	public function days($length = TRUE)
	{
		// strftime day format
		$format = ($length === TRUE OR $length > 3) ? '%A' : '%a';

		// Days of the week
		$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

		if ($this->config['week_start'] > 0)
		{
			for ($i = 0; $i < $this->config['week_start']; $i++)
			{
				array_push($days, array_shift($days));
			}
		}

		// Remove days that shouldn't be shown
		if (in_array(0, $this->config['show_days']))
		{
			for ($i = 0; $i < 7; $i++)
			{
				if ($this->config['show_days'][$i] === 0)
				{
					unset($days[$i]);
				}
			}

			$days = array_values($days);
		}

		// This is a bit awkward, but it works properly and is reliable
		foreach ($days as $i => $day)
		{
			// Convert the English names to i18n names
			$days[$i] = strftime($format, strtotime($day));
		}

		if (is_int($length) OR ctype_digit($length))
		{
			foreach ($days as $i => $day)
			{
				// Shorten the days to the expected length
				$days[$i] = substr($day, 0, $length);
			}
		}

		return $days;
	}

	/**
	 * Returns an array for use with a view. The array contains an array for
	 * each week. Each week contains 7 arrays, with a day number and status:
	 * TRUE if the day is in the month, FALSE if it is padding.
	 *
	 * @return  array
	 */
	public function weeks()
	{
		// First day of the month as a timestamp
		$first = mktime(1, 0, 0, $this->month, 1, $this->year);

		// Total number of days in this month
		$total = (int) date('t', $first);

		// Last day of the month as a timestamp
		$last  = mktime(1, 0, 0, $this->month, $total, $this->year);

		// Make the month and week empty arrays
		$month = $week = array();

		// Number of days added. When this reaches 7, start a new week
		$days = 0;
		$week_number = 1;

		$w = (int) date('w', $first) - $this->config['week_start'];
		if ($w < 0)
		{
			$w = (7 - $this->config['week_start']) + date('w', $first);
		}

		if ($w > 0)
		{
			// Number of days in the previous month
			$n = (int) date('t', mktime(1, 0, 0, $this->month - 1, 1, $this->year));

			// i = number of day, t = number of days to pad
			for ($i = $n - $w + 1, $t = $w; $t > 0; $t--, $i++)
			{
				// Notify the listeners
				$this->notify(array($this->month - 1, $i, $this->year, $week_number, FALSE));

				// Add previous month padding days
				$week[] = array($i, FALSE, $this->observed_data);
				$days++;
			}
		}

		// i = number of day
		for ($i = 1; $i <= $total; $i++)
		{
			if ($days % 7 === 0)
			{
				// Start a new week
				$month[] = $week;
				$week = array();

				$week_number++;
			}

			// Notify the listeners
			$this->notify(array($this->month, $i, $this->year, $week_number, TRUE));

			// Add days to this month
			$week[] = array($i, TRUE, $this->observed_data);
			$days++;
		}

		$w = (int) date('w', $last) - $this->config['week_start'];
		if ($w < 0)
		{
			$w = (7 - $this->config['week_start']) + date('w', $last);
		}

		if ($w >= 0)
		{
			// i = number of day, t = number of days to pad
			for ($i = 1, $t = 6 - $w; $t > 0; $t--, $i++)
			{
				// Notify the listeners
				$this->notify(array($this->month + 1, $i, $this->year, $week_number, FALSE));

				// Add next month padding days
				$week[] = array($i, FALSE, $this->observed_data);
			}
		}

		if ( ! empty($week))
		{
			// Append the remaining days
			$month[] = $week;
		}

		// Remove days that should't be shown.
		// TODO: Possibly figure out how to do this during the initial loops instead of after
		foreach ($month as $index => $week)
		{
			for ($i = 0; $i < 7; $i++)
			{
				if ($this->config['show_days'][$i] === 0)
				{
					unset($week[$i]);
				}
			}

			$remove_week = TRUE;
			foreach ($week as $day)
			{
				if ($day[1] === TRUE)
				{
					$remove_week = FALSE;
					break;
				}
			}

			if ($remove_week)
			{
				unset($month[$index]);
			}
			else
			{
				$month[$index] = array_values($week);
			}
		}

		return $month;
	}

	/**
	 * Calendar_Event factory method.
	 *
	 * @param   string  unique name for the event
	 * @return  object  Calendar_Event
	 */
	public function event()
	{
		return new Calendar_Event($this);
	}

	/**
	 * Calendar_Event factory method.
	 *
	 * @chainable
	 * @param   string  standard event type
	 * @return  object
	 */
	public function standard($name)
	{
		switch ($name)
		{
			case 'today':
				// Add an event for the current day
				$this->attach($this->event()->condition('timestamp', strtotime('today'))->add_class('today')->title('Today'));
			break;
			case 'prev-next':
				// Add an event for padding days
				$this->attach($this->event()->condition('current', FALSE)->add_class('prev-next'));
			break;
			case 'holidays':
				// Base event
				$event = $this->event()->condition('current', TRUE)->add_class('holiday');

				// Attach New Years
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 1)->condition('day', 1)->title('New Years')->output('New Years'));

				// Attach Valentine's Day
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 2)->condition('day', 14)->title('Valentine\'s Day')->output('Valentine\'s Day'));

				// Attach St. Patrick's Day
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 3)->condition('day', 17)->title('St. Patrick\'s Day')->output('St. Patrick\'s Day'));

				// Attach Easter
				$holiday = clone $event;
				$this->attach($holiday->condition('easter', TRUE)->title('Easter')->output('Easter'));

				// Attach Memorial Day
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 5)->condition('day_of_week', 1)->condition('last_occurrence', TRUE)->title('Memorial Day')->output('Memorial Day'));

				// Attach Independance Day
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 7)->condition('day', 4)->title('Independence Day')->output('Independence Day'));

				// Attach Labor Day
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 9)->condition('day_of_week', 1)->condition('occurrence', 1)->title('Labor Day')->output('Labor Day'));

				// Attach Halloween
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 10)->condition('day', 31)->title('Halloween')->output('Halloween'));

				// Attach Thanksgiving
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 11)->condition('day_of_week', 4)->condition('occurrence', 4)->title('Thanksgiving')->output('Thanksgiving'));

				// Attach Christmas
				$holiday = clone $event;
				$this->attach($holiday->condition('month', 12)->condition('day', 25)->title('Christmas')->output('Christmas'));
			break;
			case 'weekends':
				// Weekend events
				$this->attach($this->event()->condition('weekend', TRUE)->add_class('weekend'));
			break;
		}

		return $this;
	}

	/**
	 * Get the URL for a previous month link
	 *
	 * @return  string
	 */
	public function prev_month_url()
	{
		$date  = mktime(0, 0, 0, $this->month - 1, 1, $this->year);
		$month = date('n', $date);
		$year  = date('Y', $date);
		$url   = self::query(array('mon' => $month, 'yr' => $year));

		return $url;
	}

	/**
	 * Get the previous month name
	 *
	 * @param   int/bool   Length of month name. Or 'TRUE' for full name, '0' or 'FALSE' for just '$before'
	 * @param   string     String to show before the month name
	 * @return  string     Month name
	 */
	public function prev_month($length = TRUE, $before = '&lsaquo; ')
	{
		$format = ($length === TRUE OR $length > 3) ? '%B' : '%b';

		$date = mktime(0, 0, 0, $this->month - 1, 1, $this->year);

		$month = strftime($format, $date);

		if (is_int($length) OR ctype_digit($length))
		{
			$month = substr($month, 0, $length);
		}

		if ($length === 0 OR $length === FALSE)
		{
			$month = '';
		}

		return $before.$month;
	}

	/**
	 * Get the current month name.
	 *
	 * @param   int/bool   Length of month name. Or 'TRUE' for full name.
	 * @return  string     Current month name
	 */
	public function month($length = TRUE)
	{
		$format = ($length === TRUE OR $length > 3) ? '%B' : '%b';

		$date = mktime(0, 0, 0, $this->month, 1, $this->year);

		$month = strftime($format, $date);

		if (is_int($length) OR ctype_digit($length))
		{
			$month = substr($month, 0, $length);
		}

		return $month;
	}

	/**
	 * Get the URL for a next month link
	 *
	 * @return  string
	 */
	public function next_month_url()
	{
		$date  = mktime(0, 0, 0, $this->month + 1, 1, $this->year);
		$month = date('n', $date);
		$year  = date('Y', $date);
		$url   = self::query(array('mon' => $month, 'yr' => $year));

		return $url;
	}

	/**
	 * Get the next month name
	 *
	 * @param   int/bool   Length of month name. Or 'TRUE' for full name, '0' or 'FALSE' for just '$after'
	 * @param   string     String to show after the month name
	 * @return  string     Month name
	 */
	public function next_month($length = TRUE, $after = ' &rsaquo;')
	{
		$format = ($length === TRUE OR $length > 3) ? '%B' : '%b';

		$date = mktime(0, 0, 0, $this->month + 1, 1, $this->year);

		$month = strftime($format, $date);

		if (is_int($length) OR ctype_digit($length))
		{
			$month = substr($month, 0, $length);
		}

		if ($length === 0 OR $length === FALSE)
		{
			$month = '';
		}

		return $month.$after;
	}


	/**
	 * Adds new data from an observer. All event data contains and array of CSS
	 * classes and an array of output messages.
	 *
	 * @param   array  observer data.
	 * @return  void
	 */
	public function add_data(array $data)
	{
		// Add new classes
		$this->observed_data['classes'] += $data['classes'];

		// Add titles
		$this->observed_data['title'][] = $data['title'];

		if ( ! empty($data['output']))
		{
			// Only add output if it's not empty
			$this->observed_data['output'][] = $data['output'];
		}
	}

	/**
	 * Resets the observed data and sends a notify to all attached events.
	 *
	 * @param   array  UNIX timestamp
	 * @return  void
	 */
	public function notify($data)
	{
		// Reset observed data
		$this->observed_data = array
		(
			'classes' => array(),
			'title'   => array(),
			'output'  => array(),
		);

		// Send a notify
		parent::notify($data);
	}

	/**
	 * Sets up the configuration for the Calendar, internal use only
	 *
	 * @param   array   Array with Calendar settings
	 * @return  void
	 */
	protected function config(array $config)
	{
		$defaults = array(
			'week_start' => 0,
			'show_days'  => array_fill(0, 7, 1),
		);

		$this->config = $config + $defaults;
	}

	/**
	 * Merges the current GET parameters with an array of new or overloaded
	 * parameters and returns the resulting query string.
	 *
	 *     // Returns "?sort=title&limit=10" combined with any existing GET values
	 *     $query = URL::query(array('sort' => 'title', 'limit' => 10));
	 *
	 * Typically you would use this when you are sorting query results,
	 * or something similar.
	 *
	 * [!!] Parameters with a NULL value are left out.
	 *
	 * @param   array    array of GET parameters
	 * @param   boolean  include current request GET parameters
	 * @return  string
	 */
	protected static function query(array $params = NULL, $use_get = TRUE)
	{
		if ($use_get)
		{
			if ($params === NULL)
			{
				// Use only the current parameters
				$params = $_GET;
			}
			else
			{
				// Merge the current and new parameters
				$params = array_merge($_GET, $params);
			}
		}

		if (empty($params))
		{
			// No query parameters
			return '';
		}

		// Note: http_build_query returns an empty string for a params array with only NULL values
		$query = http_build_query($params, '', '&');

		// Don't prepend '?' to an empty string
		return ($query === '') ? '' : '?'.$query;
	}

}
}