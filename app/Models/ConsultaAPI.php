<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultaAPI extends Model
{
    use HasFactory;

    /**Consultar todos os voos */
    public function consultarVoos()
    {
        $consultaVoos = ConsultaAPI::consultarAPI("GET", null);
        return $consultaVoos;
    }

    /**Criar o agrupamento dos voos */
    public function agrupamentoVoos()
    {
        // Consultar API para pegar voos de ida e volta
        $param = ['outbound' => 1];
        $outbound = ConsultaAPI::consultarAPI("GET", $param);

        $param = ['inbound' => 1];
        $inbound = ConsultaAPI::consultarAPI("GET", $param);

        // Mescla os dois voos para ter todos em um único array
        $arrTotal = array_merge($outbound, $inbound);

        // Declarando variáveis
        $arrFareInbound = array();
        $grupo = array();
        $countGrupo = 0;

        // Capturando os tipos de voos distintos
        foreach ($inbound as $arrInbound) {
            if (strlen(array_search($arrInbound["fare"], $arrFareInbound)) == 0)
                array_push($arrFareInbound, $arrInbound["fare"]);
        }

        // Fazer agrupamento por tipo de voo
        for ($i = 0; $i < count($arrFareInbound); $i++) {
            $arrTemp = [];
            foreach ($inbound as $arrInbound) {
                if ($arrInbound["fare"] == $arrFareInbound[$i]) {
                    $arrTemp[] = $arrInbound;
                }
            }
            // Verificando se i é maior que 0, para mesclar todos arrays em apenas um Grupo
            if ($i > 0) {
                $countGrupo = count($grupo[0]);
                array_push($grupo[0], ConsultaAPI::criarGrupos($outbound,  $arrFareInbound[$i], $arrTemp, $countGrupo));
            } else {
                array_push($grupo, ConsultaAPI::criarGrupos($outbound,  $arrFareInbound[$i], $arrTemp, $countGrupo));
            }
        }

        // Montando o array final
        $grupo = [
            'flights' => $arrTotal,
            'groups' => $grupo[0],
            'totalGroups' => count($grupo[0]),
            'totalFlights' => count($outbound) + count($inbound),
            'cheapestPrice' => $grupo[0][0]['totalPrice'],
            'cheapestGroup' =>  $grupo[0][0]['uniqueId']

        ];

        return $grupo;
    }

    /** Realizar contas na api @type = Tipo 'Post, GET, Delete..', @param = Parametros para requisição */
    private static function consultarAPI(String $type,  $param)
    {
        // Consultar na API
        $endpoint = "http://prova.123milhas.net/api/flights";
        $client = new \GuzzleHttp\Client();
        $response = $client->request($type, $endpoint, ['query' => $param]);

        // Resposta do code da API
        $statusCode = $response->getStatusCode();
        if ($statusCode > 400  && $statusCode <= 511) {
            response($response->getBody(), $statusCode);
            return null;
        }
        // Resposta JSON da API
        $content = json_decode($response->getBody(), true);
        return $content;
    }

    /** Criar os grupos de Voos. @outbound = Array dos voos de ida, @fare = Tipo de voo, @inbound = Array voos de volta, @countGrupo = Quantos grupos formados ja existem */
    private static function criarGrupos($outbound, $fare, $inbound, $countGrupo)
    {
        // Montando um array com todos os voos daquele tipo
        $arr = [];
        foreach ($outbound as $arrOutbound) {
            if ($arrOutbound['fare'] ==   $fare) {
                $arr[] = $arrOutbound;
            }
        }

        // Montando o sgrupos
        $grupoAux = [];
        for ($i = 1; $i < count($arr) + 1; $i++) {

            $priceIda = 0;
            $ida = [];
            $k = 0;

            /* Roda todos os elementos do array, fazendo todas combinações possiveis com os voo de volta. 
             A combinação é : Um voo de ida - todos os voos de volta*/

            //  Voos de ida
            foreach ($arr as $key) {

                if ($k < $i) {
                    $ida[] = $key;
                    $priceIda += floatval($key["price"]);
                } else {
                    break;
                }
                $k++;
            }

            // Voos de volta
            for ($j = 1; $j < count($inbound) + 1; $j++) {
                $priceVolta = 0;
                $volta = [];
                $k = 0;
                foreach ($inbound as $key) {

                    if ($k < $j) {
                        $volta[] = $key;
                        $priceVolta += floatval($key["price"]);
                    } else {
                        break;
                    }
                    $k++;
                }

                // Montando o grupo
                $grupoTemp = [
                    'uniqueId' => $countGrupo + count($grupoAux),
                    'totalPrice' => $priceIda + $priceVolta,
                    'fare' => $fare,
                    'outbond' => $ida,
                    'inbound' => $volta,
                ];
                $grupoAux[] = $grupoTemp;
            }
        }
        // Ordendando pelo preço
        usort($grupoAux, function ($x, $y) {
            return $x['totalPrice'] <=> $y['totalPrice'];
        });
        return $grupoAux;
    }
}
