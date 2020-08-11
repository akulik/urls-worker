<?php

include 'Request.php';

class CheckUrlCommand
{

    /**
     * Database connection
     *
     * @var object
     */
    protected $db;

    /**
     * @var Request
     */
    private $request;

    /**
     * CheckUrlCommand constructor.
     */
    public function __construct()
    {
        // Disable PHP notice messages
        error_reporting(E_ALL ^ E_NOTICE);

        // Set timezone
        date_default_timezone_set('Europe/Stockholm');

        // Set higher memory limit
        ini_set('memory_limit', '2048M');

        // Set higher nesting level
        ini_set('xdebug.max_nesting_level', 1000);

        // Set time to infinity
        set_time_limit(0);

        // Set locale
        setlocale(LC_ALL, 'sv_SE.UTF8');

        // Database config
        $host = 'database';
        $user = 'root';
        $pass = 'rootpass';
        $name = 'korkortsjakten_prod';
        $port = 3306;

        // Connect to database
        $this->db = new \PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name, $user, $pass);

        // Set names and charset to UTF-8
        $query = $this->db->prepare('SET NAMES "utf8" COLLATE "utf8_unicode_ci"');
        $query->execute();

        // Increase db timeout to 1 day
        $query = $this->db->prepare('SET @@session.wait_timeout = 86400');
        $query->execute();

        // Create request object
        $this->request = new Request;

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function run()
    {
        $urlItem = $this->getNewUrl();
        if (!count($urlItem)) {
            return;
        }

        try {
            // Create request object
            $this->request->get($urlItem['url']);

            // Log feed status so we can find feeds that doesn't work
            $this->updateUrlHttpCode(
                $urlItem['id'],
                $this->request->getCode()
            );

            // If we didn't get a 200 response
            if ($this->request->isOk() !== true) {
                $this->warn(sprintf(
                    '[%d] %s',
                    $this->request->getCode(),
                    $urlItem['url']
                ));
            }

        } catch (\Exception $exception) {
            $this->error(sprintf(
                '[%d] => %s',
                $exception->getMessage(),
                $urlItem['url']
            ));
            $this->updateUrlStatus($urlItem['id'], 'ERROR');
        }

        $this->run();
    }

    /**
     * Get URL for verification
     *
     * @return array
     */
    private function getNewUrl()
    {
        $this->db->beginTransaction();

        try {

            $query = $this->db->prepare('SELECT * FROM urls WHERE status = ? FOR UPDATE');
            $query->execute(['NEW']);
            $urlRow = $query->fetch(\PDO::FETCH_ASSOC);

            if (!$urlRow || !isset($urlRow['id'])) {
                $this->db->rollBack();
                return [];
            }

            $this->updateUrlStatus($urlRow['id'], 'PROCESSING');

            $this->db->commit();

            return $urlRow;

        } catch (\Exception $exception) {
            $this->db->rollBack();
            return [];
        }
    }

    /**
     * Update Url http code
     *
     * @param int $id
     * @param int $status
     */
    private function updateUrlHttpCode(int $id, int $status)
    {
        $query = $this->db->prepare('UPDATE urls SET status = :status, http_code = :http_code WHERE id = :id');

        $query->execute([
            ':id' => $id,
            ':status' => 'DONE',
            ':http_code' => $status,
        ]);
    }

    /**
     * Update url status
     *
     * @param int $id
     * @param int $status
     */
    private function updateUrlStatus(int $id, string $status)
    {
        $query = $this->db->prepare('UPDATE urls SET status = :status WHERE id = :id');
        $query->execute([
            ':id' => $id,
            ':status' => $status
        ]);
    }

    /**
     * Write warning message
     *
     * @param string $message
     * @return void
     */
    public function warn($message)
    {
        echo sprintf(
            "[%s] WARNING: %s\n",
            date('Y-m-d H:i:s'),
            $message
        );
    }

    /**
     * Write error message
     *
     * @param string $message
     * @return void
     */
    public function error($message)
    {
        echo sprintf(
            "[%s] ERROR: %s\n",
            date('Y-m-d H:i:s'),
            $message
        );
    }

}
