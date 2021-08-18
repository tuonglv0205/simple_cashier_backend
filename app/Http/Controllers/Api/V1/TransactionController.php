<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Transaction;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class TransactionController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Transaction::select('*');
        if($request->start_date){
            $query->where('created_at', '>=', $request->start_date);
        }
        if($request->end_date){
            $query->where('created_at', '<=', $request->end_date);
        }
        $data = $query->get();
        return $this->responseJson(200, $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // simple validate
            if($request->amount < 0 || !$request->user_receive || !$request->user_send || !$request->type || !$request->currency){
                return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Infomation Invalid!");
            }

            $transaction = new Transaction();
            $transaction->fill($request->all());
            $transaction->save();
            return $this->responseJson(200, [], 'Create transaction success!');
        } catch (Exception $e) {
            return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Cannot create transaction!");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        return $this->responseJson(200, $transaction);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $transaction)
    {
        return $this->responseJson(200, $transaction);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction)
    {
        try {
            // simple validate
            if($request->amount < 0 || !$request->user_receive || !$request->user_send || !$request->type || !$request->currency){
                return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Infomation Invalid!");
            }

            DB::beginTransaction();
            $dataReq = $request->all();
            $transaction->fill($dataReq);
            $transaction->save();
            DB::commit();

            return $this->responseJson(200, [], 'Update transaction success!');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Cannot delete transaction!");
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        try {
            $transaction->delete();
            return $this->responseJson(200, [], 'Delete transaction success!');
        } catch (Exception $e) {
            return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Cannot delete transaction!");
        }
    }
}
