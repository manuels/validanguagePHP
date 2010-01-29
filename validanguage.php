<?php

class ValidanguageException extends Exception {
  var $element, $rule, $options, $errorMsg;

  public function __construct(/*$element, $rule, $options, $errorMsg*/) {
    $args = func_get_args();

    $this->element = current($args) ? current($args) : NULL;
    $this->rule = next($args) ? current($args) : NULL;
    $this->options = next($args) ? current($args) : NULL;
    $this->errorMsg = next($args) ? current($args) : NULL;

    if ( is_null($this->errorMsg) && isset($this->options->errorMsg) )
         $this->errorMsg = $this->options->errorMsg;

    parent::__construct();
  }

  public function getErrorMsg() { return $this->errorMsg; }
  public function getElement() { return $this->element; }
  public function getRule() { return $this->rule; }
}

class Validanguage {
  var $debug = false;

  /**
   * continue checking a single form element if a rule does not validate?
   */
  var $continueElementOnError = false;
  
  /**
   * continue checking the hole form if one element does not validate?
   */
  var $continueOnError = false;

  /**
   * continue if an unknown rule is found?
   */
  var $continueOnUnknownRule = false;
  
  protected function characters(&$value, $options) {
    $expression = $options->expression;
    $expression = str_replace('\\', '\\\\', $expression);
    $expression = str_replace("alphaUpper", "A-Z", $expression);
    $expression = str_replace("alphaLower", "a-z", $expression);
    $expression = str_replace("alpha", "A-Za-z", $expression);
    $expression = str_replace("numeric", "0-9", $expression);
    $expression = str_replace("$", "\$", $expression);
    $expression = str_replace("*", "\*", $expression);
    $expression = str_replace("/", "\/", $expression);
    

    $expression = '/^['.$expression.']*$/';
    $matches = preg_match($expression, $value);

    if ( $options->mode == "allow" ) {
      if ($matches == 0)
        throw new ValidanguageException;
    }
    else {
      if ($matches != 0)
        throw new ValidanguageException;
    }
  }

  protected function required(&$value, $option) {
    if ( !$option )
      return;
  
    if ( is_null($value) )
      throw new ValidanguageException;
    
    if ( strlen($value) == 0 )
      throw new ValidanguageException;
  }

  protected function regex(&$value, $options) {
    $matches = preg_match($options->expression, $value);

    $errorOnMatch = isset($options->errorOnMatch) ? $options->errorOnMatch : false;

    if ( $errorOnMatch ) {
      if ( $matches != 0 )
        throw new ValidanguageException;
    }
    else {
      if ( $matches == 0)
        throw new ValidanguageException;
    }
  }

  protected function requiredAlternatives(&$value, $options, $form) {
    $filledOut = 0;

    // check if the field itself is filled out
    try {
      $this->required($value);
      $filledOut++;
    }
    catch (ValidanguageException $e) {}

    if ( $filledOut > 0 )
      return;
    
    foreach ($options as $element) {
      try {
        $this->required($form->$element, $options);
        $filledOut++;
        break;
      }
      catch (ValidanguageException $e) {}
    }

    if ( $filledOut == 0 ) {
      throw new ValidanguageException;
    }
  }

  protected function validateEmail(&$value) {
    $regex = "/^([a-zA-Z0-9]+[a-zA-Z0-9._%-]*@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,4})$/";

    $matches = preg_match($regex, $value);
    if ($matches != 1)
      throw new ValidanguageException;
  }

  protected function validateNumeric(&$value) {
    $regex = "/^\d+$/";

    $matches = preg_match($regex, $value);
    if ($matches != 1)
      throw new ValidanguageException;
  }

  protected function validateUSPhoneNumber(&$value) {
    $regex = "/^\d{3}( |-|.){0,1}\d{2}( |-|.){0,1}\d{4}$/";

    $matches = preg_match($regex, $value);
    if ($matches != 1)
      throw new ValidanguageException;
  }

  protected function validateIP(&$value) {
    $bytes = explode(".", $value);

    if ( count($bytes) != 4)
      throw new ValidanguageException;
    
    for ($i = 0; $i < 4; $i++) {
      if ( !ctype_digit($bytes[$i]) ) // is int?
        throw new ValidanguageException;
      
      $b = intval($bytes[$i]);
      if ( $b < 0 || $b > 255 )
        throw new ValidanguageException;
    }
  }

  protected function validateURL(&$value) {
    $regex = "/^((([hH][tT][tT][pP][sS]?|[fF][tT][pP])\:\/\/)?([\w\.\-]+(\:[\w\.\&%\$\-]+)*@)?((([^\s\(\)\<\>\\\"\.\[\]\,@;:]+)(\.[^\s\(\)\<\>\\\"\.\[\]\,@;:]+)*(\.[a-zA-Z]{2,4}))|((([01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}([01]?\d{1,2}|2[0-4]\d|25[0-5])))(\b\:(6553[0-5]|655[0-2]\d|65[0-4]\d{2}|6[0-4]\d{3}|[1-5]\d{4}|[1-9]\d{0,3}|0)\b)?((\/[^\/][\w\.\,\?\'\\\/\+&%\$#\=~_\-@]*)*[^\.\,\?\"\'\(\)\[\]!;<>{}\s\x7F-\xFF])?)$/";

    $matches = preg_match($regex, $value);
    if ($matches != 1)
      throw new ValidanguageException;
  }

  protected function validateUSZipCode(&$value) {
    $regex = "/^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/";

    $matches = preg_match($regex, $value);
    if ($matches != 1)
      throw new ValidanguageException;
  }

  protected function validateDate(&$value, $parameters) {
    $options = json_decode($parameters[1]);
    $dateOrder = isset($options->dateOrder) ? $options->dateOrder : "mdy";
    $allowedDelimiters = isset($options->allowedDelimiters) ? $options->allowedDelimiters : "./-";
    $twoDigitYearsAllowed = isset($options->twoDigitYearsAllowed) ? $options->twoDigitYearsAllowed : false;
    $oneDigitDaysAndMonthsAllowed = isset($options->oneDigitDaysAndMonthsAllowed) ? $options->oneDigitDaysAndMonthsAllowed : true;
    $maxYear = isset($options->maxYear) ? $options->maxYear : date("Y")+15;
    $minYear = isset($options->minYear) ? $options->minYear : 1900;
    $rejectDatesInTheFuture = isset($options->rejectDatesInTheFuture) ? $options->rejectDatesInTheFuture : false;
    $rejectDatesInThePast = isset($options->rejectDatesInThePast) ? $options->rejectDatesInThePast : false;

    $usedDemitters = "";
    foreach($allowedDemitters as $d)
      if ( strchr($value, $d) !== FALSE )
        $usedDemitters .= $d;

    if ( count($usedDemitters) != 1) // they used more than one or none delimiter
      throw new ValidationException;

    $parts = explode($value, $usedDemitters);
    if ( length($parts) != 3) // they didnt give us a valid date
      throw new ValidationException;

    // Next we need to build the regex to validate the date comprises only integers and delimiters
    $regex = '^';
    for($j=0; $j < 3; $j++) {
        switch( substr($dateOrder, $j, 1) ) {
            case 'y':
                $num = $twoDigitYearsAllowed ? '{2,4}' : '{4}';
                $regex .= '\\d' + $num;
                break;
            case 'm':
            case 'd':
                $num = $oneDigitDaysAndMonthsAllowed ? '{1,2}' : '{2}';
                $regex .= '\\d' + $num;
                break;
        }
        if($j < 2) $regex .= $delimiterRegex;
    }
    $regex .= '$';
    // Run the regex
    if ( preg_match($regex, $value) != 1 )
      throw new ValidanguageException;

    // grab our dates
    $year = $parts[ strpos($dateOrder,'y') ];
    $month = $parts[ strpos($dateOrder,'m') ];
    $day = $parts[ strpos($dateOrder,'d') ];
    
    // Verify the year isnt 3-digits long to account for me being lazy in the regex check above
    if ( strlen($year) == 3 )
      throw new ValidanguageException;
    
    // Make sure the year is in bounds
    if ( ($year < $options->minYear && $year->length == 4) || ($year > $options->maxYear) )
      throw new ValidanguageException;
    
    // Next we check that the date actually exists, to rule out stuff like "12/32/1976"
    if ( !checkdate($year,$month,$day) )
      throw new ValidanguageException;
    
    if ( $options->rejectDatesInTheFuture || $options->rejectDatesInThePast ) {
      $now = new DateTime;
      $then = new DateTime;
      $then->setDate($year, $month, $day);
      
      if ( ($options->rejectDatesInTheFuture && $then > $now) ||
           ($options->rejectDatesInThePast && $then < $now) )
        throw new ValidanguageException;
    }
  }

  protected function validations(&$value, $validations) {
    foreach ($validations as $v) {
      $regex = "/^[a-z._]*/i";
      $matches = preg_match($regex, $v, $functionName);
      if ($matches != 1)
        $functionName = -1; // forces switch: default => ValidationException
      else
        $functionName = $functionName[0];

      // get parameters: first remove braces, then split by comma
      $parameters = substr($v, strlen($functionName));
      $search = array("/^\s*(\()/", // opening brace
                      "/(\))\s*$/", // closing brace
                      );
      $replace = array("", "");
      preg_replace($search, $replace, $parameters);
      $parameters = explode(",", $parameters);

      switch($functionName) {
      case "validanguage.validateEmail":
        $this->validateEmail($value);
        break;
        
      case "validanguage.validateIP":
        $this->validateIP($value);
        break;

      case "validanguage.validateURL":
        $this->validateURL($value);
        break;
        
      case "validanguage.validateNumeric":
        $this->validateNumeric($value);
        break;
        
      case "validanguage.validateUSPhoneNumber":
        $this->validateUSPhoneNumber($value);
        break;
        
      case "validanguage.validateUSZipCode":
        $this->validateUSZipCode($value);
        break;

      case "validanguage.validateDate":
        $this->validateDate($value, $parameters);
        break;
        
      default:
        throw new ValidanguageException;
      } // end switch

    } // end foreach validation
  }

  public function validate($form, $formRules) {
    $errors = array();
    
    $ignore = array("onblur", "errorMsg", "onsuccess", "onerror", "showAlert", "focusOnError",
                    "onsubmit", "onchange", "onclick", "onkeydown", "onkeyup", "onkeypress", "ontyping");

    foreach($formRules as $element => $rules) {
      try {
        $value =& $form[$element];

        if ( @$rules->onserver === false )
          continue;

        foreach($rules as $r => $options) {
          try {
            if ( in_array($r, $ignore) )
              continue;

            if ( @$options->onserver === false )
              continue;

            switch($r) {

            case "validations":
              $this->validations($value, $options);
	      break;

            case "minlength":
              if ( strlen($value) < $options )
                throw new ValidanguageException;
              break;

            case "maxlength":
              if ( strlen($value) > $options )

                throw new ValidanguageException;
              break;
              
            case "requiredAlternatives":
              $this->requiredAlternatives($value, $options, $form);
              break;

            case "characters":
              $this->characters($value, $options);
              break;

            case "required":
              $this->required($value, $options);
              break;

            case "regex":
              $this->regex($value, $options);
              break;

            default:

              // unknown rule
              if ( !$this->continueOnUnknownRule )
                throw new ValidanguageException($element, $r, $options, "Unknown validanguage option");
            } // switch rules

          }
          catch (ValidanguageException $e) {
            if ( !is_null($e->getErrorMsg()) )
              $errorMsg = $e->getErrorMsg();
            else
              if ( isset($options->errorMsg) )
                $errorMsg = $options->errorMsg;
              else
                $errorMsg = isset($rules->errorMsg) ? $rules->errorMsg : "This form input is not valid";

            $ex = new ValidanguageException($element, $r, $options, $errorMsg);
            array_push($errors, $ex);

            if ( $this->debug )
              echo $r." failed\n";

            if ( !$this->continueElementOnError )
              throw $ex;
          }
        } // foreach rule
      }
      catch (ValidanguageException $ex) {
        if ( !$this->continueOnError )
          return $errors;
      }
    } // foreach form element rule

    return $errors;
  } // validate
} // Validanguage


?>