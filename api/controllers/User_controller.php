<?php
require_once "services/User_service.php";
require_once "helpers/Response_helper.php";
class User_controller
{
    public static function install(): void
    {
        $response = User_service::install();
        Response_helper::json($response);
    }
}
