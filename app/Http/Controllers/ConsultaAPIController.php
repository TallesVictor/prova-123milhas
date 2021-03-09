<?php

namespace App\Http\Controllers;

use App\Models\ConsultaAPI;
use Illuminate\Http\Request;

class ConsultaAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function consultaVoos()
    {
        $consultaApi = new ConsultaAPI();
        $consultaApi = $consultaApi->consultaVoos();

        if (!$consultaApi) {
            return response('Voos não encontrado', 404);
        }

        return response()
            ->json($consultaApi);
    }

    public function agrupamentoVoos()
    {
        $consultaApi = new ConsultaAPI();
        $consultaApi = $consultaApi->agrupamentoVoos();

        if (!$consultaApi) {
            return response('Voos não encontrado', 404);
        }

        return response()
            ->json($consultaApi);
    }
}
