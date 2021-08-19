<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\TransactionHistory;
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
        $query = Transaction::select('*')->where('user_id', auth()->user()->id)->with('currency');
        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date);
        }
        $data = $query->get();
        return $this->responseJson(Response::HTTP_OK, $data);
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
            if ($request->amount < 0 || !$request->type || !$request->currency) {
                return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Infomation Invalid!");
            }

            $currency = Currency::where('code', strtoupper($request->currency))->first();
            if (!$currency) {
                return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Invalid currency!");
            }

            $allowedTypes = ['deposit', 'withdrawal'];
            $type = strtolower($request->type);

            if (!in_array($type, $allowedTypes)) {
                return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Invalid transaction type!");
            }

            $transaction = new Transaction();
            $transaction->user_id = auth()->user()->id;
            $transaction->type = $type;
            $transaction->amount = $request->amount;
            $transaction->content = $request->content;
            $transaction->currency_id = $currency->id;

            $transaction->save();

            if (!$this->addHistory($transaction, 'store')) {
                return throw new Exception('Add history failed!');
            }

            return $this->responseJson(Response::HTTP_OK, [], 'Create transaction success!');
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
        $transaction->currency;
        return $this->responseJson(Response::HTTP_OK, $transaction);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $transaction)
    {
        $transaction->currency;
        return $this->responseJson(Response::HTTP_OK, $transaction);
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
            if ($request->amount < 0 || !$request->type || !$request->currency) {
                return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Infomation Invalid!");
            }

            $currency = Currency::where('code', strtoupper($request->currency))->first();
            if (!$currency) {
                return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Invalid currency!");
            }

            $allowedTypes = ['deposit', 'withdrawal'];
            $type = strtolower($request->type);

            if (!in_array($type, $allowedTypes)) {
                return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Invalid transaction type!");
            }

            DB::beginTransaction();
            $transaction->type = $type;
            $transaction->amount = $request->amount;
            $transaction->content = $request->content;
            $transaction->currency_id = $currency->id;

            $transaction->save();

            if (!$this->addHistory($transaction, 'update')) {
                return throw new Exception('Add history failed!');
            }

            DB::commit();
            return $this->responseJson(Response::HTTP_OK, [], 'Update transaction success!');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Update transaction failed!");
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
            DB::beginTransaction();
            $transaction->delete();

            $history = [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'currency_id' => $transaction->currency_id,
                'transaction_type' => $transaction->type,
                'action' => 'delete'
            ];
            if (!$this->addHistory($transaction, 'delete')) {
                return throw new Exception('Add history failed!');
            }
            DB::commit();
            return $this->responseJson(Response::HTTP_OK, [], 'Delete transaction success!');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Delete transaction failed!");
        }
    }

    /**
     * Get summary all transaction
     *
     * @param  \App\Models\Transaction  $transaction
     * @return null
     */
    public function summary(Request $request)
    {
        try {
            $query = Transaction::select(
                    'currency_id',
                    'type',
                    DB::raw('SUM(amount) as total_amount')
                )
                ->where('user_id', auth()->user()->id)
                ->with('currency');
            if ($request->start_date) {
                $query->where('created_at', '>=', $request->start_date);
            }
            if ($request->end_date) {
                $query->where('created_at', '<=', $request->end_date);
            }
            $query->groupBy('type');
            $query->groupBy('currency_id');
            $transactions = $query->get();

            $currencies = Currency::all()->pluck('code')->toArray();
            $transactionTypes = ['deposit', 'withdrawal'];

            foreach ($currencies as $currency) {
                $results[$currency] = [
                    'balance' => 0,
                    'expenses' => 0,
                    'income' => 0
                ];
            }
            foreach ($transactions as $transaction) {
                // count with deposit
                $totalAmount = $transaction->total_amount;
                $currencyCode = $transaction->currency->code;

                if ($transaction->type == $transactionTypes[0]) {
                    $results[$currencyCode]['balance'] += $totalAmount;
                    $results[$currencyCode]['income'] += $totalAmount;
                }

                // count with withdrawal
                if ($transaction->type == $transactionTypes[1]) {
                    $results[$currencyCode]['balance'] -= $totalAmount;
                    $results[$currencyCode]['expenses'] += $totalAmount;
                }
            }

            return $this->responseJson(Response::HTTP_OK, $results);
        } catch (Exception $e) {
            return $this->responseJsonError(Response::HTTP_INTERNAL_SERVER_ERROR, "Can't not get summary transaction!");
        }
    }

    /**
     * Save transaction history
     *
     * @param  \App\Models\Transaction  $transaction
     * @return null
     */
    public function addHistory($transaction, $action)
    {
        try {
            $history = [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'currency_id' => $transaction->currency_id,
                'transaction_type' => $transaction->type,
                'content' => $transaction->content,
                'action' => $action
            ];

            TransactionHistory::create($history);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
