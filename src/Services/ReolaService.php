<?php

namespace Drupal\reola\Services;

use Symfony\Component\Yaml\Yaml;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

class ReolaService{

    private $stringResponse;

    public function __construct()
    {
       // $this -> stringResponse = '';
    }


   /**
    * Funcion que recopila los datos de las boyas para mostrar en el mapa
    *
    * @return $datos
    */
    public function getDatosMapa (){


        $config = \Drupal::config('reolamaps.settings');

        $datos = $this->getContextMap($config);

        $res =[];
        $res = $this->getBoyasChicas();
        $res2 = $this->getBoyasGrandes();
        $res2 = $this->addName($res2, $config);


       $geoJSON = '{"type":"FeatureCollection","features":[';
        foreach ($res as $feature)
        {

            $geoJSON = $geoJSON . '{"type":"Feature","id":"' . $feature['id'] .
              '","properties":{"name":"' . $feature['name'] . '","online":"' .$feature['devices']['wavebuoy']['online'] .
                '","boyaGrande":"0"},"geometry":{"type":"Point","coordinates":[' . $feature['longitude'] . ',' . $feature['latitude'] . ']}},';
        }

        foreach ($res2 as $feature)
        {
          $geoJSON = $geoJSON . '{"type":"Feature","id":"' . $feature['id'] .
            '","properties":{"name":"' . $feature['name'] . '","online":"1","boyaGrande":"1"},"geometry":{"type":"Point","coordinates":[' . $feature['longitude'] . ',' . $feature['latitude'] . ']}},';
        }

        $geoJSON = rtrim($geoJSON, ',');
        $geoJSON = $geoJSON . ']}';

        $datos["geoJSON"] = $geoJSON;

        return $datos;
    }


    /**
     * Función para rescatar el nombre de las Boyas Grandes y añadirlo al array geoJSON
     *
     * @param Array $str
     * @param String $config
     * @return $str
     */
    private function addName($str, $config){

      $data = ["stations" => $config->get('stations')];

      foreach ($str as &$item){
        foreach ($data as $station){
          if ($item["id"]==$station[0]["station"]["id"]) $item["name"] = $station[0]["station"]["name"];
          if ($item["id"]==$station[1]["station"]["id"]) $item["name"] = $station[1]["station"]["name"];

        }

      }
      return $str;
    }


   /**
    * Rescatar los datos de las boyas grandes para añadirlas al array geoJSON
    *
    * @return $cadena
    */
    private function getBoyasGrandes(){

        $cadena = "Hola Drupal";

        $config = \Drupal::config('reolaboyasgrandes.settings');

        $data = ['string_con' => $config->get()];


        $url = $data["string_con"]["url"];

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $data["string_con"]["url"],
            'timeout'  => 20.0,
            'verify' => false,
        ]);

        $response = $client->request('GET', $url);

        $cadena = $this->dataParser($response);
        return $cadena;

    }


    /**
     * Rescatar los datos de las boyas chicas para añadirlas al array geoJSON
     *
     * @return $cadena
     */
    private function getBoyasChicas(){

        $config = \Drupal::config('reolaboyaschicas.settings');

        $data = ['string_con' => $config->get()];


        //Obtener los datos de primer nivel

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $data["string_con"]["url"],
            'timeout'  => 20.0,
            'verify' => false,
        ]);


        $strResponse = [
            'proxy' => [
                'http'  => $data["string_con"]["proxy"]["http"],
                'https' => $data["string_con"]["proxy"]["https"],
            ],
            'query' => [
                'username' => $data["string_con"]["query"]["username"],
                'key' => $data["string_con"]["query"]["key"],
                'project' => $data["string_con"]["query"]["project"],
            ]
            ];

        // Request with proxy and queries
        $response = $client->request('GET', '', $strResponse);

        //Parsear los datos de primer nivel obtenidos
        $cadena = $this->dataParser($response);

        return $cadena;

    }


    /**
     * Rescatar la información más reciente tomada por una boya
     *
     * @param String $idBoya
     * @return $cadena
     */
    public function getUltimoDato($idBoya){
        $cadena = "";
        $config = \Drupal::config('reolaultimodato.settings');

        $data = ['string_con' => $config->get()];

        //TODO esto no me gusta un pelo
        if ((int)$idBoya>10){ //es una boya chica
          $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $data["string_con"]["url"],
            'timeout'  => 20.0,
            'verify' => false,
          ]);
          $response = $client->request('GET', (string)$idBoya);

        } else { //es una boya grande
          $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $data["string_con"]["urlGrandes"],
            'timeout'  => 20.0,
            'verify' => false,
          ]);

          $response = $client->request('GET', (string)$idBoya . '/tracks');

        }

        $cadena = $this->dataParser($response);

        return $cadena;

    }

    /**
     * Parsea la respuesta de una llamada GET
     *
     * @param Objeto $response
     * @return $data
     */
    private function dataParser ($response){

        $str = $response->getBody();
        $data = json_decode($str, true);

        return $data;

    }

   /**
    * Undocumented function
    *
    * @param String $idVariable
    * @param String $dateStart
    * @param String $dateEnd
    * @return void
    */
    public function getDatosFiltrados($idVariable, $dateStart, $dateEnd){

      $config = \Drupal::config('reolamaps.settings');
      $mapContext = $this->getcontextMap($config);

      return $mapContext;
    }

    /**
     * Datos de configuración del mapa
     *
     * @param Array $config
     * @return $datos
     */
    private function getContextMap($config){


      $datos = [

        "extent" => [ $config->get('extent.xmin'),
                        $config->get('extent.ymin'),
                        $config->get('extent.xmax'),
                        $config->get('extent.ymax')
                      ],
          "center" => [
                        $config->get('center.x'),
                        $config->get('center.y')
                      ],
         "layers" => $config->get('layers')
      ];

      return $datos;

    }


}

?>
