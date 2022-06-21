<?php

namespace Magein\Admin\View;

class ViewLog
{
    /**
     * @var bool
     */
    private bool $switch = false;

    /**
     * @var string|bool
     */
    private string $path = '';

    public function __construct()
    {
        $config = config('view.log');

        if ($config) {
            $this->switch = (bool)$config['switch'] ?? false;
            $this->path = (bool)$config['path'] ?? '';
        }
    }

    /**
     * @param $data
     * @return false|string
     */
    public function write($data)
    {
        if (!$this->switch || $this->path) {
            return false;
        }

        if (!is_file($this->path) && !touch($this->path)) {
            return false;
        }

        $date = date('Y-m-d H:i:s');
        $index = substr(md5($date), 0, 12);

        $message = '【' . $date . '】 ';
        $message .= ' ' . $index . ' :';

        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        $message .= $data;

        if (file_put_contents($this->path, $message)) {
            return $index;
        }

        return false;
    }
}