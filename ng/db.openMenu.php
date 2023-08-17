<?php
$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$DB->queryOrDie(
    'ALTER TABLE glpi_users ADD COLUMN IF NOT EXISTS menu_open longtext'
);
$menu_open = $DB->request(
    [
        'SELECT' => 'menu_open',
        'FROM'   => 'glpi_users',
        'WHERE'  => ['id' => $_SESSION["glpiID"]]
    ]
);

//debug
if (!isset($_POST['menu_name'])) {
    echo "printing query result :<br>";
    print_r($menu_open->next());
    echo "<br>";
    echo "user_id : " . $_SESSION["glpiID"];
    die();
}
$menu_open = json_decode($menu_open->next()['menu_open'], true);
if (is_null($menu_open)){
    $menu_open = [];
}
if (filter_var($_POST['open'], FILTER_VALIDATE_BOOLEAN)) {
    $menu_open[] = $_POST['menu_name'];
} else {
    $key = array_search($_POST['menu_name'], $menu_open);
    unset($menu_open[$key]);
    $menu_open = array_values($menu_open); //reindex key value
}
$menu_open = json_encode($menu_open);

$DB->updateOrInsert('glpi_users', ['menu_open' => $menu_open], ['id' => $_SESSION['glpiID']]);
