<?php
/**
 * Created by tuonglv
 * User: tuong.luong
 * Date: 16/08/2021
 * Time: 09:59
 */

namespace App\Helper;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponseService
{
    public function responseJson($code = 200, $data = null, $message = null)
    {
        $return = [];
        $return['code'] = $code;
        if ($message) $return['message'] = $message;
        $return['data'] = $data;
        return response()->json($return);
    }

    public function responseJsonError($code = null, $message = null, $internalMessage = null, $dataError = null)
    {
        return response()->json([
            'code' => $code ?? Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $message ?? trans('errors.something_error'),
            'message_internal' => $internalMessage,
            'data_error' => $dataError
        ]);
    }

    public static function responsePaginate($result, LengthAwarePaginator $resource)
    {
        return [
            'result' => $result,
            'pagination' => [
                'display' => (int)$resource->count(),
                'total_records' => (int)$resource->total(),
                'per_page' => (int)$resource->perPage(),
                'current_page' => (int)$resource->currentPage(),
                'total_pages' => (int)$resource->lastPage(),
            ],
        ];
    }
}
