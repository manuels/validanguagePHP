<?php

error_reporting(E_ALL | E_STRICT);

require("validanguage.php");

$rules = json_decode('{
  "text": {
    "regex": {
      "expression": "/^[0-9]*$/",
      "errorMsg": "regex failed"
    },
    "characters": {
      "mode": "allow",
      "expression": "numeric.$",
      "onsubmit": true,
      "errorMsg": "You may only enter numbers, periods, or the dollar sign."
    },
    "minlength": 2,
    "maxlength": 4,
    "required": true,
    "errorMsg": "Please enter a valid monetary amount",
    "onsuccess": "someObject.successHandler",
    "onerror": ["errorHandler1", "errorHandler2"]
  }
}');

$REQUEST = json_decode('{
  "text": "0.1€"
}');

$v = new Validanguage;

echo "Request:\n";
var_dump($REQUEST);

echo "\n\nRules:\n";
var_dump($rules);

$errors = $v->validate($REQUEST, $rules);

echo "\n".count($errors)." Errors:\n";
foreach($errors as $e)
     var_dump($e->getErrorMsg());

?>