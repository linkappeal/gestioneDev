<?php


namespace AppBundle\CustomFunc;

use DateTime;
use Exception;

class WebServiceClient
{
    const WS_ENTRYPOINT = '/maintenance/ws';
    const WS_ROUTE_ENTRYPOINT = '/maintenance/route';
    const WS_STANDARD_FIELDS_MAPPING_ENTRYPOINT = '/maintenance/sfm';
    const WS_SPECIFIC_FIELD_ENTRYPOINT = '/maintenance/sf';
    const WS_SPECIFIC_FIELDS_MAPPING_ENTRYPOINT = '/maintenance/scfm';
    const WS_CONTACT_ENTRYPOINT = '/maintenance/contact';
    const WS_REPORTS_ENTRYPOINT = '/maintenance/reports';
    const WS_UNROUTED_ENTRYPOINT = '/maintenance/unrouted';

    private $wsBaseUrl;
    private $wsToken;

    /**
     * WebServiceClient constructor.
     * @param $wsBaseUrl
     * @param $wsToken
     */
    public function __construct($wsBaseUrl, $wsToken)
    {
        $this->wsBaseUrl = $wsBaseUrl;
        $this->wsToken = $wsToken;
    }

    /**
     * @param null $id
     * @return array
     * @throws Exception
     */
    public function fetchWebServices($id = null)
    {
        $url = $this->wsBaseUrl . self::WS_ENTRYPOINT;

        $parameters = [];
        if ($id) {
            $parameters['filter-id'] = $id;
        }

        return $this->_get($url, $parameters, [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param $url
     * @param array $parameters
     * @param $headers
     * @return array
     * @throws Exception
     * @todo move to external file
     */
    private function _get($url, array $parameters = [], array $headers = [])
    {
        $url = sprintf("%s?%s", $url, http_build_query($parameters));

        $curl = $this->_curl($url, $headers);

        $response = curl_exec($curl);

        curl_close($curl);

        $result = $this->_parseResponse($url, $response);

        return isset($result['data']) ? $result['data'] : [];
    }

    /**
     * @param $url
     * @param $headers
     * @return false|resource
     * @todo move to external file
     */
    private function _curl($url, $headers = [])
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        return $curl;
    }

    /**
     * @param $url
     * @param $result
     * @return mixed
     * @throws Exception
     * @todo move to external file
     */
    private function _parseResponse($url, $result)
    {
        if (is_null($result)) {
            throw new Exception(sprintf("Error trying to contact %s. Null response.", $url));
        }

        if (empty($result)) {
            throw new Exception(sprintf("Error trying to contact %s. Empty response.", $url));
        }

        $response = json_decode($result, true);

        if (empty($response)) {
            throw new Exception(sprintf("Error trying to contact %s. Invalid json response: %s", $url, $result));
        }

        if (!isset($response['result']) || (strtoupper($response['result']) == 'KO') || isset($response['error'])) {
            throw new Exception(sprintf("Error trying to contact %s. Result: %s", $url, print_r($response, true)));
        }

        return $response;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function fetchRoutes()
    {
        $url = $this->wsBaseUrl . self::WS_ROUTE_ENTRYPOINT;

        $parameters = [
            'limit' => 1000
        ];

        return $this->_get($url, $parameters, [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function fetchSpecificFields()
    {
        $url = $this->wsBaseUrl . self::WS_SPECIFIC_FIELD_ENTRYPOINT;

        $parameters = [
            'limit' => 1000
        ];

        return $this->_get($url, $parameters, [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function fetchWebService($id = 0)
    {
        $url = $this->wsBaseUrl . self::WS_ENTRYPOINT . '/' . $id;

        return $this->_get($url, [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function fetchRoute($id = 0)
    {
        $url = $this->wsBaseUrl . self::WS_ROUTE_ENTRYPOINT . '/' . $id;

        return $this->_get($url, [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function fetchSpecificField($id = 0)
    {
        $url = $this->wsBaseUrl . self::WS_SPECIFIC_FIELD_ENTRYPOINT . '/' . $id;

        return $this->_get($url, [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param string $schema
     * @param string $table
     * @return array
     * @throws Exception
     */
    public function fetchStandardFieldsMapping($schema = '', $table = '')
    {
        $url = $this->wsBaseUrl . self::WS_STANDARD_FIELDS_MAPPING_ENTRYPOINT . '/' . $schema . '/' . $table;

        return $this->_get($url, [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param int $specificFieldId
     * @param string $schema
     * @param string $table
     * @return array
     * @throws Exception
     */
    public function fetchSpecificFieldsMapping($specificFieldId = 0, $schema = '', $table = '')
    {
        $url = $this->wsBaseUrl . self::WS_SPECIFIC_FIELDS_MAPPING_ENTRYPOINT . '/' . $specificFieldId . '/' . $schema . '/' . $table;

        return $this->_get($url, [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function fetchStandardFieldsMappings()
    {
        $url = $this->wsBaseUrl . self::WS_STANDARD_FIELDS_MAPPING_ENTRYPOINT;

        $parameters = [];

        return $this->_get($url, $parameters, [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function fetchSpecificFieldsMappings($schema = null, $table = null)
    {
        $url = $this->wsBaseUrl . self::WS_SPECIFIC_FIELDS_MAPPING_ENTRYPOINT;

        $parameters = [];
        if ($schema) {
            $parameters['filter-schema'] = $schema;
            $parameters['limit'] = 100;
        }
        if ($table) {
            $parameters['filter-table'] = $table;
            $parameters['limit'] = 100;
        }

        return $this->_get($url, $parameters, [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    public function storeWebService($data)
    {
        $id = isset($data['id']) ? $data['id'] : null;

        $url = is_null($id) ? $this->wsBaseUrl . self::WS_ENTRYPOINT : $this->wsBaseUrl . self::WS_ENTRYPOINT . '/' . $id;

        return $this->_post($url, json_encode($data), [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    public function storeWebserviceRoute($data)
    {
        $id = isset($data['id']) ? $data['id'] : null;

        $url = is_null($id) ? $this->wsBaseUrl . self::WS_ROUTE_ENTRYPOINT : $this->wsBaseUrl . self::WS_ROUTE_ENTRYPOINT . '/' . $id;

        return $this->_post($url, json_encode($data), [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param $url
     * @param string $payload
     * @param array $parameters
     * @param $headers
     * @return array|mixed
     * @throws Exception
     * @todo move to external file
     */
    private function _post($url, $payload = '', array $parameters = [], array $headers = [])
    {
        $url = sprintf("%s?%s", $url, http_build_query($parameters));

        $curl = $this->_curl($url, $headers);

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($curl);

        curl_close($curl);

        $result = $this->_parseResponse($url, $response);

        return isset($result['data']) ? $result['data'] : [];
    }

    /**
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    public function storeRoute($data)
    {
        $id = isset($data['id']) ? $data['id'] : null;

        $url = is_null($id) ? $this->wsBaseUrl . self::WS_ROUTE_ENTRYPOINT : $this->wsBaseUrl . self::WS_ROUTE_ENTRYPOINT . '/' . $id;

        return $this->_post($url, json_encode($data), [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    public function storeSpecificField($data)
    {
        $id = isset($data['id']) ? $data['id'] : null;

        $url = is_null($id) ? $this->wsBaseUrl . self::WS_SPECIFIC_FIELD_ENTRYPOINT : $this->wsBaseUrl . self::WS_SPECIFIC_FIELD_ENTRYPOINT . '/' . $id;

        return $this->_post($url, json_encode($data), [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    public function storeStandardFieldsMapping($data)
    {
        $schema = isset($data['schema']) ? $data['schema'] : null;
        $table = isset($data['table']) ? $data['table'] : null;

        // Try update
        try {
            $url = $this->wsBaseUrl . self::WS_STANDARD_FIELDS_MAPPING_ENTRYPOINT . '/' . $schema . '/' . $table;

            return $this->_post($url, json_encode($data), [], [
                'Authorization: Bearer ' . $this->wsToken
            ]);
        } catch (Exception $e) {
            // If update fail, try create
            $url = $this->wsBaseUrl . self::WS_STANDARD_FIELDS_MAPPING_ENTRYPOINT;

            return $this->_post($url, json_encode($data), [], [
                'Authorization: Bearer ' . $this->wsToken
            ]);
        }
    }

    /**
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    public function storeSpecificFieldMapping($data)
    {
        $schema = isset($data['schema']) ? $data['schema'] : null;
        $table = isset($data['table']) ? $data['table'] : null;
        $specificFieldId = isset($data['specific_field_id']) ? $data['specific_field_id'] : null;

        // Try update
        try {
            $url = $this->wsBaseUrl . self::WS_SPECIFIC_FIELDS_MAPPING_ENTRYPOINT . '/' . $specificFieldId . '/' . $schema . '/' . $table;

            return $this->_post($url, json_encode($data), [], [
                'Authorization: Bearer ' . $this->wsToken
            ]);
        } catch (Exception $e) {
            // If update fail, try create
            $url = $this->wsBaseUrl . self::WS_SPECIFIC_FIELDS_MAPPING_ENTRYPOINT;

            return $this->_post($url, json_encode($data), [], [
                'Authorization: Bearer ' . $this->wsToken
            ]);
        }
    }

    /**
     * @param int $id
     * @param DateTime $start
     * @param DateTime $end
     * @param string $group
     * @return array
     * @throws Exception
     */
    public function fetchWebServiceReports($id, DateTime $start, DateTime $end, $group = 'month')
    {
        $url = $this->wsBaseUrl . self::WS_REPORTS_ENTRYPOINT;

        return $this->_get(
            $url,
            [
                'ws' => $id,
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'group' => $group,
            ],
            [
                'Authorization: Bearer ' . $this->wsToken
            ]
        );
    }


    /**
     * @return array
     * @throws Exception
     */
    public function fetchUnrouted()
    {
        $url = $this->wsBaseUrl . self::WS_UNROUTED_ENTRYPOINT;

        $parameters = [
            'filter-hide' => 0
        ];
        //if ($id) {
        //    $parameters['filter-id'] = $id;
        //}

        return $this->_get($url, $parameters, [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function retryUnrouted($id)
    {
        $url = $this->wsBaseUrl . self::WS_UNROUTED_ENTRYPOINT;

        $parameters = [];
        if ($id) {
            $parameters['retry'] = $id;
        }

        return $this->_get($url, $parameters, [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function hideUnrouted($id)
    {
        $url = $this->wsBaseUrl . self::WS_UNROUTED_ENTRYPOINT;

        $parameters = [];
        if ($id) {
            $parameters['hide'] = $id;
        }

        return $this->_get($url, $parameters, [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }

    /**
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function fetchContact($id = 0)
    {
        $url = $this->wsBaseUrl . self::WS_CONTACT_ENTRYPOINT . '/' . $id;

        return $this->_get($url, [], [
            'Authorization: Bearer ' . $this->wsToken
        ]);
    }
}
