<?php
include '../db.php';
$db = new Db;

$gets = get();

try {
    if (isset($gets["select"])) {
        $where = array(
            'id' => $gets["id"],
            'post' => $gets["post"],
        );
        echo $db->select($gets["select"], $where, $gets["limit"]);
    } else if (isset($gets["save"])) {
        echo $db->save($gets["save"]);
    } else if (isset($gets["put"])) {
        echo $db->put($gets["put"], $gets["id"]);
    } else if (isset($gets["delete"])) {
        echo $db->delete($gets["delete"], $gets["id"]);
    } else if (isset($gets["signup"])) {
        echo $db->signup();
    } else if (isset($gets["login"])) {
        echo $db->login();
    }
} catch (Exception $e) {
    echo json_encode(array("error" => $e->getMessage()), JSON_UNESCAPED_UNICODE);
}

function get()
{
    $gets = array(
        "select" => null,
        "save" => null,
        "put" => null,
        "delete" => null,
        "signup" => null,
        "login" => null,
        "limit" => null,
        "id" => null,
        "post" => null,
    );
    foreach ($gets as $key => $value) {
        $gets[$key] = !empty($_GET[$key]) ? $_GET[$key] : null;
    }
    return $gets;
}
