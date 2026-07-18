<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

//echo "A<br>";

session_start();

//echo "B<br>";
require_once '../config/config.php';
require_once '../config/database.php';

//echo "C<br>";

$db = (new Database())->connect();

//echo "D<br>";

include '../app/views/layouts/header.php';

//echo "E<br>";

include '../app/views/layouts/parent_sidebar.php';

// "F<br>";

?>

<div class="col-md-10">
<h1>Dashboard Working</h1>
</div>

<?php

//echo "G<br>";

include '../app/views/layouts/footer.php';

//echo "H<br>";