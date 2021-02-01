<?php declare(strict_types = 1);

class CronSyntaxHelper {

  public static function isItTimeToRunThisCron($cron_expression, $time = '') : bool {
    if(!$time) {
      $time_iGjnw = explode(' ', date('i G j n w'));
    } else {
      $time_iGjnw = explode(' ', date('i G j n w', $time));
    }

    // we need minutes without leading zeros
    if(substr($time_iGjnw[0], 0, 1) == '0') {
      $time_iGjnw[0] = substr($time_iGjnw[0], 1, 1);
    }

    if(!($errors = self::findSyntaxErrorAndMakeNumeric($cron_expression))) {
      $expression_elements = explode(' ', $cron_expression);
      $evaluated_elements = array();

      $valid_time_elements = array(
        CronSyntaxHelper::getMinutesWhenCronRuns($expression_elements[0]),
        CronSyntaxHelper::getHoursWhenCronRuns($expression_elements[1]),
        CronSyntaxHelper::getMonthDaysWhenCronRuns($expression_elements[2]),
        CronSyntaxHelper::getMonthsWhenCronRuns($expression_elements[3]),
        CronSyntaxHelper::getWeekDaysWhenCronRuns($expression_elements[4])
      );

      foreach($time_iGjnw as $index => $time_entry) {
        if(in_array($time_entry, $valid_time_elements[$index])) {
          $evaluated_elements[$index] = 1;
        } else {
          $evaluated_elements[$index] = 0;
        }
      }

      foreach($evaluated_elements as $evaluation) {
        if($evaluation == false) {
          return false;
        }
      }
      return true;
    } else {
      // do something with the error messages - or not
       foreach($errors as $error) {
        echo '<pre>' . $error . '</pre>';
       }
      return false;
    }
  }

  public static function findSyntaxErrorAndMakeNumeric(&$cron_expression) : array {
    $error = array();

    $non_standard_expressions = array(
      '@annually' => '0 0 1 1 *',
      '@yearly'   => '0 0 1 1 *',
      '@monthly'  => '0 0 1 * *',
      '@weekly'   => '0 0 * * 0',
      '@daily'    => '0 0 * * *',
      '@midnight' => '0 0 * * *',
      '@hourly'   => '0 * * * *'
    );

    if(array_key_exists($cron_expression, $non_standard_expressions)) {
      $cron_expression = $non_standard_expressions[$cron_expression];
      return $error; // return an empty array, no errors
    }

    $cron_elements = explode(' ', $cron_expression);

    if(count($cron_elements) !== 5) {
      $error[] = 'The expression has too few or too many characters.';
      return $error; // no further evaluation after this since it is not possible to determine which value was intended to represent which position
    }

    $minute_element = $cron_elements[0];
    if(!CronSyntaxHelper::isMinuteValid($minute_element)) {
      $error[] = 'The "minute" value is not quite right.';
    }

    $hour_element = $cron_elements[1];
    if(!CronSyntaxHelper::isHourValid($hour_element)) {
      $error[] = 'The "hour" value is not quite right.';
    }

    $month_day_element = $cron_elements[2];
    if(!CronSyntaxHelper::isMonthDayValid($month_day_element)) {
      $error[] = 'The "day-of-month" value is not quite right.';
    }

    $month_element = $cron_elements[3];
    if(!CronSyntaxHelper::isMonthValid($month_element)) {
      $error[] = 'The "month" value is not quite right.';
    }

    $minute_element = $cron_elements[4];
    if(!CronSyntaxHelper::isWeekDayValid($minute_element)) {
      $error[] = 'The "day-of-week" value is not quite right.';
    }

    return $error;
  }

  // syntax validator functions

  private static function isMinuteValid($minute_element) : bool {
    $error = 0;

    if(strpos($minute_element, ',') !== false) {
      $validated_parts = array();
      foreach(explode(',', $minute_element) as $i => $minute_element_part) {
        $validated_parts[$i] = CronSyntaxHelper::isMinuteValid($minute_element_part);
      }
      return !(in_array(false, $validated_parts));
    }

    if(strpos($minute_element, '-') !== false) {
      $range_parts = explode('-', $minute_element);
      if(count($range_parts) == 2) {
        if((int)$range_parts[0] >= (int)$range_parts[1]) {
          $error = 1;
        }
      } else {
        $error = 1;
      }
    }
    $minute_pattern = '/^([1-5]\d|\d)$|^(([1-5]\d|\d)\-([1-5]\d|\d))$|^(\*|\*\/([1-5]\d|\d))$|^(([1-5]\d|\d)\-([1-5]\d|\d)\/([1-5]\d|\d))$/';
    if(!preg_match($minute_pattern, $minute_element)) {
      $error = 1;
    }
    return !$error;
  }


  private static function isHourValid($hour_element) : bool {
    $error = 0;

    if(strpos($hour_element, ',') !== false) {
      $validated_parts = array();
      foreach(explode(',', $hour_element) as $i => $hour_element_part) {
        $validated_parts[$i] = CronSyntaxHelper::isHourValid($hour_element_part);
      }
      return !(in_array(false, $validated_parts));
    }

    if(strpos($hour_element, '-') !== false) {
      $range_parts = explode('-', $hour_element);
      if(count($range_parts) == 2) {
        if((int)$range_parts[0] >= (int)$range_parts[1]) {
          $error = 1;
        }
      } else {
        $error = 1;
      }
    }
    $hour_pattern = '/^(2[0-3]|1\d|\d)$|^((2[0-3]|1\d|\d)\-(2[0-3]|1\d|\d))$|^(\*|\*\/(2[0-3]|1\d|\d))$|^((2[0-3]|1\d|\d)\-(2[0-3]|1\d|\d)\/(2[0-3]|1\d|\d))$/';
    if(!preg_match($hour_pattern, $hour_element)) {
      $error = 1;
    }
    return !($error);
  }


  private static function isMonthDayValid($month_day_element) : bool {
    $error = 0;

    if(strpos($month_day_element, ',') !== false) {
      $validated_parts = array();
      foreach(explode(',', $month_day_element) as $i => $month_day_element_part) {
        $validated_parts[$i] = CronSyntaxHelper::isMonthDayValid($month_day_element_part);
      }
      return !(in_array(false, $validated_parts));
    }

    if(strpos($month_day_element, '-') !== false) {
      $range_parts = explode('-', $month_day_element);
      if(count($range_parts) == 2) {
        if((int)$range_parts[0] >= (int)$range_parts[1]) {
          $error = 1;
        }
      } else {
        $error = 1;
      }
    }
    $month_day_pattern = '/^(3[0-1]|[1-2][0-9]|[1-9])$|^((3[0-1]|[1-2][0-9]|[1-9])\-(3[0-1]|[1-2][0-9]|[1-9]))$|^(\*|\*\/(3[0-1]|[1-2][0-9]|[1-9]))$|^((3[0-1]|[1-2][0-9]|[1-9])\-(3[0-1]|[1-2][0-9]|[1-9])\/(3[0-1]|[1-2][0-9]|[1-9]))$/';
    if(!preg_match($month_day_pattern, $month_day_element)) {
      $error = 1;
    }
    return !$error;
  }


  private static function isMonthValid($month_element) : bool {
    $error = 0;

    $allowed_string_values = array(
      1 => 'JAN',
      2 => 'FEB',
      3 => 'MAR',
      4 => 'APR',
      5 => 'MAY',
      6 => 'JUN',
      7 => 'JUL',
      8 => 'AUG',
      9 => 'SEP',
      10 =>'OCT',
      11 =>'NOV',
      12 =>'DEC'
    );

    if(strpos($month_element, ',') !== false) {
      $validated_parts = array();
      foreach(explode(',', $month_element) as $i => $month_element_part) {
        $validated_parts[$month_element_part] = CronSyntaxHelper::isMonthValid($month_element_part);
      }
      return !(in_array(false, $validated_parts));
    }

    if(strpos($month_element, '-') !== false) {
      $range_parts = explode('-', $month_element);

      if(count($range_parts) == 2) {
        if(($numeric_value1 = strval(array_search(strtoupper($range_parts[0]), $allowed_string_values))) !== '') {
          $range_parts[0] = $numeric_value1;
        }
        if(($numeric_value2 = strval(array_search(strtoupper($range_parts[1]), $allowed_string_values))) !== '') {
          $range_parts[1] = $numeric_value2;
        }
        if((int)$range_parts[0] >= (int)$range_parts[1]) {
          $error = 1;
        }
        $month_element = implode('-', $range_parts);
      } else {
        $error = 1;
      }
    } else {
      if(($numeric_value = strval(array_search(strtoupper($month_element), $allowed_string_values))) !== '') {
        $month_element = $numeric_value;
      }
    }

    $month_pattern = '/^(1[0-2]|[0-9])$|^((1[0-2]|[0-9])\-(1[0-2]|[0-9]))$|^(\*|\*\/(1[0-2]|[0-9]))$|^((1[0-2]|[0-9])\-(1[0-2]|[0-9])\/(1[0-2]|[0-9]))$/';
    if(!preg_match($month_pattern, $month_element)) {
      $error = 1;
    }

    return !$error;
  }


  private static function isWeekDayValid($minute_element) : bool {
    $error = 0;

    $allowed_string_values = array(
      'SUN',
      'MON',
      'TUE',
      'WED',
      'THU',
      'FRI',
      'SAT'
    );

    if(strpos($minute_element, ',') !== false) {
      $validated_parts = array();
      foreach(explode(',', $minute_element) as $i => $minute_element_part) {
        $validated_parts[$i] = CronSyntaxHelper::isWeekDayValid($minute_element_part);
      }
      return !(in_array(false, $validated_parts));
    }

    if(strpos($minute_element, '-') !== false) {
      $range_parts = explode('-', $minute_element);

      if(count($range_parts) == 2) {
        if(($numeric_value1 = strval(array_search(strtoupper($range_parts[0]), $allowed_string_values))) !== '') {
          $range_parts[0] = $numeric_value1;
        }
        if(($numeric_value2 = strval(array_search(strtoupper($range_parts[1]), $allowed_string_values))) !== '') {
          $range_parts[1] = $numeric_value2;
        }
        if((int)$range_parts[0] >= (int)$range_parts[1]) {
          $error = 1;
        }
        $minute_element = implode('-', $range_parts);
      } else {
        $error = 1;
      }
    } else {
      if(($numeric_value = strval(array_search(strtoupper($minute_element), $allowed_string_values))) !== '') {
        $minute_element = $numeric_value;
      }
    }

    $minute_pattern = '/^([0-6])(\,[0-6])*$|^(([0-6])\-([0-6]))$|^(\*|\*\/([0-6]))$|^([0-6]-[0-6]\/[0-6])$/';
    if(!preg_match($minute_pattern, $minute_element)) {
      $error = 1;
    }

    return !$error;
  }


  // valid value collection getters

  private static function getMinutesWhenCronRuns($minute_element) : array {
    if(strpos($minute_element, ',') !== false) {
      $valid_minute_elements = array();
      foreach(explode(',', $minute_element) as $list_part) {
        $valid_minute_elements[] = CronSyntaxHelper::getMinutesWhenCronRuns($list_part);
      }
      return is_null(array_reduce($valid_minute_elements, 'array_merge', array())) ? array() : array_reduce($valid_minute_elements, 'array_merge', array());
    }

    if(strpos($minute_element, '/') !== false) {
      $value_range = explode('/', $minute_element)[0];
      $step = explode('/', $minute_element)[1];

      if(strpos($value_range, '-') !== false) {
        $value_range = CronSyntaxHelper::getRangeValues($value_range);
      } else if($value_range == '*') {
        $value_range = CronSyntaxHelper::getRangeValues('0-59');
      }
      return CronSyntaxHelper::getStepValues($value_range, $step);
    }


    if(strpos($minute_element, '-') !== false) {
      return CronSyntaxHelper::getRangeValues($minute_element);
    }

    if($minute_element == '*') {
      return CronSyntaxHelper::getRangeValues('0-59');
    }

    return array(intval($minute_element));
  }


  private static function getHoursWhenCronRuns($hour_element) : array {
    if(strpos($hour_element, ',') !== false) {
      $valid_hour_elements = array();
      foreach(explode(',', $hour_element) as $list_part) {
        $valid_hour_elements[] = CronSyntaxHelper::getHoursWhenCronRuns($list_part);
      }
      return is_null(array_reduce($valid_hour_elements, 'array_merge', array())) ? array() : array_reduce($valid_hour_elements, 'array_merge', array());
    }

    if(strpos($hour_element, '/') !== false) {
      $value_range = explode('/', $hour_element)[0];
      $step = explode('/', $hour_element)[1];
      if(strpos($value_range, '-') !== false) {
        $value_range = CronSyntaxHelper::getRangeValues($value_range);
      } else if($value_range == '*') {
        $value_range = CronSyntaxHelper::getRangeValues('0-23');
      }
      return CronSyntaxHelper::getStepValues($value_range, $step);
    }

    if(strpos($hour_element, '-') !== false) {
      return CronSyntaxHelper::getRangeValues($hour_element);
    }

    if($hour_element == '*') {
      return CronSyntaxHelper::getRangeValues('0-23');
    }

    return array(intval($hour_element));
  }


  private static function getMonthDaysWhenCronRuns($month_day_element) : array {
    if(strpos($month_day_element, ',') !== false) {
      $valid_month_day_elements = array();
      foreach(explode(',', $month_day_element) as $list_part) {
        $valid_month_day_elements[] = CronSyntaxHelper::getMonthDaysWhenCronRuns($list_part);
      }
      return is_null(array_reduce($valid_month_day_elements, 'array_merge', array())) ? array() : array_reduce($valid_month_day_elements, 'array_merge', array());
    }

    if(strpos($month_day_element, '/') !== false) {
      $value_range = explode('/', $month_day_element)[0];
      $step = explode('/', $month_day_element)[1];
      if(strpos($value_range, '-') !== false) {
        $value_range = CronSyntaxHelper::getRangeValues($value_range);
      } else if($value_range == '*') {
        $value_range = CronSyntaxHelper::getRangeValues('1-31');
      }
      return CronSyntaxHelper::getStepValues($value_range, $step);
    }

    if(strpos($month_day_element, '-') !== false) {
      $value_range = CronSyntaxHelper::getRangeValues($month_day_element);
      if(strpos($month_day_element, '/') !== false) {
        $step = explode('/', $month_day_element)[1];
        return CronSyntaxHelper::getStepValues($value_range, $step);
      }
      return $value_range;
    }

    if($month_day_element == '*') {
      return CronSyntaxHelper::getRangeValues('1-31');
    }

    return array(intval($month_day_element));
  }


  private static function getMonthsWhenCronRuns($month_element) : array {
    $allowed_string_values = array(
      1 => 'JAN',
      2 => 'FEB',
      3 => 'MAR',
      4 => 'APR',
      5 => 'MAY',
      6 => 'JUN',
      7 => 'JUL',
      8 => 'AUG',
      9 => 'SEP',
      10 =>'OCT',
      11 =>'NOV',
      12 =>'DEC'
    );

    if(strpos($month_element, ',') !== false) {
      $valid_month_elements = array();
      foreach(explode(',', $month_element) as $list_part) {
        $valid_month_elements[] = CronSyntaxHelper::getMonthsWhenCronRuns($list_part);
      }
      return is_null(array_reduce($valid_month_elements, 'array_merge', array())) ? array() : array_reduce($valid_month_elements, 'array_merge', array());
    }

    if(strpos($month_element, '-') !== false) {
      $range_parts = explode('-', $month_element);
      foreach($range_parts as $index => $range_part) {
        if(strval(array_search(strtoupper($range_parts[$index]), $allowed_string_values)) !== '') {
          $range_parts[$index] = strval(array_search(strtoupper($range_parts[$index]), $allowed_string_values));
        }
      }
      $month_element = implode('-', $range_parts);
      $value_range = CronSyntaxHelper::getRangeValues($month_element);

      if(strpos($month_element, '/') !== false) {
        $step = explode('/', $month_element)[1];
        return CronSyntaxHelper::getStepValues($value_range, $step);
      }
      return $value_range;
    }

    if(strpos($month_element, '/') !== false) {
      $value_range = CronSyntaxHelper::getRangeValues('1-12');
      $step = explode('/', $month_element)[1];

      return CronSyntaxHelper::getStepValues($value_range, $step);
    }

    if($month_element == '*') {
      return CronSyntaxHelper::getRangeValues('1-12');
    }

    if(($numeric_value = strval(array_search(strtoupper($month_element), $allowed_string_values))) !== '') {
      $month_element = $numeric_value;
    }
    return array(intval($month_element));
  }


  private static function getWeekDaysWhenCronRuns($week_day_element) : array {
    $allowed_string_values = array(
      'SUN',
      'MON',
      'TUE',
      'WED',
      'THU',
      'FRI',
      'SAT'
    );

    if(strpos($week_day_element, ',') !== false) {
      $valid_week_day_elements = array();
      foreach(explode(',', $week_day_element) as $list_part) {
        $valid_week_day_elements[] = CronSyntaxHelper::getWeekDaysWhenCronRuns($list_part);
      }
      return is_null(array_reduce($valid_week_day_elements, 'array_merge', array())) ? array() : array_reduce($valid_week_day_elements, 'array_merge', array());
    }

    if(strpos($week_day_element, '-') !== false) {
      $range_parts = explode('-', $week_day_element);
      foreach($range_parts as $index => $range_part) {
        if(strval(array_search(strtoupper($range_parts[$index]), $allowed_string_values)) !== '') {
          $range_parts[$index] = strval(array_search(strtoupper($range_parts[$index]), $allowed_string_values));
        }
      }
      $week_day_element = implode('-', $range_parts);
      $value_range = CronSyntaxHelper::getRangeValues($week_day_element);

      if(strpos($week_day_element, '/') !== false) {
        $step = explode('/', $week_day_element)[1];
        return CronSyntaxHelper::getStepValues($value_range, $step);
      }
      return  $value_range;
    }

    if(strpos($week_day_element, '/') !== false) {
      $value_range = CronSyntaxHelper::getRangeValues('0-6');
      $step = explode('/', $week_day_element)[1];

      return CronSyntaxHelper::getStepValues($value_range, $step);
    }

    if($week_day_element == '*') {
      return CronSyntaxHelper::getRangeValues('0-6');
    }

    if(($numeric_value = strval(array_search(strtoupper($week_day_element), $allowed_string_values))) !== '') {
      $week_day_element = $numeric_value;
    }

    return array(intval($week_day_element));
  }


  // helpers

  private static function getRangeValues($range_expression) : array {
    $range_values = array();

    $startpoint = explode('-', $range_expression)[0];
    $endpoint = explode('-', $range_expression)[1];

    for($i = $startpoint; $i <= $endpoint; $i++) {
      $range_values[] = $i;
    }
    return $range_values;
  }


  private static function getStepValues($value_range, $step) : array {
    $step_values = array();

    foreach($value_range as $index => $value) {
      if($index % $step == 0) {
        $step_values[] = $value;
      }
    }
    return $step_values;
  }

  public static function debug($value, $label = '') {
    echo '<pre>DEBUG: ' . $label . ' ' . var_export($value, true) . '</pre>';
  }
}
