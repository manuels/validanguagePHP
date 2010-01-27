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
}

class Validanguage {
  var $debug = true;

  /**
   * continue checking a single form element if a rule does not validate?
   */
  var $continueElementOnError = true;
  
  /**
   * continue checking the hole form if one element does not validate?
   */
  var $continueOnError = true;

  /**
   * continue if an unknown rule is found?
   */
  var $continueOnUnknownRule = true;
  
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

  protected function required(&$value, $options) {
    if ( is_null($value) )
      throw new ValidanguageException;
    
    if ( $value === "" )
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

  public function validate($form, $formRules) {
    $errors = array();
    
    foreach($formRules as $element => $rules) {
      try {
        $value =& $form->$element;

        foreach($rules as $r => $options) {
          try {

            switch(strtolower($r)) {

            case "minlength":
              if ( strlen($value) < $options )
                throw new ValidanguageException;
              break;

            case "maxlength":
              if ( strlen($value) > $options )
                throw new ValidanguageException;
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
                throw new ValidanguageException;
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