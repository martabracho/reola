<?php

namespace Drupal\reola\Services;

use Symfony\Component\Yaml\Yaml;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\dblog\Plugin\views\wizard\Watchdog;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

use Drupal\Core\Utility\Error;


class ReolaService{

    private $stringResponse;

    public function __construct()
    {
       // $this -> stringResponse = '';
    }


   /**
    * Funcion que recopila los datos de las boyas para mostrar en el mapa inicial
    *
    * @return $datos
    */
    public function getDatosMapa (){


        $config = \Drupal::config('reola.settings');

        $datos = $this->getContextMap($config);


        $res =[];
        $res = $this->getBoyasChicas();
        $res2 = $this->getBoyasGrandes();

        $geoJSON = '{"type":"FeatureCollection","features":[';
           if ($res !== null){
              foreach ($res['boyaChicaItem'] as $feature)
              {

                  $geoJSON = $geoJSON . '{"type":"Feature","id":"' . $feature['id'] .
                    '","properties":{"name":"' . $feature['name'] . '","online":"' .$feature['devices']['wavebuoy']['online'] .
                      '","boyaGrande":"0"},"geometry":{"type":"Point","coordinates":[' . $feature['longitude'] . ',' . $feature['latitude'] . ']}},';
              }
           }
           if ($res2 !== null){
              $res2 = $this->addName($res2, $config);
              foreach ($res2 as $feature)
              {
                $online = $this->validateOnline($feature['reception_date_time']);
                $geoJSON = $geoJSON . '{"type":"Feature","id":"' . $feature['id'] .
                  '","properties":{"name":"' . $feature['name'] . '","online":"' . $online . '","boyaGrande":"1"},"geometry":{"type":"Point","coordinates":[' . $feature['longitude'] . ',' . $feature['latitude'] . ']}},';

              }
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
          if ($item["id"]==$station[2]["station"]["id"]) $item["name"] = $station[2]["station"]["name"];
          if ($item["id"]==$station[3]["station"]["id"]) $item["name"] = $station[3]["station"]["name"];
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

        $cadena = "";

        $config = \Drupal::config('reola.settings');

        $data = ['string_con' => $config->get()];

        $url = $data["string_con"]["urlBoyasGrandes"];


        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $data["string_con"]["urlBoyasGrandes"],
            'timeout'  => 20.0,
            'verify' => false,
        ]);

        $logger = \Drupal::logger('reola');
        try {
          $response = $client->request('GET', $url);
          $cadena = $this->dataParser($response);
        } catch (RequestException $e) {
          Error::logException($logger, $e);
        }


/*
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo "Base URI Boyas Grandes inválida: " . $url;
} else {
    $client = new Client([
        'base_uri' => $url,
        'timeout'  => 20.0,
        'verify' => false,
    ]);

    try {
        $response = $client->request('GET', $url);
        $cadena = $this->dataParser($response);
    } catch (RequestException $e) {
        Error::logException($logger, $e);
    }
}*/


        return $cadena;

    }


    /**
     * Rescatar los datos de las boyas chicas para añadirlas al array geoJSON
     *
     * @return $cadena
     */
    private function getBoyasChicas(){

      $cadena = "";
      $config = \Drupal::config('reola.settings');
      $data = ['string_con' => $config->get()];

      $url = $data["string_con"]["urlBoyasChicas"];
        //Obtener los datos de primer nivel
      $client = new Client([
            // Base URI is used with relative requests
          'base_uri' => $data["string_con"]["urlBoyasChicas"],
          'timeout'  => 20.0,
          'verify' => false,
        ]);

      $logger = \Drupal::logger('reola');
      try {
        $response = $client->request('GET',$url);
        //Parsear los datos de primer nivel obtenidos
        $cadena = $this->dataParser($response);

      } catch (RequestException $e){
        Error::logException($logger, $e);
      }

/*
if (!filter_var($url, FILTER_VALIDATE_URL)) {
  echo "Base URI Boyas Chicas inválida: " . $url;
} else {
  $client = new Client([
      'base_uri' => $url,
      'timeout'  => 20.0,
      'verify' => false,
  ]);

  try {
      $response = $client->request('GET', $url);
      $cadena = $this->dataParser($response);
  } catch (RequestException $e) {
      Error::logException($logger, $e);
  }
}*/


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
        $config = \Drupal::config('reola.settings');

        $data = ['string_con' => $config->get()];

        if ((int)$idBoya>10){ //es una boya chica

          $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $data["string_con"]["urlLastBoyasChicas"],
            'timeout'  => 20.0,
            'verify' => false,
          ]);

          $logger = \Drupal::logger('reola');
          try {
            $response = $client->request('GET', (string)$idBoya);
          } catch (RequestException $e){
            Error::logException($logger, $e);
          }


        } else { //es una boya grande
          $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $data["string_con"]["urlLastBoyasGrandes"],
            'timeout'  => 20.0,
            'verify' => false,
          ]);

          $logger = \Drupal::logger('reola');
          try{
            $response = $client->request('GET', (string)$idBoya . '/ultimoTrack');
          } catch (RequestException $e){
            Error::logException($logger, $e);
          }

        }

        $cadena = $this->dataParser($response);

        if ((int)$idBoya<10){
          $cadena = $this->dateFormat($cadena);
          $cadena = $this->decimalFormatGrande($cadena);
        } else{
          $cadena = $this->decimalFormatChica($cadena);
        }
        return $cadena;
    }

    public function decimalFormatGrande($cadena){

      $last = [];

      foreach($cadena as $clave => $valor){
        if (strcmp($clave, "data")==0){

            foreach($valor as $clave1 => $valor1){
              $separador = strpos($valor1['value'],'.');

              array_shift($cadena[$clave]);
              $last += [$valor1['variable']['name']. (' '). $valor1['calculation']['name'].' ('.$valor1['variable']['unit'].')' => substr($valor1['value'],0,$separador+4)];
          }


        }
      }

      $separador = strpos($cadena['latitude'], '.');
      $cadena['latitude'] = substr($cadena['latitude'],0,$separador+7);
      $separador = strpos($cadena['longitude'], '.');
      $cadena['longitude'] = substr($cadena['longitude'],0,$separador+7);
      $cadena['data'] = $last;
      return $cadena;
    }

    public function decimalFormatChica($cadena){

      $last = end($cadena['data']);
      foreach($last as $clave => $valor){
        //dpm ($clave, "Clave");
        $separador = strpos($valor,'.');
        switch ($clave){
          case "hm0":
            $last[$clave] = substr($last[$clave],0, $separador + 4);
            break;
          case "hmax":
            $last[$clave] = substr($last[$clave],0, $separador + 4);
            break;
          case "hsw":
            $last[$clave] = substr($last[$clave],0, $separador + 4);
            break;
          case "tstr":
            $last[$clave] = $last[$clave];
            break;
          case "lat":
            $last[$clave] = substr($last[$clave],0, $separador + 7);
            break;
          case "lon":
            $last[$clave] = substr($last[$clave],0, $separador + 7);
            break;
          default:
          $last[$clave] = substr($last[$clave],0, $separador + 3);
        }


      }

      $claveUltimoElemento = key($cadena["data"]);
      $cadena["data"][$claveUltimoElemento] = $last;
      return $cadena;
    }

    public function dateFormat ($cadena){
        $dateTime = $cadena['reception_date_time'];
        $separadorDate = strpos($dateTime, 'T');
        $date = substr($dateTime, 0, $separadorDate);
        $separadorHour = strpos($dateTime,'.')-$separadorDate-1;
        $hour = substr($dateTime, $separadorDate+1, $separadorHour);
        //$hour = (int)$hour+2; [UTC+2]
        $dateFormat = $date . ' ' . $hour . ' [UTC]';
        $cadena['reception_date_time'] = $dateFormat;
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
         "capas" => $config->get('capas')
      ];


      return $datos;

    }

    function validateOnline ($dateTime){

      $online = 0;
      date_default_timezone_set('UTC');
      $separador = strpos($dateTime, 'T');
      $date = substr($dateTime, 0, $separador);
      $dateFormat = DrupalDateTime::createFromFormat('Y-m-d', $date);
      $dateFormat = $dateFormat->format('Y-m-d');
      $now = new DrupalDateTime('now');
      $now = $now->format('Y-m-d');
      if ($dateFormat !== $now){
          $online = 0;
      } else {
          $online = 1;
      }
      return $online;
    }


}

?>
