<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultaAPI extends Model
{
    use HasFactory;


    public function consultaVoos()
    {
        $consultaVoos = ConsultaAPI::consultarAPI("GET", null);
        return $consultaVoos;
    }

    public function agrupamentoVoos()
    {
        $param = ['outbound' => 1];
        $outbound = ConsultaAPI::consultarAPI("GET", $param);

        $param = ['inbound' => 1];
        $inbound = ConsultaAPI::consultarAPI("GET", $param);

        $arrTotal = array_merge($outbound, $inbound);
        $arrFareInbound = array();
        $grupo = array();
        $countGrupo = 0;
        foreach ($inbound as $arrInbound) {
            if (strlen(array_search($arrInbound["fare"], $arrFareInbound)) == 0)
                array_push($arrFareInbound, $arrInbound["fare"]);
        }

        for ($i = 0; $i < count($arrFareInbound); $i++) {
            echo "<h4>$arrFareInbound[$i]</h4>";
            $arrTemp = [];
            foreach ($inbound as $arrInbound) {
                if ($arrInbound["fare"] == $arrFareInbound[$i]) {
                    $arrTemp[] = [
                        'price' => $arrInbound['price'],
                        'id' => $arrInbound['id']
                    ];
                }
            }
            if ($i > 0) {
                $countGrupo = count($grupo[0]);
                array_push($grupo[0], ConsultaAPI::criarGrupos($outbound,  $arrFareInbound[$i], $arrTemp, $countGrupo));
            } else {
                array_push($grupo, ConsultaAPI::criarGrupos($outbound,  $arrFareInbound[$i], $arrTemp, $countGrupo));
            }
        }

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

    private static function criarGrupos($outbound, $fare, $inbound, $countGrupo)
    {
        $arr = [];
        foreach ($outbound as $arrOutbound) {
            if ($arrOutbound['fare'] ==   $fare) {
                $arr[] = $arrOutbound;
            }
        }
        // dd($inbound);
        $grupoAux = [];
        for ($i = 1; $i < count($arr) + 1; $i++) {
            $priceIda = 0;
            $ida = [];
            $k = 0;
            foreach ($arr as $key) {

                if ($k < $i) {
                    $ida[] = $key;
                    $priceIda += floatval($key["price"]);
                } else {
                    break;
                }
                $k++;
            }

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

                $volta = str_replace("[,", "", $volta);
                $price = number_format($priceIda + $priceVolta, 2, ',', ' ');
                // echo count($grupo);
                $grupoTemp = [
                    'uniqueId' => $countGrupo + count($grupoAux),
                    'totalPrice' => $priceIda + $priceVolta,
                    'fare' => $fare,
                    'outbond' => $ida,
                    'inbound' => $volta,
                ];
                $grupoAux[] = $grupoTemp;
                // echo "Ida - [$ida] & Volta - [$volta] - Preço $price <br>";
            }
        }
        usort($grupoAux, function ($a, $b) {
            return $a['totalPrice'] <=> $b['totalPrice'];
        });
        return $grupoAux;
    }
}
