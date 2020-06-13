<?php


namespace app\common\model;

use think\Model;

class ConfigM extends Model
{
    protected $table = 'config';

    public function updateConfig($key, $value)
    {
        $query = $this->db();
        return $query->update(['name' => $key, 'value' => $value]);
    }

    public function getConfigByName($key)
    {
        $query = $this->db();
        $data = $query->field('value')->where('name', 'eq', $key)->select();
        if (!empty($data)) {
            return $data[0]['value'];
        }
        return '';
    }
}