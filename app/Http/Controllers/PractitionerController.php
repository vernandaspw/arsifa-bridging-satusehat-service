<?php

namespace App\Http\Controllers;

use App\Models\Satusehat\SatusehatPractitioner;
use Illuminate\Http\Request;

class PractitionerController extends Controller
{
    public function get(Request $request)
    {
        try {
            $isIHS = $request->query('isIHS');
            $orderbyCreatedAt = $request->query('orderby');
            $orderbyParamedicID = $request->query('orderby');
            $orderbyParamedicCode = $request->query('orderby');

            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $offset = ($page - 1) * $limit;

            $practitioner = SatusehatPractitioner::skip($offset)->take($limit);

            if ($isIHS) {
                if ($isIHS == 'true') {
                    $practitioner->where('IHS', '!=', null);
                } else {
                    $practitioner->where('IHS', null);
                }
            }
            if ($orderbyCreatedAt) {
                $practitioner->orderBy('created_at', $orderbyCreatedAt);
            }
            if ($orderbyParamedicID) {
                $practitioner->orderBy('ParamedicID', $orderbyParamedicID);
            }
            if ($orderbyParamedicCode) {
                $practitioner->orderBy('ParamedicCode', $orderbyParamedicCode);
            }

            $practitioners = $practitioner->get();
            $total = $practitioner->count();

            return response()->json([
                'total' => $total,
                'page' => $page,
                'totalPages' => ceil($total / $limit),
                'datas' => $practitioners,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'msg' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllNewSphaira()
    {
        return SatusehatPractitioner::getAllNewSphaira();
    }
    public function getAllNikSphaira()
    {
        return SatusehatPractitioner::getAllNikSphaira();
    }

    public function getAllIHS()
    {
        return SatusehatPractitioner::getAllIHS();
    }

}
