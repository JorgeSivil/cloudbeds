<?php

namespace CloudBeds\Application\Config;

class Config
{
    protected $config = [
        'db.username' => 'root',
        'db.password' => 'root',
        'db.host' => 'mysql',
        'db.name' => 'cloudbeds',
        'db.charset' => 'utf8mb4',
    ];

    public function get($id)
    {
        if ($this->has($id)) {
            return $this->config[$id];
        }
        return null;
    }

    public function set($id, $value)
    {
        $this->config[$id] = $value;
    }

    public function has($id)
    {
        return isset($this->config[$id]);
    }

}
