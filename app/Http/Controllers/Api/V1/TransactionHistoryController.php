<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Models\TransactionHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TransactionHistoryController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = TransactionHistory::select('*')->where('user_id', auth()->user()->id)->with('currency');
        if($request->start_date){
            $query->where('created_at', '>=', $request->start_date);
        }
        if($request->end_date){
            $query->where('created_at', '<=', $request->end_date);
        }
        $data = $query->get();
        return $this->responseJson(Response::HTTP_OK, $data);
    }
   
}
