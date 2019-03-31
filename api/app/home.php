<?php
date_default_timezone_set('Africa/Cairo');


class homeDB{
    private $host = 'localhost';
    private $MySqlUsername = 'root';
    private $MySqlPassword = '23243125';
    private $DBname        = 'mydb';

    // private $host = '127.0.0.1';
    // private $MySqlUsername = 'root';
    // private $MySqlPassword = '23243125';
    // private $DBname        = 'mydb';

    public $conn;

    private static $instance;

    function __construct(){
        try{
            $conn = new PDO("mysql:host=$this->host;dbname=$this->DBname;charset=utf8", $this->MySqlUsername, $this->MySqlPassword, []);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->beginTransaction();
            $this->conn = $conn;
        }catch(PDOException $e)
        {
            die($e->getMessage());
        }
    }

    public static function getInstance(){
        if(!isset(self::$instance)) 
        {
            self::$instance = new homeDB();
        }
        return self::$instance;
    }

    function __destruct(){
        $this->conn->commit();
        $this->conn = null; 
    }

}

class GetData{

    private $conn;
    function  __construct(){
        $DB = homeDB::getInstance();
        $this->conn = $DB->conn;
    }

    public function getUser($username){
        $dlb = $this->conn->prepare("SELECT * FROM users WHERE username = '$username'");
        $dlb->execute();
        if($dlb->rowCount() > 0){
            $data     = $dlb->fetch(PDO::FETCH_ASSOC);
            return $data;
        }else{
            return FALSE;
        }
    }

    public function getWork($user_id){
        $dlb = $this->conn->prepare("SELECT * FROM work WHERE user_id = '$user_id'");
        $dlb->execute();
        if($dlb->rowCount() > 0){
            $data     = $dlb->fetch(PDO::FETCH_ASSOC);
            return $data;
        }else{
            return FALSE;
        }
    }

    public function getUniversity($user_id){
        $dlb = $this->conn->prepare("SELECT * FROM University WHERE user_id = '$user_id'");
        $dlb->execute();
        if($dlb->rowCount() > 0){
            $data     = $dlb->fetch(PDO::FETCH_ASSOC);
            return $data;
        }else{
            return FALSE;
        }
    }

    public function GetPosts($user_id){
        if ($user_id != FALSE)
        {
            $dlb = $this->conn->prepare("SELECT * FROM Posts WHERE user_id = '$user_id'");
            $dlb->execute();
            if($dlb->rowCount() > 0){
                $data     = $dlb->fetchAll();
                return $data;
            }else{
                return FALSE;
            }
        } else {
            $dlb = $this->conn->prepare("SELECT * FROM Posts ");
            $dlb->execute();
            if($dlb->rowCount() > 0){
                $data     = $dlb->fetchAll();
                return $data;
            }else{
                return FALSE;
            }
        }
    }

    public function GetLikes($user_id){
        $dlb = $this->conn->prepare("SELECT * FROM Likes WHERE user_id = '$user_id'");
        $dlb->execute();
        if($dlb->rowCount() > 0){
            $data     = $dlb->fetchAll();
            return $data;
        }else{
            return FALSE;
        }
    }

    public function GetComments($post_id){
        $dlb = $this->conn->prepare("SELECT * FROM Comments WHERE post_id = '$post_id'");
        $dlb->execute();
        if($dlb->rowCount() > 0){
            $data     = $dlb->fetchAll();
            return $data;
        }else{
            return FALSE;
        }
    }

    public function GetName($user_id){
        $dlb = $this->conn->prepare("SELECT fname, lname, profile_picture_url FROM users WHERE user_id = '$user_id'");
        $dlb->execute();
        if($dlb->rowCount() > 0){
            $data     =  $dlb->fetch(PDO::FETCH_ASSOC);
            return $data;
        }else{
            return FALSE;
        }
    }




}

class retriveHome {
    public $home ;
    public $name ;
    public $email;
    public $username;
    public $user_id ;



    public function __prepare(){
        $GetDataX = new GetData;
        $data = $GetDataX->getUser($this->username);
        $data = (array) $data;
        $this->home = json_encode(array(
            "user_id" => $data['user_id'],
            "username" => $this->username,
            "profile_pic" => $data['profile_picture_url'],
            "personal" => array(
                "fname"     => $data['fname'],
                "lname"     => $data['lname'],
                "gender"   => $data['gender'],
                "country"  => $data['country'],
                "town"     => $data['town'],
                "contact"  => array(
                    "email"    => $this->email,
                    "phone"    => $data['phone'])
            ),
            "accType"  => $data['uType'],
            "EnterdDate" => $data['cDateTime'],
            "my_Posts" => $GetDataX->GetPosts($this->user_id),
            "posts" => $GetDataX->GetPosts(FALSE),
            "likes" => $GetDataX->GetLikes($this->user_id),


                ));
    }

}

class userActions extends retriveHome {

    public $user_id ;
    public $post_id ;
    public $like_id ;
    public $comment_id ;
    private $conn;

    function  __construct($user_id){
        $DB = homeDB::getInstance();
        $this->conn = $DB->conn;
        $this->user_id = $user_id;
    }

    public function postIdea($data){

        try{
            $title = filter_var($data['data']['title'], FILTER_SANITIZE_STRING);
            $idea_form = filter_var($data['data']['caption'], FILTER_SANITIZE_STRING);
            $skills = filter_var($data['data']['skills'], FILTER_SANITIZE_STRING);
            $skills = json_encode( explode( ';', $skills) );
            $status = $data['data']['status'];
            $curentDate = date('Y-m-d H:i:s');
            $db = $this->conn->prepare("INSERT INTO Posts (user_id, caption, date_created, title, p_status, skills)
                VALUES(
                    '$this->user_id', 
                    '$idea_form',
                    '$curentDate',
                    '$title',
                    '$status',
                    '$skills'
                    )");
            $db->execute();
            return TRUE;

        } catch (PDOException $e){
            die($e->getMessage());
        }
        return FALSE;
    }

    public function like($post_id){
        try{
            $curentDate = date('Y-m-d H:i:s');
            $this->conn->exec("INSERT INTO Likes (user_id, post_id, date_created)
                VALUES(
                    '$this->user_id', 
                    '$post_id', 
                    '$curentDate'
                    )");
            return TRUE;

        } catch (PDOException $e){
            die($e->getMessage());
        }
    }

    public function comment ($post_id, $comment_form){
        try{
            $form = filter_var($comment_form, FILTER_SANITIZE_STRING);
            $curentDate = date('Y-m-d H:i:s');
            $this->conn->exec("INSERT INTO Comments (post_id, user_id, `content`, date_created)
                VALUES(
                    '$post_id',
                    '$this->user_id', 
                    '$form', 
                    '$curentDate'
                    )");
            return TRUE;
        } catch (PDOException $e){
            die($e);
        }

    }

    public function delIdea ($post_id){
        try{
            $this->conn->exec("DELETE FROM Posts WHERE post_id = '$post_id'");
            return TRUE;
        } catch (PDOException $e){
            die($e->getMessage());
        }
    }

    public function delComment ($comment_id){
        try{
            $this->conn->exec("DELETE FROM Comments WHERE comment_id = '$comment_id'");
            return TRUE;
        } catch (PDOException $e){
            die($e->getMessage());
        }

    }

    public function unLike ($post_id){
        try{
            $this->conn->exec("DELETE FROM Likes WHERE user_id = '$this->user_id' AND post_id = '$post_id'");
            return TRUE;
        } catch (PDOException $e){
            die($e->getMessage());
        }

    }

    
    

    function __destruct(){
    }



}

