<?php

class Db extends PDO
{
    public function __construct()
    {
        $config = (include __DIR__ . '/config.php')['db'];

        $servername = $config['servername'];
        $username = $config['username'];
        $password = $config['password'];
        $database = $config['database'];

        $options = [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        parent::__construct("mysql:host=$servername;dbname=$database", $username, $password, $options);
    }

    function select($table, $whereArray = [], $limit = null)
    {
        $data = array();
        $where = $this->getWhere($whereArray);

        $sql = $this->prepare("SELECT * FROM $table $where ORDER BY id $limit");
        $sql->execute();
        $data = $sql->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    function delete($table, $id)
    {
        $sql = $this->prepare("DELETE FROM $table WHERE id=$id");
        $sql->execute();
        return $id;
    }

    function signup()
    {
        if (empty($_POST["email"]) || empty($_POST["password"]) || empty($_POST["name"])) {
            $response = array("status" => 500, "message" => "Введите пароль и эл.адрес");
            return json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        $password = md5($_POST["password"]);
        $email = $_POST['email'];
        $name = $_POST['name'];

        $sql = $this->prepare("SELECT * FROM users WHERE email='$email'");
        $sql->execute();
        $data = $sql->fetchAll(PDO::FETCH_ASSOC);

        if (count($data) != 0) {
            $response = array("status" => 500, "message" => "пользователь с таким эл.адресом уже существует");
            return json_encode($response, JSON_UNESCAPED_UNICODE);
        }

        $data = [];
        $data[":email"] = $email;
        $data[":name"] = $name;
        $data[":password"] = $password;
        $sql = $this->prepare("INSERT INTO users (email, password, name) VALUES ('$email', '$password', '$name')");
        $sql->execute($data);
        $user = $this->lastInsertId();

        $token = $password . md5($name) . md5($email);
        $sql = $this->prepare("INSERT INTO tokens (user, token) VALUES ('$user', '$token')");
        $data_token = [];
        $data_token[":user"] = $user;
        $data_token[":token"] = $token;
        $sql->execute($data);

        $response = array("status" => 200, "token" => $token, 'name' => $name);
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    function login()
    {
        if (empty($_POST["email"]) || empty($_POST["password"])) {
            $response = array("status" => 500, "message" => "Введите пароль и эл.адрес");
            return json_encode($response, JSON_UNESCAPED_UNICODE);
        }
        $password = md5($_POST["password"]);
        $email = $_POST['email'];

        $sql = $this->prepare("SELECT * FROM users WHERE email='$email' AND password='$password'");
        $sql->execute();
        $data = $sql->fetchAll(PDO::FETCH_ASSOC);

        if (count($data) == 0) {
            $response = array("status" => 500, "message" => "пароль или эл.адрес некорректны");
            return json_encode($response, JSON_UNESCAPED_UNICODE);
        }

        $name =  $data[0]["name"];
        $user =  $data[0]["id"];
        $token = $password . md5($name) . md5($email);
        $sql = $this->prepare("INSERT INTO tokens (user, token) VALUES ('$user', '$token')");
        $data_token = [];
        $data_token[":user"] = $user;
        $data_token[":token"] = $token;
        $sql->execute($data);
        $response = array("status" => 200, "token" => $token, 'name' => $name);
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    function save($table)
    {
        $cols = [];
        $data = [];

        foreach ($_POST as $name => $value) {
            if ('id' == $name) {
                continue;
            }

            $cols[] = $name;
            $data[":$name"] = $value;
        }

        $name = implode(',', $cols);
        $value = implode(',', array_keys($data));
        $sql = $this->prepare("INSERT INTO $table ($name) VALUES ($value)");
        $sql->execute($data);
        $id = $this->lastInsertId();

        return $id;
    }

    function put($table, $id)
    {
        $cols = [];
        $data = [];

        foreach ($_POST as $name => $value) {
            if ('id' == $name) {
                continue;
            }

            $cols[] = "$name = '$value'";
            $data[":$name"] = $value;
        }

        $set = implode(',', $cols);
        $value = implode(',', array_keys($data));

        $sql = $this->prepare("UPDATE $table SET $set WHERE id = $id");
        $sql->execute($data);
        return $id;
    }

    function getWhere($whereArray)
    {
        $where = '';
        $cols = array();
        foreach ($whereArray as $name => $value) {
            if ($value != '') $cols[] = "$name=$value";
        }
        if (count($cols)) {
            $where = 'WHERE ' . implode('AND ', $cols);
        }
        return $where;
    }
}
