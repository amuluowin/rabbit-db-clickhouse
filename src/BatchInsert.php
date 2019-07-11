<?php


namespace rabbit\db\clickhouse;

use rabbit\db\ConnectionInterface;

/**'
 * Class BatchInsert
 * @package rabbit\db
 */
class BatchInsert
{
    /** @var string */
    private $table;
    /** @var Connection */
    private $db;
    /** @var array */
    private $columns = [];
    /** @var string */
    private $cacheDir = '/dev/shm/';
    /** @var bool|resource */
    private $fp;
    /** @var string */
    private $ext = 'csv';
    /** @var string */
    private $fileName;
    /** @var int */
    private $hasRows = 0;

    /**
     * BatchInsert constructor.
     * @param string $table
     * @param array $columns
     * @param ConnectionInterface $db
     */
    public function __construct(string $table, string $fileName, ConnectionInterface $db)
    {
        $this->table = $table;
        $this->db = $db;
        $this->fileName = $this->cacheDir . pathinfo($fileName, PATHINFO_FILENAME) . '.' . $this->ext;
        $this->open();
    }

    private function open()
    {
        if (($this->fp = @fopen($this->fileName, 'w+')) === false) {
            throw new \InvalidArgumentException("Unable to open file: {$fileName}");
        }
    }

    private function close()
    {
        if ($this->fp !== null) {
            @fclose($this->fp);
            @unlink($this->fileName);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param array $columns
     * @return bool
     */
    public function addColumns(array $columns): bool
    {
        if (empty($columns)) {
            return false;
        }
        $this->columns = $columns;
        return true;
    }

    /**
     * @param array $rows
     * @param bool $checkFields
     * @return bool
     */
    public function addRow(array $rows, bool $checkFields = true): bool
    {
        if (empty($rows)) {
            return false;
        }
        $this->hasRows++;
        @fputcsv($this->fp, $rows);
        return true;
    }

    public function clearData()
    {
        $this->close();
        $this->open();
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $this->db->createCommand()->insertFile($this->table, $this->columns, $this->fileName);
        return $this->hasRows;
    }
}