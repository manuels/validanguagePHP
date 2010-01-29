<?php

error_reporting(E_ALL | E_STRICT);

require("validanguage.php");

/*
 * mimic: [ value1: [ rule1: [ expectedResult1: options1, ... ] ... ] ... ]
 */
$test = array(
  "" => array( "required" => array("true" => false, "false" => true)),
  "a" => array( "required" => array("true" => true, "true" => false)),
  "c" => array( "characters" => array( "true" => json_decode(' {"mode": "allow", "expression": "alphaLower", "suppress": true}'),
                                       "false" => json_decode(' {"mode": "allow", "expression": "numeric"}') ) ),
  "23.5€" => array( "characters" => array( "true" => json_decode(' {"mode": "allow", "expression": "numeric.€" }'),
                                           "false" => json_decode(' {"mode": "deny", "expression": "numeric.€"}') ) ),

  "9" => array( "validations" => array( "true" => json_decode('{"name": "validanguage.validateNumeric"}') )),
  "b" => array( "validations" => array( "false" => json_decode('{"name": "validanguage.validateNumeric"}') )),
  "test@testme.com" => array( "validations" => array( "true" => json_decode('{"name": "validanguage.validateEmail"}') )),
  "test[at]testme.com" => array( "validations" => array( "false" => json_decode('{"name": "validanguage.validateEmail"}') )),
  "192.168.1.1" => array( "validations" => array( "true" => json_decode('{"name": "validanguage.validateIP"}') )),
  "192.168.1.256" => array( "validations" => array( "false" => json_decode('{"name": "validanguage.validateIP"}') ))
// validateURL's regex does not work yet
//  "http://www.yahoo.de" => array( "validations" => array( "true" => json_decode('{"name": "validanguage.validateURL"}') )),
//  "httpwww.yahoo.de" => array( "validations" => array( "false" => json_decode('{"name": "validanguage.validateURL"}') ))
);

var_dump($test);

$v = new Validanguage;

foreach($test as $value => $rule) {
  if ( $value === "_empty_") $value = "";
  $request = array("test" => $value);

  foreach ($rule as $r => $options) {

    foreach ($options as $expectedResult => $o) {
      if ( json_encode($o) != NULL) $o = json_encode($o);
      $testme = json_decode( "{ \"test\": { \"$r\": $o } }");

      $errors = $v->validate($request, $testme);
      $realResult = count($errors) === 0;
      $realResult = $realResult ? "true" : "false";

      if ( $realResult == $expectedResult)
        echo "--- '$r: $o' succeeded for '$value'.\n";
      else {
        echo "!!! '$r: $o' failed for '$value' (Result: $realResult, Expected $expectedResult)!\n";

	foreach ($errors as $e)
	  echo $e->getErrorMsg()."\n";
        var_dump($request);
        var_dump($testme);
        die;
      }
    }
  }
}

?>