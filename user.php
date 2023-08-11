<?php

class UserController {
    function getUserInfo($userId) {
        $db = new mysqli('localhost', 'username', 'password', 'dbname');
        $result = $db->query("SELECT * FROM users WHERE id = $userId");

        $userInfo = [];
        while ($row = $result->fetch_assoc()) {
            $userInfo[] = $row;
        }

        return $userInfo;
    }

    function displayUserInfo($userId) {
        $userInfo = $this->getUserInfo($_GET['user_id']);

        echo "<h1>User Information</h1>";
        echo "<ul>";
        foreach ($userInfo as $user) {
            echo "<li>Name: " . $user['name'] . "</li>";
            echo "<li>Email: " . $user['email'] . "</li>";
            echo "<li>Number: " . $user['number'] . "</li>";
        }
        echo "</ul>";
    }

    function saveUser() {
        $db = mysqli_connect('localhost', 'username', 'password', 'dbname');

        $name = $_POST['name'];
        $email = $_POST['email'];
        if ($_POST['type'] == 'student') {
            $number = $this->stNum();
        }
        elseif ($_POST['type'] == 'trainee') {
            $number = $this->trNum();
        }
        
        if ($db) {
            if ($name) {
                if ($email) {
                    mail(
                        $_POST['email'],
                        'Registration Confirmation',
                        'Registration completed',
                        'From: orders@example.com'
                    );
                    $query = "INSERT INTO users (name, email, number) VALUES ('$name', '$email', '$number')";
                    $db->query($query);
                } 
                else return false;
            }
            else return false;
        }
        else return false;
    }

    function stNum()
    {
        return 12345; //imagine some logic here
    }

    function trNum()
    {
        return 'T546'; //imagine some logic here
    }
}

?>
