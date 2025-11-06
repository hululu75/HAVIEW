<?php
/**
 * Client PHP pour l'API Home Assistant
 */
class HomeAssistantClient
{
    private $baseUrl;
    private $accessToken;
    private $timeout;

    /**
     * Constructeur
     * @param string $baseUrl URL de Home Assistant
     * @param string $accessToken Token d'accès
     * @param int $timeout Timeout en secondes
     */
    public function __construct($baseUrl, $accessToken, $timeout = 10)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->accessToken = $accessToken;
        $this->timeout = $timeout;
    }

    /**
     * Effectue une requête à l'API Home Assistant
     * @param string $endpoint Point de terminaison de l'API
     * @param string $method Méthode HTTP (GET, POST, etc.)
     * @param array|null $data Données à envoyer
     * @return array|null Résultat de la requête
     */
    private function request($endpoint, $method = 'GET', $data = null)
    {
        $url = $this->baseUrl . '/api/' . ltrim($endpoint, '/');

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections automatiquement
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Maximum 5 redirections

        if ($data !== null && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("Erreur cURL: " . $error);
        }

        if ($httpCode >= 400) {
            throw new Exception("Erreur HTTP $httpCode: " . $response);
        }

        $decoded = json_decode($response, true);

        // Retourner un tableau vide si le décodage JSON échoue
        return $decoded !== null ? $decoded : [];
    }

    /**
     * Récupère tous les états des entités
     * @return array Liste des états
     */
    public function getStates()
    {
        return $this->request('states');
    }

    /**
     * Récupère l'état d'une entité spécifique
     * @param string $entityId ID de l'entité (ex: light.salon)
     * @return array|null État de l'entité
     */
    public function getState($entityId)
    {
        return $this->request('states/' . $entityId);
    }

    /**
     * Récupère la configuration de Home Assistant
     * @return array Configuration
     */
    public function getConfig()
    {
        return $this->request('config');
    }

    /**
     * Récupère les services disponibles
     * @return array Liste des services
     */
    public function getServices()
    {
        return $this->request('services');
    }

    /**
     * Appelle un service Home Assistant
     * @param string $domain Domaine du service (ex: light, switch)
     * @param string $service Nom du service (ex: turn_on, turn_off)
     * @param array $data Données du service (ex: entity_id, brightness)
     * @return array Résultat
     */
    public function callService($domain, $service, $data = [])
    {
        return $this->request("services/$domain/$service", 'POST', $data);
    }

    /**
     * Récupère l'historique des entités
     * @param string|null $startTime Timestamp de début (ISO 8601)
     * @param string|null $endTime Timestamp de fin (ISO 8601)
     * @param string|null $entityId ID de l'entité (optionnel)
     * @return array Historique
     */
    public function getHistory($startTime = null, $endTime = null, $entityId = null)
    {
        $endpoint = 'history/period';

        if ($startTime) {
            $endpoint .= '/' . $startTime;
        }

        $params = [];
        if ($endTime) {
            $params['end_time'] = $endTime;
        }
        if ($entityId) {
            $params['filter_entity_id'] = $entityId;
        }

        // Important: inclure tous les changements, pas seulement les significatifs
        $params['significant_changes_only'] = 'false';

        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        return $this->request($endpoint);
    }

    /**
     * Récupère les statistiques long terme des entités
     * @param string $startTime Timestamp de début (ISO 8601)
     * @param string|null $endTime Timestamp de fin (ISO 8601)
     * @param array $entityIds IDs des entités (tableau)
     * @param string $period Période de regroupement: '5minute', 'hour', 'day', 'month'
     * @return array Statistiques
     */
    public function getStatistics($startTime, $endTime = null, $entityIds = [], $period = 'hour')
    {
        // Format: /api/history/statistics/<start_time>
        // Note: start_time doit être en format ISO 8601
        $endpoint = 'history/statistics/' . urlencode($startTime);

        $params = [
            'statistic_ids' => implode(',', $entityIds),
            'period' => $period
        ];

        if ($endTime) {
            $params['end_time'] = $endTime;
        }

        $endpoint .= '?' . http_build_query($params);

        return $this->request($endpoint);
    }

    /**
     * Vérifie la connexion à Home Assistant
     * @return bool True si la connexion fonctionne
     */
    public function checkConnection()
    {
        try {
            $this->request('');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
