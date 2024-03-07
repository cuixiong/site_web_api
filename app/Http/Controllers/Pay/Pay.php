<?php
namespace App\Http\Controllers\Pay;

abstract class Pay implements PayInterface
{
    public $logdir;
    public $actionUrl;
    public $type = PayForm::AS_ARRAY;
    public $options = [];

    const KEY_IS_WECHAT = 'is_wechat';
    const KEY_IS_MOBILE = 'is_mobile';

    const OPTION_ENABLE = 1;
    const OPTION_DISENABLE = 2;

    public function __construct()
    {
        $this->logdir = base_path().'/_pay_log_';
    }

    /**
     * {@inheritdoc}
     */
    public function do($order, $options = [])
    {
        $payForm = new PayForm($this->actionUrl, $this->createFormdata($order));
        switch ($this->type) {
            case PayForm::AS_ARRAY:
                $ret = $payForm->asArray();
                break;
            case PayForm::AS_LINK:
                $ret = $payForm->asLink();
                break;
            case PayForm::AS_AUTO:
                $ret = $payForm->asAutoPost();
                break;
            default:
            $ret = $payForm->asArray();
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function queryOrder($order, $options = [])
    {
    }
    
    
    /**
     * {@inheritdoc}
     */
    public function refund($order, $options = [])
    {
    }
    
    /**
     * {@inheritdoc}
     */
    public function refund_query($order, $options = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    abstract public function createFormdata($order);

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getActionUrl()
    {
        return $this->actionUrl;
    }

    public function logger($oid, $data, $dir)
    {
        $log = sprintf("%s", var_export(array_merge([
                    '_POST' => $_POST,
                    '_GET' => $_GET,
                    '_SERVER' => $_SERVER,
                    '_input' => file_get_contents("php://input"),
                ], $data), true));
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // true参数是指是否创建多级目录，默认为false
        }
        file_put_contents($dir.$oid.'.log', $log, FILE_APPEND);
    }

    /**
     * @param array $options
     * @return self
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->options[$key] ?? null;
    }
}
