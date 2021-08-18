<?php

namespace App\Http\Controllers\Api\V1;

use App\Helper\ResponseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected $responseService;

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
