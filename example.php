<html>
<head>
  <title>validanguagePHP example form</title>
  <link rel="stylesheet" type="text/css" href="validanguage/validanguage.css" />
</head>
<body>

<?php
  if ( count($_REQUEST) > 0 ) {
    require("validanguage.php");
    $rules = json_decode(file_get_contents("example.json"));
    $validanguage = new Validanguage;
  
    $errors = $validanguage->validate($_REQUEST, $rules);

    if ( count($errors) == 0 )
      echo "<p><b>Thank you! The form has been validated on server side</b></p>";
    else {
      echo "<p><b>There were some problems validating the form on server side</b><ul>";
      foreach($errors as $e)
        echo "<li>".$e->getErrorMsg()."</li>";
      echo "</ul></p>";
    }
  }
?>

<form method="post" action="example.php" id="example">
      <label>Forename (validated on both sides) <input type="text" name="forename" id="forename" /></label><br />
      <label>Surname (only validated on client side) <input type="text" name="surname" id="surname" /></label><br />
      <input type="submit" />
</form>

<p><a href="example.php?forename=4&surname=5">Bypass client side validation</a></p>

<script src="validanguage/validanguage.js" type="text/javascript"></script>
<script type="text/javascript">
  <!--
  validanguage.el = <?=file_get_contents("example.json")?>;
  -->
</script>
</body>

</html>