<?php

namespace App\Http\Traits;

trait ApiResponse
{
    // method to send jason response
    public function apiResponse($status ,$msg ,$data=null)
    {
        $response = [
            'status'=>$status,
            'msg'=> $msg,
            'data' => $data
        ];

        return response()->json($response);
    }
}
