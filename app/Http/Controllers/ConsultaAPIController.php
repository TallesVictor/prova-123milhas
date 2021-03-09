<?php

namespace App\Http\Controllers;

use App\Models\ConsultaAPI;
use Illuminate\Http\Request;

class ConsultaAPIController extends Controller
{

    /**Consultar todos os voos */
    public function consultarVoos()
    {
        $consultaApi = new ConsultaAPI();
        $consultaApi = $consultaApi->consultarVoos();

        if (!$consultaApi) {
            return response('Voos não encontrado', 404);
        }

        return response()
            ->json($consultaApi);
    }

    /**Criar o agrupamento dos voos */
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
