<?php

namespace App\Http\Controllers;

use App\Helper\ResponseService;
use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    public $responseService;

    public function __construct()
    {
        $this->responseService = new ResponseService();
    }

    public function responseJson(int $code = 200, $data = null, $message = null, $internalMessage = null)
    {
        return $this->responseService->responseJson($code, $data, $message, $internalMessage);
    }

    public function responseJsonError(int $code = null, $message = null, $internalMessage = null, $dataError = null)
    {
        return $this->responseService->responseJsonError($code, $message, $internalMessage, $dataError);
    }
}
